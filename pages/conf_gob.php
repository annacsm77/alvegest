<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.2.9 - Zero Inline Styles & Dynamic Titles');

require_once '../includes/config.php';

// --- 1. LOGICA CRUD E WIDGET IN ALTO ---
$utente_id = $_SESSION['user_id'] ?? 0;
$messaggio = "";
$id_modifica = $_GET["id_modifica"] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Gestione Widget
    if (isset($_POST['update_widgets'])) {
        $visibili = $_POST['widget_visibile'] ?? []; 
        $conn->query("UPDATE CF_WIDGET_POS SET WP_VISIBILE = 0 WHERE WP_UTENTE_ID = $utente_id");
        if (!empty($visibili)) {
            foreach ($visibili as $w_name) {
                $w_name_esc = $conn->real_escape_string($w_name);
                $conn->query("UPDATE CF_WIDGET_POS SET WP_VISIBILE = 1 WHERE WP_UTENTE_ID = $utente_id AND WP_WIDGET_NAME = '$w_name_esc'");
            }
        }
        header("Location: conf_gob.php?status=widget_updated&tab=widgets");
        exit();
    }

    // Logica originale CF_GLOB
    $id = $_POST["id"] ?? null;
    $cf_dato = trim($_POST["cf_dato"] ?? '');
    $cf_val = trim($_POST["cf_val"] ?? '');
    $cf_descr = trim($_POST["cf_descr"] ?? '');

    if (isset($_POST["inserisci"])) {
        $stmt = $conn->prepare("INSERT INTO CF_GLOB (CF_DATO, CF_VAL, CF_DESCR) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $cf_dato, $cf_val, $cf_descr);
        if ($stmt->execute()) { header("Location: conf_gob.php?status=insert_success&tab=lista"); exit(); }
    } elseif (isset($_POST["modifica"]) && $id) {
        $stmt = $conn->prepare("UPDATE CF_GLOB SET CF_DATO = ?, CF_VAL = ?, CF_DESCR = ? WHERE ID = ?");
        $stmt->bind_param("sssi", $cf_dato, $cf_val, $cf_descr, $id);
        if ($stmt->execute()) { header("Location: conf_gob.php?status=update_success&tab=lista"); exit(); }
    } elseif (isset($_POST["elimina"]) && $id) {
        $stmt = $conn->prepare("DELETE FROM CF_GLOB WHERE ID = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) { header("Location: conf_gob.php?status=delete_success&tab=lista"); exit(); }
    }
}

require_once TPL_PATH . 'header.php';

// 3. RECUPERO DATI E MESSAGGI
$active_tab = $_GET['tab'] ?? 'lista';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'insert_success') $messaggio = "<p class='successo'>Parametro inserito!</p>";
    if ($_GET['status'] == 'update_success') $messaggio = "<p class='successo'>Dati aggiornati!</p>";
    if ($_GET['status'] == 'delete_success') $messaggio = "<p class='successo txt-danger font-bold'>Parametro rimosso!</p>";
    if ($_GET['status'] == 'widget_updated') $messaggio = "<p class='successo'>Configurazione widget salvata!</p>";
}

$row_edit = ['ID'=>'','CF_DATO'=>'','CF_VAL'=>'','CF_DESCR'=>''];
if ($id_modifica) {
    $res = $conn->query("SELECT * FROM CF_GLOB WHERE ID = $id_modifica");
    if ($row = $res->fetch_assoc()) $row_edit = $row;
}

