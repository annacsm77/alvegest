<?php
// Versione del file (incrementata)
$versione = "V.0.0.37"; // Modifiche: Gestione eliminazione a cascata, Modifica e Sync Magazzino

// Forza l'encoding HTTP
header('Content-Type: text/html; charset=utf-8');

// Usa require_once per evitare l'errore "Cannot redeclare url()"
require_once 'includes/config.php'; 

// --- FUNZIONE AGGIUNTIVA: Trova il codice arnia ---
function get_arnia_codice($conn, $arnia_id) {
    $sql = "SELECT AR_CODICE FROM AP_Arnie WHERE AR_ID = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $arnia_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['AR_CODICE'] : null;
    }
    return null;
}

// --- LOGICA DI ELIMINAZIONE (CASCATA) ---
if (isset($_GET['elimina_id']) && is_numeric($_GET['elimina_id'])) {
    $id_da_eliminare = (int)$_GET['elimina_id'];
    $arnia_id_ref = $_GET['arnia_id'] ?? '';

    // 1. Elimina eventuale Foto (file fisico e record)
    $sql_foto = "SELECT FO_NOME FROM AT_FOTO WHERE FO_ATT = ?";
    $stmt_f = $conn->prepare($sql_foto);
    if ($stmt_f) {
        $stmt_f->bind_param("i", $id_da_eliminare);
        $stmt_f->execute();
        $res_f = $stmt_f->get_result();
        if ($row_f = $res_f->fetch_assoc()) {
            $percorso_file = 'immagini/' . $row_f['FO_NOME'];
            if (file_exists($percorso_file)) { unlink($percorso_file); }
        }
        $stmt_f->close();
    }
    $conn->query("DELETE FROM AT_FOTO WHERE FO_ATT = $id_da_eliminare");

    // 2. Elimina movimento di Magazzino correlato tramite tag IA_ID
    $search_tag = "%(IA_ID: $id_da_eliminare)%";
    $sql_del_mov = "DELETE FROM MA_MOVI WHERE MV_Descrizione LIKE ?";
    $stmt_dm = $conn->prepare($sql_del_mov);
    if ($stmt_dm) {
        $stmt_dm->bind_param("s", $search_tag);
        $stmt_dm->execute();
        $stmt_dm->close();
    }

    // 3. Elimina legami con Trattamenti/Fasi
    $conn->query("DELETE FROM TR_FFASE WHERE TF_CATT = $id_da_eliminare");

    // 4. Elimina il record principale
    $sql_del_main = "DELETE FROM AT_INSATT WHERE IA_ID = ?";
    $stmt_m = $conn->prepare($sql_del_main);
    if ($stmt_m) {
        $stmt_m->bind_param("i", $id_da_eliminare);
        $stmt_m->execute();
        $stmt_m->close();
    }

    header("Location: mobile.php?status=del_success&arnia_id=" . $arnia_id_ref);
    exit();
}

