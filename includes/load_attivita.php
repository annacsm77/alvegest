<?php
require_once 'config.php';

$arnia_id = $_GET['arnia_id'] ?? null;
$tipo_id = $_GET['tipo_id'] ?? null; 
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

if (!$arnia_id) {
    echo "<p style='text-align:center; padding:20px;'>Seleziona un'arnia per vedere lo storico.</p>";
    exit;
}

// Recuperiamo IA_VREG per mostrare l'icona della regina
$sql = "SELECT i.IA_ID, i.IA_DATA, i.IA_NOTE, i.IA_VREG, a.AT_DESCR 
        FROM AT_INSATT i 
        JOIN TA_Attivita a ON i.IA_ATT = a.AT_ID 
        WHERE i.IA_CodAr = ?";

if (!empty($tipo_id)) { $sql .= " AND i.IA_ATT = " . (int)$tipo_id; }
$sql .= " ORDER BY i.IA_DATA DESC LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $arnia_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table class='selectable-table table-fixed-layout'>
            <thead>
                <tr>
                    <th style='width: 85px;'>Data</th>
                    <th style='width: 35px;'>R.</th> <th style='width: auto;'>Attività / Note</th>
                    <th style='width: 160px;'>Azioni</th>
                </tr>
            </thead>
            <tbody>";

    while ($row = $result->fetch_assoc()) {
        $data_db = $row['IA_DATA'];
        $data_f = date('d/m/y', strtotime($data_db));
        
        // Icona coroncina se Vista Regina è flaggata
        $regina_icon = ($row['IA_VREG'] == 1) ? '<i class="fa-solid fa-crown" style="color: #FFD700;" title="Regina Vista"></i>' : '';
        
        $note_js = htmlspecialchars($row['IA_NOTE'] ?? '', ENT_QUOTES);
        $note_js = str_replace(array("\r", "\n"), ' ', $note_js);
        
        echo "<tr>
                <td style='width: 85px;'>$data_f</td>
                <td style='width: 35px; text-align: center;'>$regina_icon</td>
                <td style='width: auto;'>
                    <strong>" . htmlspecialchars($row['AT_DESCR']) . "</strong><br>
                    <small>" . htmlspecialchars($row['IA_NOTE'] ?? '') . "</small>
                </td>
                <td class='action-cell' style='width: 160px; text-align: right; white-space: nowrap;'>
                    <button type='button' class='btn btn-modifica btn-xs' 
                            onclick=\"window.startEdit('{$row['IA_ID']}', '{$arnia_id}', '{$data_db}', '{$note_js}')\">
                        Modifica
                    </button>
                    <a href='gestatt.php?elimina=" . $row['IA_ID'] . "&arnia_id=" . $arnia_id . "' 
                       class='btn btn-elimina btn-xs' 
                       onclick='return confirm(\"Eliminare questa attività?\")'>
                        Elimina
                    </a>
                </td>
              </tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p style='text-align:center; padding:20px;'>Nessuna attività registrata.</p>";
}
?>