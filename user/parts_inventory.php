<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

// Obsługa usuwania
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM parts WHERE id = ?")->execute([$delete_id]);
    header("Location: parts_inventory.php?success=deleted");
    exit;
}

// Filtrowanie po kategorii
$filter_category = isset($_GET['category']) && is_numeric($_GET['category']) 
                   ? (int)$_GET['category'] 
                   : null;

// Pobieranie części z kategorią
$sql = "
    SELECT p.*, c.name as category_name
    FROM parts p
    LEFT JOIN categories c ON p.category_id = c.id
";
if ($filter_category) {
    $sql .= " WHERE p.category_id = $filter_category";
}
$sql .= " ORDER BY p.name ASC";

$parts = $pdo->query($sql)->fetchAll();

// Pobieranie kategorii do filtra
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Liczba alertów
$low_stock = array_filter($parts, fn($p) => $p['quantity'] <= $p['min_quantity']);

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Magazyn</p>
            <h1 class="dashboard-title">Stan magazynu</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
                if ($_GET['success'] === 'deleted') echo "Część została usunięta z magazynu.";
                if ($_GET['success'] === 'added')   echo "Część została dodana do magazynu.";
                if ($_GET['success'] === 'edited')  echo "Dane części zostały zaktualizowane.";
            ?>
        </div>
    <?php endif; ?>

    <!-- Alert niskiego stanu -->
    <?php if (count($low_stock) > 0): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            Uwaga: <strong><?php echo count($low_stock); ?></strong> 
            <?php echo count($low_stock) === 1 ? 'część osiągnęła' : 'części osiągnęło'; ?> 
            minimalny stan magazynowy.
        </div>
    <?php endif; ?>

    <!-- Statystyki -->
    <section class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-boxes"></i>
            <div class="stat-info">
                <span class="value"><?php echo count($parts); ?></span>
                <span class="label">Pozycji w magazynie</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-tags"></i>
            <div class="stat-info">
                <span class="value"><?php echo count($categories); ?></span>
                <span class="label">Kategorii</span>
            </div>
        </div>
        <div class="stat-card <?php echo count($low_stock) > 0 ? 'alert' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="stat-info">
                <span class="value"><?php echo count($low_stock); ?></span>
                <span class="label">Niskie stany</span>
            </div>
        </div>
    </section>

    <div class="card">
        <h2><i class="fas fa-warehouse"></i> Lista części</h2>

        <!-- Filtr kategorii -->
        <div class="filter-bar">
            <a href="parts_inventory.php" 
               class="filter-btn <?php echo !$filter_category ? 'active' : ''; ?>">
                Wszystkie
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="parts_inventory.php?category=<?php echo $cat['id']; ?>"
                   class="filter-btn <?php echo $filter_category === (int)$cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($parts)): ?>
            <p class="empty-info">Brak części w magazynie.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nazwa części</th>
                    <th>Kategoria</th>
                    <th>Numer seryjny</th>
                    <th>Ilość</th>
                    <th>Cena</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parts as $part): ?>
                <?php $is_low = $part['quantity'] <= $part['min_quantity']; ?>
                <tr class="<?php echo $is_low ? 'row-alert' : ''; ?>">
                    <td><?php echo htmlspecialchars($part['name']); ?></td>
                    <td><?php echo htmlspecialchars($part['category_name'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($part['serial_number'] ?? '—'); ?></td>
                    <td>
                        <span class="badge <?php echo $is_low ? 'badge-danger' : 'badge-success'; ?>">
                            <?php echo $part['quantity']; ?> / min. <?php echo $part['min_quantity']; ?>
                        </span>
                    </td>
                    <td><?php echo number_format($part['price'], 2, ',', ' '); ?> zł</td>
                    <td>
                        <?php
                            $status_map = [
                                'nowy'     => ['badge-success', 'Nowy'],
                                'używany'  => ['badge-info',    'Używany'],
                                'uszkodzony' => ['badge-danger', 'Uszkodzony'],
                            ];
                            $s = $status_map[$part['status']] ?? ['badge-pending', $part['status']];
                        ?>
                        <span class="badge <?php echo $s[0]; ?>"><?php echo $s[1]; ?></span>
                    </td>
                    <td class="table-actions">
                        <a href="edit_part.php?id=<?php echo $part['id']; ?>"
                           class="btn-action btn-edit" title="Edytuj">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="parts_inventory.php?delete=<?php echo $part['id']; ?>"
                           class="btn-action btn-deactivate" title="Usuń"
                           onclick="return confirm('Czy na pewno chcesz usunąć tę część?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="card-footer">
            <a href="add_part.php" class="btn-save">
                <i class="fas fa-plus"></i> Dodaj część
            </a>
        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>