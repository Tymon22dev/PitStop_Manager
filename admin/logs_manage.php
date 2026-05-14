<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$message = $_GET['success'] ?? '';
$messageType = 'success';

// --- USUWANIE DOWOLNEGO RAPORTU (Tylko Admin) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_log'])) {
    $log_id = $_POST['log_id'];

    try {
        $pdo->beginTransaction();

        $stmtOldParts = $pdo->prepare("SELECT part_id, quantity_used FROM log_parts WHERE log_id = ?");
        $stmtOldParts->execute([$log_id]);
        $oldParts = $stmtOldParts->fetchAll();

        $stmtRestoreStock = $pdo->prepare("UPDATE parts SET quantity = quantity + ? WHERE id = ?");
        foreach ($oldParts as $op) {
            $stmtRestoreStock->execute([$op['quantity_used'], $op['part_id']]);
        }

        $stmt = $pdo->prepare("DELETE FROM logs WHERE id = ?");
        $stmt->execute([$log_id]);

        $pdo->commit();
        $message = "Raport został trwale usunięty. Części wróciły do magazynu.";
        $messageType = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Błąd podczas usuwania: " . $e->getMessage();
        $messageType = "danger";
    }
}

// --- POBIERANIE WSZYSTKICH LOGÓW ---
$stmtLogs = $pdo->query("
    SELECT l.*, v.brand, v.model, v.number, u.first_name, u.last_name 
    FROM logs l 
    LEFT JOIN vehicles v ON l.vehicle_id = v.id 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC
");
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
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="card admin-panel-card">
            <h2 class="section-label"><i class="fas fa-database"></i> Wszystkie wpisy mechaników</h2>

            <div class="table-container table-responsive">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Mechanik</th>
                        <th>Pojazd / Tytuł prac</th>
                        <th>Użyte części</th>
                        <th>Akcje (Admin)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($all_logs)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Brak wpisów w systemie.</td></tr>
                    <?php else: ?>
                        <?php foreach($all_logs as $log): ?>
                            <tr>
                                <td class="text-nowrap"><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>

                                <td>
                                    <strong class="dashboard-sub"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong>
                                </td>

                                <td>
                                    <strong>#<?php echo htmlspecialchars($log['number']); ?> - <?php echo htmlspecialchars($log['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($log['brand'] . ' ' . $log['model']); ?></small>
                                </td>

                                <td>
                                    <?php
                                    $stmtParts = $pdo->prepare("
                                            SELECT p.name, lp.quantity_used FROM log_parts lp 
                                            JOIN parts p ON lp.part_id = p.id WHERE lp.log_id = ?
                                        ");
                                    $stmtParts->execute([$log['id']]);
                                    $used_parts = $stmtParts->fetchAll(PDO::FETCH_ASSOC);

                                    if (empty($used_parts)) {
                                        echo '<span class="text-muted">Brak</span>';
                                    } else {
                                        foreach($used_parts as $up) {
                                            echo '<span class="badge badge-info">' . htmlspecialchars($up['name']) . ' x' . $up['quantity_used'] . '</span> ';
                                        }
                                    }
                                    ?>
                                </td>

                                <td>
                                    <!-- Korzystamy z nowej struktury dla przycisków w tabeli -->
                                    <div class="table-actions">
                                        <form method="POST" onsubmit="return confirm('Usunąć ten raport jako Administrator? Zwróci to użyte części do magazynu.');" style="margin: 0;">
                                            <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                            <button type="submit" name="delete_log" class="btn-action btn-deactivate" title="Usuń trwale ten raport">
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