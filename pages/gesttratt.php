<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'T.1.5 - Fix Tabs & Alignment');

require_once '../includes/config.php';

// --- 1. LOGICA PHP IN ALTO (Invariata) ---
$messaggio = "";

if (isset($_GET["elimina"]) && isset($_GET["arnia_id"])) {
    $elimina_id = (int)$_GET["elimina"];
    $arnia_id_return = (int)$_GET["arnia_id"];
    $causale_ricerca = "%(IA_ID: $elimina_id)%";
    $conn->query("DELETE FROM MA_MOVI WHERE MV_Descrizione LIKE '$causale_ricerca'");
    $stmt = $conn->prepare("DELETE FROM AT_INSATT WHERE IA_ID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $elimina_id);
        if ($stmt->execute()) {
            header("Location: gesttratt.php?status=delete_success&arnia_id=$arnia_id_return&tab=attivita");
            exit();
        }
    }
}

if (isset($_POST["update_attivita"])) {
    $ia_id = (int)$_POST["ia_id"];
    $arnia_id_return = (int)$_POST["arnia_id_return"];
    $data_input = $_POST["data"];
    $tipo = (int)$_POST["tipo"];
    $note = $_POST["note"];
    $nuovo_valore = str_replace(',', '.', $_POST['valore'] ?? '1.00');
    $peri = isset($_POST["peri"]) ? 1 : 0; 
    $vreg = isset($_POST["vreg"]) ? 1 : 0; 
    $op1 = isset($_POST["op1"]) ? 1 : 0; 
    $op2 = isset($_POST["op2"]) ? 1 : 0; 

    $causale_ricerca = "%(IA_ID: $ia_id)%";
    $sql_mag = "UPDATE MA_MOVI SET MV_Data = ?, MV_Scarico = ?, MV_Note = CONCAT('Trattamento: ', ?) WHERE MV_Descrizione LIKE ?";
    $stmt_mag = $conn->prepare($sql_mag);
    if ($stmt_mag) {
        $stmt_mag->bind_param("sdss", $data_input, $nuovo_valore, $note, $causale_ricerca);
        $stmt_mag->execute();
    }

    $sql_att = "UPDATE AT_INSATT SET IA_DATA = ?, IA_ATT = ?, IA_NOTE = ?, IA_PERI = ?, IA_VREG = ?, IA_OP1 = ?, IA_OP2 = ? WHERE IA_ID = ?";
    $stmt_att = $conn->prepare($sql_att);
    if ($stmt_att) {
        $stmt_att->bind_param("sisiiiii", $data_input, $tipo, $note, $peri, $vreg, $op1, $op2, $ia_id);
        if ($stmt_att->execute()) {
            header("Location: gesttratt.php?status=update_success&arnia_id=$arnia_id_return&tab=attivita");
            exit();
        }
    }
}

require_once TPL_PATH . 'header.php'; 

// Gestione TAB attiva e Arnia preselezionata
$active_tab = $_GET['tab'] ?? 'selezione';
$preselected_arnia_id_url = $_GET['arnia_id'] ?? null;

// Gestione feedback
if (isset($_GET['status'])) {
    $st = $_GET['status'];
    if ($st == 'update_success') $messaggio = "<p class='successo'>Trattamento aggiornato!</p>";
    if ($st == 'delete_success') $messaggio = "<p class='successo txt-danger font-bold'>Trattamento eliminato!</p>";
}

// Caricamento filtri e arnie
$sql_filter = "SELECT CF_VAL FROM CF_GLOB WHERE CF_DATO = 'TIP_TRAT'";
$res_f = $conn->query($sql_filter);
$safe_ids = ($res_f && $row = $res_f->fetch_assoc()) ? preg_replace('/[^0-9,]+/', '', $row['CF_VAL']) : "0";

$sql_arnie = "SELECT a.AR_ID, a.AR_CODICE, a.AR_NOME, 
              (SELECT i.IA_PERI FROM AT_INSATT i WHERE i.IA_CodAr = a.AR_ID ORDER BY i.IA_DATA DESC, i.IA_ID DESC LIMIT 1) as ULTIMO_PERICOLO
              FROM AP_Arnie a WHERE a.AR_ATTI = 0 ORDER BY a.AR_CODICE ASC";
$result_arnie = $conn->query($sql_arnie);

