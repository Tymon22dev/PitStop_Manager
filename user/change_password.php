<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($old_pass, $user['password'])) {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmtUpdate->execute([$new_hash, $user_id])) {
            header("Location: profile.php?msg=Hasło zostało bezpiecznie zmienione.&msg_type=success");
            exit;
        }
    } else {
        $message = "Obecne hasło jest nieprawidłowe. Zmiana odrzucona ze względów bezpieczeństwa.";
        $messageType = "danger";
    }
}

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper wrapper-900">

        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Zarządzanie kontem</p>
                <h1 class="dashboard-title">Zmiana Hasła</h1>
            </div>
            <div class="current-date date-outline">
                <i class="fas fa-user-circle"></i> Zalogowany jako: <strong class="ml-1"><?php echo htmlspecialchars($user_data['username']); ?></strong>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="card admin-panel-card">
            <h2 class="section-label"><i class="fas fa-lock"></i> Wprowadź nowe zabezpieczenia</h2>

            <form action="" method="POST" class="admin-form">
                <div class="form-group">
                    <label class="label-sm">OBECNE HASŁO *</label>
                    <input type="password" name="old_password" required placeholder="Wprowadź aktualne hasło" class="dark-input">
                </div>

                <div class="form-group">
                    <label class="label-sm">NOWE HASŁO *</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Wprowadź nowe hasło (min. 6 znaków)" class="dark-input">
                    <small class="text-muted">Używaj silnych haseł (litery, cyfry, znaki specjalne).</small>
                </div>

                <div class="form-actions-row">
                    <button type="submit" name="update_password" class="btn-save">
                        <i class="fas fa-check"></i> ZAPISZ NOWE HASŁO
                    </button>
                    <a href="profile.php" class="btn-cancel">
                        <i class="fas fa-arrow-left"></i> WRÓĆ DO PROFILU
                    </a>
                </div>
            </form>
        </section>

    </main>

<?php include '../includes/footer.php'; ?>