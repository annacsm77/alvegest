<?php
require_once '../includes/config.php';
require_once '../includes/header.php'; 

// Variabile per i messaggi di feedback
$messaggio = "";

// Funzione per reindirizzare e terminare lo script
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Gestione delle operazioni (inserimento, modifica, eliminazione)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recupera i dati, inclusi i nuovi campi
    $codice = $_POST["codice"]; 
    $luogo = $_POST["luogo"];
    $note = $_POST["note"];
    $link = $_POST["link"]; 

    if (isset($_POST["inserisci"])) {
        // Logica Inserimento (4 segnaposto)
        $sql = "INSERT INTO TA_Apiari (AI_CODICE, AI_LUOGO, AI_NOTE, AI_LINK) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // 4 tipi (s, s, s, s) per (codice, luogo, note, link)
            $stmt->bind_param("ssss", $codice, $luogo, $note, $link); 
            if ($stmt->execute()) {
                redirect("apiari.php?status=insert_success"); 
            } else {
                $messaggio = "<p class='errore'>Errore durante l'inserimento: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            $messaggio = "<p class='errore'>Errore nella preparazione della query: " . $conn->error . "</p>";
        }

    } elseif (isset($_POST["modifica"])) {
        // Logica Modifica
        $id = $_POST["id"];

        // Query con 5 segnaposto: AI_CODICE, AI_LUOGO, AI_NOTE, AI_LINK, AI_ID
        $sql = "UPDATE TA_Apiari SET AI_CODICE = ?, AI_LUOGO = ?, AI_NOTE = ?, AI_LINK = ? WHERE AI_ID = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // CORREZIONE CHIAVE: 5 tipi ('ssssi') per 5 variabili ($codice, $luogo, $note, $link, $id)
            $stmt->bind_param("ssssi", $codice, $luogo, $note, $link, $id); 
            if ($stmt->execute()) {
                redirect("apiari.php?status=update_success"); 
            } else {
                $messaggio = "<p class='errore'>Errore durante la modifica: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            $messaggio = "<p class='errore'>Errore nella preparazione della query: " . $conn->error . "</p>";
        }

    } elseif (isset($_POST["elimina"])) {
        // Logica Eliminazione
        $id = $_POST["id"];
        $sql = "DELETE FROM TA_Apiari WHERE AI_ID = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                redirect("apiari.php?status=delete_success"); 
            } else {
                $messaggio = "<p class='errore'>Errore durante l'eliminazione: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            $messaggio = "<p class='errore'>Errore nella preparazione della query: " . $conn->error . "</p>";
        }
    }
}

// ------------------------------------------
// Gestione dei messaggi di successo post-redirect
// ------------------------------------------
if (isset($_GET["status"])) {
    $status = $_GET["status"];
    if ($status == "insert_success") {
        $messaggio = "<p class='successo'>Apiario inserito con successo!</p>";
    } elseif ($status == "update_success") {
        $messaggio = "<p class='successo'>Apiario modificato con successo!</p>";
    } elseif ($status == "delete_success") {
        $messaggio = "<p class='successo'>Apiario eliminato con successo!</p>";
    }
}

// Recupera i dati del record da modificare (se esiste)
$id_modifica = isset($_GET["modifica"]) ? $_GET["modifica"] : null;
$codice_modifica = "";
$luogo_modifica = "";
$note_modifica = "";
$link_modifica = ""; 

