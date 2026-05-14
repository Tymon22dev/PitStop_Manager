<?php
session_start();
require_once '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: drivers.php");
    exit;
}

$driver_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch();

if (!$driver) {
    header("Location: drivers.php");
    exit;
}

// Wiek kierowcy
$age = null;
if (!empty($driver['date_of_birth'])) {
    $age = (int)date_diff(
        new DateTime($driver['date_of_birth']),
        new DateTime()
    )->y;
}

// Ostatnie wydarzenia kierowcy
$stmt = $pdo->prepare("
    SELECT e.id, e.title, e.event_date, e.track_name, e.status, e.result
    FROM event_drivers ed
    JOIN events e ON ed.event_id = e.id
    WHERE ed.driver_id = ?
    ORDER BY e.event_date DESC
    LIMIT 10
");
$stmt->execute([$driver_id]);
$events = $stmt->fetchAll();

// Statystyki
$total_events    = count($events);
$finished_events = array_filter($events, fn($e) => $e['status'] === 'zakończone');

include '../includes/header.php';
?>

<main class="home-wrapper">

    <!-- Powrót -->
    <div style="margin-bottom: 30px;">
        <a href="drivers.php" class="btn-outline" style="display: inline-flex;">
            <i class="fas fa-arrow-left"></i> Powrót do kierowców
        </a>
    </div>

    <!-- Hero -->
    <div class="driver-hero">
        <div class="driver-hero-photo">
            <?php if (!empty($driver['photo'])): ?>
                <img src="../<?php echo htmlspecialchars($driver['photo']); ?>"
                     alt="<?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?>">
            <?php else: ?>
                <div class="driver-hero-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="driver-hero-info">
            <?php if (!empty($driver['number'])): ?>
                <span class="driver-hero-number">#<?php echo htmlspecialchars($driver['number']); ?></span>
            <?php endif; ?>

            <h1 class="driver-hero-name">
                <?php echo htmlspecialchars($driver['first_name']); ?><br>
                <span><?php echo htmlspecialchars($driver['last_name']); ?></span>
            </h1>

            <div class="driver-hero-tags">
                <?php if (!empty($driver['nationality'])): ?>
                    <span class="driver-tag">
                        <i class="fas fa-flag"></i>
                        <?php echo htmlspecialchars($driver['nationality']); ?>
                    </span>
                <?php endif; ?>
                <?php if ($age): ?>
                    <span class="driver-tag">
                        <i class="fas fa-birthday-cake"></i>
                        <?php echo $age; ?> lat
                    </span>
                <?php endif; ?>
                <?php if (!empty($driver['joined_year'])): ?>
                    <span class="driver-tag">
                        <i class="fas fa-calendar-check"></i>
                        W zespole od <?php echo htmlspecialchars($driver['joined_year']); ?>
                    </span>
                <?php endif; ?>
                <span class="badge <?php echo $driver['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo $driver['is_active'] ? 'Aktywny' : 'Były kierowca'; ?>
                </span>
            </div>

            <!-- Mini statystyki -->
            <div class="driver-hero-stats">
                <div class="driver-stat">
                    <span class="driver-stat-value"><?php echo $total_events; ?></span>
                    <span class="driver-stat-label">Startów</span>
                </div>
                <div class="driver-stat">
                    <span class="driver-stat-value"><?php echo count($finished_events); ?></span>
                    <span class="driver-stat-label">Ukończonych</span>
                </div>
                <?php if (!empty($driver['joined_year'])): ?>
                <div class="driver-stat">
                    <span class="driver-stat-value">
                        <?php echo date('Y') - (int)$driver['joined_year']; ?>
                    </span>
                    <span class="driver-stat-label">Lat w zespole</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="event-detail-grid">

        <!-- Lewa kolumna -->
        <div class="event-detail-main">

            <?php if (!empty($driver['bio'])): ?>
            <div class="card">
                <h2><i class="fas fa-user"></i> Biografia</h2>
                <p class="event-detail-desc">
                    <?php echo nl2br(htmlspecialchars($driver['bio'])); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Historia startów -->
            <?php if (!empty($events)): ?>
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
                        <?php foreach ($events as $e):
                            $eBadge = match($e['status']) {
                                'zakończone' => 'badge-success',
                                'anulowane'  => 'badge-danger',
                                default      => 'badge-pending'
                            };
                        ?>
                        <tr onclick="window.location='event_detail.php?id=<?php echo $e['id']; ?>'"
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

        <!-- Prawa kolumna -->
        <div class="event-detail-sidebar">
            <div class="card">
                <h2><i class="fas fa-id-card"></i> Dane kierowcy</h2>
                <ul class="detail-list">

                    <?php if (!empty($driver['number'])): ?>
                    <li>
                        <span class="detail-label">Numer</span>
                        <span class="detail-value" style="color: var(--primary); font-size: 1.2rem; font-weight: 800;">
                            #<?php echo htmlspecialchars($driver['number']); ?>
                        </span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($driver['nationality'])): ?>
                    <li>
                        <span class="detail-label">Narodowość</span>
                        <span class="detail-value"><?php echo htmlspecialchars($driver['nationality']); ?></span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($driver['date_of_birth'])): ?>
                    <li>
                        <span class="detail-label">Data urodzenia</span>
                        <span class="detail-value">
                            <?php echo date('d.m.Y', strtotime($driver['date_of_birth'])); ?>
                            <?php if ($age): ?>
                                <small style="color: #666;">(<?php echo $age; ?> lat)</small>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($driver['joined_year'])): ?>
                    <li>
                        <span class="detail-label">W zespole od</span>
                        <span class="detail-value"><?php echo htmlspecialchars($driver['joined_year']); ?></span>
                    </li>
                    <?php endif; ?>

                    <li>
                        <span class="detail-label">Status</span>
                        <span class="badge <?php echo $driver['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $driver['is_active'] ? 'Aktywny' : 'Były kierowca'; ?>
                        </span>
                    </li>

                </ul>
            </div>
        </div>

    </div>

</main>

<?php include '../includes/footer.php'; ?>