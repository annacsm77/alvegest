<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'T.0.9 (Fix Colonne e Titolo Pulito)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php'; 

$messaggio = "";
$active_tab = $_GET['tab'] ?? 'selezione';
$preselected_arnia_id_url = $_GET['arnia_id'] ?? null;

// --- 1. LOGICA CRUD: ELIMINAZIONE SINCRONIZZATA ---
if (isset($_GET["elimina"]) && isset($_GET["arnia_id"])) {
    $elimina_id = (int)$_GET["elimina"];
    $arnia_id_return = (int)$_GET["arnia_id"];

    // Elimina movimento magazzino collegato
    $causale_ricerca = "%(IA_ID: $elimina_id)%";
    $conn->query("DELETE FROM MA_MOVI WHERE MV_Descrizione LIKE '$causale_ricerca'");

    // Elimina attività
    $stmt = $conn->prepare("DELETE FROM AT_INSATT WHERE IA_ID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $elimina_id);
        if ($stmt->execute()) {
            header("Location: gesttratt.php?status=delete_success&arnia_id=$arnia_id_return&tab=attivita");
            exit();
        }
    }
}

// --- 2. LOGICA CRUD: SALVATAGGIO MODIFICA CON SINCRONIZZAZIONE VALORE ---
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

    // Aggiornamento Magazzino (MV_Scarico)
    $causale_ricerca = "%(IA_ID: $ia_id)%";
    $sql_mag = "UPDATE MA_MOVI SET MV_Data = ?, MV_Scarico = ?, MV_Note = CONCAT('Trattamento: ', ?) WHERE MV_Descrizione LIKE ?";
    $stmt_mag = $conn->prepare($sql_mag);
    if ($stmt_mag) {
        $stmt_mag->bind_param("sdss", $data_input, $nuovo_valore, $note, $causale_ricerca);
        $stmt_mag->execute();
    }

    // Aggiornamento Registro Attività
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

// --- 3. RECUPERO CONFIGURAZIONI E LISTE ---
$sql_filter = "SELECT CF_VAL FROM CF_GLOB WHERE CF_DATO = 'TIP_TRAT'";
$res_f = $conn->query($sql_filter);
$safe_ids = ($res_f && $row = $res_f->fetch_assoc()) ? preg_replace('/[^0-9,]+/', '', $row['CF_VAL']) : "0";

// Query Arnie con stato Pericolo (Rosino)
$sql_arnie = "SELECT a.AR_ID, a.AR_CODICE, a.AR_NOME, 
              (SELECT i.IA_PERI FROM AT_INSATT i WHERE i.IA_CodAr = a.AR_ID ORDER BY i.IA_DATA DESC, i.IA_ID DESC LIMIT 1) as ULTIMO_PERICOLO
              FROM AP_Arnie a WHERE a.AR_ATTI = 0 ORDER BY a.AR_CODICE ASC";
$result_arnie = $conn->query($sql_arnie);

// Recupero dati per eventuale form modifica
$modifica_id = $_GET["modifica"] ?? null;
$attivita_modifica = null;
if ($modifica_id) {
    $stmt = $conn->prepare("SELECT i.*, m.MV_Scarico FROM AT_INSATT i LEFT JOIN MA_MOVI m ON m.MV_Descrizione LIKE CONCAT('%(IA_ID: ', i.IA_ID, ')%') WHERE i.IA_ID = ?");
    $stmt->bind_param("i", $modifica_id);
    $stmt->execute();
    $attivita_modifica = $stmt->get_result()->fetch_assoc();
    if ($attivita_modifica) $preselected_arnia_id_url = $attivita_modifica['IA_CodAr'];
}
?>

<style>
    /* FORZATURA CSS PER SPAZIATURA E COLORE (Preso da gestatt.php) */
    .table-fixed-layout { table-layout: fixed !important; width: 100% !important; border-collapse: collapse; }
    .col-cod { width: 80px !important; text-align: center; }
    
    tr.riga-pericolo td { 
        background-color: #ffe6e6 !important; 
        color: #b94a48 !important;
        font-weight: 500;
    }
    
    tr.riga-pericolo.selected-row td {
        background-color: #ffcccc !important;
        border-top: 2px solid #d9534f !important;
        border-bottom: 2px solid #d9534f !important;
    }
