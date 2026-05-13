<?php
global $pdo;
session_start();
require_once '../config/db.php';

// Sprawdzenie uprawnień (tylko zalogowany 'user')
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'] ?? $username;

// --- STATYSTYKI ---
// 1. Liczba logów (raportów) tego konkretnego mechanika
$stmtLogs = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE user_id = ?");
$stmtLogs->execute([$user_id]);
$logsCount = $stmtLogs->fetchColumn();

// 2. Ilość części poniżej stanu minimalnego (alerty)
$lowPartsCount = $pdo->query("SELECT COUNT(*) FROM parts WHERE quantity <= min_quantity")->fetchColumn();

// --- ZAPYTANIA DO TABEL ---
// 1. Ostatnie 5 wpisów tego użytkownika
$stmtRecent = $pdo->prepare("
    SELECT l.title, l.created_at, v.brand, v.model 
    FROM logs l 
    LEFT JOIN vehicles v ON l.vehicle_id = v.id 
    WHERE l.user_id = ? 
    ORDER BY l.created_at DESC 
    LIMIT 5
");
$stmtRecent->execute([$user_id]);
$recentLogs = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

// 2. Pobranie maksymalnie 5 części wymagających uwagi (alerty)
$lowParts = $pdo->query("
    SELECT name, quantity, min_quantity 
    FROM parts 
    WHERE quantity <= min_quantity 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper">

        <div class="dashboard-header">
            <div>
                <p class="dashboard-sub">Panel Mechanika</p>
                <h1 class="dashboard-title">Witaj, <?php echo htmlspecialchars($first_name); ?></h1>
            </div>
            <div class="current-date">
                <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
            </div>
        </div>

        <section class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-clipboard-check"></i>
                <div class="stat-info">
                    <span class="value"><?php echo $logsCount; ?></span>
                    <span class="label">Twoje raporty</span>
                </div>
            </div>

            <div class="stat-card <?php echo $lowPartsCount > 0 ? 'alert' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="stat-info">
                    <span class="value"><?php echo $lowPartsCount; ?></span>
                    <span class="label">Krytyczne braki</span>
                </div>
            </div>

            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-info">
                    <span class="value"><?php echo date('H:i'); ?></span>
                    <span class="label">Aktualny czas</span>
                </div>
            </div>
        </section>

        <div class="admin-layout-grid">

            <section class="card">
                <h2><i class="fas fa-wrench"></i> Twoje ostatnie prace</h2>
                <div class="table-container table-responsive">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>Data</th>
                            <th>Pojazd</th>
                            <th>Tytuł prac</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($recentLogs)): ?>
                            <tr><td colspan="3" class="text-center text-muted">Brak wpisów w dzienniku.</td></tr>
                        <?php else: ?>
                            <?php foreach($recentLogs as $log): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('d.m.Y', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['brand'] . ' ' . $log['model']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['title']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <h2><i class="fas fa-boxes"></i> Wymagane zamówienia</h2>
                <div class="table-container table-responsive">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>Część</th>
                            <th>Stan obecny</th>
                            <th>Minimum</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lowParts)): ?>
                            <tr><td colspan="3" class="text-center text-muted">Stan magazynu jest w normie.</td></tr>
                        <?php else: ?>
                            <?php foreach($lowParts as $part): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($part['name']); ?></strong></td>
                                    <td><span class="badge badge-danger"><?php echo $part['quantity']; ?> szt.</span></td>
                                    <td><?php echo $part['min_quantity']; ?> szt.</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

<?php include '../includes/footer.php'; ?>