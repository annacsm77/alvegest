<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.1.4 (Fix Eliminazione a Cascata e Link Tab)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php';

// --- 1. INIZIALIZZAZIONE E RECUPERO PARAMETRI ---
$messaggio = "";
$modifica_id = $_GET["modifica"] ?? null;
$ricerca = $_GET["ricerca"] ?? "";
$filtro_stato = $_GET["filtro_stato"] ?? "attive";
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;

$query_string = "ricerca=" . urlencode($ricerca) . "&filtro_stato=" . urlencode($filtro_stato) . "&page=" . $page;

// --- 2. GESTIONE LOGICA POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post = $_POST["id"] ?? null;
    $atti = isset($_POST["atti"]) ? 1 : 0;
    $nucl = isset($_POST["nucl"]) ? 1 : 0;
    
    $prop = $_POST['prop'] ?? '';
    $creg = $_POST['creg'] ?? '';
    $treg = $_POST['treg'] ?? '';
    
    $data_input = $_POST["data"] ?? '';
    $data_db = null;
    $data_obj = DateTime::createFromFormat('d-m-Y', $data_input);
    if ($data_obj) {
        $data_db = $data_obj->format('Y-m-d');
    }

    try {
        if (isset($_POST["salva"])) {
            if ($id_post) {
                $sql = "UPDATE AP_Arnie SET AR_CODICE=?, AR_LUOGO=?, AR_DATA=?, AR_Note=?, AR_ATTI=?, AR_NUCL=?, AR_PROP=?, AR_CREG=?, AR_TREG=? WHERE AR_ID=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sisssisssi", $_POST['codice'], $_POST['luogo'], $data_db, $_POST['note'], $atti, $nucl, $prop, $creg, $treg, $id_post);
            } else {
                $sql = "INSERT INTO AP_Arnie (AR_CODICE, AR_LUOGO, AR_DATA, AR_Note, AR_ATTI, AR_NUCL, AR_PROP, AR_CREG, AR_TREG) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sisssisss", $_POST['codice'], $_POST['luogo'], $data_db, $_POST['note'], $atti, $nucl, $prop, $creg, $treg);
            }
            if ($stmt->execute()) {
                header("Location: arnie.php?status=success&" . $query_string);
                exit();
            }
        } elseif (isset($_POST["elimina"]) && $id_post) {
            // --- ELIMINAZIONE A CASCATA BASATA SU SCHEMA SQL ---
            // 1. Elimina record in TR_FFASE (Trattamenti)
            $conn->query("DELETE FROM TR_FFASE WHERE TF_ARNIA = $id_post");
            
            // 2. Elimina record in MA_MOVI (Movimenti Magazzino collegati all'arnia)
            $conn->query("DELETE FROM MA_MOVI WHERE MV_ARNIA_ID = $id_post");
            
            // 3. Elimina record in AT_INSATT (Attività/Ispezioni)
            $conn->query("DELETE FROM AT_INSATT WHERE IA_IDAR = $id_post");

            // 4. Infine elimina l'Arnia
            $stmt = $conn->prepare("DELETE FROM AP_Arnie WHERE AR_ID = ?");
            $stmt->bind_param("i", $id_post);
            $stmt->execute();
            
            header("Location: arnie.php?status=deleted&" . $query_string);
            exit();
        }
    } catch (Exception $e) { $messaggio = "<p class='errore'>Errore: " . $e->getMessage() . "</p>"; }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') $messaggio = "<p class='successo' style='color:green; font-weight:bold;'>Operazione completata con successo!</p>";
    if ($_GET['status'] == 'deleted') $messaggio = "<p class='successo' style='color:red; font-weight:bold;'>Arnia e dati collegati eliminati!</p>";
}

// --- 3. PREPARAZIONE QUERY CON FILTRI ---
$limit = 50;
$offset = ($page - 1) * $limit;
$where_clauses = ["1=1"];
$bind_types = "";
$bind_values = [];

if ($filtro_stato === "attive") {
    $where_clauses[] = "A.AR_ATTI = 0";
}

if (!empty($ricerca)) {
    $where_clauses[] = "(A.AR_CODICE LIKE ? OR L.AI_LUOGO LIKE ? OR A.AR_Note LIKE ?)";
    $term = "%$ricerca%";
    $bind_types .= "sss";
    $bind_values = array_merge($bind_values, [$term, $term, $term]);
}

$where_sql = implode(" AND ", $where_clauses);