</style>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2>Storico Trattamenti</h2>
        
        <?php if (isset($_GET["status"])): ?>
            <p class="successo">Operazione completata con successo!</p>
        <?php endif; ?>

        <?php if ($modifica_id && $attivita_modifica): ?>
        <div class="form-container" style="border-color: #ffc107; margin-bottom: 20px;">
            <h3>Modifica Trattamento ID: <?php echo $modifica_id; ?></h3>
            <form action="gesttratt.php" method="post">
                <input type="hidden" name="ia_id" value="<?php echo $modifica_id; ?>">
                <input type="hidden" name="arnia_id_return" value="<?php echo $attivita_modifica['IA_CodAr']; ?>">
                <input type="hidden" name="update_attivita" value="1">

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Data:</label>
                        <input type="date" name="data" value="<?php echo $attivita_modifica['IA_DATA']; ?>" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Valore Scarico:</label>
                        <input type="number" step="0.01" name="valore" value="<?php echo $attivita_modifica['MV_Scarico'] ?? 1.00; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tipo Trattamento:</label>
                    <select name="tipo" required>
                        <?php 
                        $sql_opt = "SELECT AT_ID, AT_DESCR FROM TA_Attivita WHERE AT_ID IN ($safe_ids) ORDER BY AT_DESCR ASC";
                        $res_opt = $conn->query($sql_opt);
                        while($att = $res_opt->fetch_assoc()): ?>
                            <option value="<?php echo $att['AT_ID']; ?>" <?php echo ($attivita_modifica["IA_ATT"] == $att['AT_ID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($att['AT_DESCR']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; background: #eee; padding: 10px; border-radius: 4px;">
                    <label><input type="checkbox" name="peri" <?php echo $attivita_modifica["IA_PERI"] ? 'checked' : ''; ?>> Pericolo</label>
                    <label><input type="checkbox" name="vreg" <?php echo $attivita_modifica["IA_VREG"] ? 'checked' : ''; ?>> Regina</label>
                    <label><input type="checkbox" name="op1" <?php echo $attivita_modifica["IA_OP1"] ? 'checked' : ''; ?>> Op.1</label>
                    <label><input type="checkbox" name="op2" <?php echo $attivita_modifica["IA_OP2"] ? 'checked' : ''; ?>> Op.2</label>
                </div>

                <div class="form-group">
                    <label>Note:</label>
                    <textarea name="note" rows="3"><?php echo htmlspecialchars($attivita_modifica["IA_NOTE"]); ?></textarea>
                </div>

                <div class="btn-group-form" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-salva" style="flex: 1;">Salva Modifica</button>
                    <a href="gesttratt.php?arnia_id=<?php echo $attivita_modifica['IA_CodAr']; ?>&tab=attivita" class="btn btn-annulla" style="flex: 1;">Annulla</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>" onclick="openTab(event, 'selezione')">SELEZIONE ARNIA</li>
                <li class="tab-link <?php echo ($active_tab == 'attivita') ? 'active' : ''; ?>" id="tab-link-attivita" onclick="openTab(event, 'attivita')">STORICO TRATTAMENTI</li>
            </ul>

            <div id="selezione" class="tab-content <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>">
                <div class="table-container">
                    <table id="arnie-table" class="selectable-table table-fixed-layout">
                        <thead>
                            <tr><th class="col-cod">Codice</th><th>Nome Arnia</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_arnie->fetch_assoc()): 
                                $is_sel = ($preselected_arnia_id_url == $row["AR_ID"]) ? 'selected-row' : '';
                                $danger_class = ($row['ULTIMO_PERICOLO'] == 1) ? 'riga-pericolo' : '';
                            ?>
                            <tr data-arnia-id="<?php echo $row["AR_ID"]; ?>" class="<?php echo $is_sel . ' ' . $danger_class; ?>">
                                <td class="col-cod"><strong><?php echo $row["AR_CODICE"]; ?></strong></td>
                                <td><?php echo htmlspecialchars($row["AR_NOME"]); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="attivita" class="tab-content <?php echo ($active_tab == 'attivita') ? 'active' : ''; ?>">
                <p id="selected-arnia-display" style="font-weight: bold; color: #d9534f; margin-bottom: 10px;">
                    <?php if ($preselected_arnia_id_url) echo "Caricamento dati..."; else echo "Seleziona un'arnia per visualizzare i trattamenti."; ?>
                </p>
                <div class="table-container" id="trattamenti-list-container">
                    </div>
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
    $('.tab-content').hide();
    $('.tab-link').removeClass('active');
    $('#' + tabName).show();
    if(evt) $(evt.currentTarget).addClass('active');
    if (tabName === 'attivita' && currentArniaId) loadTrattamenti(currentArniaId);
}

function loadTrattamenti(id) {
    $('#trattamenti-list-container').html('<p>Caricamento...</p>');
    $.get('../includes/load_trattamenti.php', { arnia_id: id, filter_ids: TREATMENT_IDS }, function(res) {
        $('#trattamenti-list-container').html(res);
    });
}

$(document).ready(function() {
    $('#arnie-table tbody tr').on('click', function() {
        currentArniaId = $(this).data('arnia-id');
        $('#arnie-table tr').removeClass('selected-row');
        $(this).addClass('selected-row');
        $('#selected-arnia-display').text("Storico Trattamenti per Arnia: " + $(this).find('td:eq(0)').text() + " - " + $(this).find('td:eq(1)').text());
        openTab({currentTarget: $('#tab-link-attivita')}, 'attivita');
    });

    if (currentArniaId) loadTrattamenti(currentArniaId);
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>