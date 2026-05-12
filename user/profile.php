<?php
session_start();
require_once '../config/db.php';

// Sprawdzenie, czy użytkownik jest zalogowany jako 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// --- OBSŁUGA FORMULARZY ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Aktualizacja Danych Osobowych
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$first_name, $last_name, $email, $user_id])) {
            $_SESSION['first_name'] = $first_name; // Aktualizacja imienia w sesji, żeby odświeżyć powitanie w nagłówku
            $message = "Dane profilu zostały zaktualizowane.";
            $messageType = "success";
        } else {
            $message = "Wystąpił błąd podczas aktualizacji danych.";
            $messageType = "danger";
        }
    }

    // 2. Zmiana Hasła
    if (isset($_POST['update_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];

        // Pobranie obecnego hasła (hashu) z bazy w celu weryfikacji
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Weryfikacja starego hasła
        if (password_verify($old_pass, $user['password'])) {
            // Szyfrowanie nowego hasła i zapis
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmtUpdate->execute([$new_hash, $user_id])) {
                $message = "Hasło zostało pomyślnie zmienione.";
                $messageType = "success";
            }
        } else {
            $message = "Obecne hasło jest nieprawidłowe. Zmiana odrzucona.";
            $messageType = "danger";
        }
    }
}

// Pobranie aktualnych danych użytkownika do wypełnienia formularza
$stmt = $pdo->prepare("SELECT username, email, first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper">

        <!-- Nagłówek -->
        <div class="dashboard-header">
            <div>
                <p class="dashboard-sub">Zarządzanie kontem</p>
                <h1 class="dashboard-title">Twój Profil</h1>
            </div>
            <div class="current-date">
                <i class="fas fa-user-circle"></i> Zalogowany jako: <strong><?php echo htmlspecialchars($user_data['username']); ?></strong>
            </div>
        </div>

        <!-- Alerty -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Używamy dwukolumnowego układu, żeby formularze ładnie leżały obok siebie -->
        <div class="admin-layout-grid">

            <!-- Kolumna lewa: Dane osobowe -->
            <section class="card">
                <h2><i class="fas fa-id-card"></i> Dane Osobowe</h2>
                <form action="profile.php" method="POST" class="admin-form">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Imię</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" placeholder="Wpisz imię">
                        </div>
                        <div class="form-group">
                            <label>Nazwisko</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" placeholder="Wpisz nazwisko">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Adres E-mail *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($user_data['email']); ?>" placeholder="Wpisz adres e-mail">
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-save">
                            <i class="fas fa-save"></i> Zapisz dane
                        </button>
                    </div>
                </form>
            </section>

            <!-- Kolumna prawa: Zmiana hasła -->
            <section class="card">
                <h2><i class="fas fa-lock"></i> Bezpieczeństwo</h2>
                <form action="profile.php" method="POST" class="admin-form">

                    <div class="form-group">
                        <label>Obecne hasło *</label>
                        <input type="password" name="old_password" required placeholder="Wprowadź aktualne hasło">
                    </div>

                    <div class="form-group">
                        <label>Nowe hasło *</label>
                        <input type="password" name="new_password" required minlength="6" placeholder="Wprowadź nowe hasło (min. 6 znaków)">
                        <small>Używaj silnych haseł (litery, cyfry, znaki specjalne).</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_password" class="btn-outline">
                            <i class="fas fa-key"></i> Zmień hasło
                        </button>
                    </div>
                </form>
            </section>

        </div>
    </main>

<?php include '../includes/footer.php'; ?>