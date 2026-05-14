<?php
session_start();
require_once '../config/db.php';

// Najbliższe 3 zaplanowane wyścigi
$upcoming = $pdo->query("
    SELECT * FROM events 
    WHERE status = 'zaplanowane' AND event_date >= CURDATE()
    ORDER BY event_date ASC 
    LIMIT 3
")->fetchAll();

// Ostatnie 5 zakończonych wyścigów z wynikiem
$results = $pdo->query("
    SELECT * FROM events 
    WHERE status = 'zakończone'
    ORDER BY event_date DESC 
    LIMIT 5
")->fetchAll();

// Statystyki publiczne
$total_events    = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$finished_events = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'zakończone'")->fetchColumn();
$active_vehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'aktywny'")->fetchColumn();

include '../includes/header.php';
?>

<main class="home-wrapper">

    <!-- HERO -->
    <section class="fan-hero">
        <div class="fan-hero-text">
            <span class="tag">
                <i class="fas fa-circle" style="color: var(--success); font-size: 8px;"></i>
                Sezon 2026
            </span>
            <h1 class="fan-hero-title">Witaj w świecie <span>PitStop Racing</span></h1>
            <p class="fan-hero-sub">Śledź wyniki, kalendarz wyścigów i działania naszego zespołu na żywo.</p>
            <div class="hero-actions">
                <a href="events.php" class="btn-main">
                    <i class="fas fa-flag-checkered"></i> Kalendarz wyścigów
                </a>
                <a href="vehicles.php" class="btn-outline">
                    <i class="fas fa-car"></i> Nasze pojazdy
                </a>
            </div>
        </div>

        <!-- Statystyki sezonu -->
        <div class="fan-hero-stats">
            <div class="fan-stat">
                <span class="fan-stat-value"><?php echo $total_events; ?></span>
                <span class="fan-stat-label">Wyścigów w sezonie</span>
            </div>
            <div class="fan-stat">
                <span class="fan-stat-value"><?php echo $finished_events; ?></span>
                <span class="fan-stat-label">Ukończonych</span>
            </div>
            <div class="fan-stat">
                <span class="fan-stat-value"><?php echo $active_vehicles; ?></span>
                <span class="fan-stat-label">Aktywnych pojazdów</span>
            </div>
        </div>
    </section>

    <!-- NAJBLIŻSZE WYŚCIGI -->
    <?php if (!empty($upcoming)): ?>
    <section class="fan-section">
        <div class="fan-section-header">
            <h2><i class="fas fa-clock"></i> Nadchodzące wyścigi</h2>
            <a href="events.php" class="fan-section-link">
                Wszystkie <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        <div class="upcoming-grid">
            <?php foreach ($upcoming as $event): ?>
            <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="upcoming-card">
                <?php if (!empty($event['photo'])): ?>
                    <div class="upcoming-card-img">
                        <img src="../<?php echo htmlspecialchars($event['photo']); ?>"
                             alt="<?php echo htmlspecialchars($event['title']); ?>">
                    </div>
                <?php else: ?>
                    <div class="upcoming-card-img upcoming-card-placeholder">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                <?php endif; ?>

                <div class="upcoming-card-body">
                    <div class="upcoming-card-date">
                        <span class="upcoming-day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                        <span class="upcoming-month"><?php echo date('M Y', strtotime($event['event_date'])); ?></span>
                    </div>
                    <div class="upcoming-card-info">
                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                        <?php if (!empty($event['track_name'])): ?>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['track_name']); ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="badge badge-pending">Zaplanowane</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- TABELA WYNIKÓW -->
    <?php if (!empty($results)): ?>
    <section class="fan-section">
        <div class="fan-section-header">
            <h2><i class="fas fa-trophy"></i> Ostatnie wyniki</h2>
            <a href="events.php?status=zakończone" class="fan-section-link">
                Historia <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        <div class="card">
            <table class="admin-table results-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Wyścig</th>
                        <th>Tor</th>
                        <th>Wynik</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr onclick="window.location='event_detail.php?id=<?php echo $r['id']; ?>'"
                        style="cursor: pointer;">
                        <td><?php echo date('d.m.Y', strtotime($r['event_date'])); ?></td>
                        <td><?php echo htmlspecialchars($r['title']); ?></td>
                        <td><?php echo htmlspecialchars($r['track_name'] ?? '—'); ?></td>
                        <td>
                            <?php if (!empty($r['result'])): ?>
                                <span class="result-highlight">
                                    <i class="fas fa-trophy"></i>
                                    <?php echo htmlspecialchars($r['result']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #555;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA dla gości -->
    <?php if (!isset($_SESSION['user_id'])): ?>
    <section class="fan-cta">
        <div class="fan-cta-content">
            <i class="fas fa-users"></i>
            <h2>Jesteś częścią zespołu?</h2>
            <p>Zaloguj się aby uzyskać dostęp do panelu serwisowego, magazynu i logów technicznych.</p>
            <div style="display: flex; justify-content: center;">
                <a href="login.php" class="btn-login-action">
                    <i class="fas fa-key"></i> Zaloguj się
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>