<?php
include '../includes/config.php'; // Includi la connessione al database
include '../includes/header.php';
//include '../includes/menu.php';

// Gestione dell'inserimento, modifica ed eliminazione dei dati
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["inserisci"])) {
        // Inserimento di un nuovo apicoltore
        $nome = $_POST["nome"];
        $codap = $_POST["codap"];

        // Prepara la query SQL
        $sql = "INSERT INTO TA_Apicoltore (AP_Nome, AP_codap) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            echo "<p>Errore nella preparazione della query: " . $conn->error . "</p>";
            exit;
        }

        // Usa la stringa di formattazione corretta
        $stmt->bind_param("ss", $nome, $codap);

        if ($stmt->execute()) {
            echo "<p>Apicoltore inserito con successo!</p>";
        } else {
            echo "<p>Errore durante l'inserimento: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } elseif (isset($_POST["modifica"])) {
        // Modifica di un apicoltore esistente
        $id = $_POST["id"];
        $nome = $_POST["nome"];
        $codap = $_POST["codap"];

        // Prepara la query SQL
        $sql = "UPDATE TA_Apicoltore SET AP_Nome = ?, AP_codap = ? WHERE AP_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nome, $codap, $id);

        if ($stmt->execute()) {
            echo "<p>Apicoltore modificato con successo!</p>";
        } else {
            echo "<p>Errore durante la modifica: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}

// Gestione dell'eliminazione del record
if (isset($_GET["elimina"])) {
    $elimina_id = $_GET["elimina"];

    // Query per eliminare il record
    $sql = "DELETE FROM TA_Apicoltore WHERE AP_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $elimina_id);

    if ($stmt->execute()) {
        echo "<p>Record eliminato con successo!</p>";
    } else {
        echo "<p>Errore durante l'eliminazione: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// Gestione della richiesta di modifica
$modifica_id = null;
if (isset($_GET["modifica"])) {
    $modifica_id = $_GET["modifica"];
    $sql = "SELECT * FROM TA_Apicoltore WHERE AP_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $modifica_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $apicoltore = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Apicoltori</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <!-- Menu -->
    <?php //include '../includes/menu.php'; ?>

    <!-- Contenuto principale (3 colonne) -->
    <div class="main-content">
        <!-- Colonna sinistra (10%) -->
        <div class="left-column"></div>

        <!-- Colonna centrale (80%) -->
        <div class="center-column">
            <main>
                <!-- Titolo -->
                

                <!-- Lista degli apicoltori -->
                <div class="table-container">
                    <h3>Elenco Apicoltori</h3>
                    <?php
                    $sql = "SELECT * FROM TA_Apicoltore";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        echo "<table>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Codice Apicoltore</th>
                                    <th>Azioni</th>
                                </tr>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . $row["AP_ID"] . "</td>
                                    <td>" . $row["AP_Nome"] . "</td>
                                    <td>" . $row["AP_codap"] . "</td>
                                    <td>
                                        <a href='apicoltore.php?modifica=" . $row["AP_ID"] . "' class='btn btn-modifica'>Modifica</a>
                                        <a href='apicoltore.php?elimina=" . $row["AP_ID"] . "' class='btn btn-elimina' onclick='return confirm(\"Sei sicuro di voler eliminare questo record?\");'>Elimina</a>
                                    </td>
                                  </tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p>Nessun apicoltore trovato.</p>";
                    }
                    ?>
                </div>

                <!-- Form per l'inserimento/modifica dei dati -->
                <div class="form-container">
                    <h3><?php echo $modifica_id ? "Modifica Apicoltore" : "Inserisci Nuovo Apicoltore"; ?></h3>
                    <form action="apicoltore.php" method="post">
                        <?php if ($modifica_id): ?>
                            <input type="hidden" name="id" value="<?php echo $apicoltore["AP_ID"]; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="nome">Nome:</label>
                            <input type="text" id="nome" name="nome" value="<?php echo $modifica_id ? $apicoltore["AP_Nome"] : ''; ?>" maxlength="60" required>
                        </div>
                        <div class="form-group">
                            <label for="codap">Codice Apicoltore:</label>
                            <input type="text" id="codap" name="codap" value="<?php echo $modifica_id ? $apicoltore["AP_codap"] : ''; ?>" maxlength="60" required>
                        </div>
                        <div class="form-group">
                            <?php if ($modifica_id): ?>
                                <button type="submit" name="modifica" class="btn btn-modifica btn-grande">Modifica Apicoltore</button>
                            <?php else: ?>
                                <button type="submit" name="inserisci" class="btn btn-inserisci btn-grande">Inserisci Apicoltore</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </main>
        </div>

        <!-- Colonna destra (10%) -->
        <div class="right-column"></div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>