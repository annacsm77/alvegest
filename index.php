<?php
// Versione del file per il debug
$versione = "H.0.1"; 

include 'includes/config.php'; 
// Recuperiamo qui i dati per l'header, che includerà header.php
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Apiario - Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header>
    <?php include TPL_PATH . 'header.php'; ?>
</header>

    <div class="main-content">
        <div class="left-column"></div>

        <div class="center-column">
            <main>
                <h2>Dashboard Apiario</h2>
                <p>Panoramica Rapida V.<?php echo $versione; ?></p>
                
                <div class="widget-grid">
                    <?php
                    
                    // --- WIDGET 1: TOTALE ARNIE ATTIVE ---
                    $sql_tot_arnie = "SELECT COUNT(AR_ID) AS total FROM AP_Arnie WHERE AR_ATTI = 0";
                    $result_tot_arnie = $conn->query($sql_tot_arnie);
                    $tot_arnie = $result_tot_arnie ? $result_tot_arnie->fetch_assoc()['total'] : 0;
                    ?>
                    <div class="widget-card">
                        <h4>Arnie Attive</h4>
                        <div class="value"><?php echo $tot_arnie; ?></div>
                        <a href="<?php echo url('pages/arnie.php'); ?>" class="btn-widget">Visualizza Elenco</a>
                    </div>

                    <?php
                    // --- WIDGET 2: TOTALE APIARI (LUOGHI) ---
                    $sql_tot_apiari = "SELECT COUNT(AI_ID) AS total FROM TA_Apiari";
                    $result_tot_apiari = $conn->query($sql_tot_apiari);
                    $tot_apiari = $result_tot_apiari ? $result_tot_apiari->fetch_assoc()['total'] : 0;
                    ?>
                    <div class="widget-card">
                        <h4>Apiari Registrati</h4>
                        <div class="value"><?php echo $tot_apiari; ?></div>
                        <a href="<?php echo url('pages/apiari.php'); ?>" class="btn-widget">Gestione Luoghi</a>
                    </div>
                    
                    <?php
                    // --- WIDGET 3: ULTIMA ATTIVITÀ REGISTRATA ---
                    $sql_last_act = "
                        SELECT 
                            IA.IA_DATA, 
                            A.AR_NOME
                        FROM AT_INSATT IA
                        JOIN AP_Arnie A ON IA.IA_CodAr = A.AR_ID
                        ORDER BY IA.IA_DATA DESC, IA.IA_ID DESC 
                        LIMIT 1";
                    $result_last_act = $conn->query($sql_last_act);
                    $last_act = $result_last_act && $result_last_act->num_rows > 0 ? $result_last_act->fetch_assoc() : null;
                    
                    $data_ultima = $last_act ? date('d/m/Y', strtotime($last_act['IA_DATA'])) : 'N/A';
                    $nome_arnia = $last_act ? htmlspecialchars($last_act['AR_NOME']) : 'N/A';
                    ?>
                    <div class="widget-card">
                        <h4>Ultima Attività</h4>
                        <div class="value" style="font-size: 1.5em;"><?php echo $data_ultima; ?></div>
                        <p style="margin: 0; font-size: 0.9em; color: #666;">Su arnia: <?php echo $nome_arnia; ?></p>
                        <a href="<?php echo url('pages/gestatt.php'); ?>" class="btn-widget">Storico Completo</a>
                    </div>

                </div>
            </main>
        </div>

        <div class="right-column"></div>
    </div>

<footer>
    <?php include TPL_PATH . 'footer.php'; ?>
</footer>

</body>
</html>