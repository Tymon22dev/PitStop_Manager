<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $role       = $_POST['role'] ?? 'user';

    if (!empty($username) && !empty($email) && !empty($password)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);

        if ($check->fetch()) {
            $error = "Użytkownik o takim loginie lub adresie e-mail już istnieje.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");

            if ($stmt->execute([$username, $hashedPassword, $email, $first_name, $last_name, $role])) {
                $success = "Pracownik <strong>" . htmlspecialchars($username) . "</strong> został pomyślnie dodany do systemu.";
            } else {
                $error = "Wystąpił nieoczekiwany błąd bazy danych.";
            }
        }
    } else {
        $error = "Pola Login, E-mail oraz Hasło są wymagane.";
    }
}

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Pracownicy</p>
            <h1 class="dashboard-title">Dodaj pracownika</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-user-plus"></i> Dane nowego konta</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form">

            <div class="form-row">
                <div class="form-group">
                    <label>Login użytkownika</label>
                    <input type="text" name="username" required placeholder="np. marek_mechanik">
                </div>
                <div class="form-group">
                    <label>Adres e-mail</label>
                    <input type="email" name="email" required placeholder="marek@pitstop.pro">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Imię</label>
                    <input type="text" name="first_name" placeholder="Imię">
                </div>
                <div class="form-group">
                    <label>Nazwisko</label>
                    <input type="text" name="last_name" placeholder="Nazwisko">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Hasło tymczasowe</label>
                    <input type="password" name="password" required placeholder="Minimum 8 znaków">
                    <small>Minimum 8 znaków, w tym cyfra.</small>
                </div>
                <div class="form-group">
                    <label>Rola w systemie</label>
                    <select name="role">
                        <option value="user">Mechanik (User)</option>
                        <option value="admin">Kierownik (Admin)</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-user-plus"></i> Utwórz pracownika
                </button>
                <a href="users.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </a>
            </div>

        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>