<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = $_GET['success'] ?? '';
$messageType = 'success';

$vehicle_filter = $_GET['vehicle_id'] ?? '';

// Pobranie listy pojazdów do rozwijanego menu filtrowania
$vehicles = $pdo->query("SELECT id, brand, model, number FROM vehicles WHERE status = 'aktywny'")->fetchAll(PDO::FETCH_ASSOC);

// Pobranie logów w zależności od wybranego filtra
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
$logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper wrapper-900">
        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Zarządzanie pracą</p>
                <h1 class="dashboard-title">Dziennik Serwisowy</h1>
            </div>
            <div>
                <a href="add_log.php" class="btn-save">
                    <i class="fas fa-plus"></i> DODAJ NOWY RAPORT
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="card admin-panel-card">

            <div class="filter-bar">
                <form method="GET" class="inline-form-flex">
                    <select name="vehicle_id" class="inline-select">
                        <option value="">Wszystkie pojazdy</option>
                        <?php foreach($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $vehicle_filter == $v['id'] ? 'selected' : ''; ?>>
                                #<?php echo htmlspecialchars($v['number'] . ' - ' . $v['brand'] . ' ' . $v['model']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-outline btn-sm">
                        <i class="fas fa-filter"></i> Filtruj
                    </button>
                    <?php if(!empty($vehicle_filter)): ?>
                        <a href="logs_manage.php" class="btn-cancel btn-sm">Wyczyść</a>
                    <?php endif; ?>
                </form>
            </div>

            <h2 class="section-label"><i class="fas fa-history"></i> Historia prac zespołu</h2>

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
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Brak wpisów dla wybranego filtru.</td></tr>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                            <tr>
                                <td class="text-nowrap"><?php echo date('d.m.Y', strtotime($log['created_at'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($log['title']); ?></td>
                                <td>
                                    <strong>#<?php echo htmlspecialchars($log['number']); ?></strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($log['brand'] . ' ' . $log['model']); ?></span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="log_details.php?id=<?php echo $log['id']; ?>" class="btn-action btn-activate" title="Szczegóły raportu">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <?php if ($log['user_id'] == $user_id): ?>
                                            <a href="edit_log.php?id=<?php echo $log['id']; ?>" class="btn-action btn-edit" title="Edytuj swój raport">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                        <?php endif; ?>
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