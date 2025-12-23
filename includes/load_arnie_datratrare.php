<?php
// Assicurati che il client sappia che stai inviando HTML
header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';

// Controlla l'input
if (!isset($_GET['fase_id']) || !is_numeric($_GET['fase_id'])) {
    echo "<p style='color: red;'>ID Fase non valido.</p>";
    exit;
}

$fase_id = $_GET['fase_id'];

// Query che seleziona tutte le arnie attive (AR_ATTI = 0) 
// ESCLUDENDO quelle i cui ID sono presenti nella tabella figlia TR_FFASE per la fase corrente.
$sql = "
    SELECT 
        A.AR_ID,
        A.AR_CODICE,
        A.AR_NOME,
        AI.AI_LUOGO /* Usiamo AI_LUOGO come nome dell'apiario */
    FROM AP_Arnie A
    LEFT JOIN TA_Apiari AI ON A.AR_LUOGO = AI.AI_ID
    WHERE 
        A.AR_ATTI = 0 /* Solo arnie attive*/
        AND A.AR_ID NOT IN (
            SELECT TF_ARNIA 
            FROM TR_FFASE 
            WHERE TF_PFASE = ?
        )
    ORDER BY AI.AI_LUOGO ASC, A.AR_CODICE ASC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    
    $stmt->bind_param("i", $fase_id); // Associa l'ID della Fase
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table class='griglia-scadenziario' style='font-size: 14px;'>
                <thead>
                    <tr>
                        <th style='width: 15%;'>Codice</th>
                        <th style='width: 35%;'>Nome Arnia</th>
                        <th style='width: 35%;'>Apiario</th>
                        <th style='width: 15%;'>ID Arnia</th>
                    </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            
            // Usiamo AI_LUOGO, alias confermato dall'ultima verifica dello schema
            $apiario_nome = htmlspecialchars($row['AI_LUOGO'] ?? 'N/D');
            
            echo "<tr>
                    <td>" . htmlspecialchars($row['AR_CODICE']) . "</td>
                    <td>" . htmlspecialchars($row['AR_NOME']) . "</td>
                    <td>" . $apiario_nome . "</td>
                    <td>" . $row['AR_ID'] . "</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>Tutte le arnie attive sono state trattate in questa fase.</p>";
    }
    $stmt->close();
} else {
    echo "<p style='color: red;'>Errore nella preparazione della query: " . $conn->error . "</p>";
}

$conn->close();
?>