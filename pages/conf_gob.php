<?php
// Versione del file
$versione = "V.0.2.4 (Configurazione Globale)";

include '../includes/config.php'; // Includi la connessione al database
include '../includes/header.php';

// --- DEFINIZIONE FUNZIONE CRUCIALE ---
// La funzione redirect deve essere definita qui, prima di essere chiamata.
function redirect($url, $status=null, $param_name=null) {
    global $conn;
    
    $redirect_url = url('pages/' . $url);
    if ($status) {
        $redirect_url .= "?status=" . $status;
        if ($param_name) {
            $redirect_url .= "&param=" . urlencode($param_name);
        }
    }
    // Chiudi la connessione prima del reindirizzamento (buona pratica)
    if ($conn) {
        $conn->close();
    }
    
    header("Location: " . $redirect_url);
    exit();
}
// ------------------------------------

$messaggio = "";
$successo_operazione = false;

// Variabili per il modulo di modifica/inserimento
$id_modifica = null;
$cf_dato_modifica = '';
$cf_val_modifica = '';
$cf_descr_modifica = '';
$readonly_dato = '';
$nome_parametro_operato = '';

// --- GESTIONE STATUS MODAL e MESSAGGI ---
$show_modal = false;
$search_query = trim($_GET['search'] ?? ''); // Recupera la query di ricerca

if (isset($_GET["status"])) {
    $status = $_GET["status"];
    $param_name = htmlspecialchars($_GET["param"] ?? 'Parametro');
    
    if ($status == "insert_success") {
        $modal_message = "Parametro **" . $param_name . "** inserito con successo!";
        $show_modal = true;
    } elseif ($status == "update_success") {
        $modal_message = "Parametro **" . $param_name . "** modificato con successo!";
        $show_modal = true;
    } elseif ($status == "delete_success") {
        $modal_message = "Parametro eliminato con successo!";
        $show_modal = true;
    }
}


// --- GESTIONE DELLE OPERAZIONI (Inserimento, Modifica, Eliminazione) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recupera i dati di base dal form
    $id = $_POST["id"] ?? null;
    $cf_dato = trim($_POST["cf_dato"] ?? '');
    $cf_val = trim($_POST["cf_val"] ?? '');
    $cf_descr = trim($_POST["cf_descr"] ?? '');
    $nome_parametro_operato = htmlspecialchars($cf_dato);

    if (isset($_POST["inserisci"])) {
        // --- INSERIMENTO ---
        if (!empty($cf_dato) && !empty($cf_val)) {
            $sql = "INSERT INTO CF_GLOB (CF_DATO, CF_VAL, CF_DESCR) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("sss", $cf_dato, $cf_val, $cf_descr);
                if ($stmt->execute()) {
                    redirect("conf_gob.php", "insert_success", $nome_parametro_operato); 
                } else {
                    if ($conn->errno == 1062) {
                        $messaggio = "<p class='errore'>Errore: Il nome parametro **" . $nome_parametro_operato . "** esiste già. Utilizzare un nome univoco.</p>";
                    } else {
                        $messaggio = "<p class='errore'>Errore durante l'inserimento: " . $stmt->error . "</p>";
                    }
                }
                $stmt->close();
            }
        } else {
            $messaggio = "<p class='errore'>Errore: I campi **Dato Globale** e **Valore** sono obbligatori.</p>";
        }

    } elseif (isset($_POST["modifica"]) && $id) {
        // --- MODIFICA ---
        if (!empty($cf_dato) && !empty($cf_val)) {
            $sql = "UPDATE CF_GLOB SET CF_DATO = ?, CF_VAL = ?, CF_DESCR = ? WHERE ID = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("sssi", $cf_dato, $cf_val, $cf_descr, $id);
                if ($stmt->execute()) {
                    redirect("conf_gob.php", "update_success", $nome_parametro_operato); 
                } else {
                     if ($conn->errno == 1062) {
                        $messaggio = "<p class='errore'>Errore: Il nome parametro **" . $nome_parametro_operato . "** esiste già. Utilizzare un nome univoco.</p>";
                    } else {
                        $messaggio = "<p class='errore'>Errore durante la modifica: " . $stmt->error . "</p>";
                    }
                }
                $stmt->close();
            }
        } else {
            $messaggio = "<p class='errore'>Errore: I campi **Dato Globale** e **Valore** sono obbligatori.</p>";
        }

    } elseif (isset($_POST["elimina"]) && $id) {
        // --- ELIMINAZIONE ---
        $sql = "DELETE FROM CF_GLOB WHERE ID = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                redirect("conf_gob.php", "delete_success"); 
            } else {
                $messaggio = "<p class='errore'>Errore durante l'eliminazione: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
}

// --- RECUPERO DATI PER MODIFICA ---
if (isset($_GET["id_modifica"])) {
    $id_modifica = $_GET["id_modifica"];

    $sql = "SELECT ID, CF_DATO, CF_VAL, CF_DESCR FROM CF_GLOB WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_modifica);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $config = $result->fetch_assoc();
            $cf_dato_modifica = $config["CF_DATO"];
            $cf_val_modifica = $config["CF_VAL"];
            $cf_descr_modifica = $config["CF_DESCR"];
            $readonly_dato = 'readonly'; 
        } else {
            $messaggio = "<p class='errore'>Errore: Parametro di configurazione non trovato.</p>";
            $id_modifica = null; 
        }
        $stmt->close();
    }
}

// --- RECUPERO TUTTI I DATI PER LA LISTA (con filtro di ricerca) ---
$config_list = [];
$sql_list = "SELECT ID, CF_DATO, CF_VAL, CF_DESCR FROM CF_GLOB WHERE 1=1";

