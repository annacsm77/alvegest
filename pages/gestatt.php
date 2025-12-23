<?php
require_once '../includes/config.php';
require_once '../includes/header.php'; 

// Versione del file (per il debug)
$versione = "B.0.5.2"; // Ripristinati percorsi corretti per root/includes/

// Funzione helper per reindirizzare e terminare
function redirect($url) {
    // Uso la funzione url() da config.php per costruire l'URL corretto
    header("Location: " . url($url));
    exit();
}

$messaggio = "";

// --- GESTIONE DELLE OPERAZIONI CRUD ---
if (isset($_GET["elimina"]) && isset($_GET["arnia_id"])) {
    // Gestione dell'eliminazione (GET)
    $elimina_id = (int)$_GET["elimina"];
    $arnia_selezionata_id = (int)$_GET["arnia_id"]; // Per tornare all'arnia corretta

    // 1. ELIMINAZIONE SINCRONIZZATA MOVIMENTO MAGAZZINO
    // Cerchiamo il movimento che contiene l'ID dell'attività nella descrizione
    $causale_ricerca = "%(IA_ID: $elimina_id)%";
    $sql_mag = "DELETE FROM MA_MOVI WHERE MV_Descrizione LIKE ?";
    $stmt_mag = $conn->prepare($sql_mag);
    if ($stmt_mag) {
        $stmt_mag->bind_param("s", $causale_ricerca);
        $stmt_mag->execute();
        $stmt_mag->close();
    }

    // 2. ELIMINAZIONE ATTIVITÀ (AT_INSATT)
    $sql = "DELETE FROM AT_INSATT WHERE IA_ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $elimina_id);

        if ($stmt->execute()) {
            // Torna al tab 'attivita' - CORRETTO: gestatt.php è già in pages/
            redirect("pages/gestatt.php?status=delete_success&arnia_id=" . $arnia_selezionata_id . "&tab=attivita");
        } else {
            $messaggio = "<p class='errore'>Errore durante l'eliminazione: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        $messaggio = "<p class='errore'>Errore nella preparazione della query DELETE: " . $conn->error . "</p>";
    }
} 
elseif (isset($_POST["update_attivita"])) {
    // Gestione della modifica (POST)
    $ia_id = $_POST["ia_id"];
    $arnia_id_return = $_POST["arnia_id_return"];
    $data_input = $_POST["data"];
    $tipo = $_POST["tipo"];
    $note = $_POST["note"];
    $peri = isset($_POST["peri"]) ? 1 : 0;
    $vreg = isset($_POST["vreg"]) ? 1 : 0;
    $op1 = isset($_POST["op1"]) ? 1 : 0;
    $op2 = isset($_POST["op2"]) ? 1 : 0;

    if (empty($data_input)) {
        $messaggio = "<p class='errore'>La data non può essere vuota.</p>";
        goto fine_post;
    }

    $sql = "UPDATE AT_INSATT SET IA_DATA = ?, IA_ATT = ?, IA_NOTE = ?, IA_PERI = ?, IA_VREG = ?, IA_OP1 = ?, IA_OP2 = ? WHERE IA_ID = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sisiiiii", $data_input, $tipo, $note, $peri, $vreg, $op1, $op2, $ia_id);
        if ($stmt->execute()) {
            // Logica Sincronizzazione Trattamenti 
            $sql_check_trat = "SELECT AT_TRAT FROM TA_Attivita WHERE AT_ID = ?";
            $stmt_check = $conn->prepare($sql_check_trat);
            $stmt_check->bind_param("i", $tipo);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result()->fetch_assoc();
            
            if ($res_check && $res_check['AT_TRAT'] == 1) {
                $sql_up_scad = "UPDATE TR_SCAD SET SC_DINIZIO = ? WHERE SC_ARNIA = ? AND SC_TATT = ? AND SC_CHIUSO = 0";
                $stmt_up = $conn->prepare($sql_up_scad);
                $stmt_up->bind_param("sii", $data_input, $arnia_id_return, $tipo);
                $stmt_up->execute();
                $stmt_up->close();
            }
            $stmt_check->close();

            redirect("pages/gestatt.php?status=update_success&arnia_id=" . $arnia_id_return . "&tab=attivita");
        }
        $stmt->close();
    }
}

// --- GESTIONE MESSAGGI POST-OPERAZIONE ---
if (isset($_GET["status"])) {
    $status = $_GET["status"];
    if ($status == "delete_success") $messaggio = "<p class='successo'>Attività eliminata con successo!</p>";
    elseif ($status == "update_success") $messaggio = "<p class='successo'>Attività modificata con successo!</p>";
}

fine_post:

// --- RECUPERO DATI PER MODIFICA E TAB ---
$modifica_id = $_GET["modifica"] ?? null;
$attivita_modifica = null;
$preselected_arnia_id_url = $_GET['arnia_id'] ?? null;
$active_tab = $_GET['tab'] ?? 'selezione';

