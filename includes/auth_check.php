<?php
session_start();

function checkRole($requiredRole) {
    // Jeśli nie ma sesji - wyrzuć do logowania
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../public/login.php");
        exit;
    }

    // Jeśli rola się nie zgadza (np. User próbuje wejść do Admina)
    if ($_SESSION['role'] !== $requiredRole) {
        die("Brak uprawnień do tej sekcji!");
    }
}
?>