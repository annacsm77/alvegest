<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'SP.0.5 (Layout Unificato)');

require_once '../includes/config.php';
require_once TPL_PATH . 'header.php'; 

$messaggio = "";

// --- 1. LOGICA CRUD: ELIMINAZIONE ---
if (isset($_GET["elimina"])) {
    $elimina_id = (int)$_GET["elimina"];
    $stmt = $conn->prepare("DELETE FROM AI_SPOS WHERE SP_ID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $elimina_id);
        if ($stmt->execute()) {
            header("Location: storico_spostamenti.php?status=del_success");
            exit();
        }
    }
}

// Feedback messaggi
if (isset($_GET["status"]) && $_GET["status"] == "del_success") {
    $messaggio = "<p class='successo'>Spostamento eliminato correttamente dallo storico.</p>";
}
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2>Storico Spostamenti Apiari</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link active">Elenco Spostamenti</li>
            </ul>

            <div class="tab-content active">
                <div class="table-container">
                    <?php
                    // Query corretta con JOIN per i nomi dei luoghi
                    $sql = "SELECT S.SP_ID, S.SP_DATA, S.SP_ARNIE, S.SP_TOT, 
                                   DA.AI_LUOGO AS LuogoDa, A.AI_LUOGO AS LuogoA
                            FROM AI_SPOS S
                            JOIN TA_Apiari DA ON S.SP_DA = DA.AI_ID
                            JOIN TA_Apiari A ON S.SP_A = A.AI_ID
                            ORDER BY S.SP_DATA DESC, S.SP_ID DESC";

                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0): ?>
                    <table class="selectable-table table-fixed-layout">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 100px;">Data</th>
                                <th class="col-auto">Da (Partenza)</th>
                                <th class="col-auto">A (Arrivo)</th>
                                <th style="width: 60px; text-align: center;">Tot.</th>
                                <th style="width: 200px;">Arnie (Codici)</th>
                                <th style="width: 100px; text-align: center;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $row["SP_ID"]; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row["SP_DATA"])); ?></td>
                                <td><?php echo htmlspecialchars($row["LuogoDa"]); ?></td>
                                <td><?php echo htmlspecialchars($row["LuogoA"]); ?></td>
                                <td style="text-align: center;"><strong><?php echo $row["SP_TOT"]; ?></strong></td>
                                <td style="font-size: 0.85em; color: #666;">
                                    <?php 
                                    // Mostra i codici arnie con un limite di caratteri per non rompere la riga
                                    $arnie = htmlspecialchars($row["SP_ARNIE"]);
                                    echo (strlen($arnie) > 40) ? substr($arnie, 0, 40) . "..." : $arnie; 
                                    ?>
                                </td>
                                <td class="action-cell">
                                    <a href="storico_spostamenti.php?elimina=<?php echo $row['SP_ID']; ?>" 
                                       class="btn btn-elimina" 
                                       onclick="return confirm('Eliminare definitivamente questo spostamento dallo storico?')">
                                        Elimina
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 20px;">Nessuno spostamento registrato nel database.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="versione-info">Versione: <?php echo FILE_VERSION; ?></div>
    </div>

    <div class="right-column"></div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php require_once TPL_PATH . 'footer.php'; ?>