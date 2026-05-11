<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

// Obsługa usuwania
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Sprawdzenie czy kategoria ma przypisane części
    $check = $pdo->prepare("SELECT COUNT(*) FROM parts WHERE category_id = ?");
    $check->execute([$delete_id]);
    $parts_count = $check->fetchColumn();

    if ($parts_count > 0) {
        $error = "Nie można usunąć kategorii która ma przypisane części ($parts_count szt.).";
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$delete_id]);
        header("Location: categories.php?success=deleted");
        exit;
    }
}

// Pobieranie kategorii z liczbą części
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as parts_count
    FROM categories c
    LEFT JOIN parts p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Magazyn</p>
            <h1 class="dashboard-title">Kategorie części</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
                if ($_GET['success'] === 'deleted') echo "Kategoria została usunięta.";
                if ($_GET['success'] === 'added')   echo "Kategoria została dodana.";
                if ($_GET['success'] === 'edited')  echo "Kategoria została zaktualizowana.";
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-tags"></i> Lista kategorii</h2>

        <?php if (empty($categories)): ?>
            <p class="empty-info">Brak kategorii w systemie.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nazwa</th>
                    <th>Opis</th>
                    <th>Liczba części</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                    <td><?php echo htmlspecialchars($cat['description'] ?? '—'); ?></td>
                    <td>
                        <span class="badge <?php echo $cat['parts_count'] > 0 ? 'badge-info' : 'badge-pending'; ?>">
                            <?php echo $cat['parts_count']; ?> szt.
                        </span>
                    </td>
                    <td class="table-actions">
                        <a href="edit_category.php?id=<?php echo $cat['id']; ?>"
                           class="btn-action btn-edit" title="Edytuj">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="categories.php?delete=<?php echo $cat['id']; ?>"
                           class="btn-action btn-deactivate" title="Usuń"
                           onclick="return confirm('Czy na pewno chcesz usunąć tę kategorię?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="card-footer">
            <a href="add_category.php" class="btn-save">
                <i class="fas fa-plus"></i> Dodaj kategorię
            </a>
        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>