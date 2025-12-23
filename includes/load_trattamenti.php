<?php
// Abilita la visualizzazione degli errori (temporaneamente per debug)
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// Assicurati che il client sappia che stai inviando HTML
header('Content-Type: text/html; charset=utf-8');

// Usa require_once per la configurazione del DB
require_once 'config.php';

// Controlla l'input
if (!isset($_GET['arnia_id']) || !is_numeric($_GET['arnia_id'])) {
    echo "<p style='color: red;'>ID Arnia non valido.</p>";
    exit;
}
if (empty($_GET['filter_ids'])) {
     echo "<p style='color: red;'>❌ ERRORE: La chiave TIP_TRAT in CF_GLOB è vuota. Inserisci i codici attività corretti.</p>";
     exit;
}

$arnia_id = $_GET['arnia_id'];
$filter_ids = $_GET['filter_ids']; 

// Costruzione dinamica della query con IN (?)
// NOTA: La stringa $filter_ids è già stata sanitizzata in gesttratt.php, ma verifichiamo che non sia vuota qui.
$sql = "
    SELECT 
        IA.IA_ID,
        IA.IA_DATA,
        T.AT_DESCR,
        IA.IA_NOTE,
        IA.IA_PERI,
        IA.IA_VREG,
        IA.IA_OP1,
        IA.IA_OP2
    FROM AT_INSATT IA
    JOIN TA_Attivita T ON IA.IA_ATT = T.AT_ID
    WHERE IA.IA_CodAr = ? AND IA.IA_ATT IN ($filter_ids)
    ORDER BY IA.IA_DATA DESC, IA.IA_ID DESC
";

// STAMPA DEBUG: Mostra la query prima di prepararla
//echo "<div style='border: 1px solid blue; padding: 10px; margin-bottom: 10px;'>";
//echo "DEBUG QUERY TEMPLATE: <br><code>" . htmlspecialchars($sql) . "</code><br>";
//echo "Valore \$filter_ids: " . htmlspecialchars($filter_ids) . "</div>";
// FINE STAMPA DEBUG

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Gestione del binding: solo l'ID Arnia
    $stmt->bind_param("i", $arnia_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<table>
                    <thead>
                        <tr>
                            <th style='width: 5%;'>ID</th>
                            <th style='width: 15%;'>Data</th>
                            <th style='width: 20%;'>Tipo Attività</th>
                            <th style='width: 25%;'>Note</th>
                            <th title='Pericolo'>P</th>
                            <th title='Visita Regina'>V</th>
                            <th title='Opzione 1'>O1</th>
                            <th title='Opzione 2'>O2</th>
                            <th style='width: 15%;'>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>";
            while ($row = $result->fetch_assoc()) {
                
                $peri = $row['IA_PERI'] ? "<span style='color: red;'>🚨</span>" : '';
                $vreg = $row['IA_VREG'] ? '👑' : ''; 
                $op1 = $row['IA_OP1'] ? '✅' : '';
                $op2 = $row['IA_OP2'] ? '✅' : '';
                
                $data_italiana = date('d/m/Y', strtotime($row['IA_DATA']));
                
                echo "<tr>
                        <td>" . $row['IA_ID'] . "</td>
                        <td>" . $data_italiana . "</td>
                        <td>" . htmlspecialchars($row['AT_DESCR']) . "</td>
                        <td title='" . htmlspecialchars($row['IA_NOTE']) . "'>" . htmlspecialchars(substr($row['IA_NOTE'], 0, 30)) . (strlen($row['IA_NOTE']) > 30 ? "..." : "") . "</td>
                        <td style='text-align: center;'>" . $peri . "</td>
                        <td style='text-align: center;'>" . $vreg . "</td>
                        <td style='text-align: center;'>" . $op1 . "</td>
                        <td style='text-align: center;'>" . $op2 . "</td>
                        <td class='action-cell-compact'> 
                            <div class='btn-grid-row-compact'>
                                <a href='gesttratt.php?modifica=" . $row['IA_ID'] . "&arnia_id=" . $arnia_id . "' class='btn btn-modifica btn-xs'>Modifica</a>
                                <a href='gesttratt.php?elimina=" . $row['IA_ID'] . "&arnia_id=" . $arnia_id . "' class='btn btn-elimina btn-xs' onclick='return confirm(\"Sei sicuro di voler eliminare l\\'attività ID " . $row['IA_ID'] . "?\");'>Elimina</a>
                            </div>
                        </td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>Nessun trattamento trovato per l'arnia selezionata con gli ID configurati.</p>";
        }
    } else {
        echo "<p style='color: red;'>ERRORE ESECUZIONE QUERY: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
    echo "<p style='color: red;'>Errore nella preparazione della query: " . $conn->error . "</p>";
}

$conn->close();
?>