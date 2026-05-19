<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$message = $_GET['success'] ?? '';
$messageType = 'success';

// --- USUWANIE RAPORTU Z AUTOMATYCZNYM ZWROTEM CZĘŚCI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_log'])) {
    $log_id = $_POST['log_id'];
    try {
        $pdo->beginTransaction();

        // Pobieramy i zwracamy części na magazyn przed usunięciem raportu
        $stmtOldParts = $pdo->prepare("SELECT part_id, quantity_used FROM log_parts WHERE log_id = ?");
        $stmtOldParts->execute([$log_id]);
        $oldParts = $stmtOldParts->fetchAll();

        $stmtRestoreStock = $pdo->prepare("UPDATE parts SET quantity = quantity + ? WHERE id = ?");
        foreach ($oldParts as $op) {
            $stmtRestoreStock->execute([$op['quantity_used'], $op['part_id']]);
        }

        // Usunięcie samego logu (relacje w log_parts znikną automatycznie przez ON DELETE CASCADE)
        $stmt = $pdo->prepare("DELETE FROM logs WHERE id = ?");
        $stmt->execute([$log_id]);

        $pdo->commit();
        $message = "Raport został trwale usunięty, a części wróciły do magazynu.";
        $messageType = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Wystąpił błąd podczas usuwania: " . $e->getMessage();
        $messageType = "danger";
    }
}

$vehicle_filter = $_GET['vehicle_id'] ?? '';

// Pobranie pojazdów do listy rozwijanej filtra
$vehicles = $pdo->query("SELECT id, brand, model, number FROM vehicles WHERE status = 'aktywny'")->fetchAll(PDO::FETCH_ASSOC);

// Pobieranie wszystkich logów (z filtrem lub bez)
if (!empty($vehicle_filter)) {
    $stmtLogs = $pdo->prepare("
        SELECT l.*, v.brand, v.model, v.number, u.first_name, u.last_name 
        FROM logs l 
        LEFT JOIN vehicles v ON l.vehicle_id = v.id 
        LEFT JOIN users u ON l.user_id = u.id
        WHERE l.vehicle_id = ?
        ORDER BY l.created_at DESC
    ");
    $stmtLogs->execute([$vehicle_filter]);
} else {
    $stmtLogs = $pdo->query("
        SELECT l.*, v.brand, v.model, v.number, u.first_name, u.last_name 
        FROM logs l 
        LEFT JOIN vehicles v ON l.vehicle_id = v.id 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.created_at DESC
    ");
}
$all_logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper wrapper-900">
        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Panel Administracyjny</p>
                <h1 class="dashboard-title">Nadzór nad Raportami</h1>
            </div>
            <div class="current-date date-outline">
                <i class="fas fa-shield-alt"></i> Tryb Administratora
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="card admin-panel-card">

            <div class="filter-bar">
                <form method="GET" class="inline-form-flex">
                    <select name="vehicle_id" class="inline-select">
                        <option value="">Wszystkie pojazdy zespołu</option>
                        <?php foreach($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $vehicle_filter == $v['id'] ? 'selected' : ''; ?>>
                                #<?php echo htmlspecialchars($v['number'] . ' - ' . $v['brand'] . ' ' . $v['model']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-outline btn-sm">
                        <i class="fas fa-filter"></i> Filtruj pojazd
                    </button>
                    <?php if(!empty($vehicle_filter)): ?>
                        <a href="logs_manage.php" class="btn-cancel btn-sm">Wyczyść filtr</a>
                    <?php endif; ?>
                </form>
            </div>

            <h2 class="section-label"><i class="fas fa-database"></i> Wszystkie wpisy mechaników</h2>

            <div class="table-container table-responsive">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Mechanik</th>
                        <th>Tytuł</th>
                        <th>Pojazd</th>
                        <th>Akcje</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($all_logs)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Brak wpisów spełniających kryteria wyboru.</td></tr>
                    <?php else: ?>
                        <?php foreach($all_logs as $log): ?>
                            <tr>
                                <td class="text-nowrap"><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>
                                <td><strong class="dashboard-sub"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($log['title']); ?></strong></td>
                                <td>
                                    <strong>#<?php echo htmlspecialchars($log['number']); ?></strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($log['brand'] . ' ' . $log['model']); ?></span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="log_details.php?id=<?php echo $log['id']; ?>" class="btn-action btn-activate" title="Szczegóły">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_log.php?id=<?php echo $log['id']; ?>" class="btn-action btn-edit" title="Modyfikuj wpis">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="POST" class="inline-form-flex" onsubmit="return confirm('Czy na pewno chcesz usunąć ten raport? Zużyte części automatycznie powrócą na stan magazynu.');">
                                            <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                            <button type="submit" name="delete_log" class="btn-action btn-deactivate" title="Usuń">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

<?php include '../includes/footer.php'; ?>