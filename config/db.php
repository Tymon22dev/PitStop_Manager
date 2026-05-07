<?php
// Dane do połączenia
$host = '127.0.0.1';
$db   = 'pitstop_db';
$user = 'root';
$pass = ''; // W XAMPP domyślnie puste
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;$db=pitstop_db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     // Tworzymy obiekt PDO - to nasze "połączenie"
     $pdo = new PDO($dsn, $user, $pass, $options);
     // Jeśli chcesz przetestować połączenie, odkomentuj linię poniżej:
     echo "Połączono z bazą danych pomyślnie!";
} catch (\PDOException $e) {
     // W razie błędu wyświetli komunikat
     die("Błąd połączenia z bazą: " . $e->getMessage());
}
?>