<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'M.0.5 (Layout Unificato)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php';

$messaggio = "";

// --- GESTIONE OPERAZIONI CRUD ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salva_mag'])) {
    $id = $_POST['id'] ?? null;
    $mastro = (int)$_POST['tm_mastro'];
    $smastro = (int)$_POST['tm_smastro'];
    $descrizione = trim($_POST['tm_descrizione']);
    $note = trim($_POST['tm_note']);

    if ($id) {
        $sql = "UPDATE TA_MAG SET TM_Mastro=?, TM_SMastro=?, TM_Descrizione=?, TM_Note=? WHERE ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissi", $mastro, $smastro, $descrizione, $note, $id);
    } else {
        $sql = "INSERT INTO TA_MAG (TM_Mastro, TM_SMastro, TM_Descrizione, TM_Note) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $mastro, $smastro, $descrizione, $note);
    }

    if ($stmt->execute()) {
        header("Location: ta_mag.php?status=success&tab=scheda&edit=" . ($id ?? $conn->insert_id));
        exit();
    } else {
        $messaggio = "<p class='errore'>Errore: Codice duplicato o dati non validi.</p>";
    }
    $stmt->close();
}

if (isset($_GET["status"]) && $_GET["status"] == "success") {
    $messaggio = "<p class='successo'>Categoria di magazzino salvata correttamente!</p>";
}

// Parametri per la visualizzazione
$active_tab = $_GET['tab'] ?? 'selezione';
$edit_id = $_GET['edit'] ?? null;
$row_edit = null;

if ($edit_id) {
    $res = $conn->query("SELECT * FROM TA_MAG WHERE ID = " . (int)$edit_id);
    $row_edit = $res->fetch_assoc();
}

$lista = $conn->query("SELECT * FROM TA_MAG ORDER BY TM_Mastro ASC, TM_SMastro ASC");
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2>Configurazione Magazzino</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>" id="tab-link-selezione" onclick="openTab(event, 'selezione')">ELENCO CATEGORIE</li>
                <li class="tab-link <?php echo ($active_tab == 'scheda') ? 'active' : ''; ?>" id="tab-link-scheda" onclick="openTab(event, 'scheda')">SCHEDA DETTAGLIO</li>
            </ul>

            <div id="selezione" class="tab-content <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="selectable-table table-fixed-layout">
                        <thead>
                            <tr>
                                <th style="width: 80px; text-align: center;">Mastro</th>
                                <th style="width: 80px; text-align: center;">SMastro</th>
                                <th class="col-auto">Descrizione Categoria</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $lista->fetch_assoc()): 
                                $selected_class = ($edit_id == $row['ID']) ? 'selected-row' : '';
                                // Stile per evidenziare i Mastri (SMastro = 0)
                                $mastro_style = ($row['TM_SMastro'] == 0) ? 'font-weight: bold; background-color: #f9f9f9;' : '';
                            ?>
                                <tr class="<?php echo $selected_class; ?>" style="<?php echo $mastro_style; ?>" 
                                    onclick="window.location.href='ta_mag.php?edit=<?php echo $row['ID']; ?>&tab=scheda'">
                                    <td style="text-align: center;"><?php echo $row['TM_Mastro']; ?></td>
                                    <td style="text-align: center;"><?php echo $row['TM_SMastro']; ?></td>
                                    <td class="col-auto">
                                        <?php if($row['TM_SMastro'] > 0) echo "&nbsp;&nbsp;↳ "; ?>
                                        <?php echo htmlspecialchars($row['TM_Descrizione']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-inserisci" style="margin-top: 15px; width: 250px;" onclick="window.location.href='ta_mag.php?tab=scheda'">+ NUOVA CATEGORIA</button>
            </div>

            <div id="scheda" class="tab-content <?php echo ($active_tab == 'scheda') ? 'active' : ''; ?>">
                <div class="form-container" style="border-color: #008CBA;">
                    <h3 style="margin-top:0;"><?php echo $edit_id ? "Modifica Record ID: $edit_id" : "Inserimento Nuova Categoria"; ?></h3>
                    <form action="ta_mag.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $row_edit['ID'] ?? ''; ?>">
                        
                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label>Cod. Mastro (1-99):</label>
                                <input type="number" name="tm_mastro" min="1" max="99" value="<?php echo $row_edit['TM_Mastro'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Cod. Sottomastro (0 = Mastro):</label>
                                <input type="number" name="tm_smastro" min="0" max="99" value="<?php echo $row_edit['TM_SMastro'] ?? '0'; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descrizione Categoria:</label>
                            <input type="text" name="tm_descrizione" maxlength="60" value="<?php echo htmlspecialchars($row_edit['TM_Descrizione'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Note aggiuntive:</label>
                            <textarea name="tm_note" maxlength="500" rows="4"><?php echo htmlspecialchars($row_edit['TM_Note'] ?? ''); ?></textarea>
                        </div>

                        <div class="btn-group-form" style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" name="salva_mag" class="btn btn-salva" style="flex: 1;">SALVA DATI</button>
                            <?php if($edit_id): ?>
                                <a href="ta_mag.php" class="btn btn-annulla" style="flex: 1;">ANNULLA / NUOVO</a>
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
    $('.tab-content').hide();
    $('.tab-link').removeClass('active');
    $('#' + tabName).show();
    if(evt) $(evt.currentTarget).addClass('active');
}

$(document).ready(function() {
    const activeTab = '<?php echo $active_tab; ?>';
    if(activeTab) {
        $('.tab-content').hide();
        $('#' + activeTab).show();
        $('.tab-link').removeClass('active');
        $('#tab-link-' + activeTab).addClass('active');
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>