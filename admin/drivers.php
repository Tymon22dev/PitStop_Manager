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

    $check = $pdo->prepare("SELECT COUNT(*) FROM event_drivers WHERE driver_id = ?");
    $check->execute([$delete_id]);
    $events_count = $check->fetchColumn();

    if ($events_count > 0) {
        $error = "Nie można usunąć kierowcy który ma przypisane wydarzenia ($events_count szt.).";
    } else {
        // Usuń zdjęcie jeśli istnieje
        $photo = $pdo->prepare("SELECT photo FROM drivers WHERE id = ?");
        $photo->execute([$delete_id]);
        $photo_path = $photo->fetchColumn();
        if ($photo_path && file_exists('../' . $photo_path)) {
            unlink('../' . $photo_path);
        }

        $pdo->prepare("DELETE FROM drivers WHERE id = ?")->execute([$delete_id]);
        header("Location: drivers.php?success=deleted");
        exit;
    }
}

// Obsługa aktywacji/deaktywacji
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $toggle_id = (int)$_GET['toggle'];
    $current   = $pdo->prepare("SELECT is_active FROM drivers WHERE id = ?");
    $current->execute([$toggle_id]);
    $is_active = $current->fetchColumn();

    $pdo->prepare("UPDATE drivers SET is_active = ? WHERE id = ?")
        ->execute([$is_active ? 0 : 1, $toggle_id]);
    header("Location: drivers.php");
    exit;
}

$drivers = $pdo->query("
    SELECT d.*, COUNT(ed.event_id) as events_count
    FROM drivers d
    LEFT JOIN event_drivers ed ON d.id = ed.driver_id
    GROUP BY d.id
    ORDER BY d.last_name ASC
")->fetchAll();

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Panel administracyjny</p>
            <h1 class="dashboard-title">Kierowcy</h1>
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
                if ($_GET['success'] === 'deleted') echo "Kierowca został usunięty.";
                if ($_GET['success'] === 'added')   echo "Kierowca został dodany.";
                if ($_GET['success'] === 'edited')  echo "Dane kierowcy zostały zaktualizowane.";
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-user-astronaut"></i> Lista kierowców</h2>

        <?php if (empty($drivers)): ?>
            <p class="empty-info">Brak kierowców w systemie.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Zdjęcie</th>
                    <th>Imię i nazwisko</th>
                    <th>Numer</th>
                    <th>Narodowość</th>
                    <th>W zespole od</th>
                    <th>Wydarzenia</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($drivers as $d): ?>
                <tr>
                    <td>
                        <?php if (!empty($d['photo'])): ?>
                            <img src="../<?php echo htmlspecialchars($d['photo']); ?>"
                                 alt="<?php echo htmlspecialchars($d['first_name']); ?>"
                                 class="table-avatar">
                        <?php else: ?>
                            <div class="table-avatar-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong style="color: var(--white);">
                            <?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?>
                        </strong>
                    </td>
                    <td>
                        <?php if (!empty($d['number'])): ?>
                            <span style="color: var(--primary); font-weight: 800;">
                                #<?php echo htmlspecialchars($d['number']); ?>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($d['nationality'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($d['joined_year'] ?? '—'); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php echo $d['events_count']; ?> szt.
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo $d['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $d['is_active'] ? 'Aktywny' : 'Nieaktywny'; ?>
                        </span>
                    </td>
                    <td class="table-actions">
                        <a href="edit_driver.php?id=<?php echo $d['id']; ?>"
                           class="btn-action btn-edit" title="Edytuj">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="drivers.php?toggle=<?php echo $d['id']; ?>"
                           class="btn-action <?php echo $d['is_active'] ? 'btn-deactivate' : 'btn-activate'; ?>"
                           title="<?php echo $d['is_active'] ? 'Deaktywuj' : 'Aktywuj'; ?>"
                           onclick="return confirm('<?php echo $d['is_active'] ? 'Deaktywować kierowcę?' : 'Aktywować kierowcę?'; ?>')">
                            <i class="fas <?php echo $d['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                        </a>
                        <?php if ($d['events_count'] == 0): ?>
                            <a href="drivers.php?delete=<?php echo $d['id']; ?>"
                               class="btn-action btn-deactivate" title="Usuń"
                               onclick="return confirm('Czy na pewno chcesz usunąć tego kierowcę?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php else: ?>
                            <span class="btn-action btn-disabled"
                                  title="Nie można usunąć — kierowca ma przypisane wydarzenia">
                                <i class="fas fa-lock"></i>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="card-footer">
            <a href="add_driver.php" class="btn-save">
                <i class="fas fa-plus"></i> Dodaj kierowcę
            </a>
        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>