<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

$versione = "A.1.5"; 
$messaggio = "";

// --- LOGICA CRUD ---

// 1. ELIMINAZIONE
if (isset($_GET['elimina'])) {
    $id_del = (int)$_GET['elimina'];
    $stmt = $conn->prepare("DELETE FROM TA_Attivita WHERE AT_ID = ?");
    $stmt->bind_param("i", $id_del);
    if ($stmt->execute()) {
        header("Location: attivita.php?status=del_success&tab=selezione");
        exit();
    }
}

// 2. SALVATAGGIO (Inserimento o Modifica)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salva_attivita'])) {
    $id = $_POST['id'] ?? null;
    $descrizione = trim($_POST["descrizione"] ?? '');
    $note = trim($_POST["note"] ?? '');
    $nr_ripetizioni = (int)$_POST["nr_ripetizioni"];
    $gg_validita = (int)$_POST["gg_validita"];
    $is_trattamento = isset($_POST["is_trattamento"]) ? 1 : 0;
    $at_mag_id = !empty($_POST["at_mag_id"]) ? (int)$_POST["at_mag_id"] : null;
    $at_scarico_fisso = isset($_POST["at_scarico_fisso"]) ? 1 : 0;

    if ($id) {
        $sql = "UPDATE TA_Attivita SET AT_DESCR=?, AT_NOTE=?, AT_NR=?, AT_GG=?, AT_TRAT=?, AT_MAG_ID=?, AT_SCARICO_FISSO=? WHERE AT_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiiiii", $descrizione, $note, $nr_ripetizioni, $gg_validita, $is_trattamento, $at_mag_id, $at_scarico_fisso, $id);
    } else {
        $sql = "INSERT INTO TA_Attivita (AT_DESCR, AT_NOTE, AT_NR, AT_GG, AT_TRAT, AT_MAG_ID, AT_SCARICO_FISSO) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiiii", $descrizione, $note, $nr_ripetizioni, $gg_validita, $is_trattamento, $at_mag_id, $at_scarico_fisso);
    }

    if ($stmt->execute()) {
        $redirect_id = $id ?: $conn->insert_id;
        header("Location: attivita.php?status=success&tab=scheda&edit=" . $redirect_id);
        exit();
    } else {
        $messaggio = "<p class='errore'>Errore DB: " . $conn->error . "</p>";
    }
}

// Feedback
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') $messaggio = "<p class='successo'>✅ Dati salvati correttamente!</p>";
    if ($_GET['status'] == 'del_success') $messaggio = "<p class='successo'>🗑️ Attività eliminata con successo.</p>";
}

// Recupero dati per Modifica
$edit_id = $_GET['edit'] ?? null;
$row_edit = null;
if ($edit_id) {
    $res = $conn->query("SELECT * FROM TA_Attivita WHERE AT_ID = $edit_id");
    $row_edit = $res->fetch_assoc();
}

