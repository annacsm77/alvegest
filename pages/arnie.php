<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

// Versione del file per arnie.php
$versione = "V.0.0.3 (Paginazione e Persistenza)";

// Variabile per i messaggi di feedback
$messaggio = "";
$validation_error_occurred = false;

// Funzione per reindirizzare e terminare lo script
function redirect($url) {
    header("Location: " . url('pages/' . $url));
    exit();
}

// --- RECUPERO DATI PER COMBO BOX E CONFIG ---

// Recupera tutti i proprietari dalla tabella Apicoltore
$sql_proprietari = "SELECT AP_Nome FROM TA_Apicoltore";
$result_proprietari = $conn->query($sql_proprietari); 
$proprietari = [];
if ($result_proprietari) {
    while ($row = $result_proprietari->fetch_assoc()) {
        $proprietari[] = $row["AP_Nome"];
    }
}


// Recupera tutti gli apiari per la combo box
$sql_apiari = "SELECT AI_ID, AI_LUOGO FROM TA_Apiari ORDER BY AI_LUOGO ASC";
$result_apiari = $conn->query($sql_apiari);
$apiari = [];
if ($result_apiari) {
    while ($row = $result_apiari->fetch_assoc()) {
        $apiari[] = $row;
    }
}

// Recupera NUM_R_PAG da CF_GLOB
$records_per_page = 15; // Valore di default
$sql_num_pag = "SELECT CF_VAL FROM CF_GLOB WHERE CF_DATO = 'NUM_R_PAG'";
$result_num_pag = $conn->query($sql_num_pag);

if ($result_num_pag && $result_num_pag->num_rows > 0) {
    $row = $result_num_pag->fetch_assoc();
    $val = (int) $row['CF_VAL'];
    if ($val > 0) {
        $records_per_page = $val;
    }
}
// ------------------------------------------

// --- VARIABILI DI STATO E DATI FORM PERSISTENTI (Inizializzazione) ---
$persisted_data = [
    'codice' => '', 'nome' => '', 'luogo_id' => '', 'prop' => '', 'creg' => '', 
    'treg' => '', 'atti' => 0, 'data' => '', 'nucl' => 0, 'note' => ''
];
$modifica_id = null;


