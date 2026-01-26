<?php
require_once 'includes/config.php';

$user_test = 'admin'; // Lo username che hai creato
$pass_test = 'alvegest2026'; // La password che vuoi testare

echo "<h3>Test Diagnostico Login</h3>";

// 1. Test Connessione
if ($conn) { echo "? Connessione al database: OK<br>"; } 
else { die("? Errore connessione: " . mysqli_connect_error()); }

// 2. Test Esistenza Utente
$stmt = $conn->prepare("SELECT * FROM TA_Account WHERE AC_username = ?");
$stmt->bind_param("s", $user_test);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo "? Utente '$user_test' trovato nel database.<br>";
    echo "?? Hash salvato nel DB: <code>" . $row['AC_password'] . "</code><br>";
    echo "?? Lunghezza Hash: " . strlen($row['AC_password']) . " caratteri<br>";
    
    // 3. Test Manuale password_verify
    // Generiamo un hash sul momento per vedere se la funzione risponde
    $hash_veloce = password_hash($pass_test, PASSWORD_DEFAULT);
    if (password_verify($pass_test, $hash_veloce)) {
        echo "? La funzione password_verify() lavora correttamente sul server.<br>";
    }

    // 4. Test Confronto Reale
    if (password_verify($pass_test, $row['AC_password'])) {
        echo "?? <b>SUCCESSO:</b> La password inserita corrisponde all'hash nel DB!";
    } else {
        echo "? <b>FALLIMENTO:</b> La password NON corrisponde all'hash salvato.";
    }
} else {
    echo "? Utente '$user_test' NON trovato. Controlla lo spelling nella tabella TA_Account.";
}
?>