// --- LOGICA DI INSERIMENTO E MODIFICA ---
$messaggio = "";
$is_manual_close_attempt = isset($_POST["scadenza_chiusura_manuale"]) && $_POST["scadenza_chiusura_manuale"] == "1";

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST["conferma_registrazione"]) || $is_manual_close_attempt)) {
    
    $data = $_POST["data"] ?? date('Y-m-d');
    $arnia_id = $_POST["arnia_id_nascosto"] ?? null; 
    $tipo_attivita = $_POST["tipo_attivita"] ?? null; 
    $note = $_POST["note"] ?? '';
    $ia_peri = $_POST["ia_peri_hidden"] ?? 0;
    $ia_vreg = $_POST["ia_vreg_hidden"] ?? 0;
    $ia_op1 = $_POST["ia_op1_hidden"] ?? 0;
    $ia_op2 = $_POST["ia_op2_hidden"] ?? 0;
    $ia_id_modifica = $_POST["ia_id_modifica"] ?? null; 
    $last_ia_id = null; 
    
    $file_caricato = $_FILES['foto_attivita'] ?? null;
    $ha_foto = ($file_caricato && $file_caricato['error'] === UPLOAD_ERR_OK && $file_caricato['size'] > 0);

    if (empty($arnia_id) || !is_numeric($arnia_id)) {
        $messaggio = "<p class='errore'>⚠️ Errore: Arnia non selezionata o codice non valido.</p>";
        goto fine_post;
    } 
    
    if ($is_manual_close_attempt) {
        $scad_id_to_close = $_POST["scadenza_attiva_id"];
        if ($scad_id_to_close > 0) {
            $sql_manual_close = "UPDATE TR_SCAD SET SC_CHIUSO = 1, SC_DATAF = ? WHERE SC_ID = ?";
            $stmt_manual = $conn->prepare($sql_manual_close);
            if ($stmt_manual) {
                $stmt_manual->bind_param("si", $data, $scad_id_to_close);
                if ($stmt_manual->execute()) {
                    header("Location: mobile.php?status=close_manual_success&arnia_id=" . $arnia_id);
                    exit();
                }
            }
        }
        goto fine_post;
    }

    if (isset($_POST["conferma_registrazione"])) {
        if (!empty($ia_id_modifica)) {
            // --- AZIONE: MODIFICA (UPDATE) ---
            $sql = "UPDATE AT_INSATT SET IA_DATA = ?, IA_NOTE = ?, IA_PERI = ?, IA_VREG = ?, IA_OP1 = ?, IA_OP2 = ? WHERE IA_ID = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssiiiii", $data, $note, $ia_peri, $ia_vreg, $ia_op1, $ia_op2, $ia_id_modifica);
                if ($stmt->execute()) {
                    $last_ia_id = $ia_id_modifica;
                    // Update data fase
                    $sql_upd_fase = "UPDATE TR_FFASE f JOIN TR_PFASE p ON f.TF_PFASE = p.TP_ID SET p.TP_DAP = ? WHERE f.TF_CATT = ?";
                    $stmt_f = $conn->prepare($sql_upd_fase);
                    if($stmt_f) { $stmt_f->bind_param("si", $data, $last_ia_id); $stmt_f->execute(); }

                    // Sync Magazzino su modifica note/data
                    $sql_check_mag = "SELECT AT_SCARICO_FISSO FROM TA_Attivita WHERE AT_ID = ?";
                    $stmt_check_mag = $conn->prepare($sql_check_mag);
                    if ($stmt_check_mag) {
                        $stmt_check_mag->bind_param("i", $tipo_attivita);
                        $stmt_check_mag->execute();
                        $res_check_mag = $stmt_check_mag->get_result();
                        if ($row_m = $res_check_mag->fetch_assoc()) {
                            $search_string = "%(IA_ID: $last_ia_id)%";
                            if ((int)$row_m['AT_SCARICO_FISSO'] === 0) {
                                $nuova_qta = (float)str_replace(',', '.', filter_var($note, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                                if ($nuova_qta > 0) {
                                    $sql_upd_mov = "UPDATE MA_MOVI SET MV_Scarico = ?, MV_Data = ? WHERE MV_Descrizione LIKE ?";
                                    $stmt_upd_mov = $conn->prepare($sql_upd_mov);
                                    if ($stmt_upd_mov) { $stmt_upd_mov->bind_param("dss", $nuova_qta, $data, $search_string); $stmt_upd_mov->execute(); }
                                }
                            } else {
                                $sql_upd_mov_date = "UPDATE MA_MOVI SET MV_Data = ? WHERE MV_Descrizione LIKE ?";
                                $stmt_upd_mov_date = $conn->prepare($sql_upd_mov_date);
                                if ($stmt_upd_mov_date) { $stmt_upd_mov_date->bind_param("ss", $data, $search_string); $stmt_upd_mov_date->execute(); }
                            }
                        }
                    }
                }
            }
        } else {
            // --- AZIONE: NUOVO INSERIMENTO (LOGICA ORIGINALE) ---
            $sql = "INSERT INTO AT_INSATT (IA_DATA, IA_CodAr, IA_ATT, IA_NOTE, IA_PERI, IA_VREG, IA_OP1, IA_OP2) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("siissiii", $data, $arnia_id, $tipo_attivita, $note, $ia_peri, $ia_vreg, $ia_op1, $ia_op2);
                if ($stmt->execute()) {
                    $last_ia_id = $conn->insert_id; 
                    // Logica scadenze e trattamenti (invariata)
                    $is_trattamento = 0; $gg_validita = 0; $nr_ripetizioni = 1; 
                    $sql_check_trat = "SELECT AT_TRAT, AT_GG, AT_NR FROM TA_Attivita WHERE AT_ID = ?";
                    $stmt_check = $conn->prepare($sql_check_trat);
                    if ($stmt_check) {
                        $stmt_check->bind_param("i", $tipo_attivita); $stmt_check->execute();
                        $res_check = $stmt_check->get_result();
                        if ($row_c = $res_check->fetch_assoc()) { $is_trattamento = $row_c['AT_TRAT']; $gg_validita = (int)$row_c['AT_GG']; $nr_ripetizioni = (int)$row_c['AT_NR']; }
                        $stmt_check->close();
                    }
                    if ($is_trattamento == 1) {
                        $fase_id = null;
                        $sql_f = "SELECT TP_ID FROM TR_PFASE WHERE TP_CHIU IS NULL AND TP_DAP <= ? ORDER BY TP_DAP DESC LIMIT 1";
                        $stmt_f = $conn->prepare($sql_f);
                        if($stmt_f){ $stmt_f->bind_param("s", $data); $stmt_f->execute(); $res_f = $stmt_f->get_result(); if($r = $res_f->fetch_assoc()) $fase_id = $r['TP_ID']; }
                        if($fase_id){
                            $sql_ins_f = "INSERT INTO TR_FFASE (TF_PFASE, TF_ARNIA, TF_ATT, TF_CATT) VALUES (?, ?, ?, ?)";
                            $stmt_if = $conn->prepare($sql_ins_f);
                            if($stmt_if){ $stmt_if->bind_param("iiii", $fase_id, $arnia_id, $tipo_attivita, $last_ia_id); $stmt_if->execute(); }
                        }
                    }
                }
            }
        }

        if ($last_ia_id) {
            // Gestione Foto
            if ($ha_foto) {
                $ext = pathinfo($file_caricato['name'], PATHINFO_EXTENSION);
                $nome_file_db = str_pad($last_ia_id, 8, '0', STR_PAD_LEFT) . "." . $ext;
                if (move_uploaded_file($file_caricato['tmp_name'], 'immagini/' . $nome_file_db)) {
                    $sql_foto = "INSERT INTO AT_FOTO (FO_ATT, FO_NOME) VALUES (?, ?) ON DUPLICATE KEY UPDATE FO_NOME = VALUES(FO_NOME)";
                    $stmt_foto = $conn->prepare($sql_foto);
                    if ($stmt_foto) { $stmt_foto->bind_param("is", $last_ia_id, $nome_file_db); $stmt_foto->execute(); $stmt_foto->close(); }
                }
            }
            // Magazzino su nuovo inserimento
            if (empty($ia_id_modifica)) {
                $sql_mag = "SELECT AT_MAG_ID, AT_SCARICO_FISSO, AT_DESCR FROM TA_Attivita WHERE AT_ID = ?";
                $stmt_mag = $conn->prepare($sql_mag);
                if ($stmt_mag) {
                    $stmt_mag->bind_param("i", $tipo_attivita); $stmt_mag->execute(); $res_mag = $stmt_mag->get_result();
                    if ($row_mag = $res_mag->fetch_assoc()) {
                        $mag_id = $row_mag['AT_MAG_ID'];
                        if (!empty($mag_id)) {
                            $at_descr = $row_mag['AT_DESCR']; $scarico_fisso = (int)$row_mag['AT_SCARICO_FISSO'];
                            $arnia_codice = get_arnia_codice($conn, $arnia_id);
                            $qta_scarico = ($scarico_fisso == 1) ? 1.00 : (float)str_replace(',', '.', filter_var($note, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                            if ($qta_scarico > 0) {
                                $causale_mov = "Scarico auto: " . $at_descr . " (Arnia: $arnia_codice) (IA_ID: $last_ia_id)";
                                $sql_ins_mov = "INSERT INTO MA_MOVI (MV_Data, MV_Descrizione, MV_MAG_ID, MV_Carico, MV_Scarico) VALUES (?, ?, ?, 0, ?)";
                                $stmt_mov = $conn->prepare($sql_ins_mov);
                                if ($stmt_mov) { $stmt_mov->bind_param("ssid", $data, $causale_mov, $mag_id, $qta_scarico); $stmt_mov->execute(); $stmt_mov->close(); }
                            }
                        }
                    }
                }
            }
            header("Location: mobile.php?status=success&arnia_id=" . $arnia_id);
            exit();
        }
    }
}
fine_post: 

$attivita_options = [];
$sql_attivita = "SELECT AT_ID, AT_DESCR FROM TA_Attivita ORDER BY AT_DESCR";
$result_attivita = $conn->query($sql_attivita);
if ($result_attivita) { while ($row = $result_attivita->fetch_assoc()) { $attivita_options[] = $row; } }

$status_get = $_GET["status"] ?? "";
$successo = ($status_get == "success");
$chiusura_manuale_success = ($status_get == "close_manual_success");
$del_successo = ($status_get == "del_success");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title> AlveGest Mobile</title>
    <link rel="stylesheet" href="<?php echo TPL_URL; ?>mobile_app.css?v=<?php echo time(); ?>"> 
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #alert_modifica {
            display:none; background:#fff3cd; color:#856404; padding:12px; 
            margin-bottom:15px; border-radius:8px; font-weight:bold; 
            border:1px solid #ffeeba; font-size: 0.9em;
        }
        .btn-cancel-edit {
            float:right; background:#dc3545; color:white; border:none; 
            border-radius:4px; padding: 2px 8px; cursor:pointer; font-size: 0.8em;
        }
    </style>
