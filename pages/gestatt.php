<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.B.0.8.2 - Hidden Edit Tab Fix');

require_once '../includes/config.php';

// --- 1. LOGICA PHP IN ALTO ---
$messaggio = "";

if (isset($_GET["elimina"]) && isset($_GET["arnia_id"])) {
    $elimina_id = (int)$_GET["elimina"];
    $arnia_id_return = (int)$_GET["arnia_id"];
    $conn->query("DELETE FROM MA_MOVI WHERE MV_Descrizione LIKE '%(IA_ID: $elimina_id)%'");
    $stmt = $conn->prepare("DELETE FROM AT_INSATT WHERE IA_ID = ?");
    if ($stmt) { $stmt->bind_param("i", $elimina_id); $stmt->execute(); }
    header("Location: gestatt.php?status=deleted&arnia_id=$arnia_id_return&tab=attivita"); 
    exit();
}

if (isset($_POST["conferma_modifica"])) {
    $ia_id = (int)$_POST['ia_id'];
    $nuova_data = $_POST['data'];
    $nuove_note = $_POST['note'];
    $arnia_id_return = (int)$_POST['arnia_id_return'];
    $nuovo_valore = str_replace(',', '.', $_POST['valore'] ?? '1.00');
    
    $peri = isset($_POST["peri"]) ? 1 : 0; 
    $vreg = isset($_POST["vreg"]) ? 1 : 0; 
    $op1 = isset($_POST["op1"]) ? 1 : 0; 
    $op2 = isset($_POST["op2"]) ? 1 : 0; 

    $conn->query("UPDATE MA_MOVI SET MV_Data = '$nuova_data', MV_Scarico = '$nuovo_valore', MV_Note = CONCAT('Modifica: ', '$nuove_note') WHERE MV_Descrizione LIKE '%(IA_ID: $ia_id)%'");
    
    $stmt = $conn->prepare("UPDATE AT_INSATT SET IA_DATA = ?, IA_NOTE = ?, IA_PERI = ?, IA_VREG = ?, IA_OP1 = ?, IA_OP2 = ? WHERE IA_ID = ?");
    if ($stmt) {
        $stmt->bind_param("ssiiiii", $nuova_data, $nuove_note, $peri, $vreg, $op1, $op2, $ia_id);
        $stmt->execute();
    }
    header("Location: gestatt.php?status=updated&arnia_id=$arnia_id_return&tab=attivita");
    exit();
}

require_once TPL_PATH . 'header.php'; 

$active_tab = $_GET['tab'] ?? 'selezione';
$preselected_arnia_id_url = $_GET['arnia_id'] ?? null;

if (isset($_GET['status'])) {
    $st = $_GET['status'];
    if ($st == 'updated') $messaggio = "<p class='successo'>Attività aggiornata!</p>";
    if ($st == 'deleted') $messaggio = "<p class='successo txt-danger font-bold'>Attività eliminata!</p>";
}

$sql_arnie = "SELECT a.AR_ID, a.AR_CODICE, a.AR_NOME, 
              (SELECT i.IA_PERI FROM AT_INSATT i WHERE i.IA_CodAr = a.AR_ID ORDER BY i.IA_DATA DESC, i.IA_ID DESC LIMIT 1) as ULTIMO_PERICOLO
              FROM AP_Arnie a WHERE a.AR_ATTI = 0 ORDER BY a.AR_CODICE ASC";
