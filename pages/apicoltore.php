<?php
// DEFINIZIONE VERSIONE FILE
define('FILE_VERSION', 'V.0.0.1');

require_once '../includes/config.php'; 
require_once TPL_PATH . 'header.php'; 

// 1. INIZIALIZZAZIONE VARIABILI
$messaggio = "";
$modifica_id = $_GET["modifica"] ?? null;
$nome_modifica = "";
$codap_modifica = "";

// 2. GESTIONE LOGICA POST (Inserimento, Modifica, Eliminazione)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"] ?? "";
    $codap = $_POST["codap"] ?? "";

    if (isset($_POST["inserisci"])) {
        $sql = "INSERT INTO TA_Apicoltore (AP_Nome, AP_codap) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $nome, $codap);
            if ($stmt->execute()) {
                header("Location: apicoltore.php?status=insert_success");
                exit();
            }
            $stmt->close();
        }
    } elseif (isset($_POST["modifica"])) {
        $id = $_POST["id"];
        $sql = "UPDATE TA_Apicoltore SET AP_Nome = ?, AP_codap = ? WHERE AP_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssi", $nome, $codap, $id);
            if ($stmt->execute()) {
                header("Location: apicoltore.php?status=update_success");
                exit();
            }
            $stmt->close();
        }
    } elseif (isset($_POST["elimina"])) {
        $id = $_POST["id"];
        $sql = "DELETE FROM TA_Apicoltore WHERE AP_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: apicoltore.php?status=delete_success");
                exit();
            }
            $stmt->close();
        }
    }
}

// 3. MESSAGGI DI STATO E RECUPERO DATI
if (isset($_GET["status"])) {
    $status = $_GET["status"];
    if ($status == "insert_success") $messaggio = "<p class='successo'>Apicoltore inserito correttamente!</p>";
    if ($status == "update_success") $messaggio = "<p class='successo'>Dati aggiornati con successo!</p>";
    if ($status == "delete_success") $messaggio = "<p class='successo'>Apicoltore rimosso dal sistema!</p>";
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
    $stmt->close();
}
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2 class="titolo-arnie">Gestione Apicoltori</h2>
        <?php echo $messaggio; ?>

        <div class="tabs-container">
            <ul class="tabs-menu">
                <li class="tab-link <?php echo !$modifica_id ? 'active' : ''; ?>" id="link-tab-lista" onclick="openTab(event, 'tab-lista')">Elenco Apicoltori</li>
                <li class="tab-link <?php echo $modifica_id ? 'active' : ''; ?>" id="link-tab-form" onclick="openTab(event, 'tab-form')">
                    <?php echo $modifica_id ? "Dettaglio e Modifica" : "Nuovo Apicoltore"; ?>
                </li>
            </ul>

            <div id="tab-lista" class="tab-content <?php echo !$modifica_id ? 'active' : ''; ?>">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Codice Apicoltore</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT AP_ID, AP_Nome, AP_codap FROM TA_Apicoltore";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $row["AP_ID"]; ?></td>
                                <td><?php echo htmlspecialchars($row["AP_Nome"]); ?></td>
                                <td><?php echo htmlspecialchars($row["AP_codap"]); ?></td>
                                <td>
                                    <a href="apicoltore.php?modifica=<?php echo $row['AP_ID']; ?>#tab-form" class="btn btn-modifica">Modifica</a>
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
                        <?php if ($modifica_id): ?>
                            <input type="hidden" name="id" value="<?php echo $modifica_id; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Nome:</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($nome_modifica); ?>" maxlength="60" required>
                        </div>
                        <div class="form-group">
                            <label>Codice Apicoltore:</label>
                            <input type="text" name="codap" value="<?php echo htmlspecialchars($codap_modifica); ?>" maxlength="60" required>
                        </div>

                        <div class="btn-group-form" style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" name="<?php echo $modifica_id ? 'modifica' : 'inserisci'; ?>" class="btn btn-salva" style="flex: 2;">
                                <?php echo $modifica_id ? "Salva" : "Inserisci"; ?>
                            </button>

                            <?php if ($modifica_id): ?>
                                <button type="submit" name="elimina" class="btn btn-elimina" style="flex: 1;" onclick="return confermaEliminazione();">
                                    Elimina
                                </button>
                                <a href="apicoltore.php" class="btn btn-annulla" style="flex: 1;">Annulla</a>
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
    if(window.location.hash === "#tab-form" || <?php echo $modifica_id ? 'true' : 'false'; ?>) {
        document.getElementById('link-tab-form').click();
    }
});

function confermaEliminazione() {
    return confirm("Sei sicuro di voler eliminare definitivamente questo apicoltore?");
}
</script>

<?php require_once TPL_PATH . 'footer.php'; ?>