$active_tab = $_GET['tab'] ?? 'selezione';
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column" style="padding: 5px;">
        <h2>Gestione Attività <small>(V.<?php echo $versione; ?>)</small></h2>
        <?php echo $messaggio; ?>

        <div class="tab-nav">
            <button class="tab-button" id="tab-selezione" onclick="openTab(event, 'selezione')">ELENCO</button>
            <button class="tab-button" id="tab-scheda" onclick="openTab(event, 'scheda')">DETTAGLIO SCHEDA</button>
        </div>

        <div id="selezione" class="tab-content-item">
            <div class="table-container" style="max-height: 60vh; overflow-y: auto; margin-top: 10px; border: 1px solid #ddd;">
                <table class="selectable-table">
                    <thead>
                        <tr>
                            <th>Descrizione Attività</th>
                            <th style="width: 60px; text-align: center;">Tratt.</th>
                            <th>Categoria Magazzino</th>
                            <th style="width: 80px; text-align: center;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $lista = $conn->query("SELECT T.*, M.TM_Descrizione FROM TA_Attivita T LEFT JOIN TA_MAG M ON T.AT_MAG_ID = M.ID ORDER BY AT_DESCR ASC");
                        while ($r = $lista->fetch_assoc()):
                            $sel = ($edit_id == $r['AT_ID']) ? 'selected-row' : '';
                        ?>
                            <tr class="<?php echo $sel; ?>" onclick="window.location.href='attivita.php?edit=<?php echo $r['AT_ID']; ?>&tab=scheda'">
                                <td><?php echo htmlspecialchars($r['AT_DESCR']); ?></td>
                                <td style="text-align: center;"><?php echo ($r['AT_TRAT'] == 1) ? '✅' : ''; ?></td>
                                <td><small><?php echo htmlspecialchars($r['TM_Descrizione'] ?? ''); ?></small></td>
                                <td style="text-align: center;">
                                    <a href="attivita.php?elimina=<?php echo $r['AT_ID']; ?>" class="btn btn-elimina" style="padding: 2px 5px; font-size: 10px;" onclick="event.stopPropagation(); return confirm('Eliminare questa attività?')">Elimina</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-inserisci btn-grande" style="margin-top: 15px; width: 100%;" onclick="window.location.href='attivita.php?tab=scheda'">+ AGGIUNGI NUOVA ATTIVITÀ</button>
        </div>

        <div id="scheda" class="tab-content-item">
            <div class="form-container" style="margin-top: 10px; border: 2px solid #008CBA;">
                <form action="attivita.php" method="post">
                    <input type="hidden" name="id" value="<?php echo $row_edit['AT_ID'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label>Descrizione attività:</label>
                        <input type="text" name="descrizione" value="<?php echo htmlspecialchars($row_edit['AT_DESCR'] ?? ''); ?>" required>
                    </div>

                    <div style="background-color: #f0fff0; padding: 15px; border: 1px solid #90ee90; margin-bottom: 15px; border-radius: 5px;">
                        <h4 style="margin: 0 0 10px 0; color: #2e7d32;">Integrazione Magazzino</h4>
                        <label>Sottomastro (Categoria prodotto):</label>
                        <select name="at_mag_id">
                            <option value="">-- Nessun collegamento --</option>
                            <?php
                            $sm_res = $conn->query("SELECT ID, TM_Mastro, TM_SMastro, TM_Descrizione FROM TA_MAG WHERE TM_SMastro > 0 ORDER BY TM_Mastro, TM_SMastro");
                            while($sm = $sm_res->fetch_assoc()) {
                                $s = ($row_edit['AT_MAG_ID'] == $sm['ID']) ? 'selected' : '';
                                echo "<option value='{$sm['ID']}' $s>[{$sm['TM_Mastro']}.{$sm['TM_SMastro']}] {$sm['TM_Descrizione']}</option>";
                            }
                            ?>
                        </select>
                        <div style="margin-top: 10px;">
                            <input type="checkbox" name="at_scarico_fisso" id="chk_f" value="1" <?php echo ($row_edit['AT_SCARICO_FISSO'] ?? 0) == 1 ? 'checked' : ''; ?> style="width: auto;">
                            <label for="chk_f" style="display:inline; font-weight: normal;">Scarico fisso (sempre 1 unità)</label>
                        </div>
                    </div>

                    <div class="form-group-flex">
                        <div class="form-group">
                            <label>N. Ripetizioni:</label>
                            <input type="number" name="nr_ripetizioni" value="<?php echo $row_edit['AT_NR'] ?? 0; ?>">
                        </div>
                        <div class="form-group">
                            <label>Validità (giorni):</label>
                            <input type="number" name="gg_validita" value="<?php echo $row_edit['AT_GG'] ?? 0; ?>">
                        </div>
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_trattamento" id="chk_t" value="1" <?php echo ($row_edit['AT_TRAT'] ?? 0) == 1 ? 'checked' : ''; ?> style="width: auto;">
                        <label for="chk_t" style="display:inline; margin-bottom: 0;">È un trattamento sanitario</label>
                    </div>

                    <div class="form-group">
                        <label>Note aggiuntive:</label>
                        <textarea name="note" rows="3"><?php echo htmlspecialchars($row_edit['AT_NOTE'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" name="salva_attivita" class="btn btn-inserisci btn-grande" style="flex:2;">SALVA DATI</button>
                        <?php if($edit_id): ?>
                            <a href="attivita.php?tab=scheda" class="btn btn-grande" style="background: #666; color:white; flex:1; text-align:center; text-decoration:none; line-height:40px; border-radius: 5px;">AGGIUNGI NUOVO</a>
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
    document.querySelectorAll('.tab-content-item').forEach(i => i.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    if(evt) evt.currentTarget.classList.add('active');
}
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById('tab-<?php echo $active_tab; ?>');
    if(btn) btn.click();
});
</script>

<style>
    .selected-row { background-color: #cceeff !important; font-weight: bold; }
    .tab-nav { display: flex; border-bottom: 2px solid #ccc; margin-bottom: 5px; }
    .tab-button { flex: 1; padding: 10px; cursor: pointer; border: none; background: #eee; font-weight: bold; }
    .tab-button.active { background: #008CBA; color: white; }
    .tab-content-item { display: none; }
    .tab-content-item.active { display: block; }
    .selectable-table tr:hover { background-color: #f5f5f5; cursor: pointer; }
</style>

<?php require_once '../includes/footer.php'; ?>