if ($id_modifica) {
    // SELEZIONA TUTTI I CAMPI
    $sql = "SELECT AI_CODICE, AI_LUOGO, AI_NOTE, AI_LINK FROM TA_Apiari WHERE AI_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_modifica);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $codice_modifica = $row["AI_CODICE"];
        $luogo_modifica = $row["AI_LUOGO"];
        $note_modifica = $row["AI_NOTE"];
        $link_modifica = $row["AI_LINK"]; 
    }
    $stmt->close();
}
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column" style="display: flex; gap: 20px;">
        
        <div style="flex: 1;">
            <h2 class="titolo-arnie">Apiari</h2>
            
            <?php echo $messaggio; ?>

            <h3>Elenco Apiari</h3>
            <div class="table-container">
                <?php
                // Query per visualizzare gli apiari esistenti
                $sql = "SELECT AI_ID, AI_CODICE, AI_LUOGO, AI_NOTE, AI_LINK FROM TA_Apiari"; 
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    echo "<table>
                            <tr>
                                <th>ID</th>
                                <th>Codice</th> 
                                <th>Luogo</th>
                                <th class='note-column'>Note</th>
                                <th>Mappa</th>
                                <th>Cartello</th> <th>Azioni</th>
                            </tr>";
                    while ($row = $result->fetch_assoc()) {
                        $link_presente = !empty($row["AI_LINK"]);
                        $codice_presente = !empty($row["AI_CODICE"]);
                        
                        echo "<tr>
                                <td>" . $row["AI_ID"] . "</td>
                                <td>" . htmlspecialchars($row["AI_CODICE"]) . "</td> 
                                <td>" . $row["AI_LUOGO"] . "</td>
                                <td class='note-column'>" . $row["AI_NOTE"] . "</td>
                                <td style='text-align: center;'>";
                                
                                if ($link_presente) {
                                    echo "<button class='btn btn-stampa' onclick='mostraMappa(\"" . htmlspecialchars($row["AI_LINK"]) . "\", \"" . htmlspecialchars($row["AI_LUOGO"]) . "\")' style='padding: 5px 10px;'>
                                            <span style='font-size: 1.2em;'>??</span> Vedi
                                          </button>";
                                } else {
                                    echo "N/A";
                                }
                        echo "  </td>
                                <td style='text-align: center;'>";
                                // PULSANTE STAMPA CARTELLO
                                if ($codice_presente) {
                                    echo "<a href='" . url('pages/stampa_cartello.php?id=' . $row["AI_ID"]) . "' target='_blank' class='btn btn-stampa' style='padding: 5px 10px;'>
                                            <span style='font-size: 1.2em;'>???</span> Stampa
                                          </a>";
                                } else {
                                    echo "Codice Mancante";
                                }
                        echo "  </td>
                                <td>
                                    <a href='" . url('pages/apiari.php?modifica=' . $row["AI_ID"]) . "' class='btn btn-modifica'>Modifica</a>
                                    <form method='POST' action='" . url('pages/apiari.php') . "' style='display:inline;' onsubmit='return confermaEliminazione();'>
                                        <input type='hidden' name='id' value='" . $row["AI_ID"] . "'>
                                        <button type='submit' name='elimina' class='btn btn-elimina'>Elimina</button>
                                    </form>
                                </td>
                              </tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>Nessun apiario trovato.</p>";
                }
                ?>
            </div>

            <h3><?php echo $id_modifica ? "Modifica Apiario" : "Inserisci Nuovo Apiario"; ?></h3>
            <div class="form-container">
                <form action="<?php echo url('pages/apiari.php'); ?>" method="post"> 
                    <?php if ($id_modifica): ?>
                        <input type="hidden" name="id" value="<?php echo $id_modifica; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="codice">Codice Apiario (max 12):</label>
                        <input type="text" id="codice" name="codice" value="<?php echo htmlspecialchars($codice_modifica); ?>" maxlength="12" required>
                    </div>

                    <div class="form-group">
                        <label for="luogo">Luogo:</label>
                        <input type="text" id="luogo" name="luogo" value="<?php echo htmlspecialchars($luogo_modifica); ?>" maxlength="60" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="link">Link Google Maps (Incorpora Mappa):</label>
                        <input type="url" id="link" name="link" value="<?php echo htmlspecialchars($link_modifica); ?>" maxlength="2048" placeholder="Incolla qui il link di incorporamento">
                    </div>
                    
                    <div class="form-group">
                        <label for="note">Note:</label>
                        <textarea id="note" name="note" maxlength="1000"><?php echo htmlspecialchars($note_modifica); ?></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="<?php echo $id_modifica ? 'modifica' : 'inserisci'; ?>" class="btn btn-inserisci btn-grande">
                            <?php echo $id_modifica ? "Salva Modifiche" : "Inserisci Apiario"; ?>
                        </button>
                        <?php if ($id_modifica): ?>
                            <a href="<?php echo url('pages/apiari.php'); ?>" class="btn btn-elimina btn-grande">Annulla</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div style="flex: 1;">
            <div id="map-display-container" style="border: 1px solid #ddd; padding: 10px; min-height: 400px; display: none;">
                <h3 id="map-title">Mappa Apiario</h3>
                <div id="map-iframe-wrapper" style="width: 100%; height: 350px;">
                    </div>
            </div>
        </div>

    </div>

    <div class="right-column"></div>
</main>

<script>
// Funzione per confermare l'eliminazione
function confermaEliminazione() {
    return confirm("Sei sicuro di voler eliminare questo apiario?");
}

// Funzione JavaScript per mostrare la mappa
function mostraMappa(link, luogo) {
    const container = document.getElementById('map-iframe-wrapper');
    const display = document.getElementById('map-display-container');
    const title = document.getElementById('map-title');

    // Aggiunto controllo per assicurarsi che il link sia un URL valido (richiesto per CSP e sicurezza)
    if (link && link.startsWith('http')) { 
        title.innerHTML = `Mappa di: ${luogo}`;

        // Crea e inserisce l'iframe
        container.innerHTML = `<iframe 
            src="${link}" 
            width="100%" 
            height="100%" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer">
        </iframe>`;

        display.style.display = 'block';
    } else {
        container.innerHTML = '<p style="text-align: center; margin-top: 50px;">Nessun link di incorporamento valido (deve iniziare con http). Assicurati di usare l\'URL Embed di Google Maps.</p>';
        title.innerHTML = `Mappa Apiario`;
        display.style.display = 'block';
    }
}

// Carica la mappa automaticamente se siamo in modalità modifica e il link è presente
<?php if ($id_modifica && !empty($link_modifica)): ?>
    document.addEventListener('DOMContentLoaded', () => {
        mostraMappa("<?php echo htmlspecialchars($link_modifica); ?>", "<?php echo htmlspecialchars($luogo_modifica); ?>");
    });
<?php endif; ?>
</script>

<?php
require_once '../includes/footer.php';
?>