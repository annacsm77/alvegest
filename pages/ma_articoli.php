<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

$versione = "M.0.4";
$messaggio = "";

// Logica CRUD
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
    $messaggio = "<p class='successo'>✅ Articolo salvato correttamente!</p>";
}

$active_tab = $_GET['tab'] ?? 'selezione';
$edit_id = $_GET['edit'] ?? null;
$art_edit = null;

if ($edit_id) {
    $res = $conn->query("SELECT * FROM MA_Articoli WHERE ART_ID = $edit_id");
    $art_edit = $res->fetch_assoc();
}

$lista_art = $conn->query("SELECT A.*, T.TM_Descrizione as Categoria FROM MA_Articoli A JOIN TA_MAG T ON A.ART_Mastro_ID = T.ID ORDER BY A.ART_Codice ASC");
$sm_options = $conn->query("SELECT ID, TM_Mastro, TM_SMastro, TM_Descrizione FROM TA_MAG WHERE TM_SMastro > 0 ORDER BY TM_Mastro, TM_SMastro");
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column" style="padding: 5px;">
        <h2 style="margin-top: 15px; margin-bottom: 15px;">Anagrafica Articoli <small>(V.<?php echo $versione; ?>)</small></h2>
        <?php echo $messaggio; ?>

        <div class="tab-nav">
            <button class="tab-button" id="tab-selezione" onclick="openTab(event, 'selezione')">ARTICOLI A CATALOGO</button>
            <button class="tab-button" id="tab-scheda" onclick="openTab(event, 'scheda')">SCHEDA ARTICOLO</button>
        </div>

        <div id="selezione" class="tab-content-item">
            <div class="table-container" style="max-height: 60vh; overflow-y: auto; border: 1px solid #ddd; margin-top: 10px;">
                <table class="selectable-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Codice (SKU)</th>
                            <th>Descrizione Articolo</th>
                            <th style="width: 25%;">Categoria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $lista_art->fetch_assoc()): 
                            $class = ($edit_id == $row['ART_ID']) ? 'selected-row' : '';
                        ?>
                            <tr class="<?php echo $class; ?>" onclick="window.location.href='ma_articoli.php?edit=<?php echo $row['ART_ID']; ?>&tab=scheda'">
                                <td><?php echo htmlspecialchars($row['ART_Codice']); ?></td>
                                <td><?php echo htmlspecialchars($row['ART_Descrizione']); ?></td>
                                <td><small><?php echo htmlspecialchars($row['Categoria']); ?></small></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-inserisci btn-grande" style="margin-top: 15px;" onclick="window.location.href='ma_articoli.php?tab=scheda'">+ NUOVO ARTICOLO</button>
        </div>

        <div id="scheda" class="tab-content-item">
            <div class="form-container" style="margin-top: 10px; border: 2px solid #008CBA;">
                <form action="ma_articoli.php" method="post">
                    <input type="hidden" name="id" value="<?php echo $art_edit['ART_ID'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label>Codice Articolo (SKU):</label>
                        <input type="text" name="art_codice" value="<?php echo htmlspecialchars($art_edit['ART_Codice'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Categoria (Sottomastro):</label>
                        <select name="art_mastro_id" required>
                            <option value="">-- Seleziona --</option>
                            <?php while($opt = $sm_options->fetch_assoc()): 
                                $sel = ($art_edit['ART_Mastro_ID'] == $opt['ID']) ? 'selected' : '';
                                echo "<option value='{$opt['ID']}' $sel>[{$opt['TM_Mastro']}.{$opt['TM_SMastro']}] {$opt['TM_Descrizione']}</option>";
                            endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Descrizione Completa:</label>
                        <input type="text" name="art_descrizione" value="<?php echo htmlspecialchars($art_edit['ART_Descrizione'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group-flex">
                        <div class="form-group">
                            <label>U.M.:</label>
                            <input type="text" name="art_um" maxlength="3" value="<?php echo $art_edit['ART_UM'] ?? 'PZ'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Prezzo Medio (€):</label>
                            <input type="number" name="art_prezzomedio" step="0.01" value="<?php echo $art_edit['ART_PrezzoMedio'] ?? '0.00'; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Note:</label>
                        <textarea name="art_note" rows="3"><?php echo htmlspecialchars($art_edit['ART_Note'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <button type="submit" name="salva_art" class="btn btn-inserisci btn-grande">SALVA ARTICOLO</button>
                        <?php if($edit_id): ?>
                            <a href="ma_articoli.php" class="btn btn-elimina btn-grande">ANNULLA / NUOVO</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="right-column"></div>
</main>

<script>
function openTab(evt, tabName) {
    document.querySelectorAll('.tab-content-item').forEach(item => item.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    if(evt) evt.currentTarget.classList.add('active');
}

document.addEventListener("DOMContentLoaded", function() {
    const activeTab = '<?php echo $active_tab; ?>';
    const btn = document.getElementById('tab-' + activeTab);
    if(btn) btn.click();
});
</script>

<style>
    .selected-row { background-color: #cceeff !important; font-weight: bold; }
    .tab-nav { display: flex; border-bottom: 2px solid #ccc; margin-top: 10px; }
    .tab-button { flex: 1; padding: 10px; font-weight: bold; cursor: pointer; border: none; background: #f0f0f0; transition: 0.2s; }
    .tab-button.active { background: #008CBA; color: white; }
    .tab-content-item { display: none; padding: 10px 0; }
    .tab-content-item.active { display: block; }
</style>

<?php require_once '../includes/footer.php'; ?>