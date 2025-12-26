<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'S.0.4 (Layout Unificato)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php'; 

// Data odierna per il confronto
$oggi = date('Y-m-d');

$sql = "SELECT S.SC_ID, S.SC_DINIZIO, S.SC_DATAF, S.SC_AVA, A.AR_CODICE, A.AR_NOME, T.AT_DESCR
        FROM TR_SCAD S
        JOIN AP_Arnie A ON S.SC_ARNIA = A.AR_ID
        JOIN TA_Attivita T ON S.SC_TATT = T.AT_ID
        WHERE S.SC_CHIUSO = 0
        ORDER BY S.SC_DATAF ASC, A.AR_CODICE ASC";

$result = $conn->query($sql);
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column"> 
        <h2>Scadenziario Trattamenti</h2>

        <div class="tabs-container" style="margin-top:0;">
            <ul class="tabs-menu">
                <li class="tab-link active">Trattamenti Attivi</li>
            </ul>
            
            <div class="tab-content active">
                <div class="table-container">
                    <?php if ($result && $result->num_rows > 0): ?>
                    <table class="selectable-table table-fixed-layout">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 180px;">Arnia</th>
                                <th style="width: 60px; text-align: center;">Ciclo</th>
                                <th class="col-auto">Tipo Trattamento</th>
                                <th style="width: 100px;">Scadenza</th>
                                <th style="width: 130px; text-align: center;">Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $data_scadenza = $row['SC_DATAF'];
                                $diff_days = round((strtotime($oggi) - strtotime($data_scadenza)) / 86400);

                                $status_class = 'successo'; // Verde (standard CSS)
                                $status_text = 'In corso';

                                if ($diff_days > 0) {
                                    if ($diff_days > 4) {
                                        $status_style = 'background-color: #f8d7da; color: #721c24;'; // Rosso
                                        $status_text = 'Ritardo > 4gg';
                                    } else {
                                        $status_style = 'background-color: #fff3cd; color: #856404;'; // Giallo
                                        $status_text = 'Scaduto';
                                    }
                                } else {
                                    $status_style = 'background-color: #d4edda; color: #155724;'; // Verde
                                    $status_text = 'In tempo';
                                }
                            ?>
                            <tr>
                                <td><?php echo $row["SC_ID"]; ?></td>
                                <td><strong><?php echo htmlspecialchars($row["AR_CODICE"] . " - " . $row["AR_NOME"]); ?></strong></td>
                                <td style="text-align: center;"><?php echo $row["SC_AVA"]; ?></td>
                                <td class="col-auto"><?php echo htmlspecialchars($row["AT_DESCR"]); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($data_scadenza)); ?></td>
                                <td style="text-align: center; font-weight: bold; <?php echo $status_style; ?> border-radius: 4px;">
                                    <?php echo $status_text; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p style="padding: 20px; text-align: center;">Nessun trattamento attivo o scaduto.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>

    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php require_once TPL_PATH . 'footer.php'; ?>