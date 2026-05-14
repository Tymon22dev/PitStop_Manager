<?php
session_start();
require_once '../config/db.php';

$filter = $_GET['status'] ?? 'wszyscy';

$sql = "SELECT * FROM drivers";
if ($filter === 'aktywni') {
    $sql .= " WHERE is_active = 1";
} elseif ($filter === 'nieaktywni') {
    $sql .= " WHERE is_active = 0";
}
$sql .= " ORDER BY last_name ASC";

$drivers = $pdo->query($sql)->fetchAll();

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">PitStop Racing</p>
            <h1 class="dashboard-title">Nasi kierowcy</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <!-- Filtr -->
    <div class="filter-bar" style="margin-bottom: 30px;">
        <a href="drivers.php"
           class="filter-btn <?php echo $filter === 'wszyscy' ? 'active' : ''; ?>">
            Wszyscy
        </a>
        <a href="drivers.php?status=aktywni"
           class="filter-btn <?php echo $filter === 'aktywni' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Aktywni
        </a>
        <a href="drivers.php?status=nieaktywni"
           class="filter-btn <?php echo $filter === 'nieaktywni' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i> Byli kierowcy
        </a>
    </div>

    <?php if (empty($drivers)): ?>
        <div class="card">
            <p class="empty-info">Brak kierowców dla wybranego filtra.</p>
        </div>
    <?php else: ?>
        <div class="drivers-grid">
            <?php foreach ($drivers as $d): ?>
            <a href="driver_detail.php?id=<?php echo $d['id']; ?>" class="driver-card">

                <!-- Zdjęcie -->
                <div class="driver-card-image">
                    <?php if (!empty($d['photo'])): ?>
                        <img src="../<?php echo htmlspecialchars($d['photo']); ?>"
                             alt="<?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?>">
                    <?php else: ?>
                        <div class="driver-card-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($d['number'])): ?>
                        <span class="driver-card-number">#<?php echo htmlspecialchars($d['number']); ?></span>
                    <?php endif; ?>

                    <?php if (!$d['is_active']): ?>
                        <span class="badge badge-danger driver-card-badge">Były kierowca</span>
                    <?php endif; ?>
                </div>

                <!-- Treść -->
                <div class="driver-card-body">
                    <h2 class="driver-card-name">
                        <?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?>
                    </h2>

                    <div class="driver-card-meta">
                        <?php if (!empty($d['nationality'])): ?>
                            <span>
                                <i class="fas fa-flag"></i>
                                <?php echo htmlspecialchars($d['nationality']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($d['joined_year'])): ?>
                            <span>
                                <i class="fas fa-calendar-check"></i>
                                W zespole od <?php echo htmlspecialchars($d['joined_year']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($d['bio'])): ?>
                        <p class="driver-card-bio">
                            <?php echo htmlspecialchars(substr($d['bio'], 0, 100)) . (strlen($d['bio']) > 100 ? '...' : ''); ?>
                        </p>
                    <?php endif; ?>

                    <span class="vehicle-card-link">
                        Profil kierowcy <i class="fas fa-chevron-right"></i>
                    </span>
                </div>

            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>