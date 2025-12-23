<?php
// Assicurati che il client sappia che stai inviando HTML
header('Content-Type: text/html; charset=utf-8');

// Usa require_once per la configurazione del DB
require_once 'config.php';

// Controlla l'input
if (!isset($_GET['fase_id']) || !is_numeric($_GET['fase_id'])) {
    echo "<p style='color: red;'>ID Fase non valido.</p>";
    exit;
}

$fase_id = $_GET['fase_id'];

// Costruzione della query per recuperare i dettagli delle arnie in quella fase
$sql = "
    SELECT 
        FF.TF_ID,      /* ID del record Figlio */
        A.AR_CODICE,   /* Codice Arnia */
        A.AR_NOME,     /* Nome Arnia */
        T.AT_DESCR,    /* Descrizione Tipo Attività (Trattamento) */
        IA.IA_DATA,    /* Data Attività */
        IA.IA_NOTE     /* Note dell'Attività */
    FROM TR_FFASE FF
    JOIN AP_Arnie A ON FF.TF_ARNIA = A.AR_ID
    JOIN TA_Attivita T ON FF.TF_ATT = T.AT_ID
    JOIN AT_INSATT IA ON FF.TF_CATT = IA.IA_ID
    WHERE FF.TF_PFASE = ?
    ORDER BY IA.IA_DATA DESC, FF.TF_ID DESC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    
    $stmt->bind_param("i", $fase_id); // Associa l'ID della Fase
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table style='font-size: 14px;'>
                <thead>
                    <tr>
                        <th style='width: 5%;'>ID Dett.</th>
                        <th style='width: 10%;'>Codice Arnia</th>
                        <th style='width: 20%;'>Nome Arnia</th>
                        <th style='width: 15%;'>Data Tratt.</th>
                        <th style='width: 20%;'>Tipo Trattamento</th>
                        <th style='width: 20%;'>Note Attività</th> 
                        <th style='width: 10%;'>Elimina</th> </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            
            $data_italiana = date('d/m/Y', strtotime($row['IA_DATA']));
            
            echo "<tr>
                    <td>" . $row['TF_ID'] . "</td>
                    <td>" . htmlspecialchars($row['AR_CODICE']) . "</td>
                    <td>" . htmlspecialchars($row['AR_NOME']) . "</td>
                    <td>" . $data_italiana . "</td>
                    <td>" . htmlspecialchars($row['AT_DESCR']) . "</td>
                    <td title='" . htmlspecialchars($row['IA_NOTE']) . "'>" . htmlspecialchars(substr($row['IA_NOTE'], 0, 30)) . (strlen($row['IA_NOTE']) > 30 ? "..." : "") . "</td>
                    <td>
                        <a href='fasetratt.php?elimina_movimento=" . $row['TF_ID'] . "&fase_id_return=" . $fase_id . "&tab=dettaglio' class='btn btn-elimina btn-xs' onclick='return confirm(\"Sei sicuro di voler eliminare questo movimento (ID: " . $row['TF_ID'] . ")?\");'>Elimina</a>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>Nessuna arnia ha partecipato a questa fase di trattamento (ID: " . $fase_id . ").</p>";
    }
    $stmt->close();
} else {
    echo "<p style='color: red;'>Errore nella preparazione della query: " . $conn->error . "</p>";
}

$conn->close();
?>