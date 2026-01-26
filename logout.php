<?php
// Inizializza la sessione per poterla chiudere
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Svuota tutte le variabili di sessione
$_SESSION = array();

// Se desideri distruggere anche il cookie di sessione, usa questo codice
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distrugge definitivamente la sessione sul server
session_destroy();

// Reindirizza l'utente alla pagina di login
header("Location: login.php");
exit();
?>