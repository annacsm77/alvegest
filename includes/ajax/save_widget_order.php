<?php
// includes/ajax/save_widget_order.php
require_once '../../includes/config.php';

$w = json_decode(file_get_contents('php://input'), true);
$utente_id = $_SESSION['user_id'] ?? 0;

if (isset($w['file']) && $utente_id > 0) {
    $name   = $conn->real_escape_string($w['file']);
    $width  = $conn->real_escape_string($w['width']);
    $height = $conn->real_escape_string($w['height']);
    $x      = $conn->real_escape_string($w['x']);
    $y      = $conn->real_escape_string($w['y']);

    // Query per inserire o aggiornare la riga specifica per quell'utente
    $sql = "INSERT INTO CF_WIDGET_POS (WP_UTENTE_ID, WP_WIDGET_NAME, WP_WIDTH, WP_HEIGHT, WP_X, WP_Y, WP_VISIBILE) 
            VALUES ($utente_id, '$name', '$width', '$height', '$x', '$y', 1) 
            ON DUPLICATE KEY UPDATE 
            WP_WIDTH = '$width', 
            WP_HEIGHT = '$height', 
            WP_X = '$x', 
            WP_Y = '$y'";
            
    if ($conn->query($sql)) {
        echo "OK";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Dati mancanti o sessione scaduta";
}
?>