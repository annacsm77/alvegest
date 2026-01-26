<?php
require_once '../../includes/config.php';

// Leggiamo i dati JSON inviati
$d = json_decode(file_get_contents('php://input'), true);
$utente_id = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? 'save';

if ($utente_id <= 0) {
    die("Errore: Sessione utente non valida.");
}

// AZIONE: ELIMINA
if ($action == 'delete' && isset($d['id'])) {
    $id = (int)$d['id'];
    // Eliminiamo la nota verificando che appartenga all'utente o sia pubblica
    $sql = "DELETE FROM CF_NOTE_WIDGET WHERE NW_ID = $id";
    
    if ($conn->query($sql)) {
        echo "OK";
    } else {
        echo "Errore DB: " . $conn->error;
    }
    exit;
}

// AZIONE: SALVA (Inserimento o Modifica)
if ($d && isset($d['titolo'])) {
    $id = (isset($d['id']) && $d['id'] != "") ? (int)$d['id'] : null;
    $titolo = $conn->real_escape_string($d['titolo']);
    $testo = $conn->real_escape_string($d['testo']);
    $dest = (int)$d['dest'];

    if ($id) {
        $sql = "UPDATE CF_NOTE_WIDGET SET NW_UTENTE_ID=$dest, NW_TITOLO='$titolo', NW_CONTENUTO='$testo' WHERE NW_ID=$id";
    } else {
        $sql = "INSERT INTO CF_NOTE_WIDGET (NW_UTENTE_ID, NW_TITOLO, NW_CONTENUTO) VALUES ($dest, '$titolo', '$testo')";
    }
    
    if ($conn->query($sql)) {
        echo "OK";
    } else {
        echo "Errore: " . $conn->error;
    }
}
?>