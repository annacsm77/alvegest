<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.0.5 (Fix Tab Style)');

require_once '../includes/config.php'; 
require_once TPL_PATH . 'header.php'; 

// 1. INIZIALIZZAZIONE VARIABILI
$messaggio = "";
$modifica_id = $_GET["modifica"] ?? null;
$curr = ['TI_id' => '', 'TI_CODICE' => '', 'TI_DESCR' => '', 'TI_Note' => ''];

// 2. GESTIONE LOGICA POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codice = strtoupper(substr($_POST['codice'] ?? '', 0, 4));
    $descr = $_POST['descr'] ?? "";
    $note = $_POST['note'] ?? "";

    if (isset($_POST["inserisci"])) {
        $sql = "INSERT INTO TA_TIPA (TI_CODICE, TI_DESCR, TI_Note) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $codice, $descr, $note);
            if ($stmt->execute()) {
                header("Location: tipologie_arnie.php?status=insert_success");
                exit();
            }
        }
    } elseif (isset($_POST["modifica"])) {
        $id = $_POST["id"];
        $sql = "UPDATE TA_TIPA SET TI_CODICE = ?, TI_DESCR = ?, TI_Note = ? WHERE TI_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssi", $codice, $descr, $note, $id);
            if ($stmt->execute()) {
                header("Location: tipologie_arnie.php?status=update_success");
                exit();
            }
        }
    } elseif (isset($_POST["elimina"])) {
        $id = $_POST["id"];
        $sql = "DELETE FROM TA_TIPA WHERE TI_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: tipologie_arnie.php?status=delete_success");
                exit();
            }
        }
    }
}

// 3. RECUPERO DATI PER MODIFICA
if ($modifica_id) {
    $stmt = $conn->prepare("SELECT * FROM TA_TIPA WHERE TI_id = ?");
    $stmt->bind_param("i", $modifica_id);
    $stmt->execute();
    $curr = $stmt->get_result()->fetch_assoc();
}

// 4. RECUPERO ELENCO
$res = $conn->query("SELECT * FROM TA_TIPA ORDER BY TI_CODICE ASC");
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2>Tipologie Arnie</h2>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$modifica_id ? 'active' : ''; ?>" onclick="openTab(event, 'tab-elenco')">ELENCO</li>
                <li class="tab-link <?php echo $modifica_id ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')">
                    <?php echo $modifica_id ? "MODIFICA" : "NUOVA"; ?>
                </li>
            </ul>

            <div id="tab-elenco" class="tab-content <?php echo !$modifica_id ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="paleBlueRows">
                        <thead>
                            <tr>
                                <th>Codice</th>
                                <th>Descrizione</th>
                                <th style="width: 100px; text-align: center;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($t = $res->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($t['TI_CODICE']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($t['TI_DESCR']); ?></td>
                                    <td style="text-align: center;">
                                        <a href="?modifica=<?php echo $t['TI_id']; ?>" class="btn btn-modifica" title="Modifica">✏️</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-form" class="tab-content <?php echo $modifica_id ? 'active' : ''; ?>">
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $curr['TI_id']; ?>">
                        
                        <div class="form-group">
                            <label>Codice (max 4 caratteri)</label>
                            <input type="text" name="codice" maxlength="4" value="<?php echo htmlspecialchars($curr['TI_CODICE']); ?>" required style="text-transform: uppercase;">
                        </div>

                        <div class="form-group">
                            <label>Descrizione</label>
                            <input type="text" name="descr" maxlength="60" value="<?php echo htmlspecialchars($curr['TI_DESCR']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="note" rows="4"><?php echo htmlspecialchars($curr['TI_Note']); ?></textarea>
                        </div>

                        <div class="form-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                            <?php if (!$modifica_id): ?>
                                <button type="submit" name="inserisci" class="btn btn-salva" style="flex: 1;">Inserisci</button>
                            <?php else: ?>
                                <button type="submit" name="modifica" class="btn btn-salva" style="flex: 1;">Salva</button>
                                <button type="submit" name="elimina" class="btn btn-elimina" style="flex: 1;" onclick="return confirm('Sei sicuro?');">Elimina</button>
                                <a href="tipologie_arnie.php" class="btn btn-annulla" style="flex: 1; text-align: center; text-decoration: none; line-height: 2.2;">Annulla</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="versione-info">
            Versione: <?php echo FILE_VERSION; ?>
        </div>
    </div>

    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function openTab(evt, tabName) {
    // Nascondi tutti i contenuti dei tab
    $('.tab-content').hide().removeClass('active');
    // Rimuovi classe active da tutti i link
    $('.tab-link').removeClass('active');
    
    // Mostra il tab selezionato
    $('#' + tabName).show().addClass('active');
    // Aggiungi classe active al link cliccato
    $(evt.currentTarget).addClass('active');
}

// Inizializzazione corretta per visualizzare il tab attivo al caricamento
$(document).ready(function(){
    <?php if ($modifica_id): ?>
        // Se siamo in modifica, apriamo forzatamente il tab form
        $('.tab-content').hide();
        $('#tab-form').show().addClass('active');
    <?php else: ?>
        // Altrimenti elenco
        $('.tab-content').hide();
        $('#tab-elenco').show().addClass('active');
    <?php endif; ?>
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>