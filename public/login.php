<?php
session_start();
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            header("Location: " . ($user['role'] === 'admin' ? "../admin/dashboard.php" : "../user/dashboard.php"));
            exit;
        } else {
            $error = "Nieautoryzowany dostęp: Błędne poświadczenia.";
        }
    } else {
        $error = "Wymagane uzupełnienie wszystkich pól.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dostęp do systemu - PitStop PRO</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<header class="modern-header">
    <nav>
        <div class="logo">
            <i class="fas fa-tools"></i> PitStop <span>PRO</span>
        </div>
        
        <div class="nav-links">
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <!-- Te linki można np. ukryć w PHP jeśli użytkownik nie jest zalogowany -->
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="fleet.php">Garaż</a></li>
                    <li><a href="inventory.php">Magazyn</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="auth-zone">
            <?php if(isset($_SESSION['user_id'])): ?>
                <span class="user-badge"><?php echo $_SESSION['username']; ?></span>
                <a href="../logout.php" class="active btn-login-modern">Wyloguj</a>
            <?php else: ?>
                <a href="login.php" class="active btn-login-modern">Logowanie <i class="fas fa-key"></i></a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main class="home-wrapper">
    <section class="login-section">
        
        <div class="info-box">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: #fff; text-transform: uppercase; letter-spacing: 2px;">System Login</h2>
                <p style="font-size: 0.8rem; color: #666;">Wprowadź klucz dostępu do bazy</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form class="main-form" method="POST" action="login.php">
                <div class="form-group">
                    <label for="user-login"><i class="fas fa-user-shield"></i> Operator</label>
                    <input type="text" id="user-login" name="username" placeholder="Nazwa użytkownika" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="user-pass"><i class="fas fa-key"></i> Hasło</label>
                    <input type="password" id="user-pass" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-login-action">
                    Autoryzuj <i class="fas fa-chevron-right"></i>
                </button>
            </form>

            <p class="center-text">
                Brak dostępu? <a href="#" class="highlight-link">Kontakt z HQ</a>
            </p>
        </div>
    </section>
</main>

<footer class="simple-footer">
    <p>&copy; 2026 PitStop Manager | Secure Access Point</p>
</footer>

</body>
</html>