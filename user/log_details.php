<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$log_id = $_GET['id'] ?? null;
if (!$log_id) {
    header("Location: logs_manage.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT l.*, v.brand, v.model, v.number, v.vin, u.first_name, u.last_name 
    FROM logs l 
    LEFT JOIN vehicles v ON l.vehicle_id = v.id 
    LEFT JOIN users u ON l.user_id = u.id 
    WHERE l.id = ?
");
$stmt->execute([$log_id]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) {
    header("Location: logs_manage.php");
    exit;
}

$stmtParts = $pdo->prepare("
    SELECT p.name, lp.quantity_used 
    FROM log_parts lp 
    JOIN parts p ON lp.part_id = p.id 
    WHERE lp.log_id = ?
");
$stmtParts->execute([$log_id]);
$used_parts = $stmtParts->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper wrapper-900">
        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Szczegóły raportu technicznego</p>
                <h1 class="dashboard-title"><?php echo htmlspecialchars($log['title']); ?></h1>
            </div>
            <div class="current-date date-outline">
                <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?>
            </div>
        </div>

        <div class="admin-layout-grid">
            <section class="card admin-panel-card">
                <h2 class="section-label"><i class="fas fa-file-alt"></i> Treść raportu</h2>
                <div class="form-group">
                    <label class="label-sm">AUTOR WPISU</label>
                    <input type="text" class="dark-input" readonly value="<?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>">
                </div>
                <div class="form-group">
                    <label class="label-sm">SERWISOWANY POJAZD</label>
                    <input type="text" class="dark-input" readonly value="#<?php echo htmlspecialchars($log['number'] . ' - ' . $log['brand'] . ' ' . $log['model']); ?>">
                </div>
                <div class="form-group">
                    <label class="label-sm">SZCZEGÓŁOWY OPIS TECHNICZNY</label>
                    <textarea class="dark-input" rows="8" readonly><?php echo htmlspecialchars($log['content']); ?></textarea>
                </div>
                <div class="form-actions-row">
                    <a href="logs_manage.php" class="btn-cancel">
                        <i class="fas fa-arrow-left"></i> POWRÓT DO LISTY
                    </a>
                </div>
            </section>

            <section class="card admin-panel-card">
                <h2 class="section-label"><i class="fas fa-boxes"></i> Zużyte części materiałowe</h2>
                <div class="table-container table-responsive">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>Nazwa części</th>
                            <th>Ilość</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($used_parts)): ?>
                            <tr><td colspan="2" class="text-center text-muted">Nie zarejestrowano zużycia części.</td></tr>
                        <?php else: ?>
                            <?php foreach ($used_parts as $up): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($up['name']); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo $up['quantity_used']; ?> szt.</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

<?php include '../includes/footer.php'; ?>