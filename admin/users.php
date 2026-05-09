<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

// Obsługa aktywacji / deaktywacji
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $toggle_id = (int)$_GET['toggle'];
    
    // Zabezpieczenie: nie można deaktywować samego siebie
    if ($toggle_id !== (int)$_SESSION['user_id']) {
        $current = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $current->execute([$toggle_id]);
        $current_status = $current->fetchColumn();
        
        $new_status = $current_status ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $toggle_id]);
    }
    
    header("Location: users.php");
    exit;
}

// Pobieranie wszystkich użytkowników
$users = $pdo->query("
    SELECT id, username, first_name, last_name, email, role, is_active, created_date 
    FROM users 
    ORDER BY created_date DESC
")->fetchAll();

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Panel administracyjny</p>
            <h1 class="dashboard-title">Pracownicy</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
                $msg = $_GET['success'];
                if ($msg === 'edited') echo "Dane pracownika zostały zaktualizowane.";
                if ($msg === 'deleted') echo "Konto pracownika zostało usunięte.";
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-users"></i> Lista pracowników</h2>

        <?php if (empty($users)): ?>
            <p class="empty-info">Brak pracowników w systemie.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Login</th>
                    <th>Imię i nazwisko</th>
                    <th>E-mail</th>
                    <th>Rola</th>
                    <th>Status</th>
                    <th>Data dodania</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars(trim($u['first_name'] . ' ' . $u['last_name'])); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-pending' : 'badge-info'; ?>">
                            <?php echo $u['role'] === 'admin' ? 'Admin' : 'Mechanik'; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo $u['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $u['is_active'] ? 'Aktywny' : 'Nieaktywny'; ?>
                        </span>
                    </td>
                    <td><?php echo date('d.m.Y', strtotime($u['created_date'])); ?></td>
                    <td class="table-actions">
                        <a href="edit_user.php?id=<?php echo $u['id']; ?>" 
                           class="btn-action btn-edit" title="Edytuj">
                            <i class="fas fa-edit"></i>
                        </a>

                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                            <a href="users.php?toggle=<?php echo $u['id']; ?>"
                               class="btn-action <?php echo $u['is_active'] ? 'btn-deactivate' : 'btn-activate'; ?>"
                               title="<?php echo $u['is_active'] ? 'Deaktywuj' : 'Aktywuj'; ?>"
                               onclick="return confirm('<?php echo $u['is_active'] ? 'Deaktywować tego użytkownika?' : 'Aktywować tego użytkownika?'; ?>')">
                                <i class="fas <?php echo $u['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                            </a>
                        <?php else: ?>
                            <span class="btn-action btn-disabled" title="Nie możesz deaktywować własnego konta">
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
            <a href="add_user.php" class="btn-save">
                <i class="fas fa-user-plus"></i> Dodaj pracownika
            </a>
        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>