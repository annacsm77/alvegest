<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

$versione = "M.0.4";
$messaggio = "";

// Gestione Redirect e Operazioni CRUD
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
    $messaggio = "<p class='successo'>✅ Operazione completata con successo!</p>";
}

// Parametri per la visualizzazione
$active_tab = $_GET['tab'] ?? 'selezione';
$edit_id = $_GET['edit'] ?? null;
$row_edit = null;

if ($edit_id) {
    $res = $conn->query("SELECT * FROM TA_MAG WHERE ID = $edit_id");
    $row_edit = $res->fetch_assoc();
}

$lista = $conn->query("SELECT * FROM TA_MAG ORDER BY TM_Mastro ASC, TM_SMastro ASC");
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column" style="padding: 5px;">
        <h2 style="margin-top: 15px; margin-bottom: 15px;">Configurazione Magazzino <small>(V.<?php echo $versione; ?>)</small></h2>
        <?php echo $messaggio; ?>

        <div class="tab-nav">
            <button class="tab-button" id="tab-selezione" onclick="openTab(event, 'selezione')">ELENCO CATEGORIE</button>
            <button class="tab-button" id="tab-scheda" onclick="openTab(event, 'scheda')">SCHEDA DETTAGLIO</button>
        </div>

        <div id="selezione" class="tab-content-item">
            <h4 style="margin-top: 10px;">Seleziona una riga per modificarla o visualizzarla</h4>
            <div class="table-container" style="max-height: 60vh; overflow-y: auto; border: 1px solid #ddd; margin-top: 5px;">
                <table class="selectable-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Mastro</th>
                            <th style="width: 15%;">SMastro</th>
                            <th>Descrizione Categoria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $lista->fetch_assoc()): 
                            $selected_class = ($edit_id == $row['ID']) ? 'selected-row' : '';
                            $bold_style = ($row['TM_SMastro'] == 0) ? 'font-weight: bold; background-color: #f2f2f2;' : '';
                        ?>
                            <tr class="<?php echo $selected_class; ?>" style="<?php echo $bold_style; ?>" 
                                onclick="window.location.href='ta_mag.php?edit=<?php echo $row['ID']; ?>&tab=scheda'">
                                <td><?php echo $row['TM_Mastro']; ?></td>
                                <td><?php echo $row['TM_SMastro']; ?></td>
                                <td><?php echo htmlspecialchars($row['TM_Descrizione']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-inserisci btn-grande" style="margin-top: 15px;" onclick="window.location.href='ta_mag.php?tab=scheda'">+ NUOVA CATEGORIA / MASTRO</button>
        </div>

        <div id="scheda" class="tab-content-item">
            <div class="form-container" style="margin-top: 10px; border: 2px solid #008CBA;">
                <h3><?php echo $edit_id ? "Modifica Record ID: $edit_id" : "Inserimento Nuovo Record"; ?></h3>
                <form action="ta_mag.php" method="post">
                    <input type="hidden" name="id" value="<?php echo $row_edit['ID'] ?? ''; ?>">
                    
                    <div class="form-group-flex">
                        <div class="form-group">
                            <label>Cod. Mastro (1-99):</label>
                            <input type="number" name="tm_mastro" min="1" max="99" value="<?php echo $row_edit['TM_Mastro'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Cod. Sottomastro (0 = Mastro):</label>
                            <input type="number" name="tm_smastro" min="0" max="99" value="<?php echo $row_edit['TM_SMastro'] ?? '0'; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descrizione:</label>
                        <input type="text" name="tm_descrizione" maxlength="60" value="<?php echo htmlspecialchars($row_edit['TM_Descrizione'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Note aggiuntive:</label>
                        <textarea name="tm_note" maxlength="500" rows="4"><?php echo htmlspecialchars($row_edit['TM_Note'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <button type="submit" name="salva_mag" class="btn btn-inserisci btn-grande">SALVA DATI</button>
                        <?php if($edit_id): ?>
                            <a href="ta_mag.php" class="btn btn-elimina btn-grande">ANNULLA / NUOVO</a>
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

// Inizializzazione Tab
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