$result_arnie = $conn->query($sql_arnie);
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2 class="titolo-arnie">Gestione Storico Attività</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>" id="tab-link-selezione" onclick="openTab(event, 'selezione')">SELEZIONE ARNIA</li>
                <li class="tab-link <?php echo ($active_tab == 'attivita') ? 'active' : ''; ?>" id="tab-link-attivita" onclick="openTab(event, 'attivita')">STORICO ATTIVITÀ</li>
                <li class="tab-link hidden" id="tab-link-edit" onclick="openTab(event, 'edit-tab')">MODIFICA</li>
            </ul>

            <div id="selezione" class="tab-content <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>">
                <div class="table-container">
                    <table id="arnie-table" class="selectable-table">
                        <thead>
                            <tr><th class="txt-center">CODICE</th><th>NOME ARNIA</th></tr>
                        </thead>
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

            <div id="attivita" class="tab-content <?php echo ($active_tab == 'attivita') ? 'active' : ''; ?>">
                <div class="form-container">
                    <p id="selected-arnia-display" class="font-bold txt-center">
                        <?php if ($preselected_arnia_id_url) echo "Caricamento storico..."; else echo "Seleziona un'arnia dalla scheda precedente."; ?>
                    </p>
                </div>
                <div class="table-container" id="attivita-list-container">
                    <p class="txt-muted txt-center">In attesa di selezione...</p>
                </div>
            </div>

            <div id="edit-tab" class="tab-content">
                <div class="form-container">
                    <h3 class="font-bold">Modifica Attività</h3>
                    <form action="gestatt.php" method="post">
                        <input type="hidden" name="ia_id" id="edit-ia-id">
                        <input type="hidden" name="arnia_id_return" id="edit-arnia-id">
                        
                        <div class="btn-group-flex">
                            <div class="form-group btn-flex-1">
                                <label>Data:</label>
                                <input type="date" name="data" id="edit-data" required>
                            </div>
                            <div class="form-group btn-flex-1">
                                <label>Scarico Magazzino:</label>
                                <input type="number" step="0.01" name="valore" id="edit-valore" required>
                            </div>
                        </div>

                        <div class="btn-group-flex">
                            <label class="btn-flex-1 txt-center"><input type="checkbox" name="peri" id="edit-peri"> Pericolo</label>
                            <label class="btn-flex-1 txt-center"><input type="checkbox" name="vreg" id="edit-vreg"> Regina</label>
                            <label class="btn-flex-1 txt-center"><input type="checkbox" name="op1" id="edit-op1"> Op.1</label>
                            <label class="btn-flex-1 txt-center"><input type="checkbox" name="op2" id="edit-op2"> Op.2</label>
                        </div>

                        <div class="form-group">
                            <label>Note:</label>
                            <textarea name="note" id="edit-note" rows="3"></textarea>
                        </div>

                        <div class="btn-group-flex">
                            <button type="submit" name="conferma_modifica" class="btn btn-salva btn-flex-2">Salva Modifica</button>
                            <button type="button" onclick="cancelEdit()" class="btn btn-annulla btn-flex-1">Annulla</button>
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

window.startEdit = function(id, arnia_id, data, note, valore, peri, vreg, op1, op2) {
    $('#edit-ia-id').val(id);
    $('#edit-arnia-id').val(arnia_id);
    $('#edit-data').val(data);
    $('#edit-note').val(note);
    $('#edit-valore').val(valore || '1.00');
    
    $('#edit-peri').prop('checked', peri == 1);
    $('#edit-vreg').prop('checked', vreg == 1);
    $('#edit-op1').prop('checked', op1 == 1);
    $('#edit-op2').prop('checked', op2 == 1);

    // Mostra la tab e la seleziona
    $('#tab-link-edit').removeClass('hidden').show();
    openTab(null, 'edit-tab');
};

function cancelEdit() {
    $('#tab-link-edit').addClass('hidden').hide();
    openTab(null, 'attivita');
}

function openTab(evt, tabName) {
    $('.tab-content').hide().removeClass('active');
    $('.tab-link').removeClass('active');
    
    $('#' + tabName).show().addClass('active');
    
    if(evt) {
        $(evt.currentTarget).addClass('active');
    } else {
        $('#tab-link-' + tabName).addClass('active');
    }
    
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
        $('.selectable-table tr').removeClass('selected-row');
        $(this).addClass('selected-row');
        $('#selected-arnia-display').text("Storico per: " + $(this).find('td:eq(0)').text() + " - " + $(this).find('td:eq(1)').text());
        openTab(null, 'attivita');
    });

    if (currentArniaId) {
        openTab(null, '<?php echo $active_tab; ?>');
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>