if ($modifica_id) {
    $stmt = $conn->prepare("SELECT * FROM AT_INSATT WHERE IA_ID = ?");
    $stmt->bind_param("i", $modifica_id);
    $stmt->execute();
    $attivita_modifica = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $active_tab = 'attivita';
    if ($attivita_modifica) {
        $preselected_arnia_id_url = $attivita_modifica['IA_CodAr'];
        $attivita_modifica["IA_DATA_FORMATTATA"] = $attivita_modifica["IA_DATA"];
    }
}

$attivita_options = [];
$res_att = $conn->query("SELECT AT_ID, AT_DESCR FROM TA_Attivita ORDER BY AT_DESCR ASC");
while ($row = $res_att->fetch_assoc()) $attivita_options[] = $row;

$result_arnie = $conn->query("SELECT AR_ID, AR_CODICE, AR_NOME FROM AP_Arnie WHERE AR_ATTI = 0 ORDER BY AR_CODICE ASC");
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column" style="padding: 5px;">
        <h2 style="margin-top: 15px; margin-bottom: 15px;">Gestione Storico Attività <small>(V.<?php echo $versione; ?>)</small></h2>
        <?php echo $messaggio; ?>

        <?php if ($modifica_id && $attivita_modifica): ?>
        <div class="form-container" style="margin-bottom: 10px; border: 2px solid #008CBA;">
            <h3>Modifica Attività ID: <?php echo $modifica_id; ?></h3>
            <p style="margin-top: 5px; margin-bottom: 5px;">Arnia ID: **<?php echo $attivita_modifica['IA_CodAr']; ?>**</p>
            <form action="<?php echo url('pages/gestatt.php'); ?>" method="post">
                <input type="hidden" name="ia_id" value="<?php echo $modifica_id; ?>">
                <input type="hidden" name="arnia_id_return" value="<?php echo $attivita_modifica['IA_CodAr']; ?>"> 
                <div class="form-group-flex"> 
                    <div class="form-group"><label>Data:</label><input type="date" name="data" value="<?php echo $attivita_modifica['IA_DATA_FORMATTATA']; ?>" required></div>
                    <div class="form-group"><label>Tipo:</label>
                        <select name="tipo" class="form-control" required>
                            <?php foreach ($attivita_options as $att): ?>
                                <option value="<?php echo $att['AT_ID']; ?>" <?php echo ($attivita_modifica["IA_ATT"] == $att['AT_ID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($att['AT_DESCR']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group-checkboxes"><label>Opzioni:</label>
                    <div style="display: flex; gap: 20px;">
                        <div class="checkbox-group"><input type="checkbox" name="peri" <?php echo $attivita_modifica["IA_PERI"] ? 'checked' : ''; ?>><label>Pericolo</label></div>
                        <div class="checkbox-group"><input type="checkbox" name="vreg" <?php echo $attivita_modifica["IA_VREG"] ? 'checked' : ''; ?>><label>Visita Regina</label></div>
                        <div class="checkbox-group"><input type="checkbox" name="op1" <?php echo $attivita_modifica["IA_OP1"] ? 'checked' : ''; ?>><label>Opzione 1</label></div>
                        <div class="checkbox-group"><input type="checkbox" name="op2" <?php echo $attivita_modifica["IA_OP2"] ? 'checked' : ''; ?>><label>Opzione 2</label></div>
                    </div>
                </div>
                <div class="form-group"><label>Note:</label><textarea name="note"><?php echo htmlspecialchars($attivita_modifica["IA_NOTE"]); ?></textarea></div>
                <div class="form-group">
                    <button type="submit" name="update_attivita" class="btn btn-modifica btn-grande">Salva Modifica</button>
                    <a href="<?php echo url('pages/gestatt.php?arnia_id=' . $attivita_modifica['IA_CodAr'] . '&tab=attivita'); ?>" class="btn btn-elimina btn-grande">Annulla</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="tab-nav" style="margin-top: 15px;">
            <button class="tab-button" id="tab-selezione" onclick="openTab(event, 'selezione')">SELEZIONE ARNIA</button>
            <button class="tab-button" id="tab-attivita" onclick="openTab(event, 'attivita')">STORICO ATTIVITÀ</button>
        </div>

        <div id="selezione" class="tab-content-item">
            <div class="table-container" id="arnia-list-container" style="max-height: 50vh; overflow-y: auto; border: 1px solid #ddd; margin-top: 5px;">
                <table id="arnie-table" class="selectable-table">
                    <thead><tr><th style='width: 20%;'>Codice</th><th>Nome Arnia</th></tr></thead>
                    <tbody>
                        <?php while ($row = $result_arnie->fetch_assoc()): 
                            $selected = ($preselected_arnia_id_url == $row["AR_ID"]) ? 'selected-row' : '';
                        ?>
                            <tr data-arnia-id="<?php echo $row["AR_ID"]; ?>" data-arnia-codnome="<?php echo htmlspecialchars($row["AR_CODICE"] . ' - ' . $row["AR_NOME"]); ?>" class="<?php echo $selected; ?>">
                                <td><?php echo $row["AR_CODICE"]; ?></td><td><?php echo htmlspecialchars($row["AR_NOME"]); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="attivita" class="tab-content-item">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; margin-bottom: 5px;">
                <p style="margin: 0; font-weight: bold;" id="selected-arnia-display">
                    <?php if ($preselected_arnia_id_url) {
                        $name_res = $conn->query("SELECT AR_CODICE, AR_NOME FROM AP_Arnie WHERE AR_ID = $preselected_arnia_id_url")->fetch_assoc();
                        if ($name_res) echo "Storico Attività per Arnia: <span class='red-bold'>" . htmlspecialchars($name_res['AR_CODICE'] . ' - ' . $name_res['AR_NOME']) . "</span>";
                    } else { echo "Seleziona un'arnia per visualizzare le attività."; } ?>
                </p>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <select id="filtro_attivita" class="form-control" style="width: auto; padding: 5px;">
                        <option value="">Tutte le Tipologie</option>
                        <?php foreach ($attivita_options as $att): ?>
                            <option value="<?php echo $att['AT_ID']; ?>"><?php echo htmlspecialchars($att['AT_DESCR']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="reset_filter_btn" class="btn btn-elimina" style="padding: 8px 15px;">Elimina Filtro</button>
                </div>
            </div>
            <div class="table-container" id="attivita-list-container" style="max-height: 50vh; overflow-y: auto; border: 1px solid #ddd;">
                <p id="initial-load-text"><?php echo ($preselected_arnia_id_url && $active_tab == 'attivita') ? 'Caricamento attività...' : "Seleziona un'arnia per visualizzare le attività."; ?></p>
            </div>
        </div>
    </div>
    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentArniaId = <?php echo $preselected_arnia_id_url ?? 'null'; ?>;
let currentArniaCodNome = '';

function openTab(evt, tabName) {
    $('.tab-content-item').removeClass('active'); $('.tab-button').removeClass('active');
    $('#' + tabName).addClass('active'); $(evt.currentTarget).addClass('active');
    if (tabName === 'attivita' && currentArniaId) loadAttivita(currentArniaId, currentArniaCodNome, $('#filtro_attivita').val());
}

function loadAttivita(arniaId, arniaCodiceNome, tipoId = null) {
    if (!arniaId) return;
    currentArniaCodNome = arniaCodiceNome;
    $('#arnie-table tbody tr').removeClass('selected-row'); 
    $(`#arnie-table tbody tr[data-arnia-id="${arniaId}"]`).addClass('selected-row');
    
    // CORRETTO: Punta a ../includes/load_attivita.php per uscire da pages/
    if ($('#attivita').hasClass('active') || '<?php echo $active_tab; ?>' === 'attivita') {
        $('#attivita-list-container').html('<p>Caricamento in corso...</p>');
        $.get('../includes/load_attivita.php', { arnia_id: arniaId, tipo_id: tipoId }, function(res) { 
            $('#attivita-list-container').html(res); 
        });
    }
}

$(document).ready(function() {
    $('head').append('<style>.selectable-table tbody tr:hover { background-color: #f5f5f5; cursor: pointer; } .selected-row { background-color: #cceeff !important; font-weight: bold; } .red-bold { color: red; font-weight: bold; } .tab-nav { display: flex; margin: 5px 0; border-bottom: 2px solid #ccc; } .tab-button { flex: 1; padding: 10px 5px; text-align: center; font-size: 16px; font-weight: bold; cursor: pointer; border: none; background-color: #f0f0f0; border-radius: 5px 5px 0 0; color: #333; } .tab-button.active { background-color: #008CBA; color: white; } .tab-content-item { display: none; padding: 5px 0; } .tab-content-item.active { display: block; }</style>');
    
    $('#arnie-table tbody').on('click', 'tr', function() {
        currentArniaId = $(this).data('arnia-id'); 
        currentArniaCodNome = $(this).data('arnia-codnome');
        $('#filtro_attivita').val(""); 
        openTab({currentTarget: $('#tab-attivita')[0]}, 'attivita');
    });

    $('#filtro_attivita').on('change', function() { 
        if (currentArniaId) loadAttivita(currentArniaId, currentArniaCodNome, $(this).val()); 
    });
    
    $('#reset_filter_btn').on('click', function() { 
        $('#filtro_attivita').val(""); 
        if (currentArniaId) loadAttivita(currentArniaId, currentArniaCodNome, null); 
    });
    
    if (currentArniaId) {
        let row = $(`#arnie-table tbody tr[data-arnia-id="${currentArniaId}"]`);
        if (row.length) { 
            currentArniaCodNome = row.data('arnia-codnome'); 
            if ('<?php echo $active_tab; ?>' === 'attivita') loadAttivita(currentArniaId, currentArniaCodNome, null); 
        }
    }
    openTab({currentTarget: $(`#tab-${'<?php echo $active_tab; ?>'}`)[0]}, '<?php echo $active_tab; ?>');
});
</script>
<?php require_once '../includes/footer.php'; ?>