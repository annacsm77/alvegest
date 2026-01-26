<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.1.1 - Zero Inline Styles & CSS Badges');

require_once '../includes/config.php';

// 1. Recupero Apiari e Arnie (Logica invariata)
$sql_apiari = "SELECT DISTINCT api.AI_ID, api.AI_LUOGO 
               FROM TA_Apiari api
               JOIN AP_Arnie arn ON arn.AR_LUOGO = api.AI_ID
               WHERE arn.AR_ATTI = 0
               ORDER BY api.AI_LUOGO ASC";
$res_apiari = $conn->query($sql_apiari);

$apiari = [];
if ($res_apiari && $res_apiari->num_rows > 0) {
    while ($a = $res_apiari->fetch_assoc()) {
        $apiari[$a['AI_ID']] = ['nome' => $a['AI_LUOGO'], 'arnie' => []];
    }

    $sql_arnie = "SELECT arn.AR_ID, arn.AR_CODICE, arn.AR_NOME, arn.AR_LUOGO, arn.AR_posizione,
                  (SELECT i.IA_PERI FROM AT_INSATT i WHERE i.IA_CodAr = arn.AR_ID 
                   ORDER BY i.IA_DATA DESC, i.IA_ID DESC LIMIT 1) as ultimo_pericolo
                  FROM AP_Arnie arn WHERE arn.AR_ATTI = 0
                  ORDER BY arn.AR_posizione ASC, arn.AR_CODICE ASC";
    $res_arnie = $conn->query($sql_arnie);

    if ($res_arnie && $res_arnie->num_rows > 0) {
        while ($arn = $res_arnie->fetch_assoc()) {
            if (isset($apiari[$arn['AR_LUOGO']])) {
                $apiari[$arn['AR_LUOGO']]['arnie'][] = $arn;
            }
        }
    }
}

require_once TPL_PATH . 'header.php'; 
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Disposizione Arnie</h2>

        <?php if (!empty($apiari)): ?>
        <div class="tabs-container">
            <ul class="tabs-menu">
                <?php $first = true; foreach ($apiari as $id => $dati): ?>
                    <li class="tab-link <?php echo $first ? 'active' : ''; ?>" 
                        onclick="openTab(event, 'apiario-<?php echo $id; ?>')">
                        <?php echo htmlspecialchars($dati['nome']); ?>
                    </li>
                <?php $first = false; endforeach; ?>
            </ul>

            <?php $first = true; foreach ($apiari as $id => $dati): ?>
                <div id="apiario-<?php echo $id; ?>" class="tab-content <?php echo $first ? 'active' : ''; ?>">
                    <div class="table-container">
                        <table class="selectable-table">
                            <thead>
                                <tr>
                                    <th class="txt-center">POS.</th>
                                    <th class="txt-center">CODICE</th>
                                    <th>NOME ARNIA</th>
                                    <th class="txt-center">AZIONI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dati['arnie'] as $arnia): ?>
                                <tr class="<?php echo ($arnia['ultimo_pericolo'] == 1) ? 'riga-pericolo' : ''; ?>">
                                    <td class="txt-center font-bold"><?php echo htmlspecialchars($arnia['AR_posizione']); ?></td>
                                    <td class="txt-center font-bold"><?php echo htmlspecialchars($arnia['AR_CODICE']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($arnia['AR_NOME']); ?>
                                        <?php if($arnia['ultimo_pericolo'] == 1): ?>
                                            <span class="badge-pericolo">⚠️ ATTENZIONE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="txt-center">
                                        <a href="gestatt.php?arnia_id=<?php echo $arnia['AR_ID']; ?>&tab=attivita" class="btn btn-stampa">Attività</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php $first = false; endforeach; ?>
        </div>
        <?php else: ?>
            <p class="txt-center txt-muted">Nessun apiario attivo trovato.</p>
        <?php endif; ?>
        
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>
    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function openTab(evt, tabName) {
    $('.tab-content').hide().removeClass('active');
    $('.tab-link').removeClass('active');
    $('#' + tabName).show().addClass('active');
    if(evt) $(evt.currentTarget).addClass('active');
}
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>