// Gestione dell'inserimento, modifica ed eliminazione dei dati
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Recupera e salva i dati POST (per persistenza)
    $modifica_id = $_POST["id"] ?? null;
    $persisted_data['codice'] = $_POST["codice"] ?? '';
    $persisted_data['nome'] = $_POST["nome"] ?? '';
    $persisted_data['luogo_id'] = $_POST["luogo_id"] ?? '';
    $persisted_data['prop'] = $_POST["prop"] ?? '';
    $persisted_data['creg'] = $_POST["creg"] ?? '';
    $persisted_data['treg'] = $_POST["treg"] ?? '';
    $persisted_data['atti'] = isset($_POST["atti"]) ? 1 : 0;
    $data_input = $_POST["data"] ?? '';
    $persisted_data['data'] = $data_input; // Salva l'input per la ri-visualizzazione
    $persisted_data['nucl'] = isset($_POST["nucl"]) ? 1 : 0;
    $persisted_data['note'] = $_POST["note"] ?? '';

    // Alias per la query
    $codice = $persisted_data['codice'];
    $nome = $persisted_data['nome'];
    $luogo_id = $persisted_data['luogo_id']; 
    $prop = $persisted_data['prop'];
    $creg = $persisted_data['creg'];
    $treg = $persisted_data['treg'];
    $atti = $persisted_data['atti'];
    $nucl = $persisted_data['nucl'];
    $note = $persisted_data['note'];

    // --- LOGICA DI CONVERSIONE E VALIDAZIONE DATA ---
    $data = null;
    $validation_error_occurred = false;
    try {
        $data_obj = DateTime::createFromFormat('d-m-Y', $data_input);
        if (!$data_obj || $data_obj->format('d-m-Y') !== $data_input) {
            $validation_error_occurred = true;
            throw new Exception("Formato data non valido. Utilizzare gg-mm-aaaa.");
        }
        $data = $data_obj->format('Y-m-d'); // Formato corretto per MySQL
    } catch (Exception $e) {
        $messaggio = "<p class='errore'>" . $e->getMessage() . "</p>";
        $validation_error_occurred = true; // Imposta il flag di errore
    }
    // ----------------------------------------------------
    
    // 2. Se non ci sono errori di validazione, procedi con il DB (QUERY INTERPOLATA NON SICURA)
    if (!$validation_error_occurred) {
        
        // Sanificazione minima dei dati per l'inserimento interpolato (NON SICURO)
        $codice_safe = $conn->real_escape_string($codice);
        $nome_safe = $conn->real_escape_string($nome);
        $note_safe = $conn->real_escape_string($note);
        $prop_safe = $conn->real_escape_string($prop);
        $creg_safe = $conn->real_escape_string($creg);
        $treg_safe = $conn->real_escape_string($treg);

        if (isset($_POST["inserisci"])) {
            // Inserimento
            $interpolated_sql = "INSERT INTO AP_Arnie (AR_CODICE, AR_NOME, AR_LUOGO, AR_PROP, AR_CREG, AR_TREG, AR_ATTI, AR_DATA, AR_NUCL, AR_Note) 
                                VALUES ('$codice_safe', '$nome_safe', '$luogo_id', '$prop_safe', '$creg_safe', '$treg_safe', '$atti', '$data', '$nucl', '$note_safe')";

            // ESECUZIONE DIRETTA NON SICURA
            if ($conn->query($interpolated_sql)) {
                redirect("arnie.php?status=insert_success"); 
            } else {
                $messaggio = "<p class='errore'>Errore durante l'inserimento: " . $conn->error . "</p>";
                $validation_error_occurred = true; 
            }
            
        } elseif (isset($_POST["modifica"])) {
            // Modifica
            $interpolated_sql = "UPDATE AP_Arnie 
                                SET AR_CODICE = '$codice_safe', AR_NOME = '$nome_safe', AR_LUOGO = '$luogo_id', AR_PROP = '$prop_safe', 
                                    AR_CREG = '$creg_safe', AR_TREG = '$treg_safe', AR_ATTI = '$atti', AR_DATA = '$data', 
                                    AR_NUCL = '$nucl', AR_Note = '$note_safe' 
                                WHERE AR_ID = '$modifica_id'";

            // ESECUZIONE DIRETTA NON SICURA
            if ($conn->query($interpolated_sql)) {
                redirect("arnie.php?status=update_success"); 
            } else {
                $messaggio = "<p class='errore'>Errore durante la modifica: " . $conn->error . "</p>";
                $validation_error_occurred = true; 
            }
        }
    }
}

