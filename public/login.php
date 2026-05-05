<?php
// Rozpoczynamy sesję - musi być na samej górze każdego pliku, który używa logowania
session_start();

// Dołączamy połączenie z bazą danych
require_once '../config/db.php';

$error = '';

// Sprawdzamy, czy formularz został wysłany
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Szukamy użytkownika w bazie
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Weryfikacja hasła (używamy password_verify, bo w bazie mamy hash)
        if ($user && password_verify($password, $user['password'])) {
            // Logowanie poprawne! Zapisujemy dane do sesji
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Tu zapisujemy czy to 'admin' czy 'user'

            // Przekierowanie w zależności od roli
            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../user/dashboard.php");
            }
            exit;
        } else {
            $error = "Błędny login lub hasło!";
        }
    } else {
        $error = "Wypełnij wszystkie pola!";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie - PitStop Manager</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="login-container">
        <h2>Panel Logowania</h2>
        
        <?php if ($error): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Użytkownik:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Hasło:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Zaloguj się</button>
        </form>
        <p><a href="index.php">Wróć do strony głównej (Gość)</a></p>
    </div>
</body>
</html>