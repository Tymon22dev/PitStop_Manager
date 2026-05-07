<?php
session_start();
require_once '../config/db.php';

// Weryfikacja: jeśli nie ma sesji lub rola to nie admin -> wyjazd do logowania
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}