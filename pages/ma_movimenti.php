<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

$versione = "M.1.1"; 
$messaggio = "";

// --- LOGICA CRUD (Invariata) ---
if (isset($_GET['elimina'])) {
    $id_del = (int)$_GET['elimina'];
    $stmt = $conn->prepare("DELETE FROM MA_MOVI WHERE MV_ID = ?");
    $stmt->bind_param("i", $id_del);
    if ($stmt->execute()) {
        header("Location: ma_movimenti.php?status=del_success&tab=selezione");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salva_movimento'])) {
    $id = $_POST['id'] ?? null;
    $data = $_POST["mv_data"] ?? date('Y-m-d');
    $descrizione = trim($_POST["mv_descrizione"] ?? '');
    $mag_id = (int)$_POST["mv_mag_id"];
    $carico = (float)$_POST["mv_carico"];
    $scarico = (float)$_POST["mv_scarico"];

    if ($id) {
        $sql = "UPDATE MA_MOVI SET MV_Data=?, MV_Descrizione=?, MV_MAG_ID=?, MV_Carico=?, MV_Scarico=? WHERE MV_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiddi", $data, $descrizione, $mag_id, $carico, $scarico, $id);
    } else {
        $sql = "INSERT INTO MA_MOVI (MV_Data, MV_Descrizione, MV_MAG_ID, MV_Carico, MV_Scarico) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssidd", $data, $descrizione, $mag_id, $carico, $scarico);
    }

    if ($stmt->execute()) {
        $redirect_id = $id ?: $conn->insert_id;
        header("Location: ma_movimenti.php?status=success&tab=scheda&edit=" . $redirect_id);
        exit();
    }
}

// Feedback
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') $messaggio = "<p class='successo'>✅ Movimento registrato!</p>";
    if ($_GET['status'] == 'del_success') $messaggio = "<p class='successo'>🗑️ Movimento eliminato.</p>";
    if ($_GET['status'] == 'elaborazione_ok') {
        $count = $_GET['elaborati'] ?? 0;
        $messaggio = "<p class='successo'>🔄 Elaborati $count nuovi scarichi.</p>";
    }
}