$sql_count = "SELECT COUNT(*) as total FROM AP_Arnie A LEFT JOIN TA_Apiari L ON A.AR_LUOGO = L.AI_ID WHERE $where_sql";
$stmt_count = $conn->prepare($sql_count);
if (!empty($bind_values)) $stmt_count->bind_param($bind_types, ...$bind_values);
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql_list = "SELECT A.*, L.AI_LUOGO FROM AP_Arnie A LEFT JOIN TA_Apiari L ON A.AR_LUOGO = L.AI_ID WHERE $where_sql ORDER BY A.AR_CODICE ASC LIMIT ? OFFSET ?";
$stmt_list = $conn->prepare($sql_list);
$list_bind_types = $bind_types . "ii";
$list_bind_values = array_merge($bind_values, [$limit, $offset]);
$stmt_list->bind_param($list_bind_types, ...$list_bind_values);
$stmt_list->execute();
$arnie = $stmt_list->get_result();
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2>Gestione Arnie</h2>
        
        <div class="filtri-container">
            <form method="GET" action="arnie.php">
                <input type="text" name="ricerca" placeholder="Cerca codice..." value="<?php echo htmlspecialchars($ricerca); ?>">
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
                <li class="tab-link <?php echo !$modifica_id ? 'active' : ''; ?>" onclick="openTab(event, 'tab-lista')">Elenco</li>
                <li class="tab-link <?php echo $modifica_id ? 'active' : ''; ?>" onclick="openTab(event, 'tab-form')"><?php echo $modifica_id ? "Modifica" : "Nuova"; ?></li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$modifica_id ? 'active' : ''; ?>">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Codice</th><th>Apiario</th><th>Stato</th><th>Azioni</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($a = $arnie->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($a['AR_CODICE']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($a['AI_LUOGO'] ?? 'N/D'); ?></td>
                                    <td><?php echo ($a['AR_ATTI'] == 1) ? '<span style="color:red;">Dismessa</span>' : 'Attiva'; ?></td>
                                    <td>
                                        <a href="gestatt.php?arnia_id=<?php echo $a['AR_ID']; ?>&tab=movimenti#tab-movimenti" class="btn btn-stampa" title="Vedi Attività">📝</a>
                                        <a href="arnie.php?modifica=<?php echo $a['AR_ID']; ?>&<?php echo $query_string; ?>#tab-form" class="btn btn-modifica" title="Modifica">✏️</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&ricerca=<?php echo urlencode($ricerca); ?>&filtro_stato=<?php echo $filtro_stato; ?>" class="btn <?php echo ($i == $page) ? 'btn-stampa' : 'btn-annulla'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-form" class="tab-content <?php echo $modifica_id ? 'active' : ''; ?>">
                <?php
                $curr = ['AR_ID'=>'','AR_CODICE'=>'','AR_LUOGO'=>'','AR_DATA'=>'','AR_Note'=>'','AR_ATTI'=>0,'AR_NUCL'=>0,'AR_PROP'=>'','AR_CREG'=>'','AR_TREG'=>''];
                if ($modifica_id) {
                    $st = $conn->prepare("SELECT * FROM AP_Arnie WHERE AR_ID = ?");
                    $st->bind_param("i", $modifica_id);
                    $st->execute();
                    $curr = $st->get_result()->fetch_assoc();
                }
                $data_val = !empty($curr['AR_DATA']) ? date('d-m-Y', strtotime($curr['AR_DATA'])) : date('d-m-Y');
                ?>
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $curr['AR_ID']; ?>">
                        
                        <div class="form-group">
                            <label>Codice</label>
                            <input type="text" name="codice" value="<?php echo htmlspecialchars($curr['AR_CODICE']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Apiario</label>
                            <select name="luogo">
                                <?php
                                $aps = $conn->query("SELECT AI_ID, AI_LUOGO FROM TA_Apiari ORDER BY AI_LUOGO ASC");
                                while ($api = $aps->fetch_assoc()) {
                                    $sel = ($api['AI_ID'] == $curr['AR_LUOGO']) ? "selected" : "";
                                    echo "<option value='{$api['AI_ID']}' $sel>{$api['AI_LUOGO']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Data</label>
                            <input type="text" name="data" value="<?php echo $data_val; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Proprietario</label>
                            <select name="prop">
                                <option value="">-- Seleziona --</option>
                                <?php
                                $props = $conn->query("SELECT AP_Nome FROM TA_Apicoltore ORDER BY AP_Nome ASC");
                                while ($p = $props->fetch_assoc()) {
                                    $sel = ($p['AP_Nome'] == $curr['AR_PROP']) ? "selected" : "";
                                    echo "<option value='".htmlspecialchars($p['AP_Nome'])."' $sel>".htmlspecialchars($p['AP_Nome'])."</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Anno Regina (Colore)</label>
                            <select name="creg">
                                <option value="">-- Seleziona --</option>
                                <option value="BI" <?php echo ($curr['AR_CREG'] == 'BI') ? 'selected' : ''; ?>>BI - Bianco</option>
                                <option value="GI" <?php echo ($curr['AR_CREG'] == 'GI') ? 'selected' : ''; ?>>GI - Giallo</option>
                                <option value="RO" <?php echo ($curr['AR_CREG'] == 'RO') ? 'selected' : ''; ?>>RO - Rosso</option>
                                <option value="VE" <?php echo ($curr['AR_CREG'] == 'VE') ? 'selected' : ''; ?>>VE - Verde</option>
                                <option value="AZ" <?php echo ($curr['AR_CREG'] == 'AZ') ? 'selected' : ''; ?>>AZ - Azzurro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tipo Regina</label>
                            <input type="text" name="treg" value="<?php echo htmlspecialchars($curr['AR_TREG']); ?>">
                        </div>

                        <div class="form-group">
                            <label><input type="checkbox" name="atti" <?php echo ($curr['AR_ATTI'] == 1) ? 'checked' : ''; ?>> Dismessa/Magazzino</label>
                        </div>

                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="note"><?php echo htmlspecialchars($curr['AR_Note']); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="salva" class="btn btn-salva">Salva</button>
                            <?php if ($modifica_id): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina" onclick="return confirm('ATTENZIONE: Eliminando l\'arnia verranno cancellati anche tutti i movimenti di magazzino e le attività collegate. Procedere?');">Elimina</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="right-column"></div>
</main>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) tabcontent[i].classList.remove("active");
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) tablinks[i].classList.remove("active");
    document.getElementById(tabName).classList.add("active");
    if(evt) evt.currentTarget.classList.add("active");
}
</script>

<?php include TPL_PATH . 'footer.php'; ?>