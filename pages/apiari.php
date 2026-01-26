<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.1.0.2 - Classi Abilitate per CSS');

require_once '../includes/config.php'; 

// --- 1. LOGICA POST (Invariata) ---
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
            $stmt->execute();
            header("Location: apiari.php?status=insert_success&tab=tab-lista");
            exit();
        }
    } elseif (isset($_POST["modifica"])) {
        $id = $_POST["id"];
        $sql = "UPDATE TA_Apiari SET AI_CODICE = ?, AI_LUOGO = ?, AI_NOTE = ?, AI_LINK = ? WHERE AI_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssi", $codice, $luogo, $note, $link, $id); 
            $stmt->execute();
            header("Location: apiari.php?status=update_success&tab=tab-lista");
            exit();
        }
    } elseif (isset($_POST["elimina"])) {
        $id = $_POST["id"];
        $sql = "DELETE FROM TA_Apiari WHERE AI_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            header("Location: apiari.php?status=delete_success&tab=tab-lista");
            exit();
        }
    }
}

require_once TPL_PATH . 'header.php'; 

// --- 2. RECUPERO DATI E MESSAGGI ---
$messaggio = "";
$id_modifica = $_GET["modifica"] ?? null;
$codice_modifica = ""; $luogo_modifica = ""; $note_modifica = ""; $link_modifica = ""; 

if (isset($_GET["status"])) {
    $status = $_GET["status"];
    if ($status == "insert_success") $messaggio = "<p class='successo'>Apiario inserito!</p>";
    if ($status == "update_success") $messaggio = "<p class='successo'>Salvataggio eseguito!</p>";
    if ($status == "delete_success") $messaggio = "<p class='successo txt-danger font-bold'>Apiario rimosso!</p>";
}

if ($id_modifica) {
    $stmt = $conn->prepare("SELECT * FROM TA_Apiari WHERE AI_ID = ?");
    $stmt->bind_param("i", $id_modifica);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $codice_modifica = $row["AI_CODICE"];
        $luogo_modifica = $row["AI_LUOGO"];
        $note_modifica = $row["AI_NOTE"];
        $link_modifica = $row["AI_LINK"]; 
    }
}
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Gestione Apiari</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$id_modifica ? 'active' : ''; ?>" id="link-tab-lista" onclick="openTab(event, 'tab-lista')">ELENCO</li>
                <li class="tab-link <?php echo $id_modifica ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')">MODIFICA</li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$id_modifica ? 'active' : ''; ?>">
                <div class="apiari-layout">
                    <div class="apiari-col-main">
                        <div class="table-container">
                            <table class="selectable-table">
                                <thead>
                                    <tr>
                                        <th class="txt-center">ID</th>
                                        <th>CODICE</th>
                                        <th>LUOGO</th>
                                        <th class="txt-center">MAPPA</th>
                                        <th class="txt-center">AZIONE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $result = $conn->query("SELECT AI_ID, AI_CODICE, AI_LUOGO, AI_LINK FROM TA_Apiari");
                                    while ($row = $result->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td class="txt-center txt-small txt-muted"><?php echo $row["AI_ID"]; ?></td>
                                        <td class="font-bold"><?php echo htmlspecialchars($row["AI_CODICE"]); ?></td> 
                                        <td><?php echo htmlspecialchars($row["AI_LUOGO"]); ?></td>
                                        <td class="txt-center">
                                            <?php if (!empty($row["AI_LINK"])): ?>
                                                <button class="btn btn-stampa" onclick="mostraMappa('<?php echo htmlspecialchars($row['AI_LINK']); ?>', '<?php echo htmlspecialchars($row['AI_LUOGO']); ?>')">Mappa</button>
                                            <?php endif; ?>
                                        </td>
                                        <td class="txt-center">
                                            <a href="apiari.php?modifica=<?php echo $row['AI_ID']; ?>&tab=tab-form" class="btn-tabella-modifica">Modifica</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="apiari-col-map">
                        <div id="map-display-container" class="map-box hidden">
                            <h3 id="map-title" class="titolo-mappa">Mappa</h3>
                            <div id="map-iframe-wrapper" class="map-wrapper"></div>
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
                        <div class="form-group"><label>Codice:</label><input type="text" name="codice" value="<?php echo htmlspecialchars($codice_modifica); ?>" required></div>
                        <div class="form-group"><label>Luogo:</label><input type="text" name="luogo" value="<?php echo htmlspecialchars($luogo_modifica); ?>" required></div>
                        <div class="form-group"><label>Link Maps:</label><input type="url" name="link" value="<?php echo htmlspecialchars($link_modifica); ?>"></div>
                        <div class="form-group"><label>Note:</label><textarea name="note" rows="3"><?php echo htmlspecialchars($note_modifica); ?></textarea></div>
                        <div class="btn-group-flex">
                            <button type="submit" name="<?php echo $id_modifica ? 'modifica' : 'inserisci'; ?>" class="btn btn-salva btn-flex-2">Salva</button>
                            <?php if ($id_modifica): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina btn-flex-1" onclick="return confirm('Eliminare?');">Elimina</button>
                                <a href="apiari.php" class="btn btn-annulla btn-flex-1">Annulla</a>
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
    if (evt) $(evt.currentTarget).addClass('active');
}
function mostraMappa(link, luogo) {
    if (link) {
        $('#map-title').text(`Mappa: ${luogo}`);
        $('#map-iframe-wrapper').html(`<iframe src="${link}" class="map-iframe" allowfullscreen="" loading="lazy"></iframe>`);
        $('#map-display-container').removeClass('hidden').show();
    }
}
</script>
<?php require_once TPL_PATH . 'footer.php'; ?>