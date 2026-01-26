<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.2.5 - Fix Titoli Dinamici');

require_once '../includes/config.php';

$messaggio = ""; 

// --- 1. GESTIONE LOGICA POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post = $_POST["id"] ?? null;
    $atti = isset($_POST["atti"]) ? 1 : 0;
    $attenz = isset($_POST["attenzione"]) ? 1 : 0;
    $nucl = isset($_POST["nucl"]) ? 1 : 0;
    $posizione = !empty($_POST["posizione"]) ? (int)$_POST["posizione"] : 0;
    $tipologia = !empty($_POST["tipologia_arnia"]) ? (int)$_POST["tipologia_arnia"] : null; 
    
    $prop = $_POST['prop'] ?? '';
    $creg = $_POST['creg'] ?? '';
    $treg = $_POST['treg'] ?? '';
    
    $data_input = $_POST["data"] ?? '';
    $data_db = null;
    $data_obj = DateTime::createFromFormat('d-m-Y', $data_input);
    if ($data_obj) { $data_db = $data_obj->format('Y-m-d'); }

    $ricerca_p = $_POST["ricerca_hidden"] ?? "";
    $filtro_p = $_POST["filtro_hidden"] ?? "attive";
    $page_p = $_POST["page_hidden"] ?? 1;
    $query_string = "ricerca=" . urlencode($ricerca_p) . "&filtro_stato=" . urlencode($filtro_p) . "&page=" . $page_p;

    try {
        if (isset($_POST["salva"])) {
            if ($id_post) {
                $sql = "UPDATE AP_Arnie SET AR_CODICE=?, AR_NOME=?, AR_LUOGO=?, AR_posizione=?, AR_DATA=?, AR_Note=?, AR_ATTI=?, AR_attenzione=?, AR_NUCL=?, AR_PROP=?, AR_CREG=?, AR_TREG=?, AR_TIPA=? WHERE AR_ID=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiisssiiissii", $_POST['codice'], $_POST['nome_arnia'], $_POST['luogo'], $posizione, $data_db, $_POST['note'], $atti, $attenz, $nucl, $prop, $creg, $treg, $tipologia, $id_post);
            } else {
                $sql = "INSERT INTO AP_Arnie (AR_CODICE, AR_NOME, AR_LUOGO, AR_posizione, AR_DATA, AR_Note, AR_ATTI, AR_attenzione, AR_NUCL, AR_PROP, AR_CREG, AR_TREG, AR_TIPA) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiisssiiissi", $_POST['codice'], $_POST['nome_arnia'], $_POST['luogo'], $posizione, $data_db, $_POST['note'], $atti, $attenz, $nucl, $prop, $creg, $treg, $tipologia);
            }
            if ($stmt->execute()) {
                header("Location: arnie.php?status=success&tab=tab-lista&" . $query_string);
                exit();
            }
        } elseif (isset($_POST["elimina"]) && $id_post) {
            $conn->query("DELETE FROM TR_FFASE WHERE TF_ARNIA = $id_post");
            $conn->query("DELETE FROM MA_MOVI WHERE MV_ARNIA_ID = $id_post");
            $conn->query("DELETE FROM AT_INSATT WHERE IA_IDAR = $id_post");
            $stmt = $conn->prepare("DELETE FROM AP_Arnie WHERE AR_ID = ?");
            $stmt->bind_param("i", $id_post);
            $stmt->execute();
            header("Location: arnie.php?status=deleted&tab=tab-lista&" . $query_string);
            exit();
        }
    } catch (Exception $e) { $messaggio = "<p class='errore'>Errore: " . $e->getMessage() . "</p>"; }
}

require_once TPL_PATH . 'header.php';

