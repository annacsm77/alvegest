<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'M.0.5 (Layout Unificato)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php';

$messaggio = "";
$active_tab = $_GET['tab'] ?? 'selezione';
$edit_id = $_GET['edit'] ?? null;

// --- LOGICA CRUD: SALVATAGGIO (INSERT/UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salva_art'])) {
    $id = $_POST['id'] ?? null;
    $codice = trim($_POST['art_codice']);
    $descrizione = trim($_POST['art_descrizione']);
    $mastro_id = (int)$_POST['art_mastro_id'];
    $um = trim(strtoupper($_POST['art_um']));
    $prezzo = (float)$_POST['art_prezzomedio'];
    $note = trim($_POST['art_note']);

    if ($id) {
        $sql = "UPDATE MA_Articoli SET ART_Codice=?, ART_Descrizione=?, ART_Mastro_ID=?, ART_UM=?, ART_PrezzoMedio=?, ART_Note=? WHERE ART_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisdsi", $codice, $descrizione, $mastro_id, $um, $prezzo, $note, $id);
    } else {
        $sql = "INSERT INTO MA_Articoli (ART_Codice, ART_Descrizione, ART_Mastro_ID, ART_UM, ART_PrezzoMedio, ART_Note) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisds", $codice, $descrizione, $mastro_id, $um, $prezzo, $note);
    }

    if ($stmt->execute()) {
        header("Location: ma_articoli.php?status=success&tab=scheda&edit=" . ($id ?? $conn->insert_id));
        exit();
    } else {
        $messaggio = "<p class='errore'>Errore durante il salvataggio dell'articolo.</p>";
    }
    $stmt->close();
}

if (isset($_GET["status"]) && $_GET["status"] == "success") {
    $messaggio = "<p class='successo'>Articolo salvato correttamente!</p>";
}

// --- RECUPERO DATI PER L'INTERFACCIA ---
$art_edit = null;
if ($edit_id) {
    $res = $conn->query("SELECT * FROM MA_Articoli WHERE ART_ID = " . (int)$edit_id);
    $art_edit = $res->fetch_assoc();
}

$lista_art = $conn->query("SELECT A.*, T.TM_Descrizione as Categoria FROM MA_Articoli A JOIN TA_MAG T ON A.ART_Mastro_ID = T.ID ORDER BY A.ART_Codice ASC");
$sm_options = $conn->query("SELECT ID, TM_Mastro, TM_SMastro, TM_Descrizione FROM TA_MAG WHERE TM_SMastro > 0 ORDER BY TM_Mastro, TM_SMastro");
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2>Anagrafica Articoli</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>" id="tab-link-selezione" onclick="openTab(event, 'selezione')">ARTICOLI A CATALOGO</li>
                <li class="tab-link <?php echo ($active_tab == 'scheda') ? 'active' : ''; ?>" id="tab-link-scheda" onclick="openTab(event, 'scheda')">SCHEDA ARTICOLO</li>
            </ul>

            <div id="selezione" class="tab-content <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="selectable-table table-fixed-layout">
                        <thead>
                            <tr>
                                <th class="col-cod">Codice (SKU)</th>
                                <th class="col-auto">Descrizione Articolo</th>
                                <th style="width: 200px;">Categoria</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $lista_art->fetch_assoc()): 
                                $class = ($edit_id == $row['ART_ID']) ? 'selected-row' : '';
                            ?>
                                <tr class="<?php echo $class; ?>" onclick="window.location.href='ma_articoli.php?edit=<?php echo $row['ART_ID']; ?>&tab=scheda'">
                                    <td class="col-cod"><strong><?php echo htmlspecialchars($row['ART_Codice']); ?></strong></td>
                                    <td class="col-auto"><?php echo htmlspecialchars($row['ART_Descrizione']); ?></td>
                                    <td style="width: 200px;"><small><?php echo htmlspecialchars($row['Categoria']); ?></small></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-inserisci" style="margin-top: 15px; width: 200px;" onclick="window.location.href='ma_articoli.php?tab=scheda'">+ NUOVO ARTICOLO</button>
            </div>

            <div id="scheda" class="tab-content <?php echo ($active_tab == 'scheda') ? 'active' : ''; ?>">
                <div class="form-container" style="border-color: #008CBA;">
                    <form action="ma_articoli.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $art_edit['ART_ID'] ?? ''; ?>">
                        
                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label>Codice Articolo (SKU):</label>
                                <input type="text" name="art_codice" value="<?php echo htmlspecialchars($art_edit['ART_Codice'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Categoria (Sottomastro):</label>
                                <select name="art_mastro_id" required>
                                    <option value="">-- Seleziona --</option>
                                    <?php while($opt = $sm_options->fetch_assoc()): 
                                        $sel = ($art_edit['ART_Mastro_ID'] == $opt['ID']) ? 'selected' : '';
                                        echo "<option value='{$opt['ID']}' $sel>[{$opt['TM_Mastro']}.{$opt['TM_SMastro']}] {$opt['TM_Descrizione']}</option>";
                                    endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descrizione Completa:</label>
                            <input type="text" name="art_descrizione" value="<?php echo htmlspecialchars($art_edit['ART_Descrizione'] ?? ''); ?>" required>
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label>U.M.:</label>
                                <input type="text" name="art_um" maxlength="3" value="<?php echo $art_edit['ART_UM'] ?? 'PZ'; ?>">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Prezzo Medio (€):</label>
                                <input type="number" name="art_prezzomedio" step="0.01" value="<?php echo $art_edit['ART_PrezzoMedio'] ?? '0.00'; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Note:</label>
                            <textarea name="art_note" rows="4"><?php echo htmlspecialchars($art_edit['ART_Note'] ?? ''); ?></textarea>
                        </div>

                        <div class="btn-group-form" style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="submit" name="salva_art" class="btn btn-salva" style="flex: 1;">SALVA ARTICOLO</button>
                            <?php if($edit_id): ?>
                                <a href="ma_articoli.php" class="btn btn-annulla" style="flex: 1;">ANNULLA / NUOVO</a>
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
    // Gestione dell'attivazione iniziale dei tab tramite URL
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