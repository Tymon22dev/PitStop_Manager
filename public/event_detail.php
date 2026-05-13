<?php
session_start();
require_once '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events.php");
    exit;
}

$event_id = (int)$_GET['id'];

// Pobierz wydarzenie
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: events.php");
    exit;
}

// Pobierz przypisane pojazdy
$stmtV = $pdo->prepare("
    SELECT v.number, v.brand, v.model, v.status
    FROM event_vehicles ev
    JOIN vehicles v ON ev.vehicle_id = v.id
    WHERE ev.event_id = ?
");
$stmtV->execute([$event_id]);
$vehicles = $stmtV->fetchAll();

$badgeClass = match($event['status']) {
    'zakończone' => 'badge-success',
    'anulowane'  => 'badge-danger',
    default      => 'badge-pending'
};

include '../includes/header.php';
?>

<main class="home-wrapper">

    <!-- Powrót -->
    <div style="margin-bottom: 30px;">
        <a href="events.php" class="btn-outline" style="display: inline-flex;">
            <i class="fas fa-arrow-left"></i> Powrót do kalendarza
        </a>
    </div>

    <!-- Hero ze zdjęciem -->
    <div class="event-detail-hero">
        <?php if (!empty($event['photo'])): ?>
            <img src="<?php echo htmlspecialchars('../' . $event['photo']); ?>"
                 alt="<?php echo htmlspecialchars($event['title']); ?>"
                 class="event-detail-img">
        <?php else: ?>
            <div class="event-detail-placeholder">
                <i class="fas fa-flag-checkered"></i>
            </div>
        <?php endif; ?>

        <div class="event-detail-overlay">
            <span class="badge <?php echo $badgeClass; ?> event-detail-badge">
                <?php echo htmlspecialchars($event['status']); ?>
            </span>
            <h1 class="event-detail-title"><?php echo htmlspecialchars($event['title']); ?></h1>
            <div class="event-detail-meta">
                <span>
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('d.m.Y', strtotime($event['event_date'])); ?>
                </span>
                <?php if (!empty($event['track_name'])): ?>
                    <span>
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($event['track_name']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Treść -->
    <div class="event-detail-grid">

        <!-- Lewa kolumna: opis -->
        <div class="event-detail-main">

            <?php if (!empty($event['description'])): ?>
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> Opis wydarzenia</h2>
                <p class="event-detail-desc">
                    <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                </p>
            </div>
            <?php endif; ?>

            <?php if ($event['status'] === 'zakończone' && !empty($event['result'])): ?>
            <div class="card event-result-card">
                <h2><i class="fas fa-trophy"></i> Wynik</h2>
                <p class="event-result-value">
                    <?php echo htmlspecialchars($event['result']); ?>
                </p>
            </div>
            <?php endif; ?>

        </div>

        <!-- Prawa kolumna: szczegóły -->
        <div class="event-detail-sidebar">

            <div class="card">
                <h2><i class="fas fa-info"></i> Szczegóły</h2>

                <ul class="detail-list">
                    <li>
                        <span class="detail-label">Data</span>
                        <span class="detail-value">
                            <?php echo date('d.m.Y', strtotime($event['event_date'])); ?>
                        </span>
                    </li>
                    <li>
                        <span class="detail-label">Tor</span>
                        <span class="detail-value">
                            <?php echo !empty($event['track_name']) 
                                ? htmlspecialchars($event['track_name']) 
                                : '—'; ?>
                        </span>
                    </li>
                    <li>
                        <span class="detail-label">Status</span>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars($event['status']); ?>
                        </span>
                    </li>
                    <?php if ($event['status'] === 'zakończone' && !empty($event['result'])): ?>
                    <li>
                        <span class="detail-label">Wynik</span>
                        <span class="detail-value" style="color: var(--success); font-weight: 700;">
                            <?php echo htmlspecialchars($event['result']); ?>
                        </span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Pojazdy -->
            <?php if (!empty($vehicles)): ?>
            <div class="card">
                <h2><i class="fas fa-car"></i> Pojazdy</h2>
                <ul class="vehicle-list">
                    <?php foreach ($vehicles as $v): ?>
                    <li class="vehicle-list-item">
                        <div class="vehicle-number">
                            #<?php echo htmlspecialchars($v['number'] ?? '?'); ?>
                        </div>
                        <div class="vehicle-info">
                            <span class="vehicle-name">
                                <?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?>
                            </span>
                            <?php
                                $vBadge = match($v['status']) {
                                    'aktywny'    => 'badge-success',
                                    'w_naprawie' => 'badge-pending',
                                    'wycofany'   => 'badge-danger',
                                    default      => 'badge-info'
                                };
                                $vLabel = match($v['status']) {
                                    'aktywny'    => 'Aktywny',
                                    'w_naprawie' => 'W naprawie',
                                    'wycofany'   => 'Wycofany',
                                    default      => $v['status']
                                };
                            ?>
                            <span class="badge <?php echo $vBadge; ?>">
                                <?php echo $vLabel; ?>
                            </span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>