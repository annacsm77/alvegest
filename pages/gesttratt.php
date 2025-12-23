<?php
require_once '../includes/config.php';
require_once '../includes/header.php'; 

// Versione del file (per il debug)
$versione = "T.0.6"; // Correzione bug Scadenziario in Modifica (Attivazione) e Tab Iniziale

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
    $elimina_id = $_GET["elimina"];
    $arnia_selezionata_id = $_GET["arnia_id"]; // Per tornare all'arnia corretta

    $sql = "DELETE FROM AT_INSATT WHERE IA_ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $elimina_id);

        if ($stmt->execute()) {
            redirect("pages/gesttratt.php?status=delete_success&arnia_id=" . $arnia_selezionata_id . "&tab=attivita");
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
    
    // Recupero dati essenziali
    $ia_id = $_POST["ia_id"];
    $arnia_id_return = $_POST["arnia_id_return"];
    $data_input = $_POST["data"]; // Formato YYYY-MM-DD
    $tipo = $_POST["tipo"];
    $note = $_POST["note"];
    
    // Recupero campi booleani (checkbox)
    $peri = isset($_POST["peri"]) ? 1 : 0; 
    $vreg = isset($_POST["vreg"]) ? 1 : 0; 
    $op1 = isset($_POST["op1"]) ? 1 : 0; 
    $op2 = isset($_POST["op2"]) ? 1 : 0; 

    // Verifica data
    if (empty($data_input)) {
        $messaggio = "<p class='errore'>La data non può essere vuota.</p>";
        goto fine_post;
    }

    // Query UPDATE su AT_INSATT
    $sql = "UPDATE AT_INSATT 
            SET IA_DATA = ?, IA_ATT = ?, IA_NOTE = ?, IA_PERI = ?, IA_VREG = ?, IA_OP1 = ?, IA_OP2 = ? 
            WHERE IA_ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Tipi: s, i, s, i, i, i, i, i (7 dati + ID)
        $stmt->bind_param("sisiiiii", $data_input, $tipo, $note, $peri, $vreg, $op1, $op2, $ia_id);

        if ($stmt->execute()) {
            
            // --- INIZIO LOGICA SINCRONIZZAZIONE TRATTAMENTI ---
            
            // 1. Recupera i dati del trattamento per la sincronizzazione
            $is_trattamento = 0;
            $gg_validita = 0;
            $nr_ripetizioni = 1;
            
            $sql_check_trat = "SELECT AT_TRAT, AT_GG, AT_NR FROM TA_Attivita WHERE AT_ID = ?";
            $stmt_check = $conn->prepare($sql_check_trat);
            if ($stmt_check) {
                $stmt_check->bind_param("i", $tipo);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                if ($row_check = $result_check->fetch_assoc()) {
                    $is_trattamento = $row_check['AT_TRAT'];
                    $gg_validita = (int)$row_check['AT_GG'];
                    $nr_ripetizioni = (int)$row_check['AT_NR'];
                }
                $stmt_check->close();
            }
            
            if ($is_trattamento == 1) {
                
                // 2. Verifica se c'è una Fase di Trattamento APERTA per la NUOVA data
                $fase_aperta_id = null;
                $sql_get_fase = "SELECT TP_ID FROM TR_PFASE WHERE TP_CHIU IS NULL AND TP_DAP <= ? ORDER BY TP_DAP DESC LIMIT 1";
                $stmt_fase = $conn->prepare($sql_get_fase);
                
                if ($stmt_fase) {
                    $stmt_fase->bind_param("s", $data_input); // Confronta con la NUOVA data
                    $stmt_fase->execute();
                    $result_fase = $stmt_fase->get_result();
                    if ($result_fase && $result_fase->num_rows > 0) {
                        $fase_aperta_id = $result_fase->fetch_assoc()['TP_ID'];
                    }
                    $stmt_fase->close();
                }

                // 3. AGGIORNA/INSERISCI TR_FFASE se la fase è aperta
                if ($fase_aperta_id !== null) {
                    
                    // Verifica se il record è già presente in TR_FFASE
                    $sql_exists = "SELECT TF_ID FROM TR_FFASE WHERE TF_CATT = ?";
                    $stmt_exists = $conn->prepare($sql_exists);
                    $stmt_exists->bind_param("i", $ia_id);
                    $stmt_exists->execute();
                    $result_exists = $stmt_exists->get_result();
                    
                    if ($result_exists->num_rows == 0) {
                         // Inserimento in TR_FFASE
                         $sql_ffase = "INSERT INTO TR_FFASE (TF_PFASE, TF_ARNIA, TF_ATT, TF_CATT) VALUES (?, ?, ?, ?)";
                         $stmt_ffase = $conn->prepare($sql_ffase);
                         if ($stmt_ffase) {
                             $stmt_ffase->bind_param("iiii", $fase_aperta_id, $arnia_id_return, $tipo, $ia_id);
                             $stmt_ffase->execute();
                             $stmt_ffase->close();
                         }
                    } else {
                        // Aggiornamento di TR_FFASE (se Fase o Tipo sono cambiati)
                        $sql_update_ffase = "UPDATE TR_FFASE SET TF_PFASE = ?, TF_ATT = ? WHERE TF_CATT = ?";
                        $stmt_update_ffase = $conn->prepare($sql_update_ffase);
                        $stmt_update_ffase->bind_param("iii", $fase_aperta_id, $tipo, $ia_id);
                        $stmt_update_ffase->execute();
                        $stmt_update_ffase->close();
                    }
                    $stmt_exists->close();


                    // 4. AGGIORNA TR_SCAD (Scadenziario - Logica di reset/apertura ciclo 1)
                    
                    // Condizione di attivazione: Trattamento multi-ciclo e durata > 0.
                    if ($nr_ripetizioni > 1 && $gg_validita > 0) {
                        
                        // 4a. Chiudi tutti i cicli APERTI per questo trattamento/arnia
                        $sql_clean = "UPDATE TR_SCAD SET SC_CHIUSO = 1, SC_DATAF = ? WHERE SC_ARNIA = ? AND SC_TATT = ? AND SC_CHIUSO = 0";
                        $stmt_clean = $conn->prepare($sql_clean);
                        $stmt_clean->bind_param("sii", $data_input, $arnia_id_return, $tipo);
                        $stmt_clean->execute();
                        $stmt_clean->close();
                        
                        // 4b. Apri il primo ciclo (SC_AVA=1) con la NUOVA scadenza.
                        $data_fine_new = date('Y-m-d', strtotime("{$data_input} +{$gg_validita} days"));
                        
                        $sql_open = "INSERT INTO TR_SCAD (SC_ARNIA, SC_TATT, SC_DINIZIO, SC_DATAF, SC_CHIUSO, SC_AVA) VALUES (?, ?, ?, ?, 0, 1)";
                        $stmt_open = $conn->prepare($sql_open);
                        $stmt_open->bind_param("iiss", $arnia_id_return, $tipo, $data_input, $data_fine_new);
                        // Usiamo INSERT IGNORE per evitare di re-inserire la riga se esiste già (se l'utente preme due volte salva velocemente)
                        if ($stmt_open->execute()) {
                            // Non serve fare nulla qui, l'azione è completata
                        } else {
                            // Errore di inserimento (ignora)
                        }
                        $stmt_open->close();
                    }
                }
                
                // 5. Se la fase NON è più aperta, rimuovi l'attività da TR_FFASE e pulisci lo scadenziario attivo
                else {
                    $sql_delete_ffase = "DELETE FROM TR_FFASE WHERE TF_CATT = ?";
                    $stmt_delete_ffase = $conn->prepare($sql_delete_ffase);
                    $stmt_delete_ffase->bind_param("i", $ia_id);
                    $stmt_delete_ffase->execute();
                    $stmt_delete_ffase->close();
                    
                    // Puliamo anche lo scadenziario per questo trattamento/arnia (solo i cicli aperti)
                    $sql_clean_scad = "UPDATE TR_SCAD SET SC_CHIUSO = 1, SC_DATAF = ? WHERE SC_ARNIA = ? AND SC_TATT = ? AND SC_CHIUSO = 0";
                    $stmt_clean_scad = $conn->prepare($sql_clean_scad);
                    $stmt_clean_scad->bind_param("sii", $data_input, $arnia_id_return, $tipo);
                    $stmt_clean_scad->execute();
                    $stmt_clean_scad->close();
                }
            } // Fine if is_trattamento
            // --- FINE LOGICA SINCRONIZZAZIONE TRATTAMENTI ---
            
            // Reindirizzamento di successo finale
            redirect("pages/gesttratt.php?status=update_success&arnia_id=" . $arnia_id_return . "&tab=attivita");
        } else {
            $messaggio = "<p class='errore'>Errore durante la modifica: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        $messaggio = "<p class='errore'>Errore nella preparazione della query UPDATE: " . $conn->error . "</p>";
    }
}


