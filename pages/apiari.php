<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.0.2');

require_once '../includes/config.php'; 
require_once TPL_PATH . 'header.php'; 

// 1. INIZIALIZZAZIONE VARIABILI
$messaggio = "";
$id_modifica = $_GET["modifica"] ?? null;
$codice_modifica = "";
$luogo_modifica = "";
$note_modifica = "";
$link_modifica = ""; 

// 2. GESTIONE LOGICA POST (Inserimento, Modifica, Eliminazione)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codice = $_POST["codice"] ?? ""; 
    $luogo = $_POST["luogo"] ?? "";
    $note = $_POST["note"] ?? "";
    $link = $_POST["link"] ?? ""; 

    if (isset($_POST["inserisci"])) {
        $sql = "INSERT INTO TA_Apiari (AI_CODICE, AI_LUOGO, AI_NOTE, AI_LINK) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssss", $codice, $luogo, $note, $link); 
            if ($stmt->execute()) {
                header("Location: apiari.php?status=insert_success");
                exit();
            }
            $stmt->close();
        }
    } elseif (isset($_POST["modifica"])) {
        $id = $_POST["id"];
        $sql = "UPDATE TA_Apiari SET AI_CODICE = ?, AI_LUOGO = ?, AI_NOTE = ?, AI_LINK = ? WHERE AI_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssi", $codice, $luogo, $note, $link, $id); 
            if ($stmt->execute()) {
                header("Location: apiari.php?status=update_success");
                exit();
            }
            $stmt->close();
        }
    } elseif (isset($_POST["elimina"])) {
        $id = $_POST["id"];
        $sql = "DELETE FROM TA_Apiari WHERE AI_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: apiari.php?status=delete_success");
                exit();
            }
            $stmt->close();
        }
    }
}

// 3. MESSAGGI DI STATO E RECUPERO DATI
if (isset($_GET["status"])) {
    $status = $_GET["status"];
    if ($status == "insert_success") $messaggio = "<p class='successo'>Apiario inserito correttamente!</p>";
    if ($status == "update_success") $messaggio = "<p class='successo'>Aggiornamento eseguito!</p>";
    if ($status == "delete_success") $messaggio = "<p class='successo'>Apiario eliminato!</p>";
}

if ($id_modifica) {
    $sql = "SELECT AI_ID, AI_CODICE, AI_LUOGO, AI_NOTE, AI_LINK FROM TA_Apiari WHERE AI_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_modifica);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $codice_modifica = $row["AI_CODICE"];
        $luogo_modifica = $row["AI_LUOGO"];
        $note_modifica = $row["AI_NOTE"];
        $link_modifica = $row["AI_LINK"]; 
    }
    $stmt->close();
}
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2 class="titolo-arnie">Gestione Apiari</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$id_modifica ? 'active' : ''; ?>" id="link-tab-lista" onclick="openTab(event, 'tab-lista')">Elenco e Mappa</li>
                <li class="tab-link <?php echo $id_modifica ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')">
                    <?php echo $id_modifica ? "Dettaglio e Modifica" : "Nuovo Apiario"; ?>
                </li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$id_modifica ? 'active' : ''; ?>">
                <div class="apiari-layout">
                    <div class="apiari-col-main">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th><th>Codice</th><th>Luogo</th><th>Mappa</th><th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT AI_ID, AI_CODICE, AI_LUOGO, AI_LINK FROM TA_Apiari"; 
                                    $result = $conn->query($sql);
                                    while ($row = $result->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td><?php echo $row["AI_ID"]; ?></td>
                                        <td><?php echo htmlspecialchars($row["AI_CODICE"]); ?></td> 
                                        <td><?php echo htmlspecialchars($row["AI_LUOGO"]); ?></td>
                                        <td class="cell-center">
                                            <?php if (!empty($row["AI_LINK"])): ?>
                                                <button class="btn btn-stampa" onclick="mostraMappa('<?php echo htmlspecialchars($row['AI_LINK']); ?>', '<?php echo htmlspecialchars($row['AI_LUOGO']); ?>')">Mappa</button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="apiari.php?modifica=<?php echo $row['AI_ID']; ?>#tab-form" class="btn btn-modifica">Modifica</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="apiari-col-map">
                        <div id="map-display-container">
                            <h3 id="map-title">Anteprima Mappa</h3>
                            <div id="map-iframe-wrapper"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tab-form" class="tab-content <?php echo $id_modifica ? 'active' : ''; ?>">
                <div class="form-container">
                    <form action="apiari.php" method="post">
                        <?php if ($id_modifica): ?>
                            <input type="hidden" name="id" value="<?php echo $id_modifica; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Codice Apiario:</label>
                            <input type="text" name="codice" value="<?php echo htmlspecialchars($codice_modifica); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Luogo:</label>
                            <input type="text" name="luogo" value="<?php echo htmlspecialchars($luogo_modifica); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Link Google Maps (Embed):</label>
                            <input type="url" name="link" value="<?php echo htmlspecialchars($link_modifica); ?>">
                        </div>
                        <div class="form-group">
                            <label>Note:</label>
                            <textarea name="note" rows="4"><?php echo htmlspecialchars($note_modifica); ?></textarea>
                        </div>

                        <div class="btn-group-form" style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" name="<?php echo $id_modifica ? 'modifica' : 'inserisci'; ?>" class="btn btn-salva" style="flex: 2;">
                                <?php echo $id_modifica ? "Salva" : "Inserisci"; ?>
                            </button>

                            <?php if ($id_modifica): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina" style="flex: 1;" onclick="return confermaEliminazione();">
                                    Elimina
                                </button>
                                <a href="apiari.php" class="btn btn-annulla" style="flex: 1;">Annulla</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="versione-info">
            Versione: <?php echo FILE_VERSION; ?>
        </div>
    </div>

    <div class="right-column"></div>
</main>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) { tabcontent[i].style.display = "none"; }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) { tablinks[i].className = tablinks[i].className.replace(" active", ""); }
    document.getElementById(tabName).style.display = "block";
    if (evt) evt.currentTarget.className += " active";
}

window.addEventListener('DOMContentLoaded', () => {
    if(window.location.hash === "#tab-form" || <?php echo $id_modifica ? 'true' : 'false'; ?>) {
        document.getElementById('link-tab-form').click();
    }
});

function confermaEliminazione() {
    return confirm("Sei sicuro di voler eliminare definitivamente questo apiario?");
}

function mostraMappa(link, luogo) {
    const container = document.getElementById('map-iframe-wrapper');
    const display = document.getElementById('map-display-container');
    const title = document.getElementById('map-title');
    if (link) {
        title.innerHTML = `Mappa: ${luogo}`;
        container.innerHTML = `<iframe src="${link}" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>`;
        display.style.display = 'block';
    }
}
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>