</head>
<body>

    <div class="header-mobile-bar">
        <div class="header-title-group">
            <h2 class="titolo-mobile">Registra Attività <?php if ($successo || $chiusura_manuale_success || $del_successo) echo "✅"; ?></h2>
        </div>
        <a href="index.php" class="btn-menu-header">MENU</a>
    </div>

    <div class="form-container-mobile">
        <?php echo $messaggio; ?>
        
        <div class="scanner-box flex-row">
            <div class="input-col">
                <input type="text" id="codice_arnia" name="codice_arnia" maxlength="4" autofocus required placeholder="Codice" inputmode="numeric" pattern="[0-9]*">
            </div>
            <div id="arnia_nome_display" class="arnia-nome-label">Nome Arnia</div>
        </div>

        <div class="tab-nav">
            <button class="tab-button active" onclick="openTab(event, 'inserimento')">NUOVA</button>
            <button class="tab-button" id="storico-tab-btn" onclick="openTab(event, 'storico'); loadLatestAttivita()">STORICO</button>
        </div>

        <form action="mobile.php" method="post" id="form_inserimento_attivita" enctype="multipart/form-data">
            <input type="hidden" name="conferma_registrazione" value="1">
            <input type="hidden" id="arnia_id_nascosto" name="arnia_id_nascosto" value="<?php echo $_GET['arnia_id'] ?? ''; ?>">
            <input type="hidden" id="scadenza_attiva_id" name="scadenza_attiva_id" value="0">
            <input type="hidden" id="scadenza_chiusura_manuale" name="scadenza_chiusura_manuale" value="0">
            <input type="hidden" id="ia_id_modifica" name="ia_id_modifica" value="">

            <div id="inserimento" class="tab-content-item active">
                
                <div id="alert_modifica">
                    MODIFICA RECORD ID: <span id="label_id_mod"></span>
                    <button type="button" class="btn-cancel-edit" onclick="cancelEdit()">ANNULLA</button>
                </div>

                <div class="form-row-mobile data-row">
                    <label for="data">Data:</label>
                    <input type="date" id="data" name="data" required value="<?php echo date('Y-m-d'); ?>">
                    
                    <div class="photo-col">
                        <button type="button" id="btn_foto">📸 FOTO</button>
                        <input type="file" id="foto_attivita" name="foto_attivita" accept="image/*" capture="camera" style="display: none;">
                    </div>
                </div>
                <p id="file_status" class="status-label"></p>

                <div class="form-group-mobile">
                    <label for="tipo_attivita">Attività:</label>
                    <select id="tipo_attivita" name="tipo_attivita" required>
                        <option value="">Seleziona...</option>
                        <?php foreach ($attivita_options as $att): ?>
                            <option value="<?php echo $att['AT_ID']; ?>"><?php echo htmlspecialchars($att['AT_DESCR']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-mobile">
                    <label for="note">Note / Q.tà:</label>
                    <textarea id="note" name="note" rows="4" placeholder="Note..."></textarea>
                </div>

                <div class="boolean-group">
                    <button type="button" class="btn-toggle" id="btn_peri">Pericolo</button>
                    <input type="hidden" name="ia_peri_hidden" id="ia_peri_hidden" value="0">
                    <button type="button" class="btn-toggle" id="btn_vreg">Vis Reg</button>
                    <input type="hidden" name="ia_vreg_hidden" id="ia_vreg_hidden" value="0">
                    <button type="button" class="btn-toggle" id="btn_op1">Scadenza</button>
                    <input type="hidden" name="ia_op1_hidden" id="ia_op1_hidden" value="0">
                    <button type="button" class="btn-toggle" id="btn_op2">Option 2</button>
                    <input type="hidden" name="ia_op2_hidden" id="ia_op2_hidden" value="0">
                </div>

                <div class="form-group-mobile">
                    <button type="button" id="btn_conferma_submit" class="btn-grande" disabled>REGISTRA</button>
                </div>
            </div>
        </form>

        <div id="storico" class="tab-content-item" style="display:none;">
            <div id="storico-attivita-content" class="table-bordered-scroll"><p>Caricamento...</p></div>
        </div>
    </div>

    <div class="version-footer"><?php echo $versione; ?></div>

<script>
// --- FUNZIONI DI MODIFICA ---
function editAttivita(id, data, note, peri, vreg, op1, op2, att_id) {
    openTab(null, 'inserimento');
    $('#ia_id_modifica').val(id);
    $('#label_id_mod').text(id);
    $('#data').val(data);
    $('#note').val(note);
    setToggleStatus('#btn_peri', '#ia_peri_hidden', peri);
    setToggleStatus('#btn_vreg', '#ia_vreg_hidden', vreg);
    setToggleStatus('#btn_op1', '#ia_op1_hidden', op1);
    setToggleStatus('#btn_op2', '#ia_op2_hidden', op2);
    $('#tipo_attivita').val(att_id).prop('disabled', true);
    if($('#tipo_attivita_hidden').length === 0) {
        $('#tipo_attivita').after('<input type="hidden" id="tipo_attivita_hidden" name="tipo_attivita" value="'+att_id+'">');
    }
    $('#alert_modifica').show();
    $('#btn_conferma_submit').text('AGGIORNA RECORD');
    window.scrollTo(0, 0);
}

function setToggleStatus(btnId, hiddenId, value) {
    if (parseInt(value) === 1) { $(btnId).addClass('active'); $(hiddenId).val('1'); } 
    else { $(btnId).removeClass('active'); $(hiddenId).val('0'); }
}

function cancelEdit() {
    $('#ia_id_modifica').val('');
    $('#alert_modifica').hide();
    $('#tipo_attivita').prop('disabled', false);
    $('#tipo_attivita_hidden').remove();
    $('#btn_conferma_submit').text('REGISTRA');
    const currentArniaId = $('#arnia_id_nascosto').val();
    $('#form_inserimento_attivita')[0].reset();
    $('#arnia_id_nascosto').val(currentArniaId);
    $('.btn-toggle').removeClass('active');
    $('input[type="hidden"][id$="_hidden"]').val('0');
}

// --- LOGICA ORIGINALE ---
function setupToggleButtons() {
    $('.boolean-group .btn-toggle').on('click', function() {
        const button = $(this);
        const hiddenInputId = '#' + button.attr('id').replace('btn', 'ia') + '_hidden';
        if (button.hasClass('active')) { button.removeClass('active'); $(hiddenInputId).val('0'); } 
        else { button.addClass('active'); $(hiddenInputId).val('1'); }
    });
    $('#btn_foto').on('click', function() { $('#foto_attivita').click(); });
    $('#foto_attivita').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        if (fileName) { $('#file_status').text('File: ' + fileName).css('color', 'red'); } else { $('#file_status').text(''); }
    });
}