// Applicazione del filtro di ricerca
if (!empty($search_query)) {
    $sql_list .= " AND CF_DATO LIKE ?";
    $search_param = "%" . $search_query . "%";
}

$sql_list .= " ORDER BY CF_DATO ASC";

if (!empty($search_query)) {
    $stmt_list = $conn->prepare($sql_list);
    $stmt_list->bind_param("s", $search_param);
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
} else {
    $result_list = $conn->query($sql_list);
}


if ($result_list) {
    while ($row = $result_list->fetch_assoc()) {
        $config_list[] = $row;
    }
}

?>

<div class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <main>
            <h2>Gestione Configurazioni Globali</h2>
            
            <?php echo $messaggio; // Visualizza messaggi di errore di DB ?>

            <hr>
            <h3>Parametri Esistenti</h3>

            <div class="filtri-container">
                <form method="GET" action="conf_gob.php" class="filtro-form">
                    <input type="text" name="search" placeholder="Cerca Dato Globale..." value="<?php echo htmlspecialchars($search_query); ?>" class="campo-ricerca">
                    <button type="submit" class="btn btn-filtro">Cerca</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="conf_gob.php" class="btn btn-elimina">X Annulla Filtro</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-container list-grid-container"> 
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Dato Globale</th>
                            <th>Valore</th>
                            <th>Descrizione</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($config_list) > 0): ?>
                            <?php foreach ($config_list as $item): ?>
                                <tr>
                                    <td><?php echo $item["ID"]; ?></td>
                                    <td><?php echo htmlspecialchars($item["CF_DATO"]); ?></td> 
                                    <td class="config-val-cell"><?php echo htmlspecialchars(substr($item["CF_VAL"], 0, 50)); ?><?php echo (strlen($item["CF_VAL"]) > 50) ? '...' : ''; ?></td>
                                    <td><?php echo htmlspecialchars($item["CF_DESCR"]); ?></td>
                                    <td class="actions">
                                        <a href="conf_gob.php?id_modifica=<?php echo $item["ID"]; ?>" class="btn btn-modifica">Modifica</a>
                                        
                                        <form method="post" action="conf_gob.php" style="display: inline;" onsubmit="return confermaEliminazione('<?php echo htmlspecialchars($item["CF_DATO"]); ?>')">
                                            <input type="hidden" name="id" value="<?php echo $item["ID"]; ?>">
                                            <button type="submit" name="elimina" class="btn btn-elimina">Elimina</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Nessun parametro di configurazione trovato.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <hr>

            <div class="form-container">
                <h3><?php echo $id_modifica ? "Modifica Parametro (ID: " . $id_modifica . ")" : "Nuovo Parametro"; ?></h3>
                <form action="conf_gob.php" method="post" onsubmit="return validaForm()">
                    <?php if ($id_modifica): ?>
                        <input type="hidden" name="id" value="<?php echo $id_modifica; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="cf_dato">Dato Globale (Max 20 caratteri):</label>
                        <input type="text" id="cf_dato" name="cf_dato" value="<?php echo htmlspecialchars($cf_dato_modifica); ?>" maxlength="20" required <?php echo $readonly_dato; ?>>
                    </div>
                    <div class="form-group">
                        <label for="cf_val">Valore:</label>
                        <textarea id="cf_val" name="cf_val" maxlength="500" required><?php echo htmlspecialchars($cf_val_modifica); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="cf_descr">Descrizione (Max 250 caratteri):</label>
                        <textarea id="cf_descr" name="cf_descr" maxlength="250"><?php echo htmlspecialchars($cf_descr_modifica); ?></textarea>
                    </div>

                    <div class="form-group">
                        <?php if ($id_modifica): ?>
                            <button type="submit" name="modifica" class="btn btn-modifica btn-grande">Salva Modifiche</button>
                            <a href="conf_gob.php" class="btn btn-elimina btn-grande">Annulla</a>
                        <?php else: ?>
                            <button type="submit" name="inserisci" class="btn btn-inserisci btn-grande">Inserisci Parametro</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>


        </main>
    </div>

    <div class="right-column"></div>
</div>

<?php if (isset($show_modal) && $show_modal): ?>
    <div id="successModal" class="modal-success-overlay">
        <div class="modal-success-content">
            <span class="close-modal">&times;</span>
            <p><?php echo $modal_message; ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="versione-info">
    <?php echo $versione; ?>
</div>
<?php include '../includes/footer.php'; ?>

<script>
// Funzione per confermare l'eliminazione
function confermaEliminazione(nomeDato) {
    return confirm('Sei sicuro di voler eliminare il parametro di configurazione: ' + nomeDato + '?');
}

// Funzione di validazione form
function validaForm() {
    const cfDato = document.getElementById('cf_dato').value.trim();
    if (cfDato === '') {
        alert('Il campo "Dato Globale" non può essere vuoto.');
        return false;
    }
    return true;
}

// Logica per visualizzare e chiudere la modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'flex'; // Mostra la modal
        
        const closeModal = function() {
            modal.style.display = "none";
            // Rimuovi lo status dall'URL dopo la chiusura
            window.history.pushState({}, document.title, window.location.pathname);
        };
        
        const span = document.querySelector(".close-modal");
        span.onclick = closeModal;
        
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
        
        setTimeout(closeModal, 4000);
    }
    
    // Rendere i valori lunghi più leggibili su hover
    const valCells = document.querySelectorAll('.config-val-cell');
    valCells.forEach(cell => {
        cell.title = cell.textContent;
    });
});
</script>