<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

// Sprawdzenie czy podano poprawne ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$user_id = (int)$_GET['id'];
$error   = '';
$success = '';

// Pobieranie danych użytkownika
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Jeśli użytkownik nie istnieje
if (!$user) {
    header("Location: users.php");
    exit;
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Zapis danych podstawowych ---
    if ($action === 'update_data') {
        $username   = trim($_POST['username'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $role       = $_POST['role'] ?? 'user';

        if (empty($username) || empty($email)) {
            $error = "Login i e-mail są wymagane.";
        } else {
            // Sprawdzenie unikalności loginu i emaila (pomijając obecnego usera)
            $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $check->execute([$username, $email, $user_id]);

            if ($check->fetch()) {
                $error = "Podany login lub e-mail jest już zajęty.";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, first_name = ?, last_name = ?, role = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $first_name, $last_name, $role, $user_id]);
                $success = "Dane pracownika zostały zaktualizowane.";

                // Odświeżenie danych po zapisie
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            }
        }
    }

    // --- Zmiana hasła ---
    if ($action === 'update_password') {
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || empty($confirm_password)) {
            $error = "Oba pola hasła są wymagane.";
        } elseif (strlen($new_password) < 8) {
            $error = "Hasło musi mieć minimum 8 znaków.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Hasła nie są identyczne.";
        } else {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            $success = "Hasło zostało zmienione.";
        }
    }
}

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Pracownicy</p>
            <h1 class="dashboard-title">Edycja: <?php echo htmlspecialchars($user['username']); ?></h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Karta: Dane podstawowe -->
    <div class="card">
        <h2><i class="fas fa-user-edit"></i> Dane podstawowe</h2>

        <form method="POST" action="" class="admin-form">
            <input type="hidden" name="action" value="update_data">

            <div class="form-row">
                <div class="form-group">
                    <label>Login użytkownika</label>
                    <input type="text" name="username" required
                           value="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
                <div class="form-group">
                    <label>Adres e-mail</label>
                    <input type="email" name="email" required
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Imię</label>
                    <input type="text" name="first_name"
                           value="<?php echo htmlspecialchars($user['first_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Nazwisko</label>
                    <input type="text" name="last_name"
                           value="<?php echo htmlspecialchars($user['last_name']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Rola w systemie</label>
                    <select name="role" <?php echo $user['id'] === (int)$_SESSION['user_id'] ? 'disabled' : ''; ?>>
                        <option value="user"  <?php echo $user['role'] === 'user'  ? 'selected' : ''; ?>>Mechanik (User)</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Kierownik (Admin)</option>
                    </select>
                    <?php if ($user['id'] === (int)$_SESSION['user_id']): ?>
                        <small>Nie możesz zmienić własnej roli.</small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Status konta</label>
                    <input type="text" 
                           value="<?php echo $user['is_active'] ? 'Aktywny' : 'Nieaktywny'; ?>" 
                           disabled>
                    <small>Status zmieniasz przez przycisk na liście pracowników.</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Zapisz zmiany
                </button>
                <a href="users.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Powrót do listy
                </a>
            </div>
        </form>
    </div>

    <!-- Karta: Zmiana hasła -->
    <div class="card">
        <h2><i class="fas fa-key"></i> Zmiana hasła</h2>

        <form method="POST" action="" class="admin-form">
            <input type="hidden" name="action" value="update_password">

            <div class="form-row">
                <div class="form-group">
                    <label>Nowe hasło</label>
                    <input type="password" name="new_password" placeholder="Minimum 8 znaków">
                </div>
                <div class="form-group">
                    <label>Powtórz hasło</label>
                    <input type="password" name="confirm_password" placeholder="Powtórz nowe hasło">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-key"></i> Zmień hasło
                </button>
            </div>
        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>