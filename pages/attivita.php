<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.0.4 - Zero Inline Styles & Dynamic Titles');

require_once '../includes/config.php';

// --- 1. LOGICA CRUD IN ALTO ---

// ELIMINAZIONE
if (isset($_GET['elimina'])) {
    $id_del = (int)$_GET['elimina'];
    $stmt = $conn->prepare("DELETE FROM TA_Attivita WHERE AT_ID = ?");
    $stmt->bind_param("i", $id_del);
    if ($stmt->execute()) {
        header("Location: attivita.php?status=del_success&tab=tab-lista");
        exit();
    }
}

// SALVATAGGIO (Inserimento o Modifica)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salva_attivita'])) {
    $id = $_POST['id'] ?? null;
    $descrizione = trim($_POST["descrizione"] ?? '');
    $note = trim($_POST["note"] ?? '');
    $nr_ripetizioni = (int)$_POST["nr_ripetizioni"];
    $gg_validita = (int)$_POST["gg_validita"];
    $is_trattamento = isset($_POST["is_trattamento"]) ? 1 : 0;
    $at_mag_id = !empty($_POST["at_mag_id"]) ? (int)$_POST["at_mag_id"] : null;
    $at_scarico_fisso = isset($_POST["at_scarico_fisso"]) ? 1 : 0;

    if ($id) {
        $sql = "UPDATE TA_Attivita SET AT_DESCR=?, AT_NOTE=?, AT_NR=?, AT_GG=?, AT_TRAT=?, AT_MAG_ID=?, AT_SCARICO_FISSO=? WHERE AT_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiiiii", $descrizione, $note, $nr_ripetizioni, $gg_validita, $is_trattamento, $at_mag_id, $at_scarico_fisso, $id);
    } else {
        $sql = "INSERT INTO TA_Attivita (AT_DESCR, AT_NOTE, AT_NR, AT_GG, AT_TRAT, AT_MAG_ID, AT_SCARICO_FISSO) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiiii", $descrizione, $note, $nr_ripetizioni, $gg_validita, $is_trattamento, $at_mag_id, $at_scarico_fisso);
    }

    if ($stmt->execute()) {
        $status = $id ? "update_success" : "insert_success";
        header("Location: attivita.php?status=$status&tab=tab-lista");
        exit();
    }
}

require_once TPL_PATH . 'header.php';

$messaggio = "";
if (isset($_GET['status'])) {
    $st = $_GET['status'];
    if ($st == 'insert_success') $messaggio = "<p class='successo'>Attività inserita correttamente!</p>";
    if ($st == 'update_success') $messaggio = "<p class='successo'>Dati aggiornati!</p>";
    if ($st == 'del_success') $messaggio = "<p class='successo txt-danger font-bold'>Attività rimossa definitivamente.</p>";
}

