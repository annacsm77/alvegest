<?php
// 1. GESTIONE ERRORI
// Abilita la visualizzazione degli errori per lo sviluppo
error_reporting(E_ALL); // Mostra tutti gli errori, warning e notice [cite: 2025-11-21]
ini_set('display_errors', 1); // Mostra gli errori direttamente nella pagina [cite: 2025-11-21]
ini_set('display_startup_errors', 1); // Mostra gli errori che si verificano durante l'avvio [cite: 2025-11-21]

// 2. DEFINIZIONE PERCORSI
// Percorso base del progetto per i link HTML
define('BASE_URL', '/alvegest/'); // [cite: 2025-11-21]

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
$username ="root";
$password = "154W37m8781200!";
$dbname = "AlveGest";


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>

