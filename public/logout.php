<?php
session_start();

// Usuwamy wszystkie dane sesji
$_SESSION = array();

// Niszczymy fizyczny plik sesji na serwerze
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Przekierowanie na stronę główną po wylogowaniu
header("Location: index.php");
exit;