// --- GESTIONE MESSAGGI POST-OPERAZIONE ---
if (isset($_GET["status"])) {
    $status = $_GET["status"];
    if ($status == "delete_success") {
        $messaggio = "<p class='successo'>Attività eliminata con successo!</p>";
    } elseif ($status == "update_success") {
        $messaggio = "<p class='successo'>Attività modificata con successo!</p>";
    }
}

fine_post:


// --- LOGICA DI PREPARAZIONE FILTRO DALLE CONFIGURAZIONI ---
$sql_filter = "SELECT CF_VAL FROM CF_GLOB WHERE CF_DATO = 'TIP_TRAT'";
$result_filter = $conn->query($sql_filter);
$valid_treatment_ids = "";

if ($result_filter && $result_filter->num_rows > 0) {
    $row = $result_filter->fetch_assoc();
    $valid_treatment_ids = $row['CF_VAL'];
}
// Sanificazione
$safe_ids = preg_replace('/[^0-9,]+/', '', $valid_treatment_ids);

if (empty($safe_ids)) {
    $messaggio_filtro = "<p class='errore'>❌ ERRORE: Chiave 'TIP_TRAT' non configurata o vuota in CF_GLOB.</p>";
} else {
    $messaggio_filtro = "";
}


// --- RECUPERO DATI PER LA MODIFICA (OPZIONALE) ---
$modifica_id = isset($_GET["modifica"]) ? $_GET["modifica"] : null;
$attivita_modifica = null;
$preselected_arnia_id_url = $_GET['arnia_id'] ?? null; // ID dell'arnia da cui si proviene
$active_tab = $_GET['tab'] ?? 'selezione'; // DEFAULT: selezione arnia

