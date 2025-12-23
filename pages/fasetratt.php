<?php
require_once '../includes/config.php';
require_once '../includes/header.php'; 

// Versione del file
$versione = "T.0.6"; // Aggiunto tab DA TRATTARE

// Funzione helper per reindirizzare e terminare
function redirect($url) {
    header("Location: " . url($url));
    exit();
}

$messaggio = "";
$active_phase_id = null; // ID della fase selezionata (per visualizzare i dettagli figlio)
$active_tab = $_GET['tab'] ?? 'fasi'; // Default: tab fasi

// --- VERIFICA FASE APERTA ---
$fase_aperta_id = null;
$sql_check_aperta = "SELECT TP_ID FROM TR_PFASE WHERE TP_CHIU IS NULL LIMIT 1";
$result_aperta = $conn->query($sql_check_aperta);
if ($result_aperta && $result_aperta->num_rows > 0) {
    $fase_aperta_id = $result_aperta->fetch_assoc()['TP_ID'];
}
$is_fase_aperta = ($fase_aperta_id !== null);

// --- LOGICA CRUD PER LA TABELLA PADRE (TR_PFASE) ---

if (isset($_POST["inserisci_fase"])) {
    // Gestione Inserimento Nuova Fase
    
    // Se c'è già una fase aperta, blocca l'inserimento
    if ($is_fase_aperta) {
        $messaggio = "<p class='errore'>❌ Errore: Non è possibile inserire una nuova fase finché quella ID **{$fase_aperta_id}** non è stata chiusa.</p>";
        goto fine_post;
    }
    
    $tp_dap = $_POST["tp_dap"] ?? null;
    $tp_stag = $_POST["tp_stag"] ?? null; 
    $tp_descr = $_POST["tp_descr"] ?? '';
    
    if (empty($tp_dap) || empty($tp_stag)) {
        $messaggio = "<p class='errore'>Data di apertura e Stagione sono obbligatori.</p>";
    } else {
        $sql = "INSERT INTO TR_PFASE (TP_DAP, TP_STAG, TP_DESCR) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sis", $tp_dap, $tp_stag, $tp_descr);

            if ($stmt->execute()) {
                redirect("pages/fasetratt.php?status=insert_success&tab=fasi");
            } else {
                $messaggio = "<p class='errore'>Errore durante l'inserimento: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            $messaggio = "<p class='errore'>Errore nella preparazione della query INSERT: " . $conn->error . "</p>";
        }
    }
}
elseif (isset($_POST["modifica_descrizione"])) {
    // Gestione Modifica Descrizione (POST)
    $id = $_POST["mod_id"];
    $nuova_descrizione = $_POST["mod_descrizione"] ?? '';

    $sql = "UPDATE TR_PFASE SET TP_DESCR = ? WHERE TP_ID = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("si", $nuova_descrizione, $id);

        if ($stmt->execute()) {
            redirect("pages/fasetratt.php?status=update_desc_success&tab=fasi");
        } else {
            $messaggio = "<p class='errore'>Errore durante la modifica della descrizione: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        $messaggio = "<p class='errore'>Errore nella preparazione della query UPDATE: " . $conn->error . "</p>";
    }
}
elseif (isset($_GET["chiudi_fase"]) && isset($_GET["id"]) && isset($_GET["data_chiusura"])) {
    // Gestione Chiusura Fase (GET)
    $id = $_GET["id"];
    $data_chiusura = $_GET["data_chiusura"];

    $sql = "UPDATE TR_PFASE SET TP_CHIU = ? WHERE TP_ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("si", $data_chiusura, $id);

        if ($stmt->execute()) {
            redirect("pages/fasetratt.php?status=close_success&tab=fasi");
        } else {
            $messaggio = "<p class='errore'>Errore durante la chiusura: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        $messaggio = "<p class='errore'>Errore nella preparazione della query UPDATE: " . $conn->error . "</p>";
    }
}
elseif (isset($_GET["elimina_fase"])) {
    // Gestione Eliminazione Fase Padre (e cancellazione a cascata dei figli)
    $id = $_GET["elimina_fase"];
    
    $sql = "DELETE FROM TR_PFASE WHERE TP_ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            redirect("pages/fasetratt.php?status=delete_fase_success&tab=fasi");
        } else {
            $messaggio = "<p class='errore'>Errore durante l'eliminazione della Fase: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}
elseif (isset($_GET["elimina_movimento"])) {
    // Gestione Eliminazione Movimento Figlio (TR_FFASE)
    $id = $_GET["elimina_movimento"];
    $fase_id_return = $_GET["fase_id_return"]; // Per tornare al dettaglio corretto

    $sql = "DELETE FROM TR_FFASE WHERE TF_ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            redirect("pages/fasetratt.php?status=delete_mov_success&fase_id=" . $fase_id_return . "&tab=dettaglio");
        } else {
            $messaggio = "<p class='errore'>Errore durante l'eliminazione del movimento: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}


// --- GESTIONE MESSAGGI POST-OPERAZIONE ---
if (isset($_GET["status"])) {
    $status = $_GET["status"];
    if ($status == "insert_success") {
        $messaggio = "<p class='successo'>Nuova fase di trattamento inserita con successo!</p>";
    } elseif ($status == "close_success") {
        $messaggio = "<p class='successo'>Fase di trattamento chiusa con successo!</p>";
    } elseif ($status == "delete_fase_success") {
        $messaggio = "<p class='successo'>Fase di trattamento (e tutti i movimenti correlati) eliminata con successo!</p>";
    } elseif ($status == "delete_mov_success") {
        $messaggio = "<p class='successo'>Movimento eliminato con successo!</p>";
    } elseif ($status == "update_desc_success") {
        $messaggio = "<p class='successo'>Descrizione fase modificata con successo!</p>";
    }
}

fine_post:

// Imposta l'ID della fase attiva per il dettaglio (se passato in URL)
$active_phase_id = $_GET['fase_id'] ?? null;

// --- RECUPERO DATI TABELLA PADRE (TR_PFASE) ---
$fasi = [];
$sql_fasi = "SELECT TP_ID, TP_DAP, TP_CHIU, TP_STAG, TP_DESCR FROM TR_PFASE ORDER BY TP_DAP DESC";
$result_fasi = $conn->query($sql_fasi);
if ($result_fasi) {
    while ($row = $result_fasi->fetch_assoc()) {
        $fasi[] = $row;
    }
}

// Se una fase è aperta, di default selezioniamo quell'ID per il dettaglio
if ($is_fase_aperta && $active_phase_id === null) {
    $active_phase_id = $fase_aperta_id;
}

?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column" style="padding: 5px;"> 
        <h2 style="margin-top: 10px; margin-bottom: 5px;">Gestione Fasi Trattamenti <small>(V.<?php echo $versione; ?>)</small></h2>
        <?php echo $messaggio; ?>

        <?php if (!$is_fase_aperta): ?>
        <div class="form-container" style="margin: 10px auto; padding: 10px; border: 2px solid #4CAF50;"> 
            <h3 style="margin: 5px 0;">Nuova Fase di Trattamento</h3>
            
            <form action="<?php echo url('pages/fasetratt.php'); ?>" method="post">
                <input type="hidden" name="inserisci_fase" value="1">
                
                <div class="form-group-flex"> 
                    <div class="form-group" style="margin-bottom: 5px;">
                        <label for="tp_dap">Data Apertura*:</label>
                        <input type="date" id="tp_dap" name="tp_dap" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 5px;">
                        <label for="tp_stag">Stagione (Anno)*:</label>
                        <input type="number" id="tp_stag" name="tp_stag" value="<?php echo date('Y'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 5px;">
                    <label for="tp_descr">Note (max 500 caratteri):</label>
                    <textarea id="tp_descr" name="tp_descr" maxlength="500" style="height: 50px;"></textarea>
                </div>
                
                <div class="form-group" style="text-align: right; margin-bottom: 0;">
                    <button type="submit" class="btn btn-inserisci btn-grande" style="padding: 6px 12px; font-size: 14px;">Avvia Nuova Fase</button>
                </div>
            </form>
        </div>
        <?php else: ?>
             <div class="form-container" style="margin: 10px auto; border: 2px solid orange; padding: 10px; text-align: center;">
                <h3 style="margin-top: 0; color: orange;">Fase di Trattamento in Corso!</h3>
            </div>
        <?php endif; ?>
        
        <div class="tab-nav" style="margin-top: 10px;"> 
            <button class="tab-button" id="tab-fasi" onclick="openTab(event, 'fasi')">FASI REGISTRATE</button>
            <button class="tab-button" id="tab-dettaglio" onclick="openTab(event, 'dettaglio')">ARNIE TRATTATE</button>
            <button class="tab-button" id="tab-datratrare" onclick="openTab(event, 'datratrare')">DA TRATTARE</button> </div>
        <div id="fasi" class="tab-content-item">
            <h3 style="margin-top: 5px; margin-bottom: 5px;">Storico Fasi</h3>
            <div class="table-container" id="fasi-list-container" style="max-height: 50vh; overflow-y: auto; border: 1px solid #ddd;">
                <?php
                if (!empty($fasi)) {
                    echo "<table id='fasi-table' class='selectable-table'>
                            <thead>
                                <tr>
                                    <th style='width: 5%;'>ID</th>
                                    <th style='width: 10%;'>Stag.</th>
                                    <th style='width: 15%;'>Data Ap.</th>
                                    <th style='width: 15%;'>Data Ch.</th>
                                    <th style='width: 30%;'>Descrizione</th>
                                    <th style='width: 25%;'>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>";
                    foreach ($fasi as $fase) {
                        $is_aperta = empty($fase['TP_CHIU']);
                        $status_class = $is_aperta ? 'fase-aperta' : '';
                        $data_apertura = date('d/m/Y', strtotime($fase['TP_DAP']));
                        $data_chiusura = $is_aperta ? '<span style="color: green; font-weight: bold;">APERTA</span>' : date('d/m/Y', strtotime($fase['TP_CHIU']));
                        
                        $selected_class = ($active_phase_id == $fase["TP_ID"]) ? 'selected-row' : $status_class;
                        
                        $descrizione_completa = htmlspecialchars($fase["TP_DESCR"]);
                        $descrizione_troncata = htmlspecialchars(substr($fase["TP_DESCR"], 0, 30)) . (strlen($fase["TP_DESCR"]) > 30 ? "..." : "");

                        echo "<tr data-fase-id='" . $fase["TP_ID"] . "' data-descrizione='" . $descrizione_completa . "' class='" . $selected_class . "'>
                                <td>" . $fase["TP_ID"] . "</td>
                                <td>" . htmlspecialchars($fase["TP_STAG"]) . "</td>
                                <td>" . $data_apertura . "</td>
                                <td>" . $data_chiusura . "</td>
                                <td title='" . $descrizione_completa . "'>" . $descrizione_troncata . "</td>
                                <td class='action-cell-compact' style='display: flex; gap: 3px;'>
                                    <a href='#' data-id='" . $fase['TP_ID'] . "' class='btn btn-modifica btn-xs btn-dettaglio' style='flex: 1;'>Dettaglio</a>
                                    <a href='#' data-id='" . $fase['TP_ID'] . "' data-desc='" . $descrizione_completa . "' class='btn btn-modifica btn-xs btn-modifica-desc' style='flex: 1;'>Modifica Desc.</a>"; 
                        
                        if ($is_aperta) {
                            echo "<a href='#' class='btn btn-elimina btn-xs btn-chiudi-fase' data-id='" . $fase['TP_ID'] . "' style='flex: 1;'>Chiudi</a>";
                        }
                        
                        echo "<a href='fasetratt.php?elimina_fase=" . $fase['TP_ID'] . "&tab=fasi' class='btn btn-elimina btn-xs' style='flex: 1;' onclick='return confirm(\"ATTENZIONE: Eliminare la Fase ID " . $fase['TP_ID'] . " cancellerà TUTTI i movimenti associati. Sei sicuro?\");'>Elimina</a>";
                        
                        echo "</td></tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>Nessuna fase di trattamento registrata.</p>";
                }
                ?>
            </div>
        </div>
        <div id="dettaglio" class="tab-content-item">
            <h3 style="margin-top: 5px; margin-bottom: 5px;">Arnie Trattate</h3>
            <p id="selected-fase-display" style="font-weight: bold; margin-bottom: 5px;">
                <?php echo $active_phase_id ? "Caricamento dettaglio per Fase ID: **" . $active_phase_id . "**" : "Seleziona una fase nel tab 'FASI REGISTRATE' per vedere le arnie trattate."; ?>
            </p>

            <div class="table-container" id="dettaglio-arnie-container" style="max-height: 50vh; overflow-y: auto; border: 1px solid #ddd;">
                <p>Caricamento movimenti...</p>
            </div>
        </div>
        <div id="datratrare" class="tab-content-item">
            <h3 style="margin-top: 5px; margin-bottom: 5px;">Arnie in Attesa di Trattamento</h3>
            <p id="datratrare-phase-display" style="font-weight: bold; margin-bottom: 5px;">
                <?php 
                if ($is_fase_aperta) {
                    echo "Arnie che non hanno ancora completato il trattamento nella Fase aperta (ID: {$fase_aperta_id})";
                } else {
                    echo "Nessuna Fase di Trattamento è attualmente aperta per monitorare le arnie in attesa.";
                }
                ?>
            </p>
            <div class="table-container" id="datratrare-arnie-container" style="max-height: 50vh; overflow-y: auto; border: 1px solid #ddd;">
                <?php if ($is_fase_aperta): ?>
                    <p>Clicca sul tab 'DA TRATTARE' per caricare le arnie...</p>
                <?php else: ?>
                    <p>Apri una Fase di Trattamento per visualizzare le arnie attive.</p>
                <?php endif; ?>
            </div>
        </div>
        </div>

    <div class="right-column"></div>
</main>

<div id="modalModificaDescrizione" class="modal-custom">
    <div class="modal-content-custom">
        <span class="close-button-custom">&times;</span>
        <h3 style="margin-top: 0;">Modifica Descrizione Fase <span id="modale-fase-id"></span></h3>
        <form action="fasetratt.php" method="POST" id="formModificaDescrizione">
            <input type="hidden" name="modifica_descrizione" value="1">
            <input type="hidden" name="mod_id" id="mod_id_fase">
            
            <div class="form-group">
                <label for="mod_descrizione">Nuova Descrizione (max 500 caratteri):</label>
                <textarea id="mod_descrizione" name="mod_descrizione" maxlength="500" rows="4" required style="width: 100%; box-sizing: border-box;"></textarea>
            </div>
            
            <div style="text-align: right;">
                <button type="submit" class="btn btn-modifica btn-grande">Modifica</button>
                <button type="button" class="btn btn-elimina btn-grande close-button-custom">Annulla</button>
            </div>
        </form>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
let currentFaseId = <?php echo $active_phase_id ?? 'null'; ?>;
let activeTab = '<?php echo $active_tab; ?>';
const faseApertaId = <?php echo $fase_aperta_id ?? 'null'; ?>;


// Funzione AJAX per caricare le arnie DA TRATTARE
function loadArnieDaTrattare() {
    if (!faseApertaId) return;

    $('#datratrare-arnie-container').html('<p>Caricamento in corso...</p>');
    
    $.ajax({
        // NOTA: Devi creare il file '../includes/load_arnie_datratrare.php'
        url: '../includes/load_arnie_datratrare.php',
        type: 'GET',
        data: { fase_id: faseApertaId },
        success: function(response) {
            $('#datratrare-arnie-container').html(response);
        },
        error: function(xhr, status, error) {
            $('#datratrare-arnie-container').html('<p style="color: red;">Errore nel caricamento delle arnie da trattare.</p>');
            console.error("Errore AJAX:", status, error, xhr.responseText);
        }
    });
}

// Funzione AJAX per caricare i dettagli delle arnie TRATTATE
function loadFaseDettagli(faseId) {
    if (!faseId) return;

    currentFaseId = faseId;
    $('#selected-fase-display').html(`Arnie trattate per Fase ID: <b>${faseId}</b>`);
    $('#dettaglio-arnie-container').html('<p>Caricamento in corso...</p>');
    
    // Aggiorna la selezione visiva della riga
    $('#fasi-table tbody tr').removeClass('selected-row');
    $(`#fasi-table tbody tr[data-fase-id="${faseId}"]`).addClass('selected-row');

    $.ajax({
        url: '../includes/load_fase_dettagli.php',
        type: 'GET',
        data: { fase_id: faseId, return_fase_id: faseId },
        success: function(response) {
            $('#dettaglio-arnie-container').html(response);
        },
        error: function(xhr, status, error) {
            $('#dettaglio-arnie-container').html('<p style="color: red;">Errore nel caricamento del dettaglio fase.</p>');
            console.error("Errore AJAX:", status, error, xhr.responseText);
        }
    });
}

// Funzione per gestire la navigazione tra le Tab
function openTab(evt, tabName, forceLoad = false) {
    // 1. Nascondi tutti i tab e rimuovi la classe active
    $('.tab-content-item').removeClass('active');
    $('.tab-button').removeClass('active');

    // 2. Mostra il tab corrente e aggiungi la classe active
    $('#' + tabName).addClass('active');
    $(evt.currentTarget).addClass('active');
    activeTab = tabName; 

    // 3. Caricamento condizionale
    if (tabName === 'dettaglio' && currentFaseId !== null) {
        loadFaseDettagli(currentFaseId);
    } else if (tabName === 'datratrare') {
        loadArnieDaTrattare();
    }
}

$(document).ready(function() {
    
    // Stili CSS AGGIUNTIVI per la MODALE e correzione Tab
    $('head').append('<style>.selectable-table tbody tr:hover { background-color: #f5f5f5; cursor: pointer; } .selected-row { background-color: #cceeff !important; font-weight: bold; } .form-group-flex { display: flex; gap: 20px; } .form-group-flex .form-group { flex: 1; } .btn-chiudi-fase { background-color: orange; } .btn-chiudi-fase:hover { background-color: #cc8400; } .fase-aperta { background-color: #ffffe0; } .tab-nav { display: flex; margin: 5px 0; border-bottom: 2px solid #ccc; } .tab-button { flex: 1; padding: 5px 5px; text-align: center; font-size: 14px; font-weight: bold; cursor: pointer; border: none; background-color: #f0f0f0; border-radius: 5px 5px 0 0; transition: background-color 0.2s; margin-right: 2px; color: #333; } .tab-button.active { background-color: #008CBA; color: white; } .tab-content-item { display: none; padding: 5px 0; box-sizing: border-box; } .tab-content-item.active { display: block; } .modal-custom { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); } .modal-content-custom { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; position: relative; border-radius: 8px; } .close-button-custom { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; } .close-button-custom:hover, .close-button-custom:focus { color: black; text-decoration: none; cursor: pointer; }</style>');

    // 1. Gestione del click sulla riga della Fase (Dettaglio)
    $('#fasi-table tbody').on('click', 'tr', function() {
        const faseId = $(this).data('fase-id');
        currentFaseId = faseId; // Imposta la fase corrente
        
        openTab({currentTarget: $('#tab-dettaglio')[0]}, 'dettaglio');
        history.pushState(null, '', `fasetratt.php?fase_id=${faseId}&tab=dettaglio`);
    });
    
    // 1b. Gestione del click esplicito sul bottone Dettaglio
    $('#fasi-table tbody').on('click', '.btn-dettaglio', function(e) {
        e.preventDefault(); 
        e.stopPropagation();
        const faseId = $(this).data('id');
        currentFaseId = faseId;
        openTab({currentTarget: $('#tab-dettaglio')[0]}, 'dettaglio');
        history.pushState(null, '', `fasetratt.php?fase_id=${faseId}&tab=dettaglio`);
    });
    
    // --- NUOVA LOGICA MODALE (PULSANTE) ---
    $('#fasi-table tbody').on('click', '.btn-modifica-desc', function(e) {
        e.preventDefault(); 
        e.stopPropagation();
        const id = $(this).data('id');
        const desc = $(this).data('desc');
        
        $('#mod_id_fase').val(id);
        $('#mod_descrizione').val(desc);
        $('#modale-fase-id').text(id);

        $('#modalModificaDescrizione').css('display', 'block');
    });

    // Chiusura Modale
    $('.close-button-custom').on('click', function() {
        $('#modalModificaDescrizione').css('display', 'none');
    });

    // Chiudi la modale cliccando fuori
    $(window).on('click', function(event) {
        if (event.target.id === 'modalModificaDescrizione') {
            $('#modalModificaDescrizione').css('display', 'none');
        }
    });
    // --- FINE NUOVA LOGICA MODALE ---


    // 2. Gestione del pulsante "Chiudi Fase"
    $('#fasi-table tbody').on('click', '.btn-chiudi-fase', function(e) {
        e.preventDefault();
        e.stopPropagation(); 
        const id = $(this).data('id');
        const today = new Date().toISOString().slice(0, 10); // Data odierna YYYY-MM-DD
        
        if (confirm(`Confermi di voler chiudere la Fase di Trattamento ID ${id} alla data odierna (${today})?`)) {
            window.location.href = `fasetratt.php?chiudi_fase=1&id=${id}&data_chiusura=${today}`;
        }
    });

    // 3. Caricamento Iniziale
    
    const tabToActivate = $(`#tab-${activeTab}`);
    if (tabToActivate.length) {
        openTab({currentTarget: tabToActivate[0]}, activeTab);
    }

    // Se si carica la pagina con un ID specifico o una fase aperta, carichiamo il dettaglio
    if (currentFaseId !== null && activeTab === 'dettaglio') {
        loadFaseDettagli(currentFaseId);
    } else if (faseApertaId !== null && activeTab === 'datratrare') {
         // Carica il tab DA TRATTARE solo se c'è una fase aperta
         loadArnieDaTrattare();
    }
});
</script>

<?php
require_once '../includes/footer.php';
?>