// --- 2. RECUPERO PARAMETRI ---
$modifica_id = $_GET["modifica"] ?? null;
$ricerca = $_GET["ricerca"] ?? "";
$filtro_stato = $_GET["filtro_stato"] ?? "attive";
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
$query_string_get = "ricerca=" . urlencode($ricerca) . "&filtro_stato=" . urlencode($filtro_stato) . "&page=" . $page;

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') $messaggio = "<p class='successo'>Operazione completata!</p>";
    if ($_GET['status'] == 'deleted') $messaggio = "<p class='successo txt-danger font-bold'>Arnia eliminata definitivamente.</p>";
}

$limit = 50; $offset = ($page - 1) * $limit;
$where_clauses = ["1=1"]; $bind_types = ""; $bind_values = [];
if ($filtro_stato === "attive") { $where_clauses[] = "A.AR_ATTI = 0"; }
if (!empty($ricerca)) {
    $where_clauses[] = "(A.AR_CODICE LIKE ? OR A.AR_NOME LIKE ? OR L.AI_LUOGO LIKE ? OR A.AR_Note LIKE ?)";
    $term = "%$ricerca%"; $bind_types .= "ssss"; $bind_values = array_merge($bind_values, [$term, $term, $term, $term]);
}
$where_sql = implode(" AND ", $where_clauses);