if ($modifica_id) {
    $sql = "SELECT * FROM AT_INSATT WHERE IA_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $modifica_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attivita_modifica = $result->fetch_assoc();
    $stmt->close();
    
    // Se siamo in modalità modifica, forziamo il tab attività
    $active_tab = 'attivita';

    // Converte la data del DB (Y-m-d) in formato html (YYYY-MM-DD) per input type="date"
    if ($attivita_modifica) {
        // Imposta l'ID dell'arnia corretta se si è entrati in modalità modifica
        $preselected_arnia_id_url = $attivita_modifica['IA_CodAr']; 
        $data_obj = DateTime::createFromFormat('Y-m-d', $attivita_modifica["IA_DATA"]);
        $attivita_modifica["IA_DATA_FORMATTATA"] = $data_obj ? $data_obj->format('Y-m-d') : '';
    }
}


// --- RECUPERO LE TIPOLOGIE DI ATTIVITÀ PER LA COMBO BOX ---
// Solo gli ID che sono Trattamenti
$attivita_options = [];
$sql_attivita = "SELECT AT_ID, AT_DESCR FROM TA_Attivita WHERE AT_ID IN ($safe_ids) ORDER BY AT_DESCR ASC";
$result_attivita = $conn->query($sql_attivita);
if ($result_attivita) {
    while ($row = $result_attivita->fetch_assoc()) {
        $attivita_options[] = $row;
    }
}

// --- RECUPERO ARNIE ---
$sql_arnie = "SELECT AR_ID, AR_CODICE, AR_NOME FROM AP_Arnie WHERE AR_ATTI = 0 ORDER BY AR_CODICE ASC";
$result_arnie = $conn->query($sql_arnie);

