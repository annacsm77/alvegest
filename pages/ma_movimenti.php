<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'M.1.3 (Sincro Inversa Magazzino -> Attività)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php';

$messaggio = "";

// --- LOGICA CRUD CON SINCRONIZZAZIONE INVERSA ---

// 1. ELIMINAZIONE
if (isset($_GET['elimina'])) {
    $id_del = (int)$_GET['elimina'];

    // Recuperiamo la descrizione per vedere se c'è un'attività collegata
    $res_check = $conn->query("SELECT MV_Descrizione FROM MA_MOVI WHERE MV_ID = $id_del");
    if ($row = $res_check->fetch_assoc()) {
        $desc = $row['MV_Descrizione'];
        // Cerchiamo il pattern (IA_ID: numero)
        if (preg_match('/\(IA_ID:\s*(\d+)\)/', $desc, $matches)) {
            $ia_id = (int)$matches[1];
            // Eliminiamo l'attività collegata
            $conn->query("DELETE FROM AT_INSATT WHERE IA_ID = $ia_id");
        }
    }

    $stmt = $conn->prepare("DELETE FROM MA_MOVI WHERE MV_ID = ?");
    $stmt->bind_param("i", $id_del);
    if ($stmt->execute()) {
        header("Location: ma_movimenti.php?status=del_success&tab=selezione");
        exit();
    }
}

// 2. SALVATAGGIO (INSERT/UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salva_movimento'])) {
    $id = $_POST['id'] ?? null;
    $data = $_POST["mv_data"] ?? date('Y-m-d');
    $descrizione = trim($_POST["mv_descrizione"] ?? '');
    $mag_id = (int)$_POST["mv_mag_id"];
    $carico = (float)$_POST["mv_carico"];
    $scarico = (float)$_POST["mv_scarico"];

    if ($id) {
        // Se è un UPDATE, verifichiamo se dobbiamo aggiornare anche l'attività
        $res_check = $conn->query("SELECT MV_Descrizione FROM MA_MOVI WHERE MV_ID = " . (int)$id);
        if ($row = $res_check->fetch_assoc()) {
            if (preg_match('/\(IA_ID:\s*(\d+)\)/', $row['MV_Descrizione'], $matches)) {
                $ia_id = (int)$matches[1];
                // Aggiorniamo data e note dell'attività collegata
                $stmt_att = $conn->prepare("UPDATE AT_INSATT SET IA_DATA = ?, IA_NOTE = ? WHERE IA_ID = ?");
                $stmt_att->bind_param("ssi", $data, $descrizione, $ia_id);
                $stmt_att->execute();
            }
        }

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

// Messaggi di feedback
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') $messaggio = "<p class='successo'>Movimento e attività collegata aggiornati!</p>";
    if ($_GET['status'] == 'del_success') $messaggio = "<p class='successo'>Movimento e attività eliminati correttamente.</p>";
}

