<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.1.0.1 - Zero Inline Styles');

require_once '../includes/config.php'; 

// 1. GESTIONE LOGICA POST IN ALTO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"] ?? "";
    $codap = $_POST["codap"] ?? "";

    if (isset($_POST["inserisci"])) {
        $sql = "INSERT INTO TA_Apicoltore (AP_Nome, AP_codap) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $nome, $codap);
            if ($stmt->execute()) {
                header("Location: apicoltore.php?status=insert_success&tab=tab-lista");
                exit();
            }
        }
    } elseif (isset($_POST["modifica"])) {
        $id = $_POST["id"];
        $sql = "UPDATE TA_Apicoltore SET AP_Nome = ?, AP_codap = ? WHERE AP_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssi", $nome, $codap, $id);
            if ($stmt->execute()) {
                header("Location: apicoltore.php?status=update_success&tab=tab-lista");
                exit();
            }
        }
    } elseif (isset($_POST["elimina"])) {
        $id = $_POST["id"];
        $sql = "DELETE FROM TA_Apicoltore WHERE AP_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: apicoltore.php?status=delete_success&tab=tab-lista");
                exit();
            }
        }
    }
}

require_once TPL_PATH . 'header.php'; 

// --- RECUPERO DATI E MESSAGGI ---
$modifica_id = $_GET["modifica"] ?? null;
$nome_modifica = ""; $codap_modifica = "";
$messaggio = "";

if (isset($_GET["status"])) {
    $status = $_GET["status"];
    if ($status == "insert_success") $messaggio = "<p class='successo'>Apicoltore inserito!</p>";
    if ($status == "update_success") $messaggio = "<p class='successo'>Modifiche salvate!</p>";
    if ($status == "delete_success") $messaggio = "<p class='successo txt-danger font-bold'>Apicoltore rimosso!</p>";
}

if ($modifica_id) {
    $sql = "SELECT AP_ID, AP_Nome, AP_codap FROM TA_Apicoltore WHERE AP_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $modifica_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nome_modifica = $row["AP_Nome"];
        $codap_modifica = $row["AP_codap"];
    }
}
?>

<main class="main-content">
    <div class="left-column"></div>
    <div class="center-column">
        <h2 class="titolo-arnie">Gestione Apicoltori</h2>
        <?php echo $messaggio; ?>
        
        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$modifica_id ? 'active' : ''; ?>" id="link-tab-lista" onclick="openTab(event, 'tab-lista')">ELENCO</li>
                <li class="tab-link <?php echo $modifica_id ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')">MODIFICA</li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$modifica_id ? 'active' : ''; ?>">
                <div class="table-container">
                    <table class="selectable-table">
                        <thead>
                            <tr>
                                <th class="txt-center">ID</th>
                                <th>NOME</th>
                                <th>CODICE</th>
                                <th class="txt-center">AZIONI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM TA_Apicoltore";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td class="txt-center txt-small txt-muted"><?php echo $row["AP_ID"]; ?></td>
                                <td class="font-bold"><?php echo htmlspecialchars($row["AP_Nome"]); ?></td>
                                <td><?php echo htmlspecialchars($row["AP_codap"]); ?></td>
                                <td class="txt-center">
                                    <a href="apicoltore.php?modifica=<?php echo $row['AP_ID']; ?>&tab=tab-form" class="btn-tabella-modifica">Modifica</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-form" class="tab-content <?php echo $modifica_id ? 'active' : ''; ?>">
                <div class="form-container">
                    <form action="apicoltore.php" method="post">
                        <?php if ($modifica_id): ?><input type="hidden" name="id" value="<?php echo $modifica_id; ?>"><?php endif; ?>
                        <div class="form-group">
                            <label>Nome Apicoltore:</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($nome_modifica); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Codice AP:</label>
                            <input type="text" name="codap" value="<?php echo htmlspecialchars($codap_modifica); ?>" required>
                        </div>

                        <div class="btn-group-flex">
                            <button type="submit" name="<?php echo $modifica_id ? 'modifica' : 'inserisci'; ?>" class="btn btn-salva btn-flex-2">Salva</button>
                            <?php if ($modifica_id): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina btn-flex-1" onclick="return confirm('Sicuro di voler eliminare?')">Elimina</button>
                                <a href="apicoltore.php" class="btn btn-annulla btn-flex-1">Annulla</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if(tabParam === "tab-form" || <?php echo $modifica_id ? 'true' : 'false'; ?>) {
        openTab(null, 'tab-form');
    } else {
        openTab(null, 'tab-lista');
    }
});
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>