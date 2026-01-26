<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'M.0.7 - Safe Edit Column');

require_once '../includes/config.php';

// --- LOGICA CRUD: SALVATAGGIO (Invariata) ---
$messaggio = "";
$active_tab = $_GET['tab'] ?? 'selezione';
$edit_id = $_GET['edit'] ?? null;

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
    }
}

require_once TPL_PATH . 'header.php';

if (isset($_GET["status"]) && $_GET["status"] == "success") {
    $messaggio = "<p class='successo'>Articolo salvato correttamente!</p>";
}

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
        <h2 class="titolo-arnie">Anagrafica Articoli</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>" id="tab-link-selezione" onclick="openTab(event, 'selezione')">ARTICOLI A CATALOGO</li>
                <li class="tab-link <?php echo ($active_tab == 'scheda') ? 'active' : ''; ?>" id="tab-link-scheda" onclick="openTab(event, 'scheda')">
                    <?php echo $edit_id ? "MODIFICA ARTICOLO" : "NUOVO ARTICOLO"; ?>
                </li>
            </ul>

            <div id="selezione" class="tab-content <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="selectable-table">
                        <thead>
                            <tr>
                                <th class="txt-center">CODICE (SKU)</th>
                                <th>DESCRIZIONE</th>
                                <th>CATEGORIA</th>
                                <th class="txt-center">AZIONI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $lista_art->fetch_assoc()): 
                                $class = ($edit_id == $row['ART_ID']) ? 'selected-row' : '';
                            ?>
                                <tr class="<?php echo $class; ?>">
                                    <td class="txt-center font-bold"><?php echo htmlspecialchars($row['ART_Codice']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ART_Descrizione']); ?></td>
                                    <td class="txt-small txt-muted"><?php echo htmlspecialchars($row['Categoria']); ?></td>
                                    <td class="txt-center">
                                        <a href="ma_articoli.php?edit=<?php echo $row['ART_ID']; ?>&tab=scheda" class="btn-tabella-modifica">Modifica</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="btn-group-flex">
                    <button class="btn btn-stampa btn-flex-1" onclick="window.location.href='ma_articoli.php?tab=scheda'">+ AGGIUNGI NUOVO ARTICOLO</button>
                    <div class="btn-flex-2"></div>
                </div>
            </div>

            <div id="scheda" class="tab-content <?php echo ($active_tab == 'scheda') ? 'active' : ''; ?>">
                <div class="form-container">
                    <form action="ma_articoli.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $art_edit['ART_ID'] ?? ''; ?>">
                        
                        <div class="btn-group-flex">
                            <div class="form-group btn-flex-1">
                                <label>Codice Articolo (SKU):</label>
                                <input type="text" name="art_codice" value="<?php echo htmlspecialchars($art_edit['ART_Codice'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group btn-flex-1">
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

                        <div class="btn-group-flex">
                            <div class="form-group btn-flex-1">
                                <label>U.M.:</label>
                                <input type="text" name="art_um" maxlength="3" value="<?php echo $art_edit['ART_UM'] ?? 'PZ'; ?>">
                            </div>
                            <div class="form-group btn-flex-1">
                                <label>Prezzo Medio (€):</label>
                                <input type="number" name="art_prezzomedio" step="0.01" value="<?php echo $art_edit['ART_PrezzoMedio'] ?? '0.00'; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Note Interne:</label>
                            <textarea name="art_note" rows="4"><?php echo htmlspecialchars($art_edit['ART_Note'] ?? ''); ?></textarea>
                        </div>

                        <div class="btn-group-flex">
                            <button type="submit" name="salva_art" class="btn btn-salva btn-flex-2">SALVA ARTICOLO</button>
                            <?php if($edit_id): ?>
                                <a href="ma_articoli.php" class="btn btn-annulla btn-flex-1">ANNULLA / NUOVO</a>
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
    $('.tab-content').hide().removeClass('active');
    $('.tab-link').removeClass('active');
    $('#' + tabName).show().addClass('active');
    if(evt) $(evt.currentTarget).addClass('active');
}

$(document).ready(function() {
    const activeTab = '<?php echo $active_tab; ?>';
    if(activeTab) {
        openTab(null, activeTab);
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>