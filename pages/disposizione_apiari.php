<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'D.0.6 (Stile da CSS esterno)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php'; 

// 1. Recuperiamo tutti gli apiari che hanno almeno un'arnia attiva
$sql_apiari = "SELECT DISTINCT api.AI_ID, api.AI_LUOGO 
               FROM TA_Apiari api
               JOIN AP_Arnie arn ON arn.AR_LUOGO = api.AI_ID
               WHERE arn.AR_ATTI = 0
               ORDER BY api.AI_LUOGO ASC";
$res_apiari = $conn->query($sql_apiari);

$apiari = [];
if ($res_apiari && $res_apiari->num_rows > 0) {
    while ($a = $res_apiari->fetch_assoc()) {
        $apiari[$a['AI_ID']] = [
            'nome' => $a['AI_LUOGO'],
            'arnie' => []
        ];
    }

    // 2. Recuperiamo tutte le arnie attive ordinate per posizione
    $sql_arnie = "SELECT arn.AR_ID, arn.AR_CODICE, arn.AR_NOME, arn.AR_LUOGO, arn.AR_posizione,
                  (SELECT i.IA_PERI 
                   FROM AT_INSATT i 
                   WHERE i.IA_CodAr = arn.AR_ID 
                   ORDER BY i.IA_DATA DESC, i.IA_ID DESC LIMIT 1) as ultimo_pericolo
                  FROM AP_Arnie arn
                  WHERE arn.AR_ATTI = 0
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
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2>Disposizione Arnie negli Apiari</h2>

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
                        <table class="table-disposizione" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">Pos.</th>
                                    <th style="width: 100px; text-align: center;">Codice</th>
                                    <th>Nome Arnia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dati['arnie'] as $arnia): ?>
                                <tr class="<?php echo ($arnia['ultimo_pericolo'] == 1) ? 'riga-pericolo' : ''; ?>">
                                    <td style="text-align: center; font-weight: bold;">
                                        <?php echo htmlspecialchars($arnia['AR_posizione']); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="gestatt.php?arnia_id=<?php echo $arnia['AR_ID']; ?>&tab=movimenti#tab-movimenti" class="link-gestatt">
                                            <?php echo htmlspecialchars($arnia['AR_CODICE']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($arnia['AR_NOME']); ?>
                                        <?php if($arnia['ultimo_pericolo'] == 1): ?>
                                            <span style="float: right; font-weight: bold; color: #d9534f;">
                                                <i class="fa-solid fa-triangle-exclamation"></i> ATTENZIONE
                                            </span>
                                        <?php endif; ?>
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
            <p style="text-align: center; padding: 20px;">Nessun apiario attivo trovato con arnie presenti.</p>
        <?php endif; ?>
        
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>

    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function openTab(evt, tabName) {
    $('.tab-content').hide();
    $('.tab-link').removeClass('active');
    $('#' + tabName).show();
    if(evt) $(evt.currentTarget).addClass('active');
}
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>