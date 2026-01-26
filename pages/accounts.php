<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.1.5 (Fix Ruolo Truncated)');

require_once '../includes/config.php';

// --- 1. GESTIONE LOGICA POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post = $_POST["id"] ?? null;
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nome = $_POST['nome'] ?? '';
    $ruolo = $_POST['ruolo'] ?? 'user';
    $attivo = isset($_POST['attivo']) ? 1 : 0;

    try {
        if (isset($_POST["salva"])) {
            if (!empty($id_post) && is_numeric($id_post)) {
                // UPDATE
                if (!empty($password)) {
                    $sql = "UPDATE TA_Account SET AC_username=?, AC_password=?, AC_nome=?, AC_ruolo=?, AC_attivo=? WHERE AC_id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssii", $username, $password, $nome, $ruolo, $attivo, $id_post);
                } else {
                    $sql = "UPDATE TA_Account SET AC_username=?, AC_nome=?, AC_ruolo=?, AC_attivo=? WHERE AC_id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssii", $username, $nome, $ruolo, $attivo, $id_post);
                }
            } else {
                // INSERT
                $sql = "INSERT INTO TA_Account (AC_username, AC_password, AC_nome, AC_ruolo, AC_attivo) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $username, $password, $nome, $ruolo, $attivo);
            }
            
            if ($stmt->execute()) {
                header("Location: accounts.php?status=success");
                exit();
            }
        } elseif (isset($_POST["elimina"]) && !empty($id_post)) {
            $stmt = $conn->prepare("DELETE FROM TA_Account WHERE AC_id = ?");
            $stmt->bind_param("i", $id_post);
            $stmt->execute();
            header("Location: accounts.php?status=deleted");
            exit();
        }
    } catch (Exception $e) { 
        $messaggio_errore = "Errore: " . $e->getMessage(); 
    }
}

// --- 2. CARICAMENTO HEADER ---
require_once TPL_PATH . 'header.php';

// --- 3. INIZIALIZZAZIONE PARAMETRI VISUALIZZAZIONE ---
$messaggio = "";
if (isset($messaggio_errore)) $messaggio = "<p class='errore' style='color:red; padding:10px;'>$messaggio_errore</p>";

$modifica_id = $_GET["modifica"] ?? null;
$ricerca = $_GET["ricerca"] ?? "";
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
$query_string = "ricerca=" . urlencode($ricerca) . "&page=" . $page;

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') $messaggio = "<p class='successo' style='color:green; font-weight:bold; padding:10px;'>Operazione completata con successo!</p>";
    if ($_GET['status'] == 'deleted') $messaggio = "<p class='successo' style='color:red; font-weight:bold; padding:10px;'>Account rimosso!</p>";
}

// --- 4. RECUPERO DATI PER MODIFICA ---
$curr = ['AC_id'=>'','AC_username'=>'','AC_password'=>'','AC_nome'=>'','AC_ruolo'=>'user', 'AC_attivo'=>1];
if ($modifica_id) {
    $st = $conn->prepare("SELECT * FROM TA_Account WHERE AC_id = ?");
    $st->bind_param("i", $modifica_id);
    $st->execute();
    $res_mod = $st->get_result();
    if($res_mod->num_rows > 0) {
        $curr = $res_mod->fetch_assoc();
    }
}

// --- 5. QUERY ELENCO TABELLA ---
$limit = 50;
$offset = ($page - 1) * $limit;
$where_sql = !empty($ricerca) ? "WHERE AC_username LIKE ? OR AC_nome LIKE ?" : "WHERE 1=1";
$sql_list = "SELECT * FROM TA_Account $where_sql ORDER BY AC_username ASC LIMIT ? OFFSET ?";
$stmt_list = $conn->prepare($sql_list);
if (!empty($ricerca)) {
    $term = "%$ricerca%";
    $stmt_list->bind_param("ssii", $term, $term, $limit, $offset);
} else {
    $stmt_list->bind_param("ii", $limit, $offset);
}
$stmt_list->execute();
$accounts = $stmt_list->get_result();
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2>Gestione Utenti</h2>
        
        <div class="filtri-container">
            <form method="GET" action="accounts.php">
                <input type="text" name="ricerca" placeholder="Cerca utente o nome..." value="<?php echo htmlspecialchars($ricerca); ?>">
                <button type="submit" class="btn btn-stampa">Filtra</button>
                <a href="accounts.php" class="btn btn-annulla">Reset</a>
            </form>
        </div>

        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$modifica_id ? 'active' : ''; ?>" onclick="openTab(event, 'tab-lista')">Elenco</li>
                <li class="tab-link <?php echo $modifica_id ? 'active' : ''; ?>" onclick="openTab(event, 'tab-form')"><?php echo $modifica_id ? "Modifica" : "Nuovo"; ?></li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$modifica_id ? 'active' : ''; ?>">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Username</th><th>Nome</th><th>Ruolo</th><th>Stato</th><th>Azioni</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($acc = $accounts->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($acc['AC_username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($acc['AC_nome'] ?? ''); ?></td>
                                    <td><?php echo strtoupper($acc['AC_ruolo']); ?></td>
                                    <td><?php echo ($acc['AC_attivo'] == 1) ? 'Attivo' : '<span style="color:red;">Disattivato</span>'; ?></td>
                                    <td>
                                        <a href="accounts.php?modifica=<?php echo $acc['AC_id']; ?>&<?php echo $query_string; ?>" class="btn btn-modifica">✏️</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-form" class="tab-content <?php echo $modifica_id ? 'active' : ''; ?>">
                <div class="form-container">
                    <form method="POST" action="accounts.php">
                        <input type="hidden" name="id" value="<?php echo $curr['AC_id']; ?>">
                        
                        <div class="form-group">
                            <label>Username (Login)</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($curr['AC_username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Password <?php echo $modifica_id ? "(Lascia vuoto per non cambiare)" : ""; ?></label>
                            <input type="text" name="password" placeholder="<?php echo $modifica_id ? 'Criptata/Invariata' : 'Inserisci password'; ?>" <?php echo $modifica_id ? '' : 'required'; ?>>
                        </div>

                        <div class="form-group">
                            <label>Nome Visualizzato</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($curr['AC_nome']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Ruolo</label>
                            <select name="ruolo">
                                <option value="user" <?php echo ($curr['AC_ruolo'] == 'user') ? 'selected' : ''; ?>>Utente Standard</option>
                                <option value="admin" <?php echo ($curr['AC_ruolo'] == 'admin') ? 'selected' : ''; ?>>Amministratore</option>
                                <option value="ospite" <?php echo ($curr['AC_ruolo'] == 'ospite') ? 'selected' : ''; ?>>Sola Lettura (Ospite)</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top:15px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="attivo" <?php echo ($curr['AC_attivo'] == 1) ? 'checked' : ''; ?>> 
                                Account Attivo
                            </label>
                        </div>
                        
                        <div class="form-actions" style="margin-top:20px;">
                            <button type="submit" name="salva" class="btn btn-salva">Salva Account</button>
                            <a href="accounts.php" class="btn btn-annulla">Annulla</a>
                            <?php if ($modifica_id && $curr['AC_username'] !== 'admin'): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina" onclick="return confirm('Eliminare definitivamente questo utente?');">Elimina</button>
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