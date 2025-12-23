<?php
require_once '../includes/config.php';
require_once '../includes/header.php'; 

// Versione del file
$versione = "S.0.3"; // Rimosso il join con TA_Apiari

// --- RECUPERO DATI ---
// Data odierna per il confronto
$oggi = date('Y-m-d');

$sql = "
    SELECT 
        S.SC_ID,
        S.SC_DINIZIO,
        S.SC_DATAF,
        S.SC_AVA,
        A.AR_CODICE,
        A.AR_NOME,
        T.AT_DESCR
    FROM TR_SCAD S
    JOIN AP_Arnie A ON S.SC_ARNIA = A.AR_ID
    JOIN TA_Attivita T ON S.SC_TATT = T.AT_ID
    WHERE S.SC_CHIUSO = 0
    ORDER BY S.SC_DATAF ASC, A.AR_CODICE ASC
";

$result = $conn->query($sql);

?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column" style="padding: 5px;"> 
        <h2 style="margin-top: 10px; margin-bottom: 5px;">Scadenziario Trattamenti <small>(V.<?php echo $versione; ?>)</small></h2>

        <h3 style="margin-top: 10px; margin-bottom: 5px;">Trattamenti Attivi (SC_CHIUSO = 0)</h3>
        <div class="table-container" style="max-height: 80vh; overflow-y: auto; border: 1px solid #ddd;">
            <?php
            if ($result && $result->num_rows > 0) {
                echo "<table class='griglia-scadenziario'>
                        <thead>
                            <tr>
                                <th style='width: 5%;'>ID</th>
                                <th style='width: 15%;'>Arnia</th>
                                <th style='width: 10%;'>Ciclo</th>
                                <th style='width: 30%;'>Tipo Trattamento</th>
                                <th style='width: 15%;'>Inizio</th>
                                <th style='width: 15%;'>Scadenza</th>
                                <th style='width: 10%;'>Stato</th>
                            </tr>
                        </thead>
                        <tbody>";
                while ($row = $result->fetch_assoc()) {
                    
                    $data_scadenza = $row['SC_DATAF'];
                    $data_scadenza_ts = strtotime($data_scadenza);
                    $oggi_ts = strtotime($oggi);
                    $diff_seconds = $oggi_ts - $data_scadenza_ts;
                    $diff_days = round($diff_seconds / (60 * 60 * 24)); // Differenza in giorni

                    $indicatore_colore = 'colore-verde'; // Default: Verde
                    $indicatore_testo = 'Entro Scad.';
                    
                    if ($diff_days > 0) {
                        // SCADUTO
                        if ($diff_days > 4) {
                            $indicatore_colore = 'colore-rosso';
                            $indicatore_testo = 'Ritardo > 4gg';
                        } else {
                            $indicatore_colore = 'colore-giallo';
                            $indicatore_testo = 'Scaduto (<= 4gg)';
                        }
                    } else {
                        // NON SCADUTO
                        $indicatore_testo = 'Entro Scad.';
                    }
                    
                    // Rimosso $apiario_nome

                    echo "<tr>
                            <td>" . $row["SC_ID"] . "</td>
                            <td>" . htmlspecialchars($row["AR_CODICE"]) . " - " . htmlspecialchars($row["AR_NOME"]) . "</td>
                            <td style='text-align: center;'>" . $row["SC_AVA"] . "</td>
                            <td>" . htmlspecialchars($row["AT_DESCR"]) . "</td>
                            <td>" . date('d/m/Y', strtotime($row["SC_DINIZIO"])) . "</td>
                            <td>" . date('d/m/Y', strtotime($data_scadenza)) . "</td>
                            <td class='" . $indicatore_colore . " action-cell-compact' title='" . $indicatore_testo . "' style='text-align: center;'> 
                                <span style='font-weight: bold;'>" . $indicatore_testo . "</span>
                            </td>
                          </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p>Nessun trattamento attivo o scaduto in attesa di completamento.</p>";
            }
            ?>
        </div>

    </div>

    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    
    // Stili CSS per il semaforo (Da integrare in styles.css in produzione)
    $('head').append('<style>.colore-verde { background-color: #d4edda; color: #155724; font-weight: bold; } .colore-giallo { background-color: #fff3cd; color: #856404; font-weight: bold; } .colore-rosso { background-color: #f8d7da; color: #721c24; font-weight: bold; } .griglia-scadenziario th, .griglia-scadenziario td { padding: 8px 4px; } .center-column { padding: 5px; } h2, h3 { margin-top: 10px; margin-bottom: 5px; } .griglia-scadenziario tbody tr:hover { background-color: #f0f0f0; }</style>');
    
});
</script>

<?php
require_once '../includes/footer.php';
?>