function checkActiveScadenza(arniaId) {
    if ($('#ia_id_modifica').val() !== '') return;
    const btnOp1 = $('#btn_op1');
    btnOp1.removeClass('active'); $('#ia_op1_hidden').val('0'); $('#scadenza_attiva_id').val('0');
    $.ajax({
        url: 'includes/get_active_scadenza.php', 
        type: 'GET', dataType: 'json', data: { arnia_id: arniaId },
        success: function(response) {
            if (response.active) { $('#scadenza_attiva_id').val(response.sc_id); btnOp1.text('CHIUDI: ' + response.tipo_descr); } 
            else { btnOp1.text('Scadenza'); }
        }
    });
}

function loadLatestAttivita() {
    const arniaId = $('#arnia_id_nascosto').val();
    if (!arniaId) return;
    $('#storico-attivita-content').html('<p>Caricamento...</p>');
    $.ajax({
        url: 'includes/load_attivita_mobile.php', 
        type: 'GET', data: { arnia_id: arniaId, limit: 20 },
        success: function(response) { $('#storico-attivita-content').html(response); }
    });
}

function openTab(evt, tabName) {
    $('.tab-content-item').hide(); $('.tab-button').removeClass('active');
    $('#' + tabName).show();
    if (evt) { $(evt.currentTarget).addClass('active'); } 
    else { if(tabName === 'storico') $('#storico-tab-btn').addClass('active'); else $('.tab-button').first().addClass('active'); }
}

