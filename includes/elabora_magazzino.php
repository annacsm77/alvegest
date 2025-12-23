<?php
// Poiché il file è in /includes/, puntiamo a config.php nella stessa cartella
require_once 'config.php';

$count_inseriti = 0;

// Seleziona le attività registrate (AT_INSATT) collegate a tipologie che prevedono lo scarico (TA_MAG)
$sql = "SELECT i.*, a.AT_MAG_ID, a.AT_SCARICO_FISSO, a.AT_DESCR 
        FROM AT_INSATT i
        JOIN TA_Attivita a ON i.IA_ATT = a.AT_ID
        WHERE a.AT_MAG_ID IS NOT NULL";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ia_id = $row['IA_ID'];
        $mag_id = $row['AT_MAG_ID'];
        $data = $row['IA_DATA'];
        $descrizione = "Scarico automatico: " . $row['AT_DESCR'] . " (Attività ID: $ia_id)";
        
        // Controllo per evitare duplicati nello storico movimenti
        $check = $conn->query("SELECT MV_ID FROM MA_MOVI WHERE MV_Descrizione LIKE '%(Attività ID: $ia_id)%'");
        
        if ($check && $check->num_rows == 0) {
            // Logica quantità
            if ($row['AT_SCARICO_FISSO'] == 1) {
                $qta_scarico = 1.00;
            } else {
                // Estrae solo i numeri dalle note (es: "usati 2.5 kg" diventa 2.5)
                $qta_scarico = (float) filter_var($row['IA_NOTE'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            }

            if ($qta_scarico > 0) {
                $ins = $conn->prepare("INSERT INTO MA_MOVI (MV_Data, MV_Descrizione, MV_MAG_ID, MV_Carico, MV_Scarico) VALUES (?, ?, ?, 0, ?)");
                $ins->bind_param("ssid", $data, $descrizione, $mag_id, $qta_scarico);
                if ($ins->execute()) {
                    $count_inseriti++;
                }
                $ins->close();
            }
        }
    }
}

// Reindirizzamento alla pagina movimenti con il risultato
// Usiamo la funzione url() se disponibile, altrimenti un percorso relativo
header("Location: " . url("pages/ma_movimenti.php?status=success&elaborati=" . $count_inseriti));
exit();
?>