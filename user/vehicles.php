<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$vehicles = $pdo->query("
    SELECT v.*, COUNT(l.id) as logs_count
    FROM vehicles v
    LEFT JOIN logs l ON v.id = l.vehicle_id
    GROUP BY v.id
    ORDER BY v.brand ASC, v.model ASC
")->fetchAll();

$totalVehicles = count($vehicles);
$statusCounts = [
    'aktywny' => 0,
    'w_naprawie' => 0,
    'wycofany' => 0,
];

foreach ($vehicles as $vehicle) {
    $status = $vehicle['status'] ?? 'nieznany';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Panel mechanika</p>
            <h1 class="dashboard-title">Lista pojazdów</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <section class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-car-side"></i>
            <div class="stat-info">
                <span class="value"><?php echo $totalVehicles; ?></span>
                <span class="label">Wszystkie pojazdy</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-check-circle"></i>
            <div class="stat-info">
                <span class="value"><?php echo $statusCounts['aktywny']; ?></span>
                <span class="label">Aktywne</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-tools"></i>
            <div class="stat-info">
                <span class="value"><?php echo $statusCounts['w_naprawie']; ?></span>
                <span class="label">W naprawie</span>
            </div>
        </div>
        <div class="stat-card <?php echo $statusCounts['wycofany'] > 0 ? 'alert' : ''; ?>">
            <i class="fas fa-ban"></i>
            <div class="stat-info">
                <span class="value"><?php echo $statusCounts['wycofany']; ?></span>
                <span class="label">Wycofane</span>
            </div>
        </div>
    </section>

    <div class="card">
        <h2><i class="fas fa-car"></i> Pojazdy w systemie</h2>

        <?php if (empty($vehicles)): ?>
            <p class="empty-info">Brak pojazdów w systemie.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nr</th>
                    <th>Marka / Model</th>
                    <th>VIN</th>
                    <th>Rok</th>
                    <th>Status</th>
                    <th>Logi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $vehicle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($vehicle['number'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['vin'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['year'] ?? '—'); ?></td>
                    <td>
                        <?php
                            $status_map = [
                                'aktywny' => ['badge-success', 'Aktywny'],
                                'w_naprawie' => ['badge-pending', 'W naprawie'],
                                'wycofany' => ['badge-danger', 'Wycofany'],
                            ];
                            $status_key = $vehicle['status'] ?? 'nieznany';
                            $badge = $status_map[$status_key][0] ?? 'badge-info';
                            $label = $status_map[$status_key][1] ?? ucfirst($status_key);
                        ?>
                        <span class="badge <?php echo $badge; ?>">
                            <?php echo htmlspecialchars($label); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-info"><?php echo $vehicle['logs_count']; ?> szt.</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</main>

<?php include '../includes/footer.php'; ?>
