<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.T.1.4 (Final Clean)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php'; 

// --- LOGICA PHP ---
$messaggio = "";
$active_phase_id = $_GET['fase_id'] ?? null; 
$active_tab = $_GET['tab'] ?? 'fasi'; 

// Verifica fase aperta
$fase_aperta_id = null;
$sql_check_aperta = "SELECT TP_ID FROM TR_PFASE WHERE TP_CHIU IS NULL LIMIT 1";
$result_aperta = $conn->query($sql_check_aperta);
if ($result_aperta && $result_aperta->num_rows > 0) {
    $fase_aperta_id = $result_aperta->fetch_assoc()['TP_ID'];
}
$is_fase_aperta = ($fase_aperta_id !== null);

// Gestione Salvataggio Note Modale
if (isset($_POST["modifica_descrizione"])) {
    $mod_id = $_POST['mod_id'];
    $mod_desc = $_POST['mod_descrizione'];
    $stmt = $conn->prepare("UPDATE TR_PFASE SET TP_DESCR = ? WHERE TP_ID = ?");
    $stmt->bind_param("si", $mod_desc, $mod_id);
    if ($stmt->execute()) {
        $messaggio = "<p class='successo'>Note aggiornate con successo!</p>";
    }
}

// Recupero Fasi
$fasi = [];
$res_fasi = $conn->query("SELECT * FROM TR_PFASE ORDER BY TP_DAP DESC");
if ($res_fasi) while ($row = $res_fasi->fetch_assoc()) $fasi[] = $row;

// Selezione automatica fase aperta se nessuna selezionata
if ($is_fase_aperta && $active_phase_id === null) {
    $active_phase_id = $fase_aperta_id;
}
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column"> 
        <h2 class="titolo-arnie">Gestione Fasi Trattamenti</h2>
        <?php echo $messaggio; ?>

        <?php if (!$is_fase_aperta): ?>
        <div class="form-container" style="border-color: #4CAF50;"> 
            <h3>Nuova Fase di Trattamento</h3>
            <form action="fasetratt.php" method="post">
                <input type="hidden" name="inserisci_fase" value="1">
                <div class="filtro-form"> 
                    <input type="date" name="tp_dap" value="<?php echo date('Y-m-d'); ?>" class="campo-ricerca" required>
                    <input type="number" name="tp_stag" value="<?php echo date('Y'); ?>" class="campo-ricerca" required>
                    <button type="submit" class="btn btn-salva">Avvia Nuova Fase</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo ($active_tab == 'fasi') ? 'active' : ''; ?>" id="tab-link-fasi" onclick="openTab(event, 'fasi')">FASI REGISTRATE</li>
                <li class="tab-link <?php echo ($active_tab == 'dettaglio') ? 'active' : ''; ?>" id="tab-link-dettaglio" onclick="openTab(event, 'dettaglio')">ARNIE TRATTATE</li>
                <li class="tab-link <?php echo ($active_tab == 'datratrare') ? 'active' : ''; ?>" id="tab-link-datratrare" onclick="openTab(event, 'datratrare')">DA TRATTARE</li>
            </ul>

            <div id="fasi" class="tab-content <?php echo ($active_tab == 'fasi') ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="selectable-table">
                        <thead>
                            <tr><th>ID</th><th>Stag.</th><th>Apertura</th><th>Stato</th><th>Note</th><th class="action-cell">Azioni</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fasi as $fase): $is_ap = empty($fase['TP_CHIU']); ?>
                            <tr data-fase-id="<?php echo $fase["TP_ID"]; ?>" class="<?php echo ($active_phase_id == $fase['TP_ID']) ? 'selected-row' : ''; ?>">
                                <td><?php echo $fase["TP_ID"]; ?></td>
                                <td><?php echo $fase["TP_STAG"]; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($fase['TP_DAP'])); ?></td>
                                <td><?php echo $is_ap ? '<span style="color:green; font-weight:bold;">APERTA</span>' : 'Chiusa'; ?></td>
                                <td><small><?php echo htmlspecialchars(substr($fase["TP_DESCR"], 0, 30)); ?>...</small></td>
                                <td class="action-cell-4btns">
                                    <button class="btn btn-stampa btn-xs btn-carica-dettaglio" data-id="<?php echo $fase['TP_ID']; ?>">Vedi</button>
                                    <button class="btn btn-modifica btn-xs btn-apri-modale" data-id="<?php echo $fase['TP_ID']; ?>" data-desc="<?php echo htmlspecialchars($fase['TP_DESCR']); ?>">Note</button>
                                    <?php if ($is_ap): ?>
                                        <button class="btn btn-chiudi btn-xs btn-chiudi-fase" data-id="<?php echo $fase['TP_ID']; ?>">Chiudi</button>
                                    <?php endif; ?>
                                    <a href="fasetratt.php?elimina_fase=<?php echo $fase['TP_ID']; ?>" class="btn btn-elimina btn-xs" onclick="event.stopPropagation(); return confirm('Eliminare questa fase?')">Elimina</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="dettaglio" class="tab-content <?php echo ($active_tab == 'dettaglio') ? 'active' : ''; ?>">
                <h3 id="display-fase-trattate">Dettaglio Fase</h3>
                <div class="table-container" id="container-arnie-trattate">
                    <p>Seleziona una fase...</p>
                </div>
            </div>

            <div id="datratrare" class="tab-content <?php echo ($active_tab == 'datratrare') ? 'active' : ''; ?>">
                <h3 id="display-fase-datrattare">Arnie da trattare</h3>
                <div class="table-container" id="container-arnie-datrattare">
                    <p>Caricamento...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="modalNote" class="modal-custom" style="display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; margin:10% auto; padding:20px; width:450px; border-radius:8px; border:2px solid #008CBA;">
        <h3>Note Fase <span id="modale-id-fase"></span></h3>
        <form action="fasetratt.php" method="POST">
            <input type="hidden" name="modifica_descrizione" value="1">
            <input type="hidden" name="mod_id" id="modale-input-id">
            <textarea name="mod_descrizione" id="modale-input-desc" rows="6" style="width:100%; margin-bottom:15px; padding:10px; box-sizing:border-box;"></textarea>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-salva" style="flex:1;">Salva</button>
                <button type="button" class="btn btn-annulla" onclick="$('#modalNote').hide();" style="flex:1;">Annulla</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let idFaseCorrente = <?php echo $active_phase_id ?? 'null'; ?>;
