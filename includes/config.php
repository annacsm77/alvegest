<?php
// 1. GESTIONE ERRORI
ob_start(); // Inizia l'Output Buffering
// Abilita la visualizzazione degli errori per lo sviluppo

error_reporting(E_ALL); // Mostra tutti gli errori, warning e notice [cite: 2025-11-21]

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1); // Mostra gli errori direttamente nella pagina [cite: 2025-11-21]
ini_set('display_startup_errors', 1); // Mostra gli errori che si verificano durante l'avvio [cite: 2025-11-21]

// 2. DEFINIZIONE PERCORSI
// Percorso base del progetto per i link HTML
define('BASE_URL', '/'); // [cite: 2025-11-21]

// Funzione per generare URL corretti in tutto il sito
function url($path) {
    return BASE_URL . ltrim($path, '/'); // [cite: 2025-11-21]
}

// 3. SCELTA E CONFIGURAZIONE TEMPLATE

define('BASE_PATH', dirname(__DIR__) . '/'); // Prende la cartella principale del progetto
$template_scelto = "standard";

// Definiamo il percorso fisico (per gli include PHP)
define('TPL_PATH', BASE_PATH . 'template/' . $template_scelto . '/');

// Definiamo il percorso web (per i file CSS/Immagini)
define('TPL_URL', BASE_URL . 'template/' . $template_scelto . '/');

// 4. CONNESSIONE AL DATABASE
$servername = "localhost";
$username ="csm";
$password = "Qwe782300!";
$dbname = "Alvegest";


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// --- SISTEMA DI LOG AUTOMATICO ---
if (isset($_SESSION['user_id'])) {
    $lo_utente_id = $_SESSION['user_id'];
    $lo_utente_nome = $_SESSION['user_nome'];
    $lo_pagina = $_SERVER['PHP_SELF'];
    $lo_azione = $_SERVER['REQUEST_METHOD'];
    $lo_ip = $_SERVER['REMOTE_ADDR'];
    
    // Registriamo i dati POST solo se presenti (es. quando salvi un'arnia o un utente)
    $lo_dati = "";
    if ($lo_azione === 'POST' && !empty($_POST)) {
        // Escludiamo la password dai log per sicurezza
        $post_copy = $_POST;
        if (isset($post_copy['password'])) $post_copy['password'] = '********';
        if (isset($post_copy['AC_password'])) $post_copy['AC_password'] = '********';
        $lo_dati = json_encode($post_copy);
    }

    $stmt_log = $conn->prepare("INSERT INTO TA_Log (LO_utente_id, LO_utente_nome, LO_pagina, LO_azione, LO_dati_post, LO_indirizzo_ip) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_log->bind_param("isssss", $lo_utente_id, $lo_utente_nome, $lo_pagina, $lo_azione, $lo_dati, $lo_ip);
    $stmt_log->execute();
}
// --- PROTEZIONE SOLA LETTURA ---
// Se l'utente è un 'ospite' e tenta di inviare dati (POST), lo blocchiamo
if (isset($_SESSION['AC_ruolo']) && $_SESSION['AC_ruolo'] === 'ospite' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reindirizziamo alla stessa pagina con un messaggio di errore
    header("Location: " . $_SERVER['PHP_SELF'] . "?status=error_readonly");
    exit();
}
?>
