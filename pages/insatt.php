<?php
// Imposta l'intestazione HTTP per la codifica
header('Content-Type: text/html; charset=utf-8');

include '../includes/config.php';
include '../includes/header.php'; 

// 1. Gestione Inserimento
$messaggio = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["inserisci"])) {
    // Recupera e sanifica i nuovi dati dal form
    $data_attivita = $_POST["data_attivita"];
    $arnia_selezionata = $_POST["arnia_selezionata"]; // Nuovo campo
    $tipo_attivita = $_POST["tipo_attivita"];
    $note = $_POST["note"];

    // Prepara la query SQL, con il nuovo campo IA_CodAr (i)
    $sql = "INSERT INTO AT_INSATT (IA_DATA, IA_CodAr, IA_ATT, IA_NOTE) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Associa i parametri (s=string, i=integer, i=integer, s=string)
        $stmt->bind_param("siis", $data_attivita, $arnia_selezionata, $tipo_attivita, $note);

        // Esegui la query
        if ($stmt->execute()) {
            // Pattern Post/Redirect/Get
            header("Location: insatt.php?status=success");
            exit();
        } else {
            $messaggio = "<p class='errore'>Errore durante l'inserimento: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        $messaggio = "<p class='errore'>Errore nella preparazione della query: " . $conn->error . "</p>";
    }
}

// 2. Recupero delle Attività per la combo box (da TA_Attivita)
$attivita_options = [];
$sql_attivita = "SELECT AT_ID, AT_DESCR FROM TA_Attivita ORDER BY AT_DESCR";
$result_attivita = $conn->query($sql_attivita);

if ($result_attivita) {
    while ($row = $result_attivita->fetch_assoc()) {
        $attivita_options[] = $row;
    }
}

// 3. Recupero delle Arnie per la nuova combo box (da AP_Arnie)
$arnie_options = [];
// Recupera AR_ID (memorizzato) e AR_NOME (visualizzato)
$sql_arnie = "SELECT AR_ID, AR_NOME, AR_CODICE FROM AP_Arnie ORDER BY AR_NOME";
$result_arnie = $conn->query($sql_arnie);

if ($result_arnie) {
    while ($row = $result_arnie->fetch_assoc()) {
        $arnie_options[] = $row;
    }
}


// 4. Gestione del messaggio di successo post-redirect
if (isset($_GET["status"]) && $_GET["status"] == "success") {
    $messaggio = "<p class='successo'>Attività inserita con successo!</p>";
}
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2 class="titolo-arnie">Inserimento Nuova Attività</h2>
        <?php echo $messaggio; ?>

        <div class="form-container">
            <form action="insatt.php" method="post">
                
                <div class="form-group">
                    <label for="data_attivita">Data Attività:</label>
                    <input type="date" id="data_attivita" name="data_attivita" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="arnia_selezionata">Arnia:</label>
                    <select id="arnia_selezionata" name="arnia_selezionata" class="form-control" required>
                        <option value="">Seleziona un'arnia (Codice / Nome)</option>
                        <?php foreach ($arnie_options as $arnia): ?>
                            <option value="<?php echo $arnia['AR_ID']; ?>">
                                <?php echo htmlspecialchars($arnia['AR_CODICE'] . " / " . $arnia['AR_NOME']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($arnie_options)): ?>
                         <p class='errore-select'>Nessuna arnia trovata in AP_Arnie.</p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="tipo_attivita">Tipo di Attività:</label>
                    <select id="tipo_attivita" name="tipo_attivita" class="form-control" required>
                        <option value="">Seleziona un'attività</option>
                        <?php foreach ($attivita_options as $att): ?>
                            <option value="<?php echo $att['AT_ID']; ?>">
                                <?php echo htmlspecialchars($att['AT_DESCR']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($attivita_options)): ?>
                         <p class='errore-select'>Nessun tipo di attività trovato in TA_Attivita.</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="note">Note Dettagliate:</label>
                    <textarea id="note" name="note" maxlength="1000" rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="inserisci" class="btn btn-inserisci btn-grande">
                        Registra Attività
                    </button>
                </div>
            </form>
        </div>
        
    </div>

    <div class="right-column"></div>
</main>

<?php
include '../includes/footer.php';
?>