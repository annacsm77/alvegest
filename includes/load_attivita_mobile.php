<?php
// includes/load_attivita_mobile.php - Versione specifica per Mobile App
require_once 'config.php';

$arnia_id = $_GET['arnia_id'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

if (!$arnia_id) {
    echo "<p style='text-align:center; padding:20px;'>Seleziona un'arnia per vedere lo storico.</p>";
    exit;
}

$sql = "SELECT i.IA_ID, i.IA_DATA, i.IA_NOTE, i.IA_VREG, i.IA_PERI, i.IA_OP1, i.IA_OP2, a.AT_DESCR, i.IA_ATT 
        FROM AT_INSATT i 
        JOIN TA_Attivita a ON i.IA_ATT = a.AT_ID 
        WHERE i.IA_CodAr = ? 
        ORDER BY i.IA_DATA DESC LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $arnia_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Attività / Note</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>";

    while ($row = $result->fetch_assoc()) {
        $data_f = date('d/m/y', strtotime($row['IA_DATA']));
        $regina = ($row['IA_VREG'] == 1) ? '👑 ' : '';
        $pericolo = ($row['IA_PERI'] == 1) ? '⚠️ ' : '';
        
        // Prepariamo i parametri per la funzione editAttivita definita in mobile.php
        // Parametri: id, data, note, peri, vreg, op1, op2, att_id
        $params = sprintf(
            "%d, '%s', '%s', %d, %d, %d, %d, %d",
            $row['IA_ID'],
            $row['IA_DATA'],
            addslashes($row['IA_NOTE'] ?? ''),
            $row['IA_PERI'],
            $row['IA_VREG'],
            $row['IA_OP1'],
            $row['IA_OP2'],
            $row['IA_ATT']
        );

        echo "<tr>
                <td>$data_f</td>
                <td>
                    <strong>$pericolo$regina" . htmlspecialchars($row['AT_DESCR']) . "</strong><br>
                    <small>" . htmlspecialchars($row['IA_NOTE'] ?? '') . "</small>
                </td>
                <td style='text-align: center;'>
                    <button type='button' class='btn-edit-mobile' onclick=\"editAttivita($params)\">MOD</button>
                    <a href='mobile.php?elimina_id=" . $row['IA_ID'] . "&arnia_id=" . $arnia_id . "' 
                       class='btn-delete-mobile' 
                       onclick='return confirm(\"Eliminare questa attività? Verranno rimossi anche foto e magazzino.\")'>DEL</a>
                </td>
              </tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p style='text-align:center; padding:20px;'>Nessuna attività.</p>";
}
?>