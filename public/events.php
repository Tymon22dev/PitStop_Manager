<?php
session_start();
require_once '../config/db.php';

// Pobieranie wszystkich wydarzeń, posortowanych od najbliższych
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper">
        <div class="dashboard-header" style="margin-bottom: 40px;">
            <p class="dashboard-sub">PitStop Manager</p>
            <h1 class="dashboard-title">Kalendarz Wyścigów</h1>
        </div>

        <div class="dashboard-grid">
            <?php if (empty($events)): ?>
                <p style="grid-column: span 3; text-align: center; color: #888;">Brak zaplanowanych wydarzeń w kalendarzu.</p>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <?php
                    // Ustalenie koloru badge'a na podstawie statusu
                    $badgeClass = 'badge-pending';
                    if ($event['status'] == 'zakończone') $badgeClass = 'badge-success';
                    if ($event['status'] == 'anulowane') $badgeClass = 'badge-danger';
                    ?>
                    <div class="card grid-item" style="margin-bottom: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($event['status']); ?></span>
                            <div class="current-date">
                                <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y', strtotime($event['event_date'])); ?>
                            </div>
                        </div>

                        <h3 style="color: var(--white); margin-bottom: 10px; font-size: 1.2rem;">
                            <?php echo htmlspecialchars($event['title']); ?>
                        </h3>

                        <p style="color: #aaa; font-size: 0.9rem; margin-bottom: 15px;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> <?php echo htmlspecialchars($event['track_name']); ?>
                        </p>

                        <?php if ($event['status'] == 'zakończone' && !empty($event['result'])): ?>
                            <div style="background: rgba(0, 200, 83, 0.1); border-left: 3px solid var(--success); padding: 10px; border-radius: 4px; font-size: 0.85rem; margin-bottom: 15px;">
                                <strong>Wynik:</strong> <?php echo htmlspecialchars($event['result']); ?>
                            </div>
                        <?php endif; ?>

                        <p style="color: #888; font-size: 0.85rem;">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

<?php include '../includes/footer.php'; ?>