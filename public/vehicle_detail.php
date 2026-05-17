<?php
session_start();
require_once '../config/db.php';

$ref      = $_GET['ref'] ?? '';
$event_id = isset($_GET['event_id']) && is_numeric($_GET['event_id']) 
            ? (int)$_GET['event_id'] 
            : null;

if ($ref === 'event' && $event_id) {
    $back_url   = "event_detail.php?id=$event_id";
    $back_label = "Powrót do wydarzenia";
} else {
    $back_url   = "vehicles.php";
    $back_label = "Powrót do pojazdów";
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: vehicles.php");
    exit;
}

$vehicle_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header("Location: vehicles.php");
    exit;
}

// Ostatnie wydarzenia z tym pojazdem
$events = $pdo->prepare("
    SELECT e.id, e.title, e.event_date, e.track_name, e.status, e.result
    FROM event_vehicles ev
    JOIN events e ON ev.event_id = e.id
    WHERE ev.vehicle_id = ?
    ORDER BY e.event_date DESC
    LIMIT 5
");
$events->execute([$vehicle_id]);
$recent_events = $events->fetchAll();

$badgeClass = match($vehicle['status']) {
    'aktywny'    => 'badge-success',
    'w_naprawie' => 'badge-pending',
    'wycofany'   => 'badge-danger',
    default      => 'badge-info'
};
$badgeLabel = match($vehicle['status']) {
    'aktywny'    => 'Aktywny',
    'w_naprawie' => 'W naprawie',
    'wycofany'   => 'Wycofany',
    default      => $vehicle['status']
};

include '../includes/header.php';
?>

<main class="home-wrapper">

    <!-- Powrót -->
    <div style="margin-bottom: 30px;">
        <a href="<?php echo $back_url; ?>" class="btn-outline" style="display: inline-flex;">
            <i class="fas fa-arrow-left"></i> <?php echo $back_label; ?>
        </a>
    </div>

    <!-- Hero -->
    <div class="event-detail-hero">
        <?php if (!empty($vehicle['photo'])): ?>
            <img src="../<?php echo htmlspecialchars($vehicle['photo']); ?>"
                 alt="<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>"
                 class="event-detail-img">
        <?php else: ?>
            <div class="event-detail-placeholder">
                <i class="fas fa-car"></i>
            </div>
        <?php endif; ?>

        <div class="event-detail-overlay">
            <span class="badge <?php echo $badgeClass; ?> event-detail-badge">
                <?php echo $badgeLabel; ?>
            </span>
            <h1 class="event-detail-title">
                <?php if (!empty($vehicle['number'])): ?>
                    <span style="color: var(--primary);">#<?php echo htmlspecialchars($vehicle['number']); ?></span>
                <?php endif; ?>
                <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>
            </h1>
            <div class="event-detail-meta">
                <?php if (!empty($vehicle['category'])): ?>
                    <span><i class="fas fa-flag"></i> <?php echo htmlspecialchars($vehicle['category']); ?></span>
                <?php endif; ?>
                <?php if (!empty($vehicle['year'])): ?>
                    <span><i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($vehicle['year']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="event-detail-grid">

        <!-- Lewa kolumna -->
        <div class="event-detail-main">

            <?php if (!empty($vehicle['description'])): ?>
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> O pojeździe</h2>
                <p class="event-detail-desc">
                    <?php echo nl2br(htmlspecialchars($vehicle['description'])); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Ostatnie wydarzenia -->
            <?php if (!empty($recent_events)): ?>
            <div class="card">
                <h2><i class="fas fa-flag-checkered"></i> Historia startów</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Wydarzenie</th>
                            <th>Tor</th>
                            <th>Wynik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_events as $e):
                            $eBadge = match($e['status']) {
                                'zakończone' => 'badge-success',
                                'anulowane'  => 'badge-danger',
                                default      => 'badge-pending'
                            };
                        ?>
                        <tr onclick="window.location='vehicle_detail.php?id=<?php echo $v['id']; ?>&ref=event&event_id=<?php echo $event['id']; ?>'"
                            style="cursor: pointer;">
                            <td><?php echo date('d.m.Y', strtotime($e['event_date'])); ?></td>
                            <td><?php echo htmlspecialchars($e['title']); ?></td>
                            <td><?php echo htmlspecialchars($e['track_name'] ?? '—'); ?></td>
                            <td>
                                <?php if (!empty($e['result'])): ?>
                                    <span class="result-highlight">
                                        <i class="fas fa-trophy"></i>
                                        <?php echo htmlspecialchars($e['result']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge <?php echo $eBadge; ?>">
                                        <?php echo htmlspecialchars($e['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>

        <!-- Prawa kolumna: specyfikacja -->
        <div class="event-detail-sidebar">
            <div class="card">
                <h2><i class="fas fa-cogs"></i> Specyfikacja</h2>
                <ul class="detail-list">

                    <?php if (!empty($vehicle['number'])): ?>
                    <li>
                        <span class="detail-label">Numer startowy</span>
                        <span class="detail-value" style="color: var(--primary); font-size: 1.1rem;">
                            #<?php echo htmlspecialchars($vehicle['number']); ?>
                        </span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['category'])): ?>
                    <li>
                        <span class="detail-label">Kategoria</span>
                        <span class="detail-value"><?php echo htmlspecialchars($vehicle['category']); ?></span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['engine'])): ?>
                    <li>
                        <span class="detail-label">Silnik</span>
                        <span class="detail-value"><?php echo htmlspecialchars($vehicle['engine']); ?></span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['drive_type'])): ?>
                    <li>
                        <span class="detail-label">Napęd</span>
                        <span class="detail-value"><?php echo htmlspecialchars($vehicle['drive_type']); ?></span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['weight'])): ?>
                    <li>
                        <span class="detail-label">Masa</span>
                        <span class="detail-value"><?php echo htmlspecialchars($vehicle['weight']); ?> kg</span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['year'])): ?>
                    <li>
                        <span class="detail-label">Rok produkcji</span>
                        <span class="detail-value"><?php echo htmlspecialchars($vehicle['year']); ?></span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($vehicle['debut_year'])): ?>
                    <li>
                        <span class="detail-label">Rok debiutu</span>
                        <span class="detail-value"><?php echo htmlspecialchars($vehicle['debut_year']); ?></span>
                    </li>
                    <?php endif; ?>

                    <li>
                        <span class="detail-label">Status</span>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
                    </li>

                </ul>
            </div>
        </div>

    </div>

</main>

<?php include '../includes/footer.php'; ?>