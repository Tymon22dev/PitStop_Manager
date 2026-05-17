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
    SELECT v.id, v.number, v.brand, v.model, v.status, v.photo
    FROM event_vehicles ev
    JOIN vehicles v ON ev.vehicle_id = v.id
    WHERE ev.event_id = ?
");
$stmtV->execute([$event_id]);
$vehicles = $stmtV->fetchAll();

// Pobierz przypisanych kierowców
$stmtD = $pdo->prepare("
    SELECT d.id, d.first_name, d.last_name, d.number, d.nationality, d.photo
    FROM event_drivers ed
    JOIN drivers d ON ed.driver_id = d.id
    WHERE ed.event_id = ?
    ORDER BY d.last_name ASC
");
$stmtD->execute([$event_id]);
$event_drivers = $stmtD->fetchAll();

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
    <div class="event-detail-new-grid">

        <!-- Lewa kolumna: pojazdy -->
        <?php if (!empty($vehicles)): ?>
        <div class="event-detail-vehicles">
            <div class="card">
                <h2><i class="fas fa-car"></i> Pojazdy</h2>
                <div class="event-vehicles-grid">
                    <?php foreach ($vehicles as $v):
                        $vehicle_url = 'vehicle_detail.php?id=' . $v['id'] . '&ref=event&event_id=' . $event['id'];
                    ?>
                    <a href="<?php echo $vehicle_url; ?>" class="event-vehicle-card">
                        <?php if (!empty($v['photo'])): ?>
                            <img src="../<?php echo htmlspecialchars($v['photo']); ?>"
                                 alt="<?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?>">
                        <?php else: ?>
                            <div class="event-vehicle-placeholder">
                                <i class="fas fa-car"></i>
                            </div>
                        <?php endif; ?>
                        <div class="event-vehicle-info">
                            <?php if (!empty($v['number'])): ?>
                                <span class="event-vehicle-number">#<?php echo htmlspecialchars($v['number']); ?></span>
                            <?php endif; ?>
                            <span class="event-vehicle-name">
                                <?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Prawa kolumna: szczegóły + kierowcy -->
        <div class="event-detail-side">

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

            <?php if (!empty($event_drivers)): ?>
            <div class="card">
                <h2><i class="fas fa-user-astronaut"></i> Kierowcy</h2>
                <div class="event-drivers-list">
                    <?php foreach ($event_drivers as $d):
                        $driver_url = 'driver_detail.php?id=' . $d['id'] . '&ref=event&event_id=' . $event['id'];
                    ?>
                    <a href="<?php echo $driver_url; ?>" class="event-driver-card">
                        <?php if (!empty($d['photo'])): ?>
                            <img src="../<?php echo htmlspecialchars($d['photo']); ?>"
                                 alt="<?php echo htmlspecialchars($d['first_name']); ?>"
                                 class="event-driver-photo">
                        <?php else: ?>
                            <div class="event-driver-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div class="event-driver-info">
                            <span class="event-driver-name">
                                <?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?>
                            </span>
                            <span class="event-driver-meta">
                                <?php if (!empty($d['number'])): ?>
                                    #<?php echo htmlspecialchars($d['number']); ?>
                                <?php endif; ?>
                                <?php if (!empty($d['nationality'])): ?>
                                    · <?php echo htmlspecialchars($d['nationality']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <i class="fas fa-chevron-right event-driver-arrow"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Dół: opis i wynik -->
    <?php if (!empty($event['description']) || ($event['status'] === 'zakończone' && !empty($event['result']))): ?>
    <div class="event-bottom-grid">

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
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>