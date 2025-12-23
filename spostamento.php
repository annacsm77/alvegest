<?php
// Versione del file (per tracciamento)
$versione = "S.0.0.2"; 

// Forza l'encoding HTTP
header('Content-Type: text/html; charset=utf-8');
require_once 'includes/config.php'; 

// --- RECUPERO DATI APIARI PER COMBO BOX (Invariato) ---
$sql_apiari = "SELECT AI_ID, AI_LUOGO FROM TA_Apiari ORDER BY AI_LUOGO ASC";
$result_apiari = $conn->query($sql_apiari);
$apiari = [];
if ($result_apiari) {
    while ($row = $result_apiari->fetch_assoc()) {
        $apiari[] = $row;
    }
}
// ------------------------------------------

$messaggio = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["conferma_spostamento"])) {
    
    // 1. Recupera i dati POST
    $luogo_da = $_POST["luogo_da"];
    $luogo_a = $_POST["luogo_a"];
    $data_spostamento = $_POST["data"];
    $arnie_spostate_codici = $_POST["arnie_scansionate"]; 
    $totale_arnie = $_POST["totale_arnie"];
    $attivita_spostamento_id = 3; // ID ATTIVITÀ 'Spostamento' come richiesto (Verificare che in TA_Attivita sia ID=3)
    
    // 2. Controllo Preliminare
    if ($luogo_da == $luogo_a) {
        $messaggio = "<p class='errore'>⚠️ Errore: Apiario di Partenza e di Arrivo non possono essere gli stessi.</p>";
        goto fine_post;
    }
    if (empty($arnie_spostate_codici)) {
        $messaggio = "<p class='errore'>⚠️ Errore: Nessuna arnia inserita per lo spostamento.</p>";
        goto fine_post;
    }
    
    // --- PRE-TRANSAZIONE: MAPPATURA E NOMI ---
    $codici_array = explode(';', trim($arnie_spostate_codici, ';'));
    $placeholders = implode(',', array_fill(0, count($codici_array), '?'));
    $types = str_repeat('s', count($codici_array));
    
    // A) RECUPERA NOME APIARIO DI ARRIVO
    $luogo_a_nome = "Sconosciuto";
    $sql_get_luogo_a_nome = "SELECT AI_LUOGO FROM TA_Apiari WHERE AI_ID = ?";
    $stmt_luogo = $conn->prepare($sql_get_luogo_a_nome);
    $stmt_luogo->bind_param("i", $luogo_a);
    $stmt_luogo->execute();
    $stmt_luogo->bind_result($luogo_a_nome);
    $stmt_luogo->fetch();
    $stmt_luogo->close();
    
    $note_attivita = "Spostata a: " . $luogo_a_nome;
    
    // B) RECUPERA AR_ID PER OGNI CODICE ARNIA
    $arnia_ids_map = []; // Mappa AR_CODICE => AR_ID
    $sql_get_arnia_ids = "SELECT AR_ID, AR_CODICE FROM AP_Arnie WHERE AR_CODICE IN ($placeholders)";
    $stmt_arnie = $conn->prepare($sql_get_arnia_ids);
    
    // Binding dinamico per i codici arnia
    $ref_array_arnie = [];
    $ref_array_arnie[] = $types;
    foreach ($codici_array as $key => $value) {
        $ref_array_arnie[] = &$codici_array[$key];
    }
    call_user_func_array([$stmt_arnie, 'bind_param'], $ref_array_arnie);
    $stmt_arnie->execute();
    $result_arnie = $stmt_arnie->get_result();

    while ($row = $result_arnie->fetch_assoc()) {
        // Usiamo l'ID della riga del DB (AR_ID) per l'aggiornamento e l'inserimento attività
        $arnia_ids_map[$row['AR_CODICE']] = $row['AR_ID']; 
    }
    $stmt_arnie->close();

    // Rimuovi i codici non trovati dalla lista che verrà aggiornata
    $codici_trovati = array_keys($arnia_ids_map);
    
    if (count($codici_trovati) != count($codici_array)) {
        $codici_non_trovati = array_diff($codici_array, $codici_trovati);
        $messaggio .= "<p class='errore'>⚠️ Attenzione: I seguenti codici Arnia non sono stati trovati e non sono stati spostati/registrati: " . implode(', ', $codici_non_trovati) . "</p>";
    }
    
    // --- ESECUZIONE TRANSAZIONE ---
    $successo_transazione = false;
    $conn->begin_transaction();
    
    try {
        // 1) INSERIMENTO STORICO (AI_SPOS)
        $sql_insert = "INSERT INTO AI_SPOS (SP_DA, SP_A, SP_DATA, SP_ARNIE, SP_TOT) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iissi", $luogo_da, $luogo_a, $data_spostamento, $arnie_spostate_codici, $totale_arnie);
        $stmt_insert->execute();

        // 2) AGGIORNAMENTO ARNIE (AP_Arnie)
        // Aggiorna la colonna AR_LUOGO al nuovo ID Apiario ($luogo_a)
        if (!empty($codici_trovati)) {
            // Rifacciamo i placeholder basandoci solo sui codici validi trovati
            $placeholders_validi = implode(',', array_fill(0, count($codici_trovati), '?'));
            $types_validi = str_repeat('s', count($codici_trovati));
            
            $sql_update_arnie = "UPDATE AP_Arnie SET AR_LUOGO = ? WHERE AR_CODICE IN ($placeholders_validi)";
            $stmt_update = $conn->prepare($sql_update_arnie);
            
            // Binding dinamico per ID Luogo + codici arnie validi
            $bind_params = array_merge([$luogo_a], $codici_trovati);
            $bind_types = 'i' . $types_validi; 
            
            $ref_array = [];
            $ref_array[] = $bind_types;
            foreach ($bind_params as $key => $value) {
                $ref_array[] = &$bind_params[$key];
            }
            call_user_func_array([$stmt_update, 'bind_param'], $ref_array);

            $stmt_update->execute();
        }

        // 3) INSERIMENTO ATTIVITÀ (AT_INSATT) - NUOVO REQUISITO
        $sql_insert_att = "INSERT INTO AT_INSATT (IA_DATA, IA_CodAr, IA_ATT, IA_NOTE) VALUES (?, ?, ?, ?)";
        $stmt_insert_att = $conn->prepare($sql_insert_att);

        // Loop attraverso gli ID del database (AR_ID) di tutte le arnie spostate
        foreach ($arnia_ids_map as $arnia_codice => $arnia_db_id) {
            // IA_CodAr in AT_INSATT DEVE essere AR_ID dalla tabella AP_Arnie
            $stmt_insert_att->bind_param("siis", $data_spostamento, $arnia_db_id, $attivita_spostamento_id, $note_attivita);
            $stmt_insert_att->execute();
        }
        $stmt_insert_att->close();
        
        // C) COMMIT
        $conn->commit();
        $successo_transazione = true;

    } catch (Exception $e) {
        $conn->rollback();
        $messaggio = "<p class='errore'>❌ Errore Transazione (Rollback): " . $e->getMessage() . " / " . $conn->error . "</p>";
    }
    
    if ($successo_transazione) {
        header("Location: spostamento.php?status=success");
        exit();
    }
}

