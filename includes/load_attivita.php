<?php
require_once 'config.php';

$arnia_id = $_GET['arnia_id'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

if (!$arnia_id) {
    echo "<p>Seleziona un'arnia per vedere lo storico.</p>";
    exit;
}

// DETERMINA LA CARTELLA DI PROVENIENZA
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Se siamo in "pages", usiamo il file per desktop, altrimenti quello per mobile
if ($current_dir == 'pages') {
    $url_base = "../includes/elimina_attivita.php"; // Per gestatt.php
} else {
    $url_base = "includes/elimina_attivita_mobile.php"; // Per mobile.php
}

$sql = "SELECT i.*, a.AT_DESCR FROM AT_INSATT i JOIN TA_Attivita a ON i.IA_ATT = a.AT_ID WHERE i.IA_CodAr = ? ORDER BY i.IA_DATA DESC LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $arnia_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table><thead><tr><th>Data</th><th>Attività</th><th>Note</th><th>Azione</th></tr></thead><tbody>";
    while ($row = $result->fetch_assoc()) {
        $data_f = date('d/m/y', strtotime($row['IA_DATA']));
        $url_elimina = $url_base . "?id=" . $row['IA_ID'] . "&arnia_id=" . $arnia_id;
        
        echo "<tr>
                <td>$data_f</td>
                <td>" . htmlspecialchars($row['AT_DESCR']) . "</td>
                <td><small>" . htmlspecialchars($row['IA_NOTE']) . "</small></td>
                <td>
                    <a href='" . $url_elimina . "' class='btn-elimina-small' onclick='return confirm(\"Eliminare attività e scarico magazzino?\")'>Elimina</a>
                </td>
              </tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nessuna attività registrata.</p>";
}
?>