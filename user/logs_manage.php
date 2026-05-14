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

// --- POBIERANIE HISTORII - TYLKO DLA ZALOGOWANEGO UŻYTKOWNIKA ---
$stmtLogs = $pdo->prepare("
    SELECT l.*, v.brand, v.model, v.number 
    FROM logs l 
    LEFT JOIN vehicles v ON l.vehicle_id = v.id 
    WHERE l.user_id = ? 
    ORDER BY l.created_at DESC
");
$stmtLogs->execute([$user_id]);
$logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper wrapper-900">
        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Zarządzanie pracą</p>
                <h1 class="dashboard-title">Dziennik Serwisowy</h1>
            </div>
            <div class="current-date date-outline">
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
            <h2 class="section-label"><i class="fas fa-history"></i> Twoja historia prac</h2>

            <div class="table-container table-responsive">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pojazd</th>
                        <th>Szczegóły prac</th>
                        <th>Użyte części</th>
                        <th>Akcje</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Brak wpisów w dzienniku.</td></tr>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                            <tr>
                                <td class="text-nowrap"><?php echo date('d.m.Y', strtotime($log['created_at'])); ?></td>

                                <td>
                                    <strong>#<?php echo htmlspecialchars($log['number']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($log['brand'] . ' ' . $log['model']); ?></small>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($log['title']); ?></strong>
                                </td>

                                <td>
                                    <?php
                                    $stmtParts = $pdo->prepare("
                                            SELECT p.name, lp.quantity_used 
                                            FROM log_parts lp 
                                            JOIN parts p ON lp.part_id = p.id 
                                            WHERE lp.log_id = ?
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
                                    <!-- Korzystamy z Twoich nowych klas do przycisków w tabeli -->
                                    <div class="table-actions">
                                        <a href="edit_log.php?id=<?php echo $log['id']; ?>" class="btn-action btn-edit" title="Popraw swój wpis">
                                            <i class="fas fa-pen"></i>
                                        </a>
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