// Gestione feedback post-redirect
if (isset($_GET["status"]) && $_GET["status"] == "success") {
    $messaggio = "<div style='text-align: center; font-size: 36px; margin: 5px 0;'>✅ Spostamento e Attività Registrati</div>";
}
fine_post:
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📱 Spostamento Apiari</title>
    <link rel="stylesheet" href="css/styles.css"> 
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        .form-container-mobile { max-width: 600px; margin: 0 auto; padding: 5px; box-sizing: border-box; background-color: #f9f9f9; }
        .header-mobile-bar { max-width: 600px; margin: 0 auto; padding: 5px 10px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; }
        .titolo-mobile { font-size: 18px; font-weight: bold; margin: 0; }
        /* Stili per il pulsante di switch */
        .btn-menu, .btn-switch { color: #006400 !important; border: 1px solid #006400; padding: 5px 10px; text-decoration: none; font-size: 14px; margin-left: 5px; border-radius: 4px; }
        .version-footer { position: fixed; bottom: 0; left: 0; padding: 2px 5px; font-size: 10px; color: #666; background-color: #fff; border-top-right-radius: 5px; z-index: 100; }
        .form-group label { font-size: 18px; font-weight: bold; color: #006400; margin-bottom: 2px; }
        input[type="date"], input[type="text"], input[type="number"], select.form-control, textarea, .btn-grande { font-size: 26px; min-height: 60px; padding: 5px; box-sizing: border-box; width: 100%; margin-bottom: 5px;}
        .arnie-list { height: 120px; overflow-y: scroll; border: 1px solid #ccc; padding: 5px; background-color: white; font-size: 16px; margin-bottom: 5px; }
        .errore { color: red; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header-mobile-bar">
        <h2 class="titolo-mobile">Spostamento Apiari</h2>
        <div>
            <a href="mobile.php" class="btn-switch">INSERIMENTO</a>
            <a href="index.php" class="btn-menu">MENU</a>
        </div>
    </div>

    <div class="form-container-mobile">
        
        <?php echo $messaggio; ?>

        <form action="spostamento.php" method="post" id="form_spostamento">
            <input type="hidden" name="conferma_spostamento" value="1">
            <input type="hidden" name="arnie_scansionate" id="arnie_scansionate_hidden" value="">
            <input type="hidden" name="totale_arnie" id="totale_arnie_hidden" value="0">
            
            <div class="form-group">
                <label for="luogo_da">Da (Apiario di Partenza):</label>
                <select id="luogo_da" name="luogo_da" class="form-control" required>
                    <option value="">Seleziona Apiario</option>
                    <?php foreach ($apiari as $apiario): ?>
                        <option value="<?php echo $apiario['AI_ID']; ?>">
                            <?php echo htmlspecialchars($apiario['AI_LUOGO']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="luogo_a">A (Apiario di Arrivo):</label>
                <select id="luogo_a" name="luogo_a" class="form-control" required>
                    <option value="">Seleziona Apiario</option>
                    <?php foreach ($apiari as $apiario): ?>
                        <option value="<?php echo $apiario['AI_ID']; ?>">
                            <?php echo htmlspecialchars($apiario['AI_LUOGO']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="data">Data Spostamento:</label>
                <input type="date" id="data" name="data" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="codice_arnia_scanner">Codice Arnia da Spostare:</label>
                <input type="text" id="codice_arnia_scanner" placeholder="Scannerizza codice qui" required>
                
                <p style="margin: 5px 0;">Arnie Inserite (<span id="count_arnie">0</span>):</p>
                <div id="arnie_list_display" class="arnie-list">
                    </div>
            </div>

            <div class="form-group">
                <button type="button" id="btn_conferma_spostamento" class="btn btn-inserisci btn-grande" disabled>
                    CONFERMA SPOSTAMENTO
                </button>
            </div>
        </form>

    </div>

    <div class="version-footer">
        <?php echo $versione; ?>
    </div>

<script>
let arnieSpostate = new Set(); // Usa un Set per garantire l'unicità

// Funzione per aggiornare la lista visiva e i campi nascosti
function updateArnieList() {
    const listDiv = $('#arnie_list_display');
    const hiddenInput = $('#arnie_scansionate_hidden');
    const countSpan = $('#count_arnie');
    
    // Aggiorna l'array di codici (separati da ;)
    const codes = Array.from(arnieSpostate);
    const codesString = codes.join(';');
    
    // 1. Aggiorna i campi del form
    hiddenInput.val(codesString);
    $('#totale_arnie_hidden').val(codes.length);
    countSpan.text(codes.length);

    // 2. Aggiorna la lista visiva
    listDiv.html(codes.length === 0 ? 'Nessuna arnia scannerizzata.' : codes.join('<br>'));
    listDiv.scrollTop(listDiv.prop("scrollHeight")); 

    // 3. Abilita/Disabilita pulsante
    $('#btn_conferma_spostamento').prop('disabled', codes.length === 0);
}

// Gestione della scannerizzazione/inserimento del codice
$('#codice_arnia_scanner').on('change', function() {
    const codice = $(this).val().trim().toUpperCase();
    $(this).val(''); // Pulisce immediatamente l'input per il prossimo scan

    if (codice && !arnieSpostate.has(codice)) {
        arnieSpostate.add(codice);
        updateArnieList();
    } else if (codice) {
        alert(`L'arnia con codice ${codice} è già stata aggiunta.`);
    }
    // Riporta il focus sul campo scanner
    $(this).focus(); 
});

// Funzione di Submit con Conferma
$('#btn_conferma_spostamento').on('click', function() {
    const form = $('#form_spostamento');
    const luogoda = $('#luogo_da option:selected').text();
    const luogoA = $('#luogo_a option:selected').text();
    const dataSposto = $('#data').val();
    const totale = arnieSpostate.size;

    if (luogoA === luogoda) {
        alert("⚠️ Apiario di Partenza e di Arrivo non possono essere gli stessi.");
        return;
    }
    
    let messaggioConferma = "CONFERMI LO SPOSTAMENTO?\n\n";
    messaggioConferma += `DA: ${luogoda}\n`;
    messaggioConferma += `A: ${luogoA}\n`;
    messaggioConferma += `Data: ${dataSposto}\n`;
    messaggioConferma += `Totale Arnie: ${totale}`;

    if (confirm(messaggioConferma)) {
        form.submit();
    }
});

$(document).ready(function() {
    updateArnieList();
});
</script>
</body>
</html>