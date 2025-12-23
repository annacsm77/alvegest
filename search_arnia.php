<?php
// Imposta l'intestazione per il JSON
header('Content-Type: application/json');

// Usa require_once per evitare l'errore "Cannot redeclare url()"
require_once 'includes/config.php';

// Assicurati che l'input ci sia
if (!isset($_GET['codice'])) {
    echo json_encode(['success' => false, 'message' => 'Codice mancante']);
    exit;
}

// Recupera e pulisci il codice (presupponendo che il codice arnia sia un numero o una stringa)
$codice_ricerca = $_GET['codice'];

// 1. Prepara la query
// Cerchiamo l'arnia in base al codice univoco AR_CODICE
$sql = "SELECT AR_ID, AR_NOME FROM AP_Arnie WHERE AR_CODICE = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Il codice arnia è un intero (i) secondo il tuo DB
    $stmt->bind_param("i", $codice_ricerca); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $arnia = $result->fetch_assoc();
        // Arnia trovata: restituisce ID e Nome
        echo json_encode([
            'success' => true,
            'id' => $arnia['AR_ID'],
            'nome' => htmlspecialchars($arnia['AR_NOME'])
        ]);
    } else {
        // Arnia non trovata
        echo json_encode(['success' => false, 'message' => 'Arnia non trovata']);
    }

    $stmt->close();
} else {
    // Errore di preparazione della query
    echo json_encode(['success' => false, 'message' => 'Errore DB: ' . $conn->error]);
}

// Chiudi la connessione (opzionale, ma buona pratica)
$conn->close();
?>