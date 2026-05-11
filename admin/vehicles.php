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

    // Sprawdzenie czy pojazd ma przypisane logi
    $check = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE vehicle_id = ?");
    $check->execute([$delete_id]);
    $logs_count = $check->fetchColumn();

    if ($logs_count > 0) {
        $error = "Nie można usunąć pojazdu który ma przypisane logi serwisowe ($logs_count szt.).";
    } else {
        $pdo->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$delete_id]);
        header("Location: vehicles.php?success=deleted");
        exit;
    }
}

$vehicles = $pdo->query("
    SELECT v.*, COUNT(l.id) as logs_count
    FROM vehicles v
    LEFT JOIN logs l ON v.id = l.vehicle_id
    GROUP BY v.id
    ORDER BY v.brand ASC, v.model ASC
")->fetchAll();

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Panel administracyjny</p>
            <h1 class="dashboard-title">Pojazdy</h1>
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
                if ($_GET['success'] === 'deleted') echo "Pojazd został usunięty.";
                if ($_GET['success'] === 'added')   echo "Pojazd został dodany.";
                if ($_GET['success'] === 'edited')  echo "Dane pojazdu zostały zaktualizowane.";
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-car"></i> Lista pojazdów</h2>

        <?php if (empty($vehicles)): ?>
            <p class="empty-info">Brak pojazdów w systemie.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nr</th>
                    <th>Marka / Model</th>
                    <th>VIN</th>
                    <th>Rok</th>
                    <th>Status</th>
                    <th>Logi</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td><?php echo htmlspecialchars($v['number'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?></td>
                    <td><?php echo htmlspecialchars($v['vin'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($v['year'] ?? '—'); ?></td>
                    <td>
                        <?php
                            $status_map = [
                                'aktywny'    => 'badge-success',
                                'w_naprawie' => 'badge-pending',
                                'wycofany'   => 'badge-danger',
                            ];
                            $status_label = [
                                'aktywny'    => 'Aktywny',
                                'w_naprawie' => 'W naprawie',
                                'wycofany'   => 'Wycofany',
                            ];
                            $badge = $status_map[$v['status']] ?? 'badge-info';
                            $label = $status_label[$v['status']] ?? $v['status'];
                        ?>
                        <span class="badge <?php echo $badge; ?>">
                            <?php echo $label; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-info">
                            <?php echo $v['logs_count']; ?> szt.
                        </span>
                    </td>
                    <td class="table-actions">
                        <a href="edit_vehicle.php?id=<?php echo $v['id']; ?>"
                           class="btn-action btn-edit" title="Edytuj">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($v['logs_count'] == 0): ?>
                            <a href="vehicles.php?delete=<?php echo $v['id']; ?>"
                               class="btn-action btn-deactivate" title="Usuń"
                               onclick="return confirm('Czy na pewno chcesz usunąć ten pojazd?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php else: ?>
                            <span class="btn-action btn-disabled" 
                                  title="Nie można usunąć — pojazd ma przypisane logi">
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
            <a href="add_vehicle.php" class="btn-save">
                <i class="fas fa-plus"></i> Dodaj pojazd
            </a>
        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>