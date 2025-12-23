<?php
// Abilita la visualizzazione degli errori
error_reporting(E_ALL); // Mostra tutti gli errori, warning e notice
ini_set('display_errors', 1); // Mostra gli errori direttamente nella pagina
ini_set('display_startup_errors', 1); // Mostra gli errori che si verificano durante l'avvio di PHP




//define('BASE_URL', '/'); // Imposta il percorso base del progetto
 define('BASE_URL', '/alvegest/'); 


define('DB_SERVER', '');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');
define('DB_NAME', 'AlveGest');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

function url($path) {
    return BASE_URL . ltrim($path, '/');
}


?>
