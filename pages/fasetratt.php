<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.T.1.7 - Zero Inline Styles & jQuery Clean');

require_once '../includes/config.php';

// --- 1. LOGICA PHP IN ALTO ---
$messaggio = "";

if (isset($_POST["modifica_descrizione"])) {
    $mod_id = $_POST['mod_id'];
    $mod_desc = $_POST['mod_descrizione'];
    $stmt = $conn->prepare("UPDATE TR_PFASE SET TP_DESCR = ? WHERE TP_ID = ?");
    $stmt->bind_param("si", $mod_desc, $mod_id);
    if ($stmt->execute()) {
        header("Location: fasetratt.php?status=note_success&tab=fasi");
        exit();
    }
}

// Verifica fase aperta
$fase_aperta_id = null;
$sql_check_aperta = "SELECT TP_ID FROM TR_PFASE WHERE TP_CHIU IS NULL LIMIT 1";
$result_aperta = $conn->query($sql_check_aperta);
if ($result_aperta && $result_aperta->num_rows > 0) {
    $fase_aperta_id = $result_aperta->fetch_assoc()['TP_ID'];
}
$is_fase_aperta = ($fase_aperta_id !== null);

require_once TPL_PATH . 'header.php'; 

$active_phase_id = $_GET['fase_id'] ?? null; 
$active_tab = $_GET['tab'] ?? 'fasi'; 

if (isset($_GET['status']) && $_GET['status'] == 'note_success') {
    $messaggio = "<p class='successo'>Note aggiornate correttamente!</p>";
}

// Recupero Fasi
$fasi = [];
$res_fasi = $conn->query("SELECT * FROM TR_PFASE ORDER BY TP_DAP DESC");
if ($res_fasi) while ($row = $res_fasi->fetch_assoc()) $fasi[] = $row;

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
        <div class="form-container"> 
            <h3 class="font-bold">Avvia Nuova Fase di Trattamento</h3>
            <form action="fasetratt.php" method="post">
                <input type="hidden" name="inserisci_fase" value="1">
                <div class="btn-group-flex"> 
                    <input type="date" name="tp_dap" value="<?php echo date('Y-m-d'); ?>" class="btn-flex-1" required>
                    <input type="number" name="tp_stag" value="<?php echo date('Y'); ?>" class="btn-flex-1" required>
                    <button type="submit" class="btn btn-salva btn-flex-2">Avvia Nuova Fase</button>
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
                            <tr>
                                <th class="txt-center">ID</th>
                                <th class="txt-center">STAG.</th>
                                <th>APERTURA</th>
                                <th>STATO</th>
                                <th>NOTE</th>
                                <th class="txt-center">AZIONI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fasi as $fase): $is_ap = empty($fase['TP_CHIU']); ?>
                            <tr data-fase-id="<?php echo $fase["TP_ID"]; ?>" class="<?php echo ($active_phase_id == $fase['TP_ID']) ? 'selected-row' : ''; ?> clickable-row">
                                <td class="txt-center txt-small txt-muted"><?php echo $fase["TP_ID"]; ?></td>
                                <td class="txt-center"><?php echo $fase["TP_STAG"]; ?></td>
                                <td class="font-bold"><?php echo date('d/m/Y', strtotime($fase['TP_DAP'])); ?></td>
                                <td><?php echo $is_ap ? '<span class="txt-success font-bold">APERTA</span>' : 'Chiusa'; ?></td>
                                <td class="txt-small"><?php echo htmlspecialchars(substr($fase["TP_DESCR"], 0, 40)); ?>...</td>
                                <td class="txt-center">
                                    <div class="btn-group-flex">
                                        <button class="btn btn-stampa btn-carica-dettaglio" data-id="<?php echo $fase['TP_ID']; ?>">Vedi</button>
                                        <button class="btn-tabella-modifica btn-apri-modale" data-id="<?php echo $fase['TP_ID']; ?>" data-desc="<?php echo htmlspecialchars($fase['TP_DESCR']); ?>">Note</button>
                                        <?php if ($is_ap): ?>
                                            <button class="btn btn-annulla btn-chiudi-fase" data-id="<?php echo $fase['TP_ID']; ?>">Chiudi</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="dettaglio" class="tab-content <?php echo ($active_tab == 'dettaglio') ? 'active' : ''; ?>">
                <h3 id="display-fase-trattate" class="font-bold">Dettaglio Fase</h3>
                <div class="table-container" id="container-arnie-trattate">
                    <p class="txt-muted">Seleziona una fase dalla lista...</p>
                </div>
            </div>

            <div id="datratrare" class="tab-content <?php echo ($active_tab == 'datratrare') ? 'active' : ''; ?>">
                <h3 id="display-fase-datrattare" class="font-bold">Arnie da trattare</h3>
                <div class="table-container" id="container-arnie-datrattare">
                    <p class="txt-muted">Caricamento in corso...</p>
                </div>
            </div>
        </div>
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>
</main>

<div id="modalNote" class="modal-custom">
    <div class="modal-content-small">
        <h3 class="font-bold">Note Fase <span id="modale-id-fase"></span></h3>
        <form action="fasetratt.php" method="POST">
            <input type="hidden" name="modifica_descrizione" value="1">
            <input type="hidden" name="mod_id" id="modale-input-id">
            <textarea name="mod_descrizione" id="modale-input-desc" rows="6" class="full-width"></textarea>
            <div class="btn-group-flex">
                <button type="submit" class="btn btn-salva btn-flex-2">Salva</button>
                <button type="button" class="btn btn-annulla btn-flex-1" onclick="$('#modalNote').hide();">Annulla</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let idFaseCorrente = <?php echo $active_phase_id ?? 'null'; ?>;

function openTab(evt, tabName) {
    $('.tab-content').hide().removeClass('active');
    $('.tab-link').removeClass('active');
    $('#' + tabName).show().addClass('active');
    if(evt) $(evt.currentTarget).addClass('active');
    if (tabName === 'dettaglio') caricaArnieTrattate(idFaseCorrente);
    if (tabName === 'datratrare') caricaArnieDaTrattare();
}

function caricaArnieTrattate(id) {
    if(!id) return;
    idFaseCorrente = id;
    $.get('../includes/load_fase_dettagli.php', { fase_id: id }, (res) => $('#container-arnie-trattate').html(res));
}

function caricaArnieDaTrattare() {
    const idDaUsare = idFaseCorrente || <?php echo $fase_aperta_id ?? 'null'; ?>;
    if(!idDaUsare) return;
    $.get('../includes/load_arnie_datratrare.php', { fase_id: idDaUsare }, (res) => $('#container-arnie-datrattare').html(res));
}

$(document).ready(function() {
    // Apertura Modale
    $('.btn-apri-modale').on('click', function(e) {
        e.stopPropagation();
        $('#modale-id-fase').text($(this).data('id'));
        $('#modale-input-id').val($(this).data('id'));
        $('#modale-input-desc').val($(this).data('desc'));
        $('#modalNote').show(); 
    });

    // Selezione Riga / Caricamento Dettagli
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