$edit_id = $_GET['edit'] ?? null;
$row_edit = ['AT_ID'=>'','AT_DESCR'=>'','AT_NOTE'=>'','AT_NR'=>0,'AT_GG'=>0,'AT_TRAT'=>0,'AT_MAG_ID'=>'','AT_SCARICO_FISSO'=>0];
if ($edit_id) {
    $res = $conn->query("SELECT * FROM TA_Attivita WHERE AT_ID = $edit_id");
    if ($row = $res->fetch_assoc()) $row_edit = $row;
}
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Gestione Attività</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$edit_id ? 'active' : ''; ?>" id="link-tab-lista" onclick="openTab(event, 'tab-lista')">ELENCO</li>
                <li class="tab-link <?php echo $edit_id ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')">
                    <?php echo $edit_id ? "MODIFICA" : "NUOVA"; ?>
                </li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$edit_id ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="selectable-table">
                        <thead>
                            <tr>
                                <th>DESCRIZIONE</th>
                                <th class="txt-center">TRATT.</th>
                                <th>MAGAZZINO</th>
                                <th class="txt-center">AZIONI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $lista = $conn->query("SELECT T.*, M.TM_Descrizione FROM TA_Attivita T LEFT JOIN TA_MAG M ON T.AT_MAG_ID = M.ID ORDER BY AT_DESCR ASC");
                            while ($r = $lista->fetch_assoc()):
                            ?>
                            <tr>
                                <td class="font-bold"><?php echo htmlspecialchars($r['AT_DESCR']); ?></td>
                                <td class="txt-center"><?php echo ($r['AT_TRAT'] == 1) ? '✅' : ''; ?></td>
                                <td class="txt-small txt-muted"><?php echo htmlspecialchars($r['TM_Descrizione'] ?? 'Nessuno'); ?></td>
                                <td class="txt-center">
                                    <a href="attivita.php?edit=<?php echo $r['AT_ID']; ?>&tab=tab-form" class="btn-tabella-modifica">Modifica</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-form" class="tab-content <?php echo $edit_id ? 'active' : ''; ?>">
                <div class="form-container">
                    <form action="attivita.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $row_edit['AT_ID']; ?>">
                        
                        <div class="form-group">
                            <label>Descrizione attività:</label>
                            <input type="text" name="descrizione" value="<?php echo htmlspecialchars($row_edit['AT_DESCR']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Sottomastro (Magazzino):</label>
                            <select name="at_mag_id">
                                <option value="">-- Nessun collegamento --</option>
                                <?php
                                $sm_res = $conn->query("SELECT ID, TM_Mastro, TM_SMastro, TM_Descrizione FROM TA_MAG WHERE TM_SMastro > 0 ORDER BY TM_Mastro, TM_SMastro");
                                while($sm = $sm_res->fetch_assoc()) {
                                    $s = ($row_edit['AT_MAG_ID'] == $sm['ID']) ? 'selected' : '';
                                    echo "<option value='{$sm['ID']}' $s>[{$sm['TM_Mastro']}.{$sm['TM_SMastro']}] {$sm['TM_Descrizione']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group-checkbox">
                            <input type="checkbox" name="at_scarico_fisso" id="chk_f" value="1" <?php echo ($row_edit['AT_SCARICO_FISSO'] == 1) ? 'checked' : ''; ?>>
                            <label for="chk_f">Scarico fisso (1 unità)</label>
                        </div>

                        <div class="btn-group-flex">
                            <div class="form-group btn-flex-1">
                                <label>N. Ripetizioni:</label>
                                <input type="number" name="nr_ripetizioni" value="<?php echo $row_edit['AT_NR']; ?>">
                            </div>
                            <div class="form-group btn-flex-1">
                                <label>Validità (giorni):</label>
                                <input type="number" name="gg_validita" value="<?php echo $row_edit['AT_GG']; ?>">
                            </div>
                        </div>

                        <div class="form-group-checkbox">
                            <input type="checkbox" name="is_trattamento" id="chk_t" value="1" <?php echo ($row_edit['AT_TRAT'] == 1) ? 'checked' : ''; ?>>
                            <label for="chk_t">È un trattamento sanitario</label>
                        </div>

                        <div class="form-group">
                            <label>Note aggiuntive:</label>
                            <textarea name="note" rows="3"><?php echo htmlspecialchars($row_edit['AT_NOTE']); ?></textarea>
                        </div>

                        <div class="btn-group-flex">
                            <button type="submit" name="salva_attivita" class="btn btn-salva btn-flex-2">
                                <?php echo $edit_id ? "Salva Modifiche" : "Inserisci Attività"; ?>
                            </button>
                            <?php if($edit_id): ?>
                                <a href="attivita.php?elimina=<?php echo $edit_id; ?>" class="btn btn-elimina btn-flex-1" onclick="return confirm('Eliminare questa attività?')">Elimina</a>
                                <a href="attivita.php" class="btn btn-annulla btn-flex-1">Annulla</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>
    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function openTab(evt, tabName) {
    $('.tab-content').hide().removeClass('active');
    $('.tab-link').removeClass('active');
    $('#' + tabName).show().addClass('active');
    if (evt) $(evt.currentTarget).addClass('active');
}

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if(tabParam === 'tab-form' || <?php echo $edit_id ? 'true' : 'false'; ?>) {
        openTab(null, 'tab-form');
    } else {
        openTab(null, 'tab-lista');
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>