// Gestione dell'eliminazione del record (GET)
if (isset($_GET["elimina"])) {
    $elimina_id = $_GET["elimina"];
    $sql = "DELETE FROM AP_Arnie WHERE AR_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $elimina_id);

    if ($stmt->execute()) {
        redirect("arnie.php?status=delete_success"); 
    } else {
        $messaggio = "<p class='errore'>Errore durante l'eliminazione: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// ------------------------------------------
// Gestione dei messaggi di successo post-redirect
// ------------------------------------------
if (isset($_GET["status"])) {
    $status = $_GET["status"];
    if ($status == "insert_success") {
        $messaggio = "<p class='successo'>Arnia inserita con successo!</p>";
    } elseif ($status == "update_success") {
        $messaggio = "<p class='successo'>Arnia modificata con successo!</p>";
    } elseif ($status == "delete_success") {
        $messaggio = "<p class='successo'>Arnia eliminata con successo!</p>";
    }
}

fine_post:


// --- RECUPERO DATI PER MODIFICA (o Ritorno Errore) ---
if (isset($_GET["modifica"])) {
    $modifica_id = $_GET["modifica"];
    if (!$validation_error_occurred) { // Solo se non c'è stato un errore POST
        $sql = "SELECT * FROM AP_Arnie WHERE AR_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $modifica_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $arnia = $result->fetch_assoc();
        $stmt->close();
        
        // Popola i dati persistenti dal DB 
        if ($arnia) {
            $persisted_data['codice'] = $arnia["AR_CODICE"];
            $persisted_data['nome'] = $arnia["AR_NOME"];
            $persisted_data['luogo_id'] = $arnia["AR_LUOGO"];
            $persisted_data['prop'] = $arnia["AR_PROP"];
            $persisted_data['creg'] = $arnia["AR_CREG"];
            $persisted_data['treg'] = $arnia["AR_TREG"];
            $persisted_data['atti'] = $arnia["AR_ATTI"];
            $persisted_data['nucl'] = $arnia["AR_NUCL"];
            $persisted_data['note'] = $arnia["AR_Note"];

            // Converte la data del DB (Y-m-d) in formato italiano (d-m-Y)
            $data_obj = DateTime::createFromFormat('Y-m-d', $arnia["AR_DATA"]);
            $persisted_data['data'] = $data_obj ? $data_obj->format('d-m-Y') : $arnia["AR_DATA"];
        }
    }
}


// --- CONFIGURAZIONE PAGINAZIONE & FILTRI ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;
$mostra_dismesse = isset($_GET["mostra_dismesse"]) ? $_GET["mostra_dismesse"] : false;
$ricerca = isset($_GET["ricerca"]) ? $_GET["ricerca"] : "";

// 1. Conto il totale dei record
$sql_count = "SELECT COUNT(*) AS total FROM AP_Arnie A WHERE 1=1";
if (!$mostra_dismesse) {
    $sql_count .= " AND AR_ATTI = 0";
}
if (!empty($ricerca)) {
    $sql_count .= " AND AR_NOME LIKE ?";
}

$stmt_count = $conn->prepare($sql_count);
if (!empty($ricerca)) {
    $ricerca_param = "%" . $ricerca . "%";
    $stmt_count->bind_param("s", $ricerca_param);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$stmt_count->close();


// 2. RECUPERO DATI ARNIE PER LISTA (con paginazione)
$sql = "SELECT 
            A.*, 
            L.AI_LUOGO AS NomeApiario 
        FROM AP_Arnie A
        LEFT JOIN TA_Apiari L ON A.AR_LUOGO = L.AI_ID
        WHERE 1=1";
                
if (!$mostra_dismesse) {
    $sql .= " AND A.AR_ATTI = 0"; 
}
if (!empty($ricerca)) {
    $sql .= " AND A.AR_NOME LIKE ?"; 
}
// Ordinamento fisso per AR_CODICE ASC e applicazione LIMIT/OFFSET
$sql .= " ORDER BY A.AR_CODICE ASC LIMIT $records_per_page OFFSET $offset"; 

$stmt_select = $conn->prepare($sql);
if (!empty($ricerca)) {
    $ricerca_param = "%" . $ricerca . "%";
    $stmt_select->bind_param("s", $ricerca_param);
}
$stmt_select->execute();
$result = $stmt_select->get_result();

?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <main>
            <h2 class="titolo-arnie">Arnie</h2>
            
            <?php echo $messaggio; ?>

            <div class="filtri-container">
                <form method="GET" action="arnie.php" class="filtro-form">
                    <input type="hidden" name="mostra_dismesse" value="<?php echo $mostra_dismesse ? '0' : '1'; ?>">
                    <button type="submit" class="btn btn-filtro">
                        <?php echo $mostra_dismesse ? 'Nascondi Dismesse' : 'Mostra Tutte'; ?>
                    </button>
                </form>

                <form method="GET" action="arnie.php" class="filtro-form">
                    <input type="text" name="ricerca" placeholder="Cerca per nome" value="<?php echo htmlspecialchars($ricerca); ?>" class="campo-ricerca">
                    <button type="submit" class="btn btn-filtro">Cerca</button>
                </form>
            </div>

            <div class="table-container">
                <h3>Elenco Arnie (Totale: <?php echo $total_records; ?>)</h3>
                <?php if ($result->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class='cod-column' title='Codice Arnia'>Cod</th>
                                <th>Nome</th>
                                <th>Luogo</th>
                                <th>Proprietario</th>
                                <th class='colore-regina-column' title='Colore Regina'>CRe</th>
                                <th>Tipo Regina</th>
                                <th class='dismessa-column' title='Dismessa'>Di</th>
                                <th class='data-creazione-column'>Data Creazione</th>
                                <th title='Indica se l&#39;arnia è nata come Nucleo'>Nucleo</th>
                                <th class='note-column' title='Passa sopra per leggere la nota completa.'>Note</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()):
                                $data_obj = DateTime::createFromFormat('Y-m-d', $row["AR_DATA"]);
                                $data_formattata = $data_obj ? $data_obj->format('d-m-Y') : $row["AR_DATA"];
                                $nome_apiario = $row['NomeApiario'] ? htmlspecialchars($row['NomeApiario']) : 'N/A';
                            ?>
                            <tr>
                                <td><?php echo $row["AR_ID"]; ?></td>
                                <td class='cod-column'><?php echo $row["AR_CODICE"]; ?></td>
                                <td><?php echo htmlspecialchars($row["AR_NOME"]); ?></td>
                                <td><?php echo $nome_apiario; ?></td>
                                <td><?php echo htmlspecialchars($row["AR_PROP"]); ?></td>
                                <td class='colore-regina-column' title='Colore Regina: <?php echo htmlspecialchars($row["AR_CREG"]); ?>'><?php echo htmlspecialchars($row["AR_CREG"]); ?></td>
                                <td><?php echo htmlspecialchars($row["AR_TREG"]); ?></td>
                                <td class='dismessa-column' title='Dismessa: <?php echo ($row["AR_ATTI"] ? 'Sì' : 'No'); ?>'><?php echo ($row["AR_ATTI"] ? 'Sì' : 'No'); ?></td>
                                <td class='data-creazione-column'><?php echo $data_formattata; ?></td>
                                <td><?php echo ($row["AR_NUCL"] ? 'Sì' : 'No'); ?></td>
                                <td class='note-column' title='<?php echo htmlspecialchars($row["AR_Note"]); ?>'><?php echo htmlspecialchars($row["AR_Note"]); ?></td>
                                <td class='action-cell'> 
                                    <div class='btn-grid-2x2'>
                                        
                                        <a href='<?php echo url('pages/arnie.php?modifica=' . $row["AR_ID"]); ?>' class='btn btn-modifica btn-grid-item'>Modifica</a>
                                        
                                        <form method='POST' action='<?php echo url('pages/arnie.php'); ?>' style='display:contents; margin:0;' onsubmit='return confirm("Sei sicuro di voler eliminare questo record?");'>
                                            <input type='hidden' name='id' value='<?php echo $row["AR_ID"]; ?>'>
                                            <button type='submit' name='elimina' class='btn btn-elimina btn-grid-item'>Elimina</button>
                                        </form>

                                        <a href='<?php echo url('includes/stampa_etichetta.php?codice=' . $row["AR_CODICE"] . '&nome=' . urlencode($row["AR_NOME"]) . '&proprietario=' . urlencode($row["AR_PROP"])); ?>' class='btn btn-stampa btn-grid-item' target='_blank'>Stampa</a>
                                        
                                        <a href='<?php echo url('pages/gestatt.php?arnia_id=' . $row["AR_ID"]); ?>' class='btn btn-attivita btn-grid-item'>Attività</a>
                                        
                                    </div>
                                </td>
                              </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nessuna arnia trovata.</p>
                <?php endif; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination-controls" style="text-align: center; margin-top: 15px;">
                <p>Pagina <?php echo $page; ?> di <?php echo $total_pages; ?></p>
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&mostra_dismesse=<?php echo $mostra_dismesse; ?>&ricerca=<?php echo htmlspecialchars($ricerca); ?>" class="btn btn-filtro">Pagina Precedente</a>
                <?php endif; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&mostra_dismesse=<?php echo $mostra_dismesse; ?>&ricerca=<?php echo htmlspecialchars($ricerca); ?>" class="btn btn-filtro">Pagina Successiva</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            

            <div class="form-container">
                <h3><?php echo $modifica_id ? "Modifica Arnia" : "Inserisci Nuova Arnia"; ?></h3>
                <form action="<?php echo url('pages/arnie.php'); ?>" method="post">
                    <?php if ($modifica_id): ?>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($modifica_id); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="codice">Codice:</label>
                        <input type="number" id="codice" name="codice" value="<?php echo htmlspecialchars($persisted_data['codice']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($persisted_data['nome']); ?>" maxlength="60" required>
                    </div>
                    <div class="form-group">
                        <label for="luogo_id">Luogo Apiario:</label>
                        <select id="luogo_id" name="luogo_id" class="form-control" required>
                            <option value="">Seleziona Apiario</option>
                            <?php foreach ($apiari as $apiario): ?>
                                <option value="<?php echo $apiario['AI_ID']; ?>" <?php echo ($persisted_data['luogo_id'] == $apiario['AI_ID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($apiario['AI_LUOGO']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="prop">Proprietario:</label>
                        <select id="prop" name="prop" class="form-control" required>
                            <?php foreach ($proprietari as $proprietario): ?>
                                <option value="<?php echo htmlspecialchars($proprietario); ?>" <?php echo ($persisted_data['prop'] == $proprietario) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proprietario); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="creg">Colore Regina:</label>
                        <input type="text" id="creg" name="creg" value="<?php echo htmlspecialchars($persisted_data['creg']); ?>" maxlength="3" required>
                    </div>
                    <div class="form-group">
                        <label for="treg">Tipo Regina:</label>
                        <input type="text" id="treg" name="treg" value="<?php echo htmlspecialchars($persisted_data['treg']); ?>" maxlength="15" required>
                    </div>
                    <div class="form-group">
                        <label for="atti">Dismessa:</label>
                        <input type="checkbox" id="atti" name="atti" <?php echo $persisted_data['atti'] ? 'checked' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="data">Data Creazione (gg-mm-aaaa):</label>
                        <input type="text" id="data" name="data" value="<?php echo htmlspecialchars($persisted_data['data']); ?>" placeholder="gg-mm-aaaa" required>
                    </div>
                    <div class="form-group">
                        <label for="nucl">Nucleo:</label>
                        <input type="checkbox" id="nucl" name="nucl" <?php echo $persisted_data['nucl'] ? 'checked' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="note">Note:</label>
                        <textarea id="note" name="note" maxlength="1000"><?php echo htmlspecialchars($persisted_data['note']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <?php if ($modifica_id): ?>
                            <button type="submit" name="modifica" class="btn btn-modifica btn-grande">Modifica Arnia</button>
                        <?php else: ?>
                            <button type="submit" name="inserisci" class="btn btn-modifica btn-grande">Inserisci Arnia</button>
                        <?php endif; ?>
                        <?php if ($modifica_id): ?>
                            <a href="<?php echo url('pages/arnie.php'); ?>" class="btn btn-elimina btn-grande">Annulla</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <div class="right-column"></div>
</main>

<?php 
if (!empty($ricerca) && isset($stmt_select)) {
    $stmt_select->close();
}
require_once '../includes/footer.php';
?>