$filtro_sm = $_GET['sm_id'] ?? null;
$edit_id = $_GET['edit'] ?? null;
$row_edit = null;
if ($edit_id) {
    $res = $conn->query("SELECT * FROM MA_MOVI WHERE MV_ID = $edit_id");
    $row_edit = $res->fetch_assoc();
}
$active_tab = $_GET['tab'] ?? 'selezione';
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column" style="padding: 5px;">
        <h2>Movimenti di Magazzino <small>(V.<?php echo $versione; ?>)</small></h2>
        <?php echo $messaggio; ?>

        <div class="tab-nav">
            <button class="tab-button" id="tab-selezione" onclick="openTab(event, 'selezione')">STORICO MOVIMENTI</button>
            <button class="tab-button" id="tab-scheda" onclick="openTab(event, 'scheda')">NUOVO / DETTAGLIO</button>
        </div>

        <div id="selezione" class="tab-content-item">
            <div style="background: #f4f4f4; padding: 10px; border: 1px solid #ccc; margin-top: 10px; border-radius: 5px;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                    <div>
                        <label><strong>Filtra Prodotto:</strong></label>
                        <select id="filter_sm" onchange="filtraPerSM(this.value)" style="padding: 5px;">
                            <option value="">-- Seleziona per vedere la Giacenza --</option>
                            <?php
                            $sm_list = $conn->query("SELECT ID, TM_Mastro, TM_SMastro, TM_Descrizione FROM TA_MAG WHERE TM_SMastro > 0 ORDER BY TM_Mastro, TM_SMastro");
                            while($fsm = $sm_list->fetch_assoc()) {
                                $selected = ($filtro_sm == $fsm['ID']) ? 'selected' : '';
                                echo "<option value='{$fsm['ID']}' $selected>[{$fsm['TM_Mastro']}.{$fsm['TM_SMastro']}] {$fsm['TM_Descrizione']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button class="btn" style="background-color: #f39c12; color: white;" onclick="if(confirm('Elaborare scarichi?')) window.location.href='../includes/elabora_magazzino.php'">🔄 ELABORA SCARICHI</button>
                </div>
            </div>

            <div class="table-container" style="max-height: 55vh; overflow-y: auto; margin-top: 10px; border: 1px solid #ddd;">
                <table class="selectable-table">
                    <thead>
                        <tr>
                            <th style="width: 90px;">Data</th>
                            <th>Descrizione</th>
                            <th style="width: 70px; text-align: right;">Carico</th>
                            <th style="width: 70px; text-align: right;">Scarico</th>
                            <th style="width: 90px; text-align: right; background-color: #eef;">Giacenza</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Calcoliamo la giacenza iniziale se c'è un filtro (utile per paginazione futura o ordine)
                        $giacenza_progressiva = 0;

                        // Per calcolare il saldo progressivo corretto, dobbiamo ordinare i dati dal più vecchio al più recente
                        // e poi mostrarli (o calcolarli) in base alla logica della tabella.
                        // Usiamo una query che recupera i movimenti in ordine ASC per calcolare il saldo.
                        $sql_lista = "SELECT V.*, M.TM_Descrizione FROM MA_MOVI V LEFT JOIN TA_MAG M ON V.MV_MAG_ID = M.ID ";
                        if ($filtro_sm) $sql_lista .= " WHERE V.MV_MAG_ID = " . (int)$filtro_sm;
                        $sql_lista .= " ORDER BY MV_Data ASC, MV_ID ASC"; // Ordine cronologico per il calcolo
                        
                        $result_movi = $conn->query($sql_lista);
                        $righe = [];
                        
                        while ($r = $result_movi->fetch_assoc()) {
                            $giacenza_progressiva += ($r['MV_Carico'] - $r['MV_Scarico']);
                            $r['Giacenza_Calc'] = $giacenza_progressiva;
                            $righe[] = $r;
                        }

                        // Invertiamo l'array per mostrare i più recenti in alto nella tabella
                        $righe_visualizza = array_reverse($righe);

                        foreach ($righe_visualizza as $r):
                            $sel = ($edit_id == $r['MV_ID']) ? 'selected-row' : '';
                        ?>
                            <tr class="<?php echo $sel; ?>" onclick="window.location.href='ma_movimenti.php?edit=<?php echo $r['MV_ID']; ?>&tab=scheda&sm_id=<?php echo $filtro_sm; ?>'">
                                <td><?php echo date('d/m/Y', strtotime($r['MV_Data'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($r['TM_Descrizione']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($r['MV_Descrizione']); ?></small>
                                </td>
                                <td style="text-align: right; color: green;"><?php echo $r['MV_Carico'] > 0 ? number_format($r['MV_Carico'], 2, ',', '.') : ''; ?></td>
                                <td style="text-align: right; color: red;"><?php echo $r['MV_Scarico'] > 0 ? number_format($r['MV_Scarico'], 2, ',', '.') : ''; ?></td>
                                <td style="text-align: right; font-weight: bold; background-color: #f9f9ff;">
                                    <?php echo number_format($r['Giacenza_Calc'], 2, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-inserisci btn-grande" style="margin-top: 15px; width: 100%;" onclick="window.location.href='ma_movimenti.php?tab=scheda'">+ NUOVO MOVIMENTO MANUALE</button>
        </div>

        <div id="scheda" class="tab-content-item">
            <div class="form-container" style="margin-top: 10px; border: 2px solid #008CBA; padding: 15px;">
                <form action="ma_movimenti.php" method="post">
                    <input type="hidden" name="id" value="<?php echo $row_edit['MV_ID'] ?? ''; ?>">
                    <div class="form-group">
                        <label>Data Movimento:</label>
                        <input type="date" name="mv_data" value="<?php echo $row_edit['MV_Data'] ?? date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Prodotto (Sottomastro):</label>
                        <select name="mv_mag_id" required>
                            <option value="">-- Seleziona Categoria --</option>
                            <?php
                            $sm_res = $conn->query("SELECT ID, TM_Mastro, TM_SMastro, TM_Descrizione FROM TA_MAG WHERE TM_SMastro > 0 ORDER BY TM_Mastro, TM_SMastro");
                            while($sm = $sm_res->fetch_assoc()) {
                                $s = ($row_edit['MV_MAG_ID'] == $sm['ID']) ? 'selected' : '';
                                echo "<option value='{$sm['ID']}' $s>[{$sm['TM_Mastro']}.{$sm['TM_SMastro']}] {$sm['TM_Descrizione']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Descrizione / Causale:</label>
                        <input type="text" name="mv_descrizione" value="<?php echo htmlspecialchars($row_edit['MV_Descrizione'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group-flex" style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label style="color: green;">Quantità CARICO:</label>
                            <input type="number" name="mv_carico" step="0.01" value="<?php echo $row_edit['MV_Carico'] ?? 0; ?>">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label style="color: red;">Quantità SCARICO:</label>
                            <input type="number" name="mv_scarico" step="0.01" value="<?php echo $row_edit['MV_Scarico'] ?? 0; ?>">
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" name="salva_movimento" class="btn btn-inserisci btn-grande" style="flex:2;">SALVA MOVIMENTO</button>
                        <?php if($edit_id): ?>
                            <a href="ma_movimenti.php?tab=scheda" class="btn btn-grande" style="background: #666; color:white; flex:1; text-align:center; text-decoration:none; line-height:40px; border-radius: 5px;">NUOVO</a>
                            <a href="ma_movimenti.php?elimina=<?php echo $edit_id; ?>" class="btn btn-elimina btn-grande" style="flex:1;" onclick="return confirm('Eliminare definitivamente?')">ELIMINA</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="right-column"></div>
</main>

<script>
function filtraPerSM(smId) {
    window.location.href = 'ma_movimenti.php?tab=selezione' + (smId ? '&sm_id=' + smId : '');
}
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