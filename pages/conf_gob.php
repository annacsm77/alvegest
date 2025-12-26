<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.2.5 (Template Tabs)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php';

// --- LOGICA CRUD ---

$messaggio = "";
$id_modifica = $_GET["id_modifica"] ?? null;
$search_query = trim($_GET['search'] ?? '');

// 1. GESTIONE OPERAZIONI POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"] ?? null;
    $cf_dato = trim($_POST["cf_dato"] ?? '');
    $cf_val = trim($_POST["cf_val"] ?? '');
    $cf_descr = trim($_POST["cf_descr"] ?? '');

    if (isset($_POST["inserisci"])) {
        $sql = "INSERT INTO CF_GLOB (CF_DATO, CF_VAL, CF_DESCR) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $cf_dato, $cf_val, $cf_descr);
            if ($stmt->execute()) {
                header("Location: conf_gob.php?status=insert_success");
                exit();
            }
            $stmt->close();
        }
    } elseif (isset($_POST["modifica"]) && $id) {
        $sql = "UPDATE CF_GLOB SET CF_DATO = ?, CF_VAL = ?, CF_DESCR = ? WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssi", $cf_dato, $cf_val, $cf_descr, $id);
            if ($stmt->execute()) {
                header("Location: conf_gob.php?status=update_success");
                exit();
            }
            $stmt->close();
        }
    } elseif (isset($_POST["elimina"]) && $id) {
        $sql = "DELETE FROM CF_GLOB WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: conf_gob.php?status=delete_success");
                exit();
            }
            $stmt->close();
        }
    }
}

// 2. MESSAGGI DI STATO
if (isset($_GET["status"])) {
    $st = $_GET["status"];
    if ($st == "insert_success") $messaggio = "<p class='successo'>Parametro inserito correttamente!</p>";
    if ($st == "update_success") $messaggio = "<p class='successo'>Parametro aggiornato!</p>";
    if ($st == "delete_success") $messaggio = "<p class='successo'>Parametro eliminato!</p>";
}

// 3. RECUPERO DATI PER MODIFICA
$cf_dato_m = ''; $cf_val_m = ''; $cf_descr_m = ''; $readonly = '';
if ($id_modifica) {
    $stmt = $conn->prepare("SELECT CF_DATO, CF_VAL, CF_DESCR FROM CF_GLOB WHERE ID = ?");
    $stmt->bind_param("i", $id_modifica);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($config = $res->fetch_assoc()) {
        $cf_dato_m = $config["CF_DATO"];
        $cf_val_m = $config["CF_VAL"];
        $cf_descr_m = $config["CF_DESCR"];
        $readonly = 'readonly';
    }
    $stmt->close();
}

// 4. RECUPERO LISTA FILTRATA
$where = " WHERE 1=1 ";
if (!empty($search_query)) {
    $ric_safe = $conn->real_escape_string($search_query);
    $where .= " AND CF_DATO LIKE '%$ric_safe%' ";
}
$result_list = $conn->query("SELECT * FROM CF_GLOB $where ORDER BY CF_DATO ASC");
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Configurazioni Globali</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$id_modifica ? 'active' : ''; ?>" id="link-tab-lista" onclick="openTab(event, 'tab-lista')">Elenco Parametri</li>
                <li class="tab-link <?php echo $id_modifica ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')">
                    <?php echo $id_modifica ? "Modifica Parametro" : "Nuovo Parametro"; ?>
                </li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$id_modifica ? 'active' : ''; ?>">
                <div class="filtri-container">
                    <form method="GET" action="conf_gob.php" class="filtro-form">
                        <input type="text" name="search" placeholder="Cerca parametro..." value="<?php echo htmlspecialchars($search_query); ?>" class="campo-ricerca">
                        <button type="submit" class="btn btn-stampa">Cerca</button>
                        <a href="conf_gob.php" class="btn btn-annulla">Reset</a>
                    </form>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Dato Globale</th>
                                <th>Valore</th>
                                <th>Descrizione</th>
                                <th class="action-cell">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_list->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row["CF_DATO"]); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($row["CF_VAL"], 0, 40)) . (strlen($row["CF_VAL"]) > 40 ? '...' : ''); ?></td>
                                <td><small><?php echo htmlspecialchars($row["CF_DESCR"]); ?></small></td>
                                <td class="action-cell">
                                    <a href="conf_gob.php?id_modifica=<?php echo $row['ID']; ?>&search=<?php echo urlencode($search_query); ?>#tab-form" class="btn btn-modifica">Modifica</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-form" class="tab-content <?php echo $id_modifica ? 'active' : ''; ?>">
                <div class="form-container">
                    <form action="conf_gob.php" method="post">
                        <?php if ($id_modifica): ?> <input type="hidden" name="id" value="<?php echo $id_modifica; ?>"> <?php endif; ?>

                        <div class="form-group">
                            <label>Dato Globale (Chiave):</label>
                            <input type="text" name="cf_dato" value="<?php echo htmlspecialchars($cf_dato_m); ?>" maxlength="20" required <?php echo $readonly; ?>>
                        </div>
                        <div class="form-group">
                            <label>Valore:</label>
                            <textarea name="cf_val" rows="3" required><?php echo htmlspecialchars($cf_val_m); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Descrizione:</label>
                            <textarea name="cf_descr" rows="2"><?php echo htmlspecialchars($cf_descr_m); ?></textarea>
                        </div>

                        <div class="btn-group-form" style="display:flex; gap:10px; margin-top:20px;">
                            <button type="submit" name="<?php echo $id_modifica ? 'modifica' : 'inserisci'; ?>" class="btn btn-salva" style="flex:2;">Salva Parametro</button>
                            <?php if ($id_modifica): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina" onclick="return confirm('Eliminare definitivamente questo parametro?')">Elimina</button>
                                <a href="conf_gob.php?search=<?php echo urlencode($search_query); ?>" class="btn btn-annulla">Annulla</a>
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

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");
    document.getElementById(tabName).style.display = "block";
    if (evt) evt.currentTarget.className += " active";
}

window.addEventListener('DOMContentLoaded', () => {
    if(window.location.search.indexOf('id_modifica=') > -1 || window.location.hash === "#tab-form") {
        document.getElementById('link-tab-form').click();
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>