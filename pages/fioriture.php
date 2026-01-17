<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.0.6 (Layout Nativo con Tab)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php';

// 1. INIZIALIZZAZIONE VARIABILI
$messaggio = "";
$modifica_fio_id = $_GET['modifica'] ?? null;
$curr = ['FI_id' => '', 'FI_CODICE' => '', 'FI_Note' => ''];

// --- LOGICA SALVATAGGIO ANAGRAFICA FIORITURA ---
if (isset($_POST['salva_fioritura'])) {
    $codice = strtoupper(substr($_POST['fi_codice'], 0, 4));
    $note = $_POST['fi_note'];
    $fio_id = $_POST['fi_id'] ?? null;

    if ($fio_id) {
        $stmt = $conn->prepare("UPDATE TA_Fioriture SET FI_CODICE = ?, FI_Note = ? WHERE FI_id = ?");
        $stmt->bind_param("ssi", $codice, $note, $fio_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO TA_Fioriture (FI_CODICE, FI_Note) VALUES (?, ?)");
        $stmt->bind_param("ss", $codice, $note);
    }
    
    if ($stmt->execute()) {
        header("Location: fioriture.php?status=ok");
        exit();
    }
}

// --- LOGICA AGGIUNTA COLLEGAMENTO APIARIO (TA_APIFIO) ---
if (isset($_POST['aggiungi_apiario'])) {
    $fio_id = $_POST['fa_codfio'];
    $api_id = $_POST['fa_cod_api'];
    $inizio = $_POST['fa_inizio'];
    $fine = !empty($_POST['fa_fine']) ? $_POST['fa_fine'] : null;

    $stmt = $conn->prepare("INSERT INTO TA_APIFIO (FA_CodFio, FA_COD_API, FA_inizio, FA_fine) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $fio_id, $api_id, $inizio, $fine);
    if ($stmt->execute()) {
        header("Location: fioriture.php?modifica=$fio_id&status=link_ok#tab-collegamenti");
        exit();
    }
}

// --- LOGICA ELIMINAZIONE COLLEGAMENTO ---
if (isset($_GET['del_api'])) {
    $del_id = (int)$_GET['del_api'];
    $conn->query("DELETE FROM TA_APIFIO WHERE FA_Id = $del_id");
    header("Location: fioriture.php?modifica=$modifica_fio_id#tab-collegamenti");
    exit();
}

// RECUPERO DATI PER MODIFICA
if ($modifica_fio_id) {
    $stmt = $conn->prepare("SELECT * FROM TA_Fioriture WHERE FI_id = ?");
    $stmt->bind_param("i", $modifica_fio_id);
    $stmt->execute();
    $curr = $stmt->get_result()->fetch_assoc();
}

// RECUPERO ELENCO FIORITURE
$res_fioriture = $conn->query("SELECT * FROM TA_Fioriture ORDER BY FI_CODICE ASC");
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2>Gestione Fioriture Nomadi</h2>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link active" onclick="openTab(event, 'tab-elenco')">ELENCO</li>
                <li class="tab-link" id="link-tab-form" onclick="openTab(event, 'tab-form')">
                    <?php echo $modifica_fio_id ? "MODIFICA" : "NUOVA"; ?>
                </li>
                <?php if ($modifica_fio_id): ?>
                    <li class="tab-link" id="link-tab-collegamenti" onclick="openTab(event, 'tab-collegamenti')">COLLEGAMENTI APIARI</li>
                <?php endif; ?>
            </ul>

            <div id="tab-elenco" class="tab-content active">
                <div class="table-container">
                    <table class="paleBlueRows">
                        <thead>
                            <tr>
                                <th>Codice</th>
                                <th>Note / Descrizione</th>
                                <th style="width: 100px; text-align: center;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($f = $res_fioriture->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($f['FI_CODICE']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($f['FI_Note']); ?></td>
                                    <td style="text-align: center;">
                                        <a href="?modifica=<?php echo $f['FI_id']; ?>" class="btn btn-modifica">✏️</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-form" class="tab-content">
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="fi_id" value="<?php echo $curr['FI_id']; ?>">
                        <div class="form-group">
                            <label>Codice Fioritura (es. ACAC, TIGL)</label>
                            <input type="text" name="fi_codice" maxlength="4" value="<?php echo htmlspecialchars($curr['FI_CODICE']); ?>" required style="text-transform: uppercase;">
                        </div>
                        <div class="form-group">
                            <label>Note / Descrizione Estesa</label>
                            <textarea name="fi_note" rows="3"><?php echo htmlspecialchars($curr['FI_Note']); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="salva_fioritura" class="btn btn-salva" style="flex: 1;">Salva Anagrafica</button>
                            <?php if ($modifica_fio_id): ?>
                                <a href="fioriture.php" class="btn btn-annulla" style="flex: 1; text-align: center; text-decoration: none; line-height: 2.2;">Annulla</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($modifica_fio_id): ?>
            <div id="tab-collegamenti" class="tab-content">
                <div class="form-container" style="margin-bottom: 20px; border: 1px dashed #ccc;">
                    <h4 style="margin-bottom: 10px; color: var(--secondary-bg);">Collega Apiario a <?php echo $curr['FI_CODICE']; ?></h4>
                    <form method="POST">
                        <input type="hidden" name="fa_codfio" value="<?php echo $modifica_fio_id; ?>">
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <div class="form-group" style="flex: 2; min-width: 200px;">
                                <label>Apiario:</label>
                                <select name="fa_cod_api" required>
                                    <?php
                                    $apiari = $conn->query("SELECT AI_ID, AI_LUOGO FROM TA_Apiari ORDER BY AI_LUOGO ASC");
                                    while ($a = $apiari->fetch_assoc()) echo "<option value='{$a['AI_ID']}'>{$a['AI_LUOGO']}</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Inizio:</label>
                                <input type="date" name="fa_inizio" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Fine:</label>
                                <input type="date" name="fa_fine">
                            </div>
                            <div style="flex: 0 0 100%; text-align: right;">
                                <button type="submit" name="aggiungi_apiario" class="btn btn-salva">Collega Apiario</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table class="paleBlueRows">
                        <thead>
                            <tr>
                                <th>Apiario</th>
                                <th>Inizio</th>
                                <th>Fine</th>
                                <th style="text-align: center;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT f.*, a.AI_LUOGO FROM TA_APIFIO f JOIN TA_Apiari a ON f.FA_COD_API = a.AI_ID WHERE f.FA_CodFio = ? ORDER BY f.FA_inizio DESC");
                            $stmt->bind_param("i", $modifica_fio_id);
                            $stmt->execute();
                            $links = $stmt->get_result();
                            while ($l = $links->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($l['AI_LUOGO']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($l['FA_inizio'])); ?></td>
                                    <td><?php echo $l['FA_fine'] ? date('d/m/Y', strtotime($l['FA_fine'])) : 'In corso'; ?></td>
                                    <td style="text-align: center;">
                                        <a href="?modifica=<?php echo $modifica_fio_id; ?>&del_api=<?php echo $l['FA_Id']; ?>" 
                                           class="btn btn-elimina" style="padding: 2px 8px;"
                                           onclick="return confirm('Rimuovere il collegamento?')">Rimuovi</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="versione-info">
            Versione: <?php echo FILE_VERSION; ?>
        </div>
    </div>

    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function openTab(evt, tabName) {
    $('.tab-content').hide().removeClass('active');
    $('.tab-link').removeClass('active');
    $('#' + tabName).show().addClass('active');
    $(evt.currentTarget).addClass('active');
    window.location.hash = tabName;
}

$(document).ready(function(){
    const hash = window.location.hash;
    if (hash) {
        $('.tab-link').removeClass('active');
        $(`.tab-link[onclick*="${hash.replace('#','')}"]`).addClass('active');
        $('.tab-content').hide();
        $(hash).show().addClass('active');
    } else if (<?php echo $modifica_fio_id ? 'true' : 'false'; ?>) {
        $('#link-tab-form').click();
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>