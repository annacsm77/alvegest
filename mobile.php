<?php
// Versione del file (incrementata)
$versione = "V.0.0.34"; // Modifiche grafiche: Bottoni storico e intestazione tabella

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

// --- LOGICA DI INSERIMENTO (INTEGRALE - NESSUNA MODIFICA) ---
// [Le 445 righe di logica originale rimangono qui, inclusi scadenziari e magazzino]
$messaggio = "";
$is_manual_close_attempt = isset($_POST["scadenza_chiusura_manuale"]) && $_POST["scadenza_chiusura_manuale"] == "1";
$debug_foto_messaggio = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST["conferma_registrazione"]) || $is_manual_close_attempt)) {
    
    $data = $_POST["data"] ?? date('Y-m-d');
    $arnia_id = $_POST["arnia_id_nascosto"] ?? null; 
    $tipo_attivita = $_POST["tipo_attivita"] ?? null; 
    $note = $_POST["note"] ?? '';
    $ia_peri = $_POST["ia_peri_hidden"] ?? 0;
    $ia_vreg = $_POST["ia_vreg_hidden"] ?? 0;
    $ia_op1 = $_POST["ia_op1_hidden"] ?? 0;
    $ia_op2 = $_POST["ia_op2_hidden"] ?? 0;
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
        $sql = "INSERT INTO AT_INSATT (IA_DATA, IA_CodAr, IA_ATT, IA_NOTE, IA_PERI, IA_VREG, IA_OP1, IA_OP2) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("siissiii", $data, $arnia_id, $tipo_attivita, $note, $ia_peri, $ia_vreg, $ia_op1, $ia_op2);

            if ($stmt->execute()) {
                $last_ia_id = $conn->insert_id; 

                $is_trattamento = 0; $gg_validita = 0; $nr_ripetizioni = 1; 
                $sql_check_trat = "SELECT AT_TRAT, AT_GG, AT_NR FROM TA_Attivita WHERE AT_ID = ?";
                $stmt_check = $conn->prepare($sql_check_trat);
                if ($stmt_check) {
                    $stmt_check->bind_param("i", $tipo_attivita);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    if ($row_check = $result_check->fetch_assoc()) {
                        $is_trattamento = $row_check['AT_TRAT']; $gg_validita = (int)$row_check['AT_GG']; $nr_ripetizioni = (int)$row_check['AT_NR'];
                    }
                    $stmt_check->close();
                }

                if ($is_trattamento == 1) {
                    $fase_aperta_id = null;
                    $sql_get_fase = "SELECT TP_ID FROM TR_PFASE WHERE TP_CHIU IS NULL AND TP_DAP <= ? ORDER BY TP_DAP DESC LIMIT 1";
                    $stmt_fase = $conn->prepare($sql_get_fase);
                    if ($stmt_fase) {
                        $stmt_fase->bind_param("s", $data); $stmt_fase->execute();
                        $result_fase = $stmt_fase->get_result();
                        if ($result_fase && $result_fase->num_rows > 0) { $fase_aperta_id = $result_fase->fetch_assoc()['TP_ID']; }
                        $stmt_fase->close();
                    }
                    if ($fase_aperta_id !== null) {
                        $sql_ffase = "INSERT INTO TR_FFASE (TF_PFASE, TF_ARNIA, TF_ATT, TF_CATT) VALUES (?, ?, ?, ?)";
                        $stmt_ffase = $conn->prepare($sql_ffase);
                        if ($stmt_ffase) { $stmt_ffase->bind_param("iiii", $fase_aperta_id, $arnia_id, $tipo_attivita, $last_ia_id); $stmt_ffase->execute(); $stmt_ffase->close(); }
                    }
                    if ($gg_validita > 0) {
                        $scad_attiva = null; $sql_find_active = "SELECT SC_ID, SC_AVA FROM TR_SCAD WHERE SC_ARNIA = ? AND SC_TATT = ? AND SC_CHIUSO = 0";
                        $stmt_find = $conn->prepare($sql_find_active);
                        if ($stmt_find) { $stmt_find->bind_param("ii", $arnia_id, $tipo_attivita); $stmt_find->execute(); $result_find = $stmt_find->get_result(); $scad_attiva = $result_find->fetch_assoc(); $stmt_find->close(); }
                        if ($scad_attiva) {
                            $current_sc_id = $scad_attiva['SC_ID']; $current_sc_ava = (int)$scad_attiva['SC_AVA']; $next_sc_ava = $current_sc_ava + 1;
                            $next_data_fine = date('Y-m-d', strtotime("{$data} +{$gg_validita} days"));
                            $sql_close = "UPDATE TR_SCAD SET SC_CHIUSO = 1, SC_DATAF = ? WHERE SC_ID = ?"; 
                            $stmt_close = $conn->prepare($sql_close);
                            if ($stmt_close) { $stmt_close->bind_param("si", $data, $current_sc_id); $stmt_close->execute(); $stmt_close->close(); }
                            if ($next_sc_ava <= $nr_ripetizioni) { 
                                $sql_next = "INSERT INTO TR_SCAD (SC_ARNIA, SC_TATT, SC_DINIZIO, SC_DATAF, SC_CHIUSO, SC_AVA) VALUES (?, ?, ?, ?, 0, ?)";
                                $stmt_next = $conn->prepare($sql_next);
                                if ($stmt_next) { $stmt_next->bind_param("iissi", $arnia_id, $tipo_attivita, $data, $next_data_fine, $next_sc_ava); $stmt_next->execute(); $stmt_next->close(); }
                            }
                        } else {
                            if ($nr_ripetizioni >= 1) {
                                $data_fine_first = date('Y-m-d', strtotime("{$data} +{$gg_validita} days"));
                                $sql_scad = "INSERT INTO TR_SCAD (SC_ARNIA, SC_TATT, SC_DINIZIO, SC_DATAF, SC_CHIUSO, SC_AVA) VALUES (?, ?, ?, ?, 0, 1)";
                                $stmt_scad = $conn->prepare($sql_scad);
                                if ($stmt_scad) { $stmt_scad->bind_param("iiss", $arnia_id, $tipo_attivita, $data, $data_fine_first); $stmt_scad->execute(); $stmt_scad->close(); }
                            }
                        } 
                    } 
                } 

                if ($ia_op1 == 1) {
                    $sql_find_any_active = "SELECT SC_ID FROM TR_SCAD WHERE SC_ARNIA = ? AND SC_CHIUSO = 0 LIMIT 1";
                    $stmt_find_any = $conn->prepare($sql_find_any_active);
                    if ($stmt_find_any) {
                        $stmt_find_any->bind_param("i", $arnia_id); $stmt_find_any->execute(); $result_any = $stmt_find_any->get_result();
                        if ($row_any = $result_any->fetch_assoc()) {
                            $scad_id_to_close_manual_scad = $row_any['SC_ID'];
                            $sql_close_manual_scad = "UPDATE TR_SCAD SET SC_CHIUSO = 1, SC_DATAF = ? WHERE SC_ID = ?";
                            $stmt_close_manual_scad = $conn->prepare($sql_close_manual_scad);
                            if ($stmt_close_manual_scad) { $stmt_close_manual_scad->bind_param("si", $data, $scad_id_to_close_manual_scad); $stmt_close_manual_scad->execute(); $stmt_close_manual_scad->close(); }
                        }
                        $stmt_find_any->close();
                    }
                }

                if ($ha_foto) {
                    $ext = pathinfo($file_caricato['name'], PATHINFO_EXTENSION);
                    $padded_ia_id = str_pad($last_ia_id, 8, '0', STR_PAD_LEFT);
                    $nome_file_db = $padded_ia_id . "." . $ext;
                    $upload_dir = 'immagini/'; $percorso_salvataggio = $upload_dir . $nome_file_db;
                    if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0755, true); }
                    if (move_uploaded_file($file_caricato['tmp_name'], $percorso_salvataggio)) {
                        $sql_foto = "INSERT INTO AT_FOTO (FO_ATT, FO_NOME) VALUES (?, ?)";
                        $stmt_foto = $conn->prepare($sql_foto);
                        if ($stmt_foto) { $stmt_foto->bind_param("is", $last_ia_id, $nome_file_db); $stmt_foto->execute(); $stmt_foto->close(); }
                    }
                }

                $sql_mag = "SELECT AT_MAG_ID, AT_SCARICO_FISSO, AT_DESCR FROM TA_Attivita WHERE AT_ID = ?";
                $stmt_mag = $conn->prepare($sql_mag);
                if ($stmt_mag) {
                    $stmt_mag->bind_param("i", $tipo_attivita);
                    $stmt_mag->execute();
                    $res_mag = $stmt_mag->get_result();
                    if ($row_mag = $res_mag->fetch_assoc()) {
                        $mag_id = $row_mag['AT_MAG_ID'];
                        if (!empty($mag_id)) {
                            $at_descr = $row_mag['AT_DESCR'];
                            $scarico_fisso = (int)$row_mag['AT_SCARICO_FISSO'];
                            $arnia_codice = get_arnia_codice($conn, $arnia_id);

                            if ($scarico_fisso == 1) {
                                $qta_scarico = 1.00;
                            } else {
                                $note_pulite = filter_var($note, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                                $qta_scarico = (float)str_replace(',', '.', $note_pulite);
                            }
                            if ($qta_scarico > 0) {
                                $causale_mov = "Scarico auto: " . $at_descr . " (Arnia: $arnia_codice) (IA_ID: $last_ia_id)";
                                $sql_ins_mov = "INSERT INTO MA_MOVI (MV_Data, MV_Descrizione, MV_MAG_ID, MV_Carico, MV_Scarico) VALUES (?, ?, ?, 0, ?)";
                                $stmt_mov = $conn->prepare($sql_ins_mov);
                                if ($stmt_mov) {
                                    $stmt_mov->bind_param("ssid", $data, $causale_mov, $mag_id, $qta_scarico);
                                    $stmt_mov->execute(); $stmt_mov->close();
                                }
                            }
                        }
                    }
                    $stmt_mag->close();
                }
                
                header("Location: mobile.php?status=success&arnia_id=" . $arnia_id);
                exit();
                
            } else { $messaggio = "<p class='errore'>Errore registrazione: " . $stmt->error . "</p>"; }
            $stmt->close();
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

            <div id="inserimento" class="tab-content-item active">
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
function setupToggleButtons() {
    $('.boolean-group .btn-toggle').on('click', function() {
        const button = $(this);
        const hiddenInputId = '#' + button.attr('id').replace('btn', 'ia') + '_hidden';
        if (button.hasClass('active')) {
            button.removeClass('active');
            $(hiddenInputId).val('0');
        } else {
            button.addClass('active');
            $(hiddenInputId).val('1');
        }
    });
    $('#btn_foto').on('click', function() { $('#foto_attivita').click(); });
    $('#foto_attivita').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        if (fileName) { $('#file_status').text('File: ' + fileName).css('color', 'red'); } else { $('#file_status').text(''); }
    });
}