const idFaseAperta = <?php echo $fase_aperta_id ?? 'null'; ?>;

function caricaArnieTrattate(id) {
    if(!id) return;
    idFaseCorrente = id;
    $('#display-fase-trattate').html(`Arnie Trattate - Fase ID: <b>${id}</b>`);
    $.get('../includes/load_fase_dettagli.php', { fase_id: id }, (res) => $('#container-arnie-trattate').html(res));
}

function caricaArnieDaTrattare() {
    const idDaUsare = idFaseCorrente || idFaseAperta;
    if(!idDaUsare) return;
    $('#display-fase-datrattare').html(`Arnie da Trattare - Fase ID: <b>${idDaUsare}</b>`);
    $.get('../includes/load_arnie_datratrare.php', { fase_id: idDaUsare }, (res) => $('#container-arnie-datrattare').html(res));
}

function openTab(evt, tabName) {
    $('.tab-content').hide();
    $('.tab-link').removeClass('active');
    $('#' + tabName).show();
    if(evt) $(evt.currentTarget).addClass('active');
    
    if (tabName === 'dettaglio') caricaArnieTrattate(idFaseCorrente);
    if (tabName === 'datratrare') caricaArnieDaTrattare();
}

$(document).ready(function() {
    $('.btn-apri-modale').on('click', function(e) {
        e.stopPropagation();
        $('#modale-id-fase').text($(this).data('id'));
        $('#modale-input-id').val($(this).data('id'));
        $('#modale-input-desc').val($(this).data('desc'));
        $('#modalNote').show();
    });

    $('.btn-carica-dettaglio, .selectable-table tbody tr').on('click', function(e) {
        e.stopPropagation();
        const id = $(this).data('id') || $(this).data('fase-id');
        caricaArnieTrattate(id);
        openTab({currentTarget: $('#tab-link-dettaglio')}, 'dettaglio');
    });

    if(idFaseCorrente) {
        caricaArnieTrattate(idFaseCorrente);
        if('<?php echo $active_tab; ?>' === 'datratrare') caricaArnieDaTrattare();
    }
});
</script>
<?php require_once TPL_PATH . 'footer.php'; ?>