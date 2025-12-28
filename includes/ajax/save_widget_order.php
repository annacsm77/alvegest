<?php
include '../config.php';
$w = json_decode(file_get_contents('php://input'), true);

if (isset($w['file'])) {
    $name = $conn->real_escape_string($w['file']);
    $width = $conn->real_escape_string($w['width']);
    $height = $conn->real_escape_string($w['height']);
    $x = $conn->real_escape_string($w['x']);
    $y = $conn->real_escape_string($w['y']);

    $sql = "INSERT INTO CF_WIDGET_POS (WP_WIDGET_NAME, WP_WIDTH, WP_HEIGHT, WP_X, WP_Y) 
            VALUES ('$name', '$width', '$height', '$x', '$y') 
            ON DUPLICATE KEY UPDATE 
            WP_WIDTH = '$width', WP_HEIGHT = '$height', WP_X = '$x', WP_Y = '$y'";
    $conn->query($sql);
    echo "OK";
}
?>