$filtro_sm = $_GET['sm_id'] ?? null;
$edit_id = $_GET['edit'] ?? null;
$row_edit = null;
if ($edit_id) {
    $res = $conn->query("SELECT * FROM MA_MOVI WHERE MV_ID = " . (int)$edit_id);
    $row_edit = $res->fetch_assoc();
}
$active_tab = $_GET['tab'] ?? 'selezione';
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2>Movimenti di Magazzino</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>" id="tab-link-selezione" onclick="openTab(event, 'selezione')">STORICO MOVIMENTI</li>
                <li class="tab-link <?php echo ($active_tab == 'scheda') ? 'active' : ''; ?>" id="tab-link-scheda" onclick="openTab(event, 'scheda')">DETTAGLIO MOVIMENTO</li>
            </ul>

            <div id="selezione" class="tab-content <?php echo ($active_tab == 'selezione') ? 'active' : ''; ?>">
                <div class="filtri-container">
                    <div class="filtro-form">
                        <div style="flex: 2;">
                            <label>Prodotto:</label>
                            <select id="filter_sm" onchange="filtraPerSM(this.value)" class="campo-ricerca">
                                <option value="">-- Seleziona Prodotto --</option>
                                <?php
                                $sm_list = $conn->query("SELECT ID, TM_Mastro, TM_SMastro, TM_Descrizione FROM TA_MAG WHERE TM_SMastro > 0 ORDER BY TM_Mastro, TM_SMastro");
                                while($fsm = $sm_list->fetch_assoc()) {
                                    $selected = ($filtro_sm == $fsm['ID']) ? 'selected' : '';
                                    echo "<option value='{$fsm['ID']}' $selected>[{$fsm['TM_Mastro']}.{$fsm['TM_SMastro']}] {$fsm['TM_Descrizione']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div style="flex: 1; display: flex; align-items: flex-end;">
                            <button class="btn" style="background-color: #f39c12; width: 100%;" onclick="if(confirm('Elaborare scarichi automatici?')) window.location.href='../includes/elabora_magazzino.php'">ELABORA SCARICHI</button>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="selectable-table table-fixed-layout">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Data</th>
                                <th class="col-auto">Descrizione / Attività Collegata</th>
                                <th style="width: 80px; text-align: right;">Carico</th>
                                <th style="width: 80px; text-align: right;">Scarico</th>
                                <th style="width: 100px; text-align: right; background-color: #f9f9ff;">Giacenza</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $giacenza_progressiva = 0;
                            $sql_lista = "SELECT V.*, M.TM_Descrizione FROM MA_MOVI V LEFT JOIN TA_MAG M ON V.MV_MAG_ID = M.ID ";
                            if ($filtro_sm) $sql_lista .= " WHERE V.MV_MAG_ID = " . (int)$filtro_sm;
                            $sql_lista .= " ORDER BY MV_Data ASC, MV_ID ASC";
                            
                            $result_movi = $conn->query($sql_lista);
                            $righe = [];
                            while ($r = $result_movi->fetch_assoc()) {
                                $giacenza_progressiva += ($r['MV_Carico'] - $r['MV_Scarico']);
                                $r['Giacenza_Calc'] = $giacenza_progressiva;
                                $righe[] = $r;
                            }

                            foreach (array_reverse($righe) as $r):
                                $sel = ($edit_id == $r['MV_ID']) ? 'selected-row' : '';
                            ?>
                                <tr class="<?php echo $sel; ?>" onclick="window.location.href='ma_movimenti.php?edit=<?php echo $r['MV_ID']; ?>&tab=scheda&sm_id=<?php echo $filtro_sm; ?>'">
                                    <td><?php echo date('d/m/Y', strtotime($r['MV_Data'])); ?></td>
                                    <td class="col-auto">
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
                <button class="btn btn-inserisci" style="margin-top: 15px; width: 250px;" onclick="window.location.href='ma_movimenti.php?tab=scheda'">+ NUOVO MOVIMENTO MANUALE</button>
            </div>

            <div id="scheda" class="tab-content <?php echo ($active_tab == 'scheda') ? 'active' : ''; ?>">
                <div class="form-container" style="border-color: #008CBA;">
                    <form action="ma_movimenti.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $row_edit['MV_ID'] ?? ''; ?>">
                        
                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label>Data:</label>
                                <input type="date" name="mv_data" value="<?php echo $row_edit['MV_Data'] ?? date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Prodotto:</label>
                                <select name="mv_mag_id" required>
                                    <option value="">-- Seleziona --</option>
                                    <?php
                                    $sm_res = $conn->query("SELECT ID, TM_Mastro, TM_SMastro, TM_Descrizione FROM TA_MAG WHERE TM_SMastro > 0 ORDER BY TM_Mastro, TM_SMastro");
                                    while($sm = $sm_res->fetch_assoc()) {
                                        $s = ($row_edit['MV_MAG_ID'] == $sm['ID']) ? 'selected' : '';
                                        echo "<option value='{$sm['ID']}' $s>[{$sm['TM_Mastro']}.{$sm['TM_SMastro']}] {$sm['TM_Descrizione']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descrizione / Causale (Se collegata ad attività, non modificare il codice IA_ID):</label>
                            <input type="text" name="mv_descrizione" value="<?php echo htmlspecialchars($row_edit['MV_Descrizione'] ?? ''); ?>" required>
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label style="color: green;">Carico:</label>
                                <input type="number" name="mv_carico" step="0.01" value="<?php echo $row_edit['MV_Carico'] ?? 0; ?>">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label style="color: red;">Scarico:</label>
                                <input type="number" name="mv_scarico" step="0.01" value="<?php echo $row_edit['MV_Scarico'] ?? 0; ?>">
                            </div>
                        </div>

                        <div class="btn-group-form" style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" name="salva_movimento" class="btn btn-salva" style="flex:2;">SALVA</button>
                            <?php if($edit_id): ?>
                                <a href="ma_movimenti.php?tab=scheda" class="btn btn-annulla" style="flex:1;">NUOVO</a>
                                <a href="ma_movimenti.php?elimina=<?php echo $edit_id; ?>" class="btn btn-elimina" style="flex:1;" onclick="return confirm('L\'eliminazione rimuoverà anche l\'attività apistica collegata. Procedere?')">ELIMINA</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function filtraPerSM(smId) {
    window.location.href = 'ma_movimenti.php?tab=selezione' + (smId ? '&sm_id=' + smId : '');
}

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