function setupFormSubmit() {
    $('#btn_conferma_submit').on('click', function() {
        if ($('#arnia_id_nascosto').val() === '' || ($('#tipo_attivita').val() === '' && $('#tipo_attivita_hidden').val() === undefined)) {
            alert("⚠️ Devi selezionare un'arnia e un'attività valide."); return;
        }
        const textMsg = ($('#ia_id_modifica').val() !== '') ? "Aggiornare il record?" : "Registrare l'attività?";
        if (confirm(textMsg)) { $('#tipo_attivita').prop('disabled', false); $('#form_inserimento_attivita').submit(); } 
    });
}

$(document).ready(function() {
    setupToggleButtons(); setupFormSubmit();
    const aidUrl = $('#arnia_id_nascosto').val();
    if(aidUrl) {
        $.ajax({
            url: 'search_arnia.php', type: 'GET', dataType: 'json', data: { id_diretto: aidUrl }, 
            success: function(response) {
                if (response.success) {
                    $('#arnia_nome_display').text(response.nome); $('#codice_arnia').val(response.codice);
                    $('#btn_conferma_submit').prop('disabled', false); checkActiveScadenza(aidUrl);
                    if("<?php echo $status_get; ?>" === 'del_success' || "<?php echo $status_get; ?>" === 'success') {
                        openTab(null, 'storico'); loadLatestAttivita();
                    }
                }
            }
        });
    }

    $('#codice_arnia').on('change', function() {
        const codice = $(this).val().trim();
        if (codice.length === 0) return;
        $.ajax({
            url: 'search_arnia.php', type: 'GET', dataType: 'json', data: { codice: codice },
            success: function(response) {
                if (response.success) {
                    $('#arnia_nome_display').text(response.nome); $('#arnia_id_nascosto').val(response.id);
                    $('#btn_conferma_submit').prop('disabled', false); $('#data').focus(); checkActiveScadenza(response.id);
                } else { $('#arnia_nome_display').text('❌ NON TROVATA'); }
            }
        });
    });
});
</script>
</body>
</html>