<?php
session_start();
require_once '../config/db.php';

// Filtrowanie po statusie
$filter = $_GET['status'] ?? 'wszystkie';

$sql = "SELECT e.*, GROUP_CONCAT(CONCAT(v.brand, ' ', v.model) SEPARATOR ', ') as vehicle_names
        FROM events e
        LEFT JOIN event_vehicles ev ON e.id = ev.event_id
        LEFT JOIN vehicles v ON ev.vehicle_id = v.id";

if ($filter !== 'wszystkie') {
    $sql .= " WHERE e.status = " . $pdo->quote($filter);
}

$sql .= " GROUP BY e.id ORDER BY e.event_date ASC";

$events = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">PitStop Manager</p>
            <h1 class="dashboard-title">Kalendarz Wyścigów</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <!-- Filtr statusów -->
    <div class="filter-bar" style="margin-bottom: 30px;">
        <a href="events.php" class="filter-btn <?php echo $filter === 'wszystkie' ? 'active' : ''; ?>">
            Wszystkie
        </a>
        <a href="events.php?status=zaplanowane" class="filter-btn <?php echo $filter === 'zaplanowane' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> Zaplanowane
        </a>
        <a href="events.php?status=zakończone" class="filter-btn <?php echo $filter === 'zakończone' ? 'active' : ''; ?>">
            <i class="fas fa-flag-checkered"></i> Zakończone
        </a>
        <a href="events.php?status=anulowane" class="filter-btn <?php echo $filter === 'anulowane' ? 'active' : ''; ?>">
            <i class="fas fa-ban"></i> Anulowane
        </a>
    </div>

    <!-- Lista wydarzeń -->
    <?php if (empty($events)): ?>
        <div class="card">
            <p class="empty-info">Brak wydarzeń dla wybranego filtra.</p>
        </div>
    <?php else: ?>
        <div class="events-list">
            <?php foreach ($events as $event):
                $badgeClass = match($event['status']) {
                    'zakończone' => 'badge-success',
                    'anulowane'  => 'badge-danger',
                    default      => 'badge-pending'
                };
                $has_photo = !empty($event['photo']);
            ?>
            <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="event-card">

                <!-- Zdjęcie lub placeholder -->
                <div class="event-card-image">
                    <?php if ($has_photo): ?>
                        <img src="<?php echo htmlspecialchars('../' . $event['photo']); ?>"
                             alt="<?php echo htmlspecialchars($event['title']); ?>">
                    <?php else: ?>
                        <div class="event-card-placeholder">
                            <i class="fas fa-flag-checkered"></i>
                        </div>
                    <?php endif; ?>
                    <span class="event-card-badge badge <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($event['status']); ?>
                    </span>
                </div>

                <!-- Treść karty -->
                <div class="event-card-body">
                    <div class="event-card-meta">
                        <span><i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y', strtotime($event['event_date'])); ?></span>
                        <?php if (!empty($event['track_name'])): ?>
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['track_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <h2 class="event-card-title"><?php echo htmlspecialchars($event['title']); ?></h2>

                    <?php if (!empty($event['description'])): ?>
                        <p class="event-card-desc">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 120)) . (strlen($event['description']) > 120 ? '...' : ''); ?>
                        </p>
                    <?php endif; ?>

                    <div class="event-card-footer">
                        <?php if ($event['status'] === 'zakończone' && !empty($event['result'])): ?>
                            <span class="event-result">
                                <i class="fas fa-trophy"></i> <?php echo htmlspecialchars($event['result']); ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($event['vehicle_names'])): ?>
                            <span class="event-vehicles">
                                <i class="fas fa-car"></i> <?php echo htmlspecialchars($event['vehicle_names']); ?>
                            </span>
                        <?php endif; ?>

                        <span class="event-card-link">
                            Szczegóły <i class="fas fa-chevron-right"></i>
                        </span>
                    </div>
                </div>

            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>