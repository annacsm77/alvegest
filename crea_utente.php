<?php
require_once 'includes/config.php';
$conn->set_charset("utf8mb4");

// 1. Pulizia totale della tabella per evitare vecchi residui
$conn->query("TRUNCATE TABLE TA_Account");

// 2. Creazione hash
$username = 'admin';
$password_chiara = 'apiario2026';
$hash = password_hash($password_chiara, PASSWORD_DEFAULT);

// 3. Inserimento con query preparata
$stmt = $conn->prepare("INSERT INTO TA_Account (AC_username, AC_password, AC_nome, AC_attivo) VALUES (?, ?, 'Amministratore', 1)");
$stmt->bind_param("ss", $username, $hash);

if ($stmt->execute()) {
    echo "<h3>SISTEMA RESETTATO</h3>";
    echo "Dati inseriti correttamente.<br>";
    echo "Ora prova il login con user: <b>admin</b> e pass: <b>apiario2026</b><br>";
    
    // Test immediato interno
    if (password_verify($password_chiara, $hash)) {
        echo "? Test interno PHP: OK!";
    } else {
        echo "? Test interno PHP: FALLITO (C'è un problema nel motore PHP del server)";
    }
}
?>