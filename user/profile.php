<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = $_GET['msg'] ?? '';
$messageType = $_GET['msg_type'] ?? 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
    if ($stmt->execute([$first_name, $last_name, $email, $user_id])) {
        $_SESSION['first_name'] = $first_name;
        $message = "Dane osobowe zostały pomyślnie zaktualizowane.";
        $messageType = "success";
    } else {
        $message = "Wystąpił błąd podczas zapisywania danych.";
        $messageType = "danger";
    }
}

$stmt = $pdo->prepare("SELECT username, email, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper wrapper-900">

        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Zarządzanie kontem</p>
                <h1 class="dashboard-title">Twój Profil</h1>
            </div>
            <div class="current-date date-outline">
                <i class="fas fa-user-circle"></i> Zalogowany jako: <strong class="ml-1"><?php echo htmlspecialchars($user_data['username']); ?></strong>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="card admin-panel-card">
            <h2 class="section-label"><i class="fas fa-id-card"></i> DANE OSOBOWE</h2>

            <form action="" method="POST" class="admin-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="label-sm">IMIĘ</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" class="dark-input">
                    </div>
                    <div class="form-group">
                        <label class="label-sm">NAZWISKO</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" class="dark-input">
                    </div>
                </div>

                <div class="form-group">
                    <label class="label-sm">ADRES E-MAIL *</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($user_data['email']); ?>" class="dark-input">
                </div>

                <!-- Używamy nowej klasy .form-actions-between do rozsunięcia elementów -->
                <div class="form-actions-row form-actions-between">
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fas fa-save"></i> ZAPISZ DANE
                    </button>

                    <!-- Używamy istniejącej klasy .btn-cancel, która idealnie pasuje do linków pobocznych -->
                    <a href="change_password.php" class="btn-cancel">
                        <i class="fas fa-key"></i> ZMIEŃ HASŁO
                    </a>
                </div>
            </form>
        </section>

    </main>

<?php include '../includes/footer.php'; ?>