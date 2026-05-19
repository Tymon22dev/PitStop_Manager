<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$username = $_SESSION['username'];

// --- Statystyki z bazy ---
$active_users   = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1 AND role = 'user'")->fetchColumn();
$active_vehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'aktywny'")->fetchColumn();
$low_parts      = $pdo->query("SELECT COUNT(*) FROM parts WHERE quantity <= min_quantity")->fetchColumn();

// --- Ostatnie 5 logów technicznych ---
$recent_logs = $pdo->query("
    SELECT l.title, l.created_at,
           u.first_name, u.last_name,
           v.brand, v.model
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
    LEFT JOIN vehicles v ON l.vehicle_id = v.id
    ORDER BY l.created_at DESC
    LIMIT 5
")->fetchAll();

include '../includes/header.php';
?>

<main class="home-wrapper">

    <!-- Powitanie -->
    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Panel administracyjny</p>
            <h1 class="dashboard-title">Witaj, <?php echo htmlspecialchars($username); ?></h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <!-- Karty statystyk -->
    <section class="stats-grid">

        <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="stat-info">
                <span class="value"><?php echo $active_users; ?></span>
                <span class="label">Aktywnych pracowników</span>
            </div>
        </div>

        <div class="stat-card">
            <i class="fas fa-car"></i>
            <div class="stat-info">
                <span class="value"><?php echo $active_vehicles; ?></span>
                <span class="label">Pojazdów w flocie</span>
            </div>
        </div>

        <div class="stat-card <?php echo $low_parts > 0 ? 'alert' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="stat-info">
                <span class="value"><?php echo $low_parts; ?></span>
                <span class="label">Niskie stany części</span>
            </div>
        </div>

    </section>

    <!-- Ostatnie logi -->
    <section class="card">
        <h2><i class="fas fa-clipboard-list"></i> Ostatnie logi techniczne</h2>

        <?php if (empty($recent_logs)): ?>
            <p class="empty-info">Brak wpisów w bazie.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mechanik</th>
                    <th>Tytuł</th>
                    <th>Pojazd</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($log['title']); ?></td>
                    <td><?php echo htmlspecialchars($log['brand'] . ' ' . $log['model']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="card-footer">
            <a href="logs_manage.php" class="btn-outline">
                Wszystkie logi <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </section>

</main>

<?php include '../includes/footer.php'; ?>