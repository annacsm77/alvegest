<?php
require_once '../includes/config.php';
require_once '../includes/header.php'; // Include header e menu
?>

<main class="main-content">
    <div class="left-column"></div>

    <div class="center-column">
        <h2>Storico Spostamenti Apiari</h2>

        <div class="table-container">
            <h3>Elenco Spostamenti</h3>
            <?php
            // Query per selezionare tutti i dati, unendo le tabelle TA_Apiari per ottenere i nomi dei luoghi
            $sql = "SELECT 
                        S.SP_ID,
                        S.SP_DATA,
                        S.SP_ARNIE,
                        S.SP_TOT,
                        DA.AI_LUOGO AS LuogoDa,
                        A.AI_LUOGO AS LuogoA
                    FROM AI_SPOS S
                    JOIN TA_Apiari DA ON S.SP_DA = DA.AI_ID
                    JOIN TA_Apiari A ON S.SP_A = A.AI_ID
                    ORDER BY S.SP_DATA DESC, S.SP_ID DESC";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo "<table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Da (Partenza)</th>
                                <th>A (Arrivo)</th>
                                <th>Arnie Spostate</th>
                                <th style='width: 60px;'>Totale</th>
                                <th style='width: 150px;'>Codici Arnie</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $row["SP_ID"] . "</td>
                            <td>" . date('d-m-Y', strtotime($row["SP_DATA"])) . "</td>
                            <td>" . htmlspecialchars($row["LuogoDa"]) . "</td>
                            <td>" . htmlspecialchars($row["LuogoA"]) . "</td>
                            <td style='text-align: center;'>" . $row["SP_TOT"] . "</td>
                            <td style='text-align: center;'>" . $row["SP_TOT"] . "</td> <td class='note-column' title='" . htmlspecialchars($row["SP_ARNIE"]) . "'>" . htmlspecialchars(substr($row["SP_ARNIE"], 0, 20)) . "...</td>
                            <td>
                                <button class='btn btn-elimina' onclick='return confirm(\"Sei sicuro di voler eliminare questo spostamento?\");'>Elimina</button>
                            </td>
                          </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p>Nessun spostamento registrato.</p>";
            }
            ?>
        </div>

    </div>

    <div class="right-column"></div>
</main>

<?php
require_once '../includes/footer.php';
?>