$result_list = $conn->query("SELECT * FROM CF_GLOB ORDER BY CF_DATO ASC");
$result_widgets = $conn->query("SELECT * FROM CF_WIDGET_POS WHERE WP_UTENTE_ID = $utente_id ORDER BY WP_WIDGET_NAME ASC");
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Configurazioni Globali</h2>
        
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo $active_tab == 'lista' ? 'active' : ''; ?>" id="link-tab-lista" onclick="openTab(event, 'tab-lista')">ELENCO</li>
                <li class="tab-link <?php echo ($active_tab == 'form' || $id_modifica) ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')">
                    <?php echo $id_modifica ? "MODIFICA" : "NUOVO"; ?>
                </li>
                <li class="tab-link <?php echo $active_tab == 'widgets' ? 'active' : ''; ?>" id="link-tab-widgets" onclick="openTab(event, 'tab-widgets')">WIDGETS</li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo $active_tab == 'lista' ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="selectable-table">
                        <thead><tr><th>DATO</th><th>VALORE</th><th class="txt-center">AZIONI</th></tr></thead>
                        <tbody>
                            <?php while ($row = $result_list->fetch_assoc()): ?>
                            <tr>
                                <td class="font-bold"><?php echo htmlspecialchars($row["CF_DATO"]); ?></td>
                                <td class="txt-small"><?php echo htmlspecialchars(substr($row["CF_VAL"], 0, 50)); ?>...</td>
                                <td class="txt-center"><a href="conf_gob.php?id_modifica=<?php echo $row['ID']; ?>&tab=form" class="btn-tabella-modifica">Modifica</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-form" class="tab-content <?php echo ($active_tab == 'form' || $id_modifica) ? 'active' : ''; ?>">
                <div class="form-container">
                    <form method="post">
                        <input type="hidden" name="id" value="<?php echo $row_edit['ID']; ?>">
                        <div class="form-group">
                            <label>Nome Dato:</label>
                            <input type="text" name="cf_dato" value="<?php echo htmlspecialchars($row_edit['CF_DATO']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Valore:</label>
                            <textarea name="cf_val" rows="4"><?php echo htmlspecialchars($row_edit['CF_VAL']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Descrizione:</label>
                            <input type="text" name="cf_descr" value="<?php echo htmlspecialchars($row_edit['CF_DESCR']); ?>">
                        </div>
                        <div class="btn-group-flex">
                            <button type="submit" name="<?php echo $id_modifica ? 'modifica' : 'inserisci'; ?>" class="btn btn-salva btn-flex-2">
                                <?php echo $id_modifica ? "Salva Modifiche" : "Inserisci Parametro"; ?>
                            </button>
                            <?php if ($id_modifica): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina btn-flex-1" onclick="return confirm('Eliminare questo parametro?')">Elimina</button>
                                <a href="conf_gob.php" class="btn btn-annulla btn-flex-1">Annulla</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div id="tab-widgets" class="tab-content <?php echo $active_tab == 'widgets' ? 'active' : ''; ?>">
                <div class="form-container">
                    <form method="post">
                        <div class="table-container">
                            <table class="selectable-table">
                                <thead><tr><th class="txt-center">VISIBILE</th><th>NOME WIDGET</th><th class="txt-center">POSIZIONE</th></tr></thead>
                                <tbody>
                                    <?php while($w = $result_widgets->fetch_assoc()): ?>
                                    <tr>
                                        <td class="txt-center"><input type="checkbox" name="widget_visibile[]" value="<?php echo $w['WP_WIDGET_NAME']; ?>" <?php echo ($w['WP_VISIBILE'] == 1) ? 'checked' : ''; ?>></td>
                                        <td class="font-bold"><?php echo htmlspecialchars($w['WP_WIDGET_NAME']); ?></td>
                                        <td class="txt-center txt-small txt-muted"><?php echo $w['WP_X']; ?>, <?php echo $w['WP_Y']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="btn-group-flex">
                            <button type="submit" name="update_widgets" class="btn btn-salva btn-flex-2">Salva Configurazione Widget</button>
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
    const tabParam = urlParams.get('tab') || 'lista';
    if(tabParam === 'form' || <?php echo $id_modifica ? 'true' : 'false'; ?>) {
        openTab(null, 'tab-form');
    } else if (tabParam === 'widgets') {
        openTab(null, 'tab-widgets');
    } else {
        openTab(null, 'tab-lista');
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>