// Logica per Tab Modifica
$modifica_id = $_GET["modifica"] ?? null;
$attivita_modifica = null;
if ($modifica_id) {
    $stmt = $conn->prepare("SELECT i.*, m.MV_Scarico FROM AT_INSATT i LEFT JOIN MA_MOVI m ON m.MV_Descrizione LIKE CONCAT('%(IA_ID: ', i.IA_ID, ')%') WHERE i.IA_ID = ?");
    $stmt->bind_param("i", $modifica_id);
    $stmt->execute();
    $attivita_modifica = $stmt->get_result()->fetch_assoc();
    if ($attivita_modifica) {
        $preselected_arnia_id_url = $attivita_modifica['IA_CodAr'];
        $active_tab = 'modifica';
    }
}
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Storico Trattamenti</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link" id="link-tab-selezione" onclick="openTab(event, 'selezione')">SELEZIONE</li>
                <li class="tab-link" id="link-tab-attivita" onclick="openTab(event, 'attivita')">STORICO</li>
                <li class="tab-link <?php echo ($modifica_id ? '' : 'hidden'); ?>" id="link-tab-modifica" onclick="openTab(event, 'modifica')">MODIFICA</li>
            </ul>

            <div id="selezione" class="tab-content" style="display:none;">
                <div class="table-container">
                    <table id="arnie-table" class="selectable-table">
                        <thead><tr><th class="txt-center">CODICE</th><th>NOME ARNIA</th></tr></thead>
                        <tbody>
                            <?php while ($row = $result_arnie->fetch_assoc()): 
                                $is_sel = ($preselected_arnia_id_url == $row["AR_ID"]) ? 'selected-row' : '';
                                $danger_class = ($row['ULTIMO_PERICOLO'] == 1) ? 'riga-pericolo' : '';
                            ?>
                            <tr data-arnia-id="<?php echo $row["AR_ID"]; ?>" class="<?php echo $is_sel . ' ' . $danger_class; ?> clickable-row">
                                <td class="txt-center font-bold"><?php echo $row["AR_CODICE"]; ?></td>
                                <td><?php echo htmlspecialchars($row["AR_NOME"]); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="attivita" class="tab-content" style="display:none;">
                <div class="form-container">
                    <p id="selected-arnia-display" class="font-bold txt-center">
                        <?php if ($preselected_arnia_id_url) echo "Caricamento storico..."; else echo "Seleziona un'arnia."; ?>
                    </p>
                </div>
                <div class="table-container" id="trattamenti-list-container">
                    <p class="txt-muted txt-center">In attesa di selezione...</p>
                </div>
            </div>

            <div id="modifica" class="tab-content" style="display:none;">
                <?php if ($attivita_modifica): ?>
                <div class="form-container">
                    <h3 class="font-bold">Modifica Trattamento</h3>
                    <form action="gesttratt.php" method="post">
                        <input type="hidden" name="ia_id" value="<?php echo $modifica_id; ?>">
                        <input type="hidden" name="arnia_id_return" value="<?php echo $attivita_modifica['IA_CodAr']; ?>">
                        <input type="hidden" name="update_attivita" value="1">
                        <div class="btn-group-flex">
                            <div class="form-group btn-flex-1"><label>Data:</label><input type="date" name="data" value="<?php echo $attivita_modifica['IA_DATA']; ?>" required></div>
                            <div class="form-group btn-flex-1"><label>Scarico:</label><input type="number" step="0.01" name="valore" value="<?php echo $attivita_modifica['MV_Scarico'] ?? 1.00; ?>" required></div>
                        </div>
                        <div class="form-group">
                            <label>Trattamento:</label>
                            <select name="tipo" required>
                                <?php 
                                $res_opt = $conn->query("SELECT AT_ID, AT_DESCR FROM TA_Attivita WHERE AT_ID IN ($safe_ids) ORDER BY AT_DESCR ASC");
                                while($att = $res_opt->fetch_assoc()): ?>
                                    <option value="<?php echo $att['AT_ID']; ?>" <?php echo ($attivita_modifica["IA_ATT"] == $att['AT_ID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($att['AT_DESCR']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="btn-group-flex">
                            <label class="btn-flex-1 txt-center"><input type="checkbox" name="peri" <?php echo $attivita_modifica["IA_PERI"] ? 'checked' : ''; ?>> Pericolo</label>
                            <label class="btn-flex-1 txt-center"><input type="checkbox" name="vreg" <?php echo $attivita_modifica["IA_VREG"] ? 'checked' : ''; ?>> Regina</label>
                            <label class="btn-flex-1 txt-center"><input type="checkbox" name="op1" <?php echo $attivita_modifica["IA_OP1"] ? 'checked' : ''; ?>> Op.1</label>
                            <label class="btn-flex-1 txt-center"><input type="checkbox" name="op2" <?php echo $attivita_modifica["IA_OP2"] ? 'checked' : ''; ?>> Op.2</label>
                        </div>
                        <div class="form-group"><label>Note:</label><textarea name="note" rows="3"><?php echo htmlspecialchars($attivita_modifica["IA_NOTE"]); ?></textarea></div>
                        <div class="btn-group-flex">
                            <button type="submit" class="btn btn-salva btn-flex-2">Salva Modifiche</button>
                            <a href="gesttratt.php?elimina=<?php echo $modifica_id; ?>&arnia_id=<?php echo $attivita_modifica['IA_CodAr']; ?>" class="btn btn-elimina btn-flex-1" onclick="return confirm('Eliminare?')">Elimina</a>
                            <a href="gesttratt.php?arnia_id=<?php echo $attivita_modifica['IA_CodAr']; ?>&tab=attivita" class="btn btn-annulla btn-flex-1">Annulla</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>
    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentArniaId = <?php echo $preselected_arnia_id_url ?? 'null'; ?>;
const TREATMENT_IDS = '<?php echo $safe_ids; ?>';

function openTab(evt, tabName) {
    // Nasconde tutti i contenuti
    $('.tab-content').hide();
    $('.tab-link').removeClass('active');
    
    // Mostra quello selezionato
    $('#' + tabName).show();
    $('#link-tab-' + tabName).addClass('active');

    // Se entriamo nello storico, carichiamo i dati
    if (tabName === 'attivita' && currentArniaId) loadTrattamenti(currentArniaId);
}

function loadTrattamenti(id) {
    $.get('../includes/load_trattamenti.php', { arnia_id: id, filter_ids: TREATMENT_IDS }, function(res) {
        $('#trattamenti-list-container').html(res);
    });
}

$(document).ready(function() {
    // Gestione click riga tabella
    $('#arnie-table tbody tr').on('click', function() {
        currentArniaId = $(this).data('arnia-id');
        $('#arnie-table tr').removeClass('selected-row');
        $(this).addClass('selected-row');
        $('#selected-arnia-display').text("Storico: " + $(this).find('td:eq(0)').text() + " - " + $(this).find('td:eq(1)').text());
        openTab(null, 'attivita');
    });

    // Inizializzazione tab corretta all'avvio
    const activeTab = '<?php echo $active_tab; ?>';
    openTab(null, activeTab);
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>