$sql_list = "SELECT A.*, L.AI_LUOGO FROM AP_Arnie A LEFT JOIN TA_Apiari L ON A.AR_LUOGO = L.AI_ID WHERE $where_sql ORDER BY A.AR_CODICE ASC LIMIT ? OFFSET ?";
$stmt_list = $conn->prepare($sql_list);
$stmt_list->bind_param($bind_types . "ii", ...array_merge($bind_values, [$limit, $offset]));
$stmt_list->execute();
$arnie = $stmt_list->get_result();
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Gestione Arnie</h2>
        
        <div class="filtri-container">
            <form method="GET" action="arnie.php" class="btn-group-flex-filter">
                <input type="text" name="ricerca" placeholder="Cerca..." value="<?php echo htmlspecialchars($ricerca); ?>">
                <select name="filtro_stato">
                    <option value="attive" <?php echo ($filtro_stato == 'attive') ? 'selected' : ''; ?>>Arnie Attive</option>
                    <option value="tutte" <?php echo ($filtro_stato == 'tutte') ? 'selected' : ''; ?>>Tutte le Arnie</option>
                </select>
                <button type="submit" class="btn btn-stampa">Filtra</button>
                <a href="arnie.php" class="btn btn-annulla">Reset</a>
            </form>
        </div>

        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$modifica_id ? 'active' : ''; ?>" id="link-tab-lista" onclick="openTab(event, 'tab-lista')">ELENCO</li>
                <li class="tab-link <?php echo $modifica_id ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')"><?php echo $modifica_id ? "MODIFICA" : "NUOVA"; ?></li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$modifica_id ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="selectable-table">
                        <thead>
                            <tr><th>Codice</th><th>Nome</th><th>Apiario</th><th class="txt-center">Pos.</th><th>Stato</th><th class="txt-center">Azioni</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($a = $arnie->fetch_assoc()): ?>
                                <tr <?php echo ($a['AR_attenzione'] == 1) ? 'class="riga-pericolo"' : ''; ?>>
                                    <td class="font-bold">
                                        <?php echo htmlspecialchars($a['AR_CODICE']); ?><?php echo ($a['AR_attenzione'] == 1) ? ' 🚨' : ''; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($a['AR_NOME'] ?? ''); ?></td>
                                    <td class="txt-small"><?php echo htmlspecialchars($a['AI_LUOGO'] ?? 'N/D'); ?></td>
                                    <td class="txt-center"><?php echo htmlspecialchars($a['AR_posizione'] ?? '0'); ?></td>
                                    <td class="txt-small"><?php echo ($a['AR_ATTI'] == 1) ? 'Dismessa' : 'Attiva'; ?></td>
                                    <td class="txt-center">
                                        <a href="gestatt.php?arnia_id=<?php echo $a['AR_ID']; ?>&tab=attivita" class="btn btn-stampa">Attività</a>
                                        <a href="arnie.php?modifica=<?php echo $a['AR_ID']; ?>&<?php echo $query_string_get; ?>&tab=tab-form" class="btn-tabella-modifica">Modifica</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-form" class="tab-content <?php echo $modifica_id ? 'active' : ''; ?>">
                <?php
                $curr = ['AR_ID'=>'','AR_CODICE'=>'','AR_NOME'=>'','AR_LUOGO'=>'','AR_posizione'=>0,'AR_DATA'=>'','AR_Note'=>'','AR_ATTI'=>0,'AR_attenzione'=>0,'AR_NUCL'=>0,'AR_PROP'=>'','AR_CREG'=>'','AR_TREG'=>'','AR_TIPA'=>null];
                if ($modifica_id) {
                    $st = $conn->prepare("SELECT * FROM AP_Arnie WHERE AR_ID = ?");
                    $st->bind_param("i", $modifica_id); $st->execute(); $curr = $st->get_result()->fetch_assoc();
                }
                $data_val = !empty($curr['AR_DATA']) ? date('d-m-Y', strtotime($curr['AR_DATA'])) : date('d-m-Y');
                ?>
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $curr['AR_ID']; ?>">
                        <input type="hidden" name="ricerca_hidden" value="<?php echo htmlspecialchars($ricerca); ?>">
                        <input type="hidden" name="filtro_hidden" value="<?php echo htmlspecialchars($filtro_stato); ?>">
                        <input type="hidden" name="page_hidden" value="<?php echo $page; ?>">
                        
                        <div class="form-group">
                            <label>Codice Arnia:</label>
                            <input type="text" name="codice" value="<?php echo htmlspecialchars($curr['AR_CODICE']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nome:</label>
                            <input type="text" name="nome_arnia" value="<?php echo htmlspecialchars($curr['AR_NOME']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tipologia:</label>
                            <select name="tipologia_arnia">
                                <option value="">-- Seleziona --</option>
                                <?php
                                $tips = $conn->query("SELECT TI_id, TI_DESCR FROM TA_TIPA ORDER BY TI_DESCR ASC");
                                while ($t = $tips->fetch_assoc()) {
                                    echo "<option value='{$t['TI_id']}' ".($t['TI_id'] == $curr['AR_TIPA'] ? "selected" : "").">{$t['TI_DESCR']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Apiario e Posizione:</label>
                            <div class="btn-group-flex"> 
                                <select name="luogo" class="btn-flex-2">
                                    <?php
                                    $aps = $conn->query("SELECT AI_ID, AI_LUOGO FROM TA_Apiari ORDER BY AI_LUOGO ASC");
                                    while ($api = $aps->fetch_assoc()) {
                                        echo "<option value='{$api['AI_ID']}' ".($api['AI_ID'] == $curr['AR_LUOGO'] ? "selected" : "").">{$api['AI_LUOGO']}</option>";
                                    }
                                    ?>
                                </select>
                                <input type="number" name="posizione" value="<?php echo (int)$curr['AR_posizione']; ?>" class="btn-flex-1 txt-center">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Note:</label>
                            <textarea name="note" rows="3"><?php echo htmlspecialchars($curr['AR_Note']); ?></textarea>
                        </div>
                        
                        <div class="btn-group-flex">
                            <button type="submit" name="salva" class="btn btn-salva btn-flex-2">
                                <?php echo $modifica_id ? "Salva Modifiche" : "Inserisci Nuova Arnia"; ?>
                            </button>
                            <?php if ($modifica_id): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina btn-flex-1" onclick="return confirm('Eliminare definitivamente?')">Elimina</button>
                                <a href="arnie.php?<?php echo $query_string_get; ?>" class="btn btn-annulla btn-flex-1">Annulla</a>
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
    if(tabParam === "tab-form" || <?php echo $modifica_id ? 'true' : 'false'; ?>) {
        openTab(null, 'tab-form');
    } else {
        openTab(null, 'tab-lista');
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>