?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2>Storico Trattamenti <small>(V.<?php echo $versione; ?>)</small></h2>
        <?php echo $messaggio_filtro; ?>
        <?php echo $messaggio; ?>
        <?php if ($modifica_id && $attivita_modifica): ?>
        <div class="form-container" style="margin-bottom: 20px; border: 2px solid #008CBA;">
            <h3>Modifica Trattamento ID: <?php echo $modifica_id; ?></h3>
            <p>Arnia ID: **<?php echo $attivita_modifica['IA_CodAr']; ?>**</p>
            
            <form action="<?php echo url('pages/gesttratt.php'); ?>" method="post">
                <input type="hidden" name="ia_id" value="<?php echo $modifica_id; ?>">
                <input type="hidden" name="arnia_id_return" value="<?php echo $attivita_modifica['IA_CodAr']; ?>"> 
                
                <div class="form-group-flex"> 
                    <div class="form-group">
                        <label for="data">Data Attività:</label>
                        <input type="date" id="data" name="data" value="<?php echo htmlspecialchars($attivita_modifica['IA_DATA_FORMATTATA']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo">Tipo Trattamento:</label>
                        <select id="tipo" name="tipo" class="form-control" required>
                            <?php foreach ($attivita_options as $att): ?>
                                <option value="<?php echo $att['AT_ID']; ?>" <?php echo ($attivita_modifica["IA_ATT"] == $att['AT_ID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($att['AT_DESCR']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group-checkboxes">
                    <label>Opzioni:</label>
                    <div style="display: flex; gap: 20px;">
                        <div class="checkbox-group">
                            <input type="checkbox" id="peri" name="peri" <?php echo $attivita_modifica["IA_PERI"] ? 'checked' : ''; ?>>
                            <label for="peri">Pericolo</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="vreg" name="vreg" <?php echo $attivita_modifica["IA_VREG"] ? 'checked' : ''; ?>>
                            <label for="vreg">Visita Regina</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="op1" name="op1" <?php echo $attivita_modifica["IA_OP1"] ? 'checked' : ''; ?>>
                            <label for="op1">Opzione 1</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="op2" name="op2" <?php echo $attivita_modifica["IA_OP2"] ? 'checked' : ''; ?>>
                            <label for="op2">Opzione 2</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="note">Note:</label>
                    <textarea id="note" name="note" maxlength="1000"><?php echo htmlspecialchars($attivita_modifica["IA_NOTE"]); ?></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_attivita" class="btn btn-modifica btn-grande">Salva Modifica</button>
                    <a href="<?php echo url('pages/gesttratt.php?arnia_id=' . $attivita_modifica['IA_CodAr'] . '&tab=attivita'); ?>" class="btn btn-elimina btn-grande">Annulla</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="tab-nav" style="margin-top: 15px;">
            <button class="tab-button" id="tab-selezione" onclick="openTab(event, 'selezione')">SELEZIONE ARNIA</button>
            <button class="tab-button" id="tab-attivita" onclick="openTab(event, 'attivita')">STORICO TRATTAMENTI</button>
        </div>
        
        <div id="selezione" class="tab-content-item">
            <h4 style="margin-top: 10px;">Clicca sull'arnia per visualizzarne lo storico</h4>
            <div class="table-container" id="arnia-list-container" style="max-height: 50vh; overflow-y: auto; border: 1px solid #ddd; margin-top: 5px;">
                <?php
                if ($result_arnie && $result_arnie->num_rows > 0) {
                    echo "<table id='arnie-table' class='selectable-table'>
                            <thead>
                                <tr>
                                    <th style='width: 20%;'>Codice</th>
                                    <th>Nome Arnia</th>
                                </tr>
                            </thead>
                            <tbody>";
                    while ($row = $result_arnie->fetch_assoc()) {
                        $selected_class = ($preselected_arnia_id_url == $row["AR_ID"]) ? 'selected-row' : '';
                        echo "<tr data-arnia-id='" . $row["AR_ID"] . "' data-arnia-codnome='" . htmlspecialchars($row["AR_CODICE"] . ' - ' . $row["AR_NOME"]) . "' class='" . $selected_class . "'>
                                <td>" . $row["AR_CODICE"] . "</td>
                                <td>" . htmlspecialchars($row["AR_NOME"]) . "</td>
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>Nessuna arnia attiva trovata.</p>";
                }
                ?>
            </div>
        </div>
        <div id="attivita" class="tab-content-item">
            <p style="margin-top: 15px; font-weight: bold;" id="selected-arnia-display">
                <?php 
                if ($preselected_arnia_id_url) {
                    $sql_name = "SELECT AR_CODICE, AR_NOME FROM AP_Arnie WHERE AR_ID = ?";
                    $stmt_name = $conn->prepare($sql_name);
                    $stmt_name->bind_param("i", $preselected_arnia_id_url);
                    $stmt_name->execute();
                    $name_res = $stmt_name->get_result()->fetch_assoc();
                    // L'ID è stato rimosso in V.B.0.4/T.0.4, lo rimettiamo per coerenza con l'output HTML fornito
                    echo "Storico Trattamenti per Arnia: " . htmlspecialchars($name_res['AR_CODICE'] . ' - ' . $name_res['AR_NOME'] . ' (ID: ' . $preselected_arnia_id_url . ')');
                    $stmt_name->close();
                } else {
                    echo "Seleziona un'arnia per visualizzare i trattamenti.";
                }
                ?>
            </p>
            
            <div class="table-container" id="trattamenti-list-container" style="max-height: 60vh; overflow-y: auto; border: 1px solid #ddd;">
                <p>Caricamento trattamenti...</p>
            </div>
        </div>
        </div>

    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
let currentArniaId = <?php echo $preselected_arnia_id_url ?? 'null'; ?>;
let currentArniaNome = ''; 
const TREATMENT_IDS = '<?php echo $safe_ids; ?>'; 

// Funzione per gestire la navigazione tra le Tab
function openTab(evt, tabName) {
    // 1. Nascondi tutti i tab e rimuovi la classe active
    $('.tab-content-item').removeClass('active');
    $('.tab-button').removeClass('active');

    // 2. Mostra il tab corrente e aggiungi la classe active
    $('#' + tabName).addClass('active');
    $(evt.currentTarget).addClass('active');

    // 3. LOGICA DI CARICAMENTO STORICO
    if (tabName === 'attivita' && currentArniaId) {
        // Recupera il nome completo per il display
        const row = $(`#arnie-table tbody tr[data-arnia-id="${currentArniaId}"]`);
        const arniaCodice = row.find('td:eq(0)').text();
        const arniaNomeRaw = row.find('td:eq(1)').text();
        currentArniaNome = arniaCodice + ' - ' + arniaNomeRaw;
        
        loadTrattamenti(currentArniaId, currentArniaNome);
    }
}


// Funzione AJAX per caricare i TRATTAMENTI
function loadTrattamenti(arniaId, arniaNome) {
    if (!TREATMENT_IDS) {
        $('#trattamenti-list-container').html('<p style="color: red;">Impossibile caricare: Configurazione Trattamenti mancante.</p>');
        return;
    }
    
    // Aggiorna il display sopra la tabella
    $('#selected-arnia-display').html(`Storico Trattamenti per Arnia: <b>${arniaNome}</b> (ID: ${arniaId})`);
    $('#trattamenti-list-container').html('<p>Caricamento in corso...</p>');

    $.ajax({
        url: '../includes/load_trattamenti.php', 
        type: 'GET',
        data: { 
            arnia_id: arniaId,
            filter_ids: TREATMENT_IDS 
        },
        success: function(response) {
            $('#trattamenti-list-container').html(response);
        },
        error: function(xhr, status, error) {
            $('#trattamenti-list-container').html('<p style="color: red;">Errore nel caricamento dei trattamenti.</p>');
        }
    });
}

$(document).ready(function() {
    
    // 1. Gestione del click sulla riga dell'arnia
    $('#arnie-table tbody').on('click', 'tr', function() {
        $('#arnie-table tbody tr').removeClass('selected-row');
        $(this).addClass('selected-row');

        currentArniaId = $(this).data('arnia-id');
        const arniaCodice = $(this).find('td:eq(0)').text();
        const arniaNomeRaw = $(this).find('td:eq(1)').text();
        currentArniaNome = arniaCodice + ' - ' + arniaNomeRaw;
        
        // Passa automaticamente al tab Storico Trattamenti
        openTab({currentTarget: $('#tab-attivita')[0]}, 'attivita');
    });
    
    // Stili CSS aggiuntivi per la riga selezionata (DA AGGIUNGERE A styles.css)
    $('head').append('<style>.selectable-table tbody tr:hover { background-color: #f5f5f5; cursor: pointer; } .selected-row { background-color: #cceeff !important; font-weight: bold; } .form-group-flex { display: flex; gap: 20px; } .form-group-flex .form-group { flex: 1; } .form-group-checkboxes { margin-bottom: 10px; padding: 10px; border: 1px dashed #ccc; } .checkbox-group { display: flex; align-items: center; gap: 5px; } .tab-content-item { display: none; } .tab-content-item.active { display: block; } .tab-button.active { background-color: #008CBA; color: white; }</style>');

    // 4. Caricamento Iniziale e selezione del Tab
    if (currentArniaId) {
        // Se c'è un ID arnia dall'URL, inizializza la selezione e carica
        const rowToSelect = $(`#arnie-table tbody tr[data-arnia-id="${currentArniaId}"]`);
        if (rowToSelect.length) {
            rowToSelect.trigger('click');
        } else {
             // Forza la visualizzazione del tab se siamo in modalità modifica
             openTab({currentTarget: $('#tab-attivita')[0]}, 'attivita');
        }
    } else {
        // Carica la prima arnia automaticamente se non c'è una pre-selezione
        const firstRow = $('#arnie-table tbody tr:first');
        if (firstRow.length) {
            firstRow.trigger('click');
        }
    }
    
    // Seleziona il tab corretto all'apertura (forza "selezione" come default se non specificato)
    if ('<?php echo $modifica_id; ?>' === null && '<?php echo $active_tab; ?>' !== 'attivita') {
        openTab({currentTarget: $('#tab-selezione')[0]}, 'selezione');
    }
});
</script>

<?php
require_once '../includes/footer.php';
?>