function checkActiveScadenza(arniaId) {
    const btnOp1 = $('#btn_op1');
    btnOp1.removeClass('active');
    $('#ia_op1_hidden').val('0');
    $('#scadenza_attiva_id').val('0');
    $.ajax({
        url: 'includes/get_active_scadenza.php', 
        type: 'GET',
        dataType: 'json',
        data: { arnia_id: arniaId },
        success: function(response) {
            if (response.active) {
                $('#scadenza_attiva_id').val(response.sc_id);
                btnOp1.text('CHIUDI: ' + response.tipo_descr);
            } else { btnOp1.text('Scadenza'); }
        }
    });
}

function loadLatestAttivita() {
    const arniaId = $('#arnia_id_nascosto').val();
    if (!arniaId) return;
    $('#storico-attivita-content').html('<p>Caricamento...</p>');
    $.ajax({
        url: 'includes/load_attivita_mobile.php', 
        type: 'GET',
        data: { arnia_id: arniaId, limit: 20 },
        success: function(response) { $('#storico-attivita-content').html(response); }
    });
}

function openTab(evt, tabName) {
    $('.tab-content-item').hide();
    $('.tab-button').removeClass('active');
    $('#' + tabName).show();
    $(evt ? evt.currentTarget : (tabName === 'storico' ? '#storico-tab-btn' : '.tab-button:first')).addClass('active');
}

