<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.1.6 - Zero Inline Styles');

require_once '../includes/config.php';

// --- 1. LOGICA PHP ---
if ($_SESSION['AC_ruolo'] !== 'admin') {
    require_once TPL_PATH . 'header.php';
    echo "<div class='errore-messaggio'>Accesso Negato.</div>";
    require_once TPL_PATH . 'footer.php';
    exit();
}

$messaggio = "";

// Svuotamento log antecedenti a oggi
if (isset($_POST['svuota_logs_ieri'])) {
    $ieri = date('Y-m-d 00:00:00');
    $stmt = $conn->prepare("DELETE FROM TA_Log WHERE LO_data < ?");
    $stmt->bind_param("s", $ieri);
    if ($stmt->execute()) {
        header("Location: logs.php?status=cleared");
        exit();
    }
}

require_once TPL_PATH . 'header.php';

// --- 2. FILTRI E PAGINAZIONE ---
$ricerca = $_GET["ricerca"] ?? "";
$data_filtro = $_GET["data_filtro"] ?? "";
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($ricerca)) {
    $where_clauses[] = "(LO_utente_nome LIKE ? OR LO_pagina LIKE ?)";
    $term = "%$ricerca%";
    $params[] = $term; $params[] = $term;
    $types .= "ss";
}
if (!empty($data_filtro)) {
    $where_clauses[] = "DATE(LO_data) = ?";
    $params[] = $data_filtro;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Conteggio totale
$sql_count = "SELECT COUNT(*) as totale FROM TA_Log WHERE $where_sql";
$stmt_c = $conn->prepare($sql_count);
if ($types) $stmt_c->bind_param($types, ...$params);
$stmt_c->execute();
$total_rows = $stmt_c->get_result()->fetch_assoc()['totale'];
$total_pages = ceil($total_rows / $limit);

// Query dati
$sql_list = "SELECT * FROM TA_Log WHERE $where_sql ORDER BY LO_data DESC LIMIT ? OFFSET ?";
$stmt_list = $conn->prepare($sql_list);
$params_list = array_merge($params, [$limit, $offset]);
$types_list = $types . "ii";
$stmt_list->bind_param($types_list, ...$params_list);
$stmt_list->execute();
$logs = $stmt_list->get_result();

if (isset($_GET['status']) && $_GET['status'] == 'cleared') $messaggio = "<p class='successo'>Log precedenti a oggi eliminati correttamente.</p>";
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Log Attività Sistema</h2>
        <?php echo $messaggio; ?>

        <div class="filtri-container btn-group-flex-filter">
            <form method="GET" action="logs.php" class="btn-group-flex-filter">
                <input type="text" name="ricerca" placeholder="Cerca..." value="<?php echo htmlspecialchars($ricerca); ?>">
                <input type="date" name="data_filtro" value="<?php echo htmlspecialchars($data_filtro); ?>">
                <button type="submit" class="btn btn-stampa">Filtra</button>
                <a href="logs.php" class="btn btn-annulla">Reset</a>
            </form>

            <div class="flex-spacer"></div>

            <form method="POST" onsubmit="return confirm('Eliminare i log fino a ieri?');">
                <button type="submit" name="svuota_logs_ieri" class="btn btn-elimina">Svuota fino a ieri</button>
            </form>
        </div>

        <div class="table-container">
            <table class="selectable-table">
                <thead>
                    <tr>
                        <th>DATA</th>
                        <th>UTENTE</th>
                        <th>PAGINA</th>
                        <th class="txt-center">AZIONE</th>
                        <th>DATI POST</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $logs->fetch_assoc()): 
                        $badge_class = ($log['LO_azione'] === 'POST') ? 'badge-post' : 'badge-get';
                    ?>
                        <tr>
                            <td class="txt-small txt-muted"><?php echo date('d-m-Y H:i', strtotime($log['LO_data'])); ?></td>
                            <td class="font-bold"><?php echo htmlspecialchars($log['LO_utente_nome']); ?></td>
                            <td class="txt-small"><?php echo htmlspecialchars(basename($log['LO_pagina'])); ?></td>
                            <td class="txt-center"><span class="badge <?php echo $badge_class; ?>"><?php echo $log['LO_azione']; ?></span></td>
                            <td class="txt-small"><?php echo htmlspecialchars($log['LO_dati_post']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="logs.php?page=<?php echo $i; ?>&ricerca=<?php echo urlencode($ricerca); ?>&data_filtro=<?php echo $data_filtro; ?>" 
                   class="btn-paginazione <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>
    <div class="right-column"></div>
</main>

<?php require_once TPL_PATH . 'footer.php'; ?>