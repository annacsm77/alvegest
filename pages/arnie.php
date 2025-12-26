<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.0.6 (Fix Ricerca e Selezione)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php';

// --- 1. INIZIALIZZAZIONE E RECUPERO PARAMETRI ---
$messaggio = "";
$modifica_id = $_GET["modifica"] ?? null;
$ricerca = $_GET["ricerca"] ?? "";
$filtro_stato = $_GET["filtro_stato"] ?? "attive";
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;

// --- 2. GESTIONE LOGICA POST (Inserimento, Modifica, Eliminazione) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post = $_POST["id"] ?? null;
    $atti = isset($_POST["atti"]) ? 1 : 0;
    $nucl = isset($_POST["nucl"]) ? 1 : 0;
    
    // Validazione data
    $data_input = $_POST["data"] ?? '';
    $data_db = "";
    try {
        $data_obj = DateTime::createFromFormat('d-m-Y', $data_input);
        if ($data_obj) {
            $data_db = $data_obj->format('Y-m-d');
        }

        if (isset($_POST["inserisci"])) {
            $sql = "INSERT INTO AP_Arnie (AR_CODICE, AR_NOME, AR_LUOGO, AR_PROP, AR_CREG, AR_TREG, AR_ATTI, AR_DATA, AR_NUCL, AR_Note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisssisis", $_POST['codice'], $_POST['nome'], $_POST['luogo_id'], $_POST['prop'], $_POST['creg'], $_POST['treg'], $atti, $data_db, $nucl, $_POST['note']);
            if ($stmt->execute()) header("Location: arnie.php?status=insert_success");
        } elseif (isset($_POST["modifica"])) {
            $sql = "UPDATE AP_Arnie SET AR_CODICE=?, AR_NOME=?, AR_LUOGO=?, AR_PROP=?, AR_CREG=?, AR_TREG=?, AR_ATTI=?, AR_DATA=?, AR_NUCL=?, AR_Note=? WHERE AR_ID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisssisisi", $_POST['codice'], $_POST['nome'], $_POST['luogo_id'], $_POST['prop'], $_POST['creg'], $_POST['treg'], $atti, $data_db, $nucl, $_POST['note'], $id_post);
            if ($stmt->execute()) header("Location: arnie.php?status=update_success");
        } elseif (isset($_POST["elimina"])) {
            $stmt = $conn->prepare("DELETE FROM AP_Arnie WHERE AR_ID = ?");
            $stmt->bind_param("i", $id_post);
            if ($stmt->execute()) header("Location: arnie.php?status=delete_success");
        }
    } catch (Exception $e) { $messaggio = "<p class='errore'>".$e->getMessage()."</p>"; }
}

// --- 3. RECUPERO DATI PER L'INTERFACCIA ---
// Messaggi di feedback
if (isset($_GET["status"])) {
    $st = $_GET["status"];
    if ($st == "insert_success") $messaggio = "<p class='successo'>Arnia inserita!</p>";
    if ($st == "update_success") $messaggio = "<p class='successo'>Arnia aggiornata!</p>";
    if ($st == "delete_success") $messaggio = "<p class='successo'>Arnia eliminata!</p>";
}

// Dati per il form se siamo in modifica
$persisted = ['AR_CODICE'=>'','AR_NOME'=>'','AR_LUOGO'=>'','AR_PROP'=>'','AR_CREG'=>'','AR_TREG'=>'','AR_ATTI'=>0,'AR_NUCL'=>0,'AR_Note'=>'','data'=>''];
if ($modifica_id) {
    $stmt = $conn->prepare("SELECT * FROM AP_Arnie WHERE AR_ID = ?");
    $stmt->bind_param("i", $modifica_id);
    $stmt->execute();
    $res_mod = $stmt->get_result();
    if ($ar = $res_mod->fetch_assoc()) {
        $persisted = $ar;
        $d = DateTime::createFromFormat('Y-m-d', $ar["AR_DATA"]);
        $persisted['data'] = $d ? $d->format('d-m-Y') : '';
    }
}

// Dati per i menu a tendina
$proprietari = $conn->query("SELECT AP_Nome FROM TA_Apicoltore");
$apiari = $conn->query("SELECT AI_ID, AI_LUOGO FROM TA_Apiari ORDER BY AI_LUOGO ASC");

// Query per la lista (con filtri e ricerca)
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;
$where = " WHERE 1=1 ";
if ($filtro_stato === "attive") $where .= " AND AR_ATTI = 0 ";
if (!empty($ricerca)) {
    $ric_safe = $conn->real_escape_string($ricerca);
    $where .= " AND AR_NOME LIKE '%$ric_safe%' ";
}