function setupFormSubmit() {
    $('#btn_conferma_submit').on('click', function() {
        if ($('#arnia_id_nascosto').val() === '' || $('#tipo_attivita').val() === '') {
            alert("⚠️ Devi selezionare un'arnia valida."); return;
        }
        if (confirm("Registrare l'attività?")) { $('#form_inserimento_attivita').submit(); } 
    });
}

$(document).ready(function() {
    setupToggleButtons();
    setupFormSubmit();

    const aidUrl = $('#arnia_id_nascosto').val();
    if(aidUrl) {
        $.ajax({
            url: 'search_arnia.php',
            type: 'GET',
            dataType: 'json',
            data: { id_diretto: aidUrl }, 
            success: function(response) {
                if (response.success) {
                    $('#arnia_nome_display').text(response.nome);
                    $('#codice_arnia').val(response.codice);
                    $('#btn_conferma_submit').prop('disabled', false); 
                    checkActiveScadenza(aidUrl);
                    const stat = "<?php echo $status_get; ?>";
                    if(stat === 'del_success' || stat === 'success') {
                        openTab(null, 'storico');
                        loadLatestAttivita();
                    }
                }
            }
        });
    }

    $('#codice_arnia').on('change', function() {
        const codice = $(this).val().trim();
        if (codice.length === 0) return;
        $.ajax({
            url: 'search_arnia.php',
            type: 'GET',
            dataType: 'json',
            data: { codice: codice },
            success: function(response) {
                if (response.success) {
                    $('#arnia_nome_display').text(response.nome);
                    $('#arnia_id_nascosto').val(response.id);
                    $('#btn_conferma_submit').prop('disabled', false); 
                    $('#data').focus(); 
                    checkActiveScadenza(response.id);
                } else { $('#arnia_nome_display').text('❌ NON TROVATA'); }
            }
        });
    });
});
</script>
</body>
</html>