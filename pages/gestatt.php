<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.B.0.7.2 (Logica Temporale Pericolo)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php'; 

$messaggio = "";
$active_tab = $_GET['tab'] ?? 'selezione';
$preselected_arnia_id_url = $_GET['arnia_id'] ?? null;

// --- LOGICA CRUD: ELIMINAZIONE E MODIFICA (Invariate) ---
if (isset($_GET["elimina"]) && isset($_GET["arnia_id"])) {
    $elimina_id = (int)$_GET["elimina"];
    $arnia_id_return = (int)$_GET["arnia_id"];
    $conn->query("DELETE FROM MA_MOVI WHERE MV_Descrizione LIKE '%(IA_ID: $elimina_id)%'");
    $stmt = $conn->prepare("DELETE FROM AT_INSATT WHERE IA_ID = ?");
    if ($stmt) { $stmt->bind_param("i", $elimina_id); $stmt->execute(); }
    header("Location: gestatt.php?status=delete_success&arnia_id=$arnia_id_return&tab=attivita"); exit();
}

if (isset($_POST["conferma_modifica"])) {
    $ia_id = (int)$_POST['ia_id'];
    $nuova_data = $_POST['data'];
    $nuove_note = $_POST['note'];
    $conn->query("UPDATE MA_MOVI SET MV_Data = '$nuova_data', MV_Note = CONCAT('Modifica: ', '$nuove_note') WHERE MV_Descrizione LIKE '%(IA_ID: $ia_id)%'");
    $stmt = $conn->prepare("UPDATE AT_INSATT SET IA_DATA = ?, IA_NOTE = ? WHERE IA_ID = ?");
    $stmt->bind_param("ssi", $nuova_data, $nuove_note, $ia_id);
    $stmt->execute();
    header("Location: gestatt.php?status=update_success&arnia_id=".$_POST['arnia_id_return']."&tab=attivita"); exit();
}

// --- QUERY ARNIE: RECUPERO STATO PERICOLO TEMPORALE ---
// Spiegazione: Ordiniamo per data decrescente (più recente sopra) e prendiamo la prima riga
$sql_arnie = "SELECT a.AR_ID, a.AR_CODICE, a.AR_NOME, 
              (SELECT i.IA_PERI 
               FROM AT_INSATT i 
               WHERE i.IA_CodAr = a.AR_ID 
               ORDER BY i.IA_DATA DESC, i.IA_ID DESC 
               LIMIT 1) as ULTIMO_PERICOLO
              FROM AP_Arnie a 
              WHERE a.AR_ATTI = 0 
              ORDER BY a.AR_CODICE ASC";
$result_arnie = $conn->query($sql_arnie);

$res_att = $conn->query("SELECT AT_ID, AT_DESCR FROM TA_Attivita ORDER BY AT_DESCR ASC");
$attivita_options = [];
while ($row = $res_att->fetch_assoc()) $attivita_options[] = $row;
?>

<style>
    /* FORZATURA CSS PER SFONDO ROSINO */
    .table-fixed-layout { table-layout: fixed !important; width: 100% !important; border-collapse: collapse; }
    .col-cod { width: 80px !important; text-align: center; }
    
    /* Applichiamo il colore direttamente alle celle TD della riga pericolo */
    tr.riga-pericolo td { 
        background-color: #ffe6e6 !important; 
        color: #b94a48 !important;
        font-weight: 500;
    }
    
    /* Sovrascrittura per riga selezionata che è anche in pericolo */
    tr.riga-pericolo.selected-row td {
        background-color: #ffcccc !important;
        border-top: 2px solid #d9534f !important;
        border-bottom: 2px solid #d9534f !important;
    }
</style>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Gestione Storico Attività</h2>
        
        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>" id="tab-link-selezione" onclick="openTab(event, 'selezione')">Selezione Arnia</li>
                <li class="tab-link <?php echo ($active_tab == 'attivita') ? 'active' : ''; ?>" id="tab-link-attivita" onclick="openTab(event, 'attivita')">Storico Attività</li>
                <li class="tab-link" id="tab-link-edit" style="display:none;" onclick="openTab(event, 'edit-tab')">Modifica</li>
            </ul>

            <div id="selezione" class="tab-content <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>">
                <div class="table-container">
                    <table id="arnie-table" class="selectable-table table-fixed-layout">
                        <thead><tr><th class="col-cod">Codice</th><th>Nome Arnia</th></tr></thead>
                        <tbody>
                            <?php while ($row = $result_arnie->fetch_assoc()): 
                                $is_sel = ($preselected_arnia_id_url == $row["AR_ID"]) ? 'selected-row' : '';
                                // Se l'ultimo IA_PERI è 1, aggiungiamo la classe CSS
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
                <div class="table-container" id="attivita-list-container"></div>
            </div>

            <div id="edit-tab" class="tab-content">
                <div class="form-container" style="border-color: #ffc107;">
                    <form action="gestatt.php" method="POST">
                        <input type="hidden" name="ia_id" id="edit-ia-id">
                        <input type="hidden" name="arnia_id_return" id="edit-arnia-id">
                        <div class="form-group"><label>Data:</label><input type="date" name="data" id="edit-data" required></div>
                        <div class="form-group"><label>Note:</label><textarea name="note" id="edit-note" rows="5"></textarea></div>
                        <div class="btn-group-form" style="display:flex; gap:10px;">
                            <button type="submit" name="conferma_modifica" class="btn btn-salva" style="flex:1;">Salva</button>
                            <button type="button" class="btn btn-annulla" style="flex:1;" onclick="cancelEdit()">Annulla</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentArniaId = <?php echo $preselected_arnia_id_url ?? 'null'; ?>;

window.startEdit = function(id, arnia_id, data, note) {
    $('#edit-ia-id').val(id);
    $('#edit-arnia-id').val(arnia_id);
    $('#edit-data').val(data);
    $('#edit-note').val(note);
    $('#tab-link-edit').show();
    openTab(null, 'edit-tab');
    $('.tab-link').removeClass('active');
    $('#tab-link-edit').addClass('active');
};

function cancelEdit() {
    $('#tab-link-edit').hide();
    openTab(null, 'attivita');
    $('#tab-link-attivita').addClass('active');
}

function openTab(evt, tabName) {
    $('.tab-content').hide();
    $('.tab-link').removeClass('active');
    $('#' + tabName).show();
    if(evt) $(evt.currentTarget).addClass('active');
    if (tabName === 'attivita' && currentArniaId) caricaAttivita(currentArniaId);
}

function caricaAttivita(id) {
    if(!id) return;
    $.get('../includes/load_attivita.php', { arnia_id: id }, function(res) { 
        $('#attivita-list-container').html(res); 
    });
}

$(document).ready(function() {
    $('#arnie-table tbody tr').on('click', function() {
        currentArniaId = $(this).data('arnia-id');
        $('#arnie-table tr').removeClass('selected-row');
        $(this).addClass('selected-row');
        openTab({currentTarget: $('#tab-link-attivita')}, 'attivita');
    });
    if(currentArniaId) caricaAttivita(currentArniaId);
});
</script>