$total_records = $conn->query("SELECT COUNT(*) FROM AP_Arnie $where")->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);
$result_lista = $conn->query("SELECT A.*, L.AI_LUOGO FROM AP_Arnie A LEFT JOIN TA_Apiari L ON A.AR_LUOGO = L.AI_ID $where ORDER BY A.AR_CODICE ASC LIMIT $records_per_page OFFSET $offset");
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Gestione Arnie</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$modifica_id ? 'active' : ''; ?>" id="link-tab-lista" onclick="openTab(event, 'tab-lista')">Elenco Arnie</li>
                <li class="tab-link <?php echo $modifica_id ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')">
                    <?php echo $modifica_id ? "Dettaglio Arnia" : "Nuova Arnia"; ?>
                </li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$modifica_id ? 'active' : ''; ?>">
                <div class="filtri-container" style="background: #f4f4f4; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <form method="GET" action="arnie.php" class="filtro-form">
                        <select name="filtro_stato" onchange="this.form.submit()">
                            <option value="attive" <?php echo $filtro_stato == 'attive' ? 'selected' : ''; ?>>Solo Attive</option>
                            <option value="tutte" <?php echo $filtro_stato == 'tutte' ? 'selected' : ''; ?>>Tutte</option>
                        </select>
                        <input type="text" name="ricerca" placeholder="Cerca nome..." value="<?php echo htmlspecialchars($ricerca); ?>" class="campo-ricerca">
                        <button type="submit" class="btn btn-stampa">Cerca</button>
                        <a href="arnie.php" class="btn btn-annulla" title="Pulisci filtri">Reset</a>
                    </form>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Cod</th><th>Nome</th><th>Luogo</th><th>Stato</th><th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_lista->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row["AR_CODICE"]; ?></td>
                                <td><?php echo htmlspecialchars($row["AR_NOME"]); ?></td>
                                <td><?php echo htmlspecialchars($row["AI_LUOGO"] ?? 'N/A'); ?></td>
                                <td><?php echo $row["AR_ATTI"] ? '<span style="color:red">Dismessa</span>' : 'Attiva'; ?></td>
                                <td>
                                    <a href="arnie.php?modifica=<?php echo $row['AR_ID']; ?>&ricerca=<?php echo urlencode($ricerca); ?>&filtro_stato=<?php echo $filtro_stato; ?>&page=<?php echo $page; ?>#tab-form" class="btn btn-modifica">Modifica</a>
                                    <a href="gestatt.php?arnia_id=<?php echo $row['AR_ID']; ?>" class="btn btn-attivita">Attività</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination-controls" style="text-align:center; margin-top:15px;">
                        <a href="?page=<?php echo max(1, $page-1); ?>&filtro_stato=<?php echo $filtro_stato; ?>&ricerca=<?php echo urlencode($ricerca); ?>" class="btn btn-annulla">Prec.</a>
                        <span>Pagina <?php echo $page; ?> di <?php echo $total_pages; ?></span>
                        <a href="?page=<?php echo min($total_pages, $page+1); ?>&filtro_stato=<?php echo $filtro_stato; ?>&ricerca=<?php echo urlencode($ricerca); ?>" class="btn btn-annulla">Succ.</a>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-form" class="tab-content <?php echo $modifica_id ? 'active' : ''; ?>">
                <div class="form-container">
                    <form action="arnie.php" method="post">
                        <?php if ($modifica_id): ?> <input type="hidden" name="id" value="<?php echo $modifica_id; ?>"> <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Codice:</label>
                            <input type="text" name="codice" value="<?php echo htmlspecialchars($persisted['AR_CODICE']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nome:</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($persisted['AR_NOME']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Luogo:</label>
                            <select name="luogo_id" required>
                                <?php while ($api = $apiari->fetch_assoc()): ?>
                                    <option value="<?php echo $api['AI_ID']; ?>" <?php echo ($persisted['AR_LUOGO'] == $api['AI_ID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($api['AI_LUOGO']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Data (gg-mm-aaaa):</label>
                            <input type="text" name="data" value="<?php echo $persisted['data']; ?>" placeholder="gg-mm-aaaa" required>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="atti" <?php echo $persisted['AR_ATTI'] ? 'checked' : ''; ?>> Dismessa</label>
                        </div>
                        <div class="form-group">
                            <label>Note:</label>
                            <textarea name="note"><?php echo htmlspecialchars($persisted['AR_Note']); ?></textarea>
                        </div>

                        <div class="btn-group-form" style="display:flex; gap:10px; margin-top:20px;">
                            <button type="submit" name="<?php echo $modifica_id ? 'modifica' : 'inserisci'; ?>" class="btn btn-salva" style="flex:2;">Salva</button>
                            <?php if ($modifica_id): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina" onclick="return confirm('Eliminare l\'arnia?');">Elimina</button>
                                <a href="arnie.php?ricerca=<?php echo urlencode($ricerca); ?>&filtro_stato=<?php echo $filtro_stato; ?>&page=<?php echo $page; ?>" class="btn btn-annulla">Annulla</a>
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
    // Se c'è un ID modifica nell'URL o l'hash è tab-form, attiva il tab del form
    if(window.location.search.indexOf('modifica=') > -1 || window.location.hash === "#tab-form") {
        document.getElementById('link-tab-form').click();
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>