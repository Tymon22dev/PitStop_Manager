<?php
session_start();
require_once '../config/db.php';

// Filtrowanie po statusie
$filter = $_GET['status'] ?? 'wszystkie';

$sql = "SELECT * FROM vehicles";
if ($filter !== 'wszystkie') {
    $sql .= " WHERE status = " . $pdo->quote($filter);
}
$sql .= " ORDER BY brand ASC, model ASC";

$vehicles = $pdo->query($sql)->fetchAll();

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">PitStop Racing</p>
            <h1 class="dashboard-title">Nasze pojazdy</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <!-- Filtr -->
    <div class="filter-bar" style="margin-bottom: 30px;">
        <a href="vehicles.php"
           class="filter-btn <?php echo $filter === 'wszystkie' ? 'active' : ''; ?>">
            Wszystkie
        </a>
        <a href="vehicles.php?status=aktywny"
           class="filter-btn <?php echo $filter === 'aktywny' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Aktywne
        </a>
        <a href="vehicles.php?status=w_naprawie"
           class="filter-btn <?php echo $filter === 'w_naprawie' ? 'active' : ''; ?>">
            <i class="fas fa-wrench"></i> W naprawie
        </a>
        <a href="vehicles.php?status=wycofany"
           class="filter-btn <?php echo $filter === 'wycofany' ? 'active' : ''; ?>">
            <i class="fas fa-ban"></i> Wycofane
        </a>
    </div>

    <?php if (empty($vehicles)): ?>
        <div class="card">
            <p class="empty-info">Brak pojazdów dla wybranego filtra.</p>
        </div>
    <?php else: ?>
        <div class="vehicles-grid">
            <?php foreach ($vehicles as $v):
                $badgeClass = match($v['status']) {
                    'aktywny'    => 'badge-success',
                    'w_naprawie' => 'badge-pending',
                    'wycofany'   => 'badge-danger',
                    default      => 'badge-info'
                };
                $badgeLabel = match($v['status']) {
                    'aktywny'    => 'Aktywny',
                    'w_naprawie' => 'W naprawie',
                    'wycofany'   => 'Wycofany',
                    default      => $v['status']
                };
            ?>
            <a href="vehicle_detail.php?id=<?php echo $v['id']; ?>" class="vehicle-card">

                <!-- Zdjęcie -->
                <div class="vehicle-card-image">
                    <?php if (!empty($v['photo'])): ?>
                        <img src="../<?php echo htmlspecialchars($v['photo']); ?>"
                             alt="<?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?>">
                    <?php else: ?>
                        <div class="vehicle-card-placeholder">
                            <i class="fas fa-car"></i>
                        </div>
                    <?php endif; ?>
                    <span class="badge <?php echo $badgeClass; ?> vehicle-card-badge">
                        <?php echo $badgeLabel; ?>
                    </span>
                    <?php if (!empty($v['number'])): ?>
                        <span class="vehicle-card-number">#<?php echo htmlspecialchars($v['number']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Treść -->
                <div class="vehicle-card-body">
                    <h2 class="vehicle-card-title">
                        <?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?>
                    </h2>

                    <div class="vehicle-card-specs">
                        <?php if (!empty($v['category'])): ?>
                            <span class="vehicle-spec">
                                <i class="fas fa-flag"></i>
                                <?php echo htmlspecialchars($v['category']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($v['engine'])): ?>
                            <span class="vehicle-spec">
                                <i class="fas fa-cog"></i>
                                <?php echo htmlspecialchars($v['engine']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($v['drive_type'])): ?>
                            <span class="vehicle-spec">
                                <i class="fas fa-road"></i>
                                <?php echo htmlspecialchars($v['drive_type']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($v['year'])): ?>
                            <span class="vehicle-spec">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo htmlspecialchars($v['year']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($v['description'])): ?>
                        <p class="vehicle-card-desc">
                            <?php echo htmlspecialchars(substr($v['description'], 0, 100)) . (strlen($v['description']) > 100 ? '...' : ''); ?>
                        </p>
                    <?php endif; ?>

                    <span class="vehicle-card-link">
                        Szczegóły <i class="fas fa-chevron-right"></i>
                    </span>
                </div>

            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>