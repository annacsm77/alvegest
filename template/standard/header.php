<?php
// Avvia la sessione se non è già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protezione della pagina: se l'utente non è loggato e non si trova già su login.php, reindirizza
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: " . url('login.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlveGest - Gestione Apiario</title>
    <link rel="stylesheet" href="<?php echo TPL_URL; ?>styles.css">
    <link rel="stylesheet" href="<?php echo TPL_URL; ?>header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php if (isset($_SESSION['AC_ruolo']) && $_SESSION['AC_ruolo'] === 'ospite'): ?>
<style>
    /* Nasconde i pulsanti di modifica nell'elenco */
    .btn-modifica, .btn-elimina, .btn-salva {
        display: none !important;
    }
    
    /* Disabilita tutti i campi di input e select nei form */
    form input, form select, form textarea {
        pointer-events: none;
        background-color: #f9f9f9 !important;
        color: #999 !important;
    }
    
    <script>
// Impedisce il salto (scroll) verso il basso se è presente un'ancora nell'URL
if (window.location.hash) {
    // Salviamo l'ancora
    var hash = window.location.hash;
    // Puliamo l'URL rimuovendo l'ancora (così il browser non scende)
    history.replaceState(null, null, window.location.pathname + window.location.search);
    
    // Forziamo lo scroll in cima alla pagina all'avvio
    window.scrollTo(0, 0);

    // Se esiste una funzione openTab, la usiamo per attivare la tab corretta
    window.addEventListener('DOMContentLoaded', () => {
        const targetTab = hash.replace('#', '');
        // Prova a chiamare la funzione openTab se definita nella pagina
        if (typeof openTab === "function") {
            openTab(null, targetTab);
        }
        // Riporta ancora una volta lo scroll in alto per sicurezza dopo il caricamento del DOM
        window.scrollTo(0, 0);
    });
}
</script>
    
    /* Nasconde il tab "Nuovo/Modifica" se preferisci che vedano solo l'elenco */
    /* .tabs-menu li:nth-child(2) { display: none !important; } */
</style>
<?php endif; ?>
</head>
<body>
    <header class="amz-header">
        <div class="amz-header-top">
            <div class="amz-logo">
                <a href="<?php echo url('index.php'); ?>" style="text-decoration: none;">
                    <span class="amz-logo-white">AlveGest</span><span class="amz-logo-orange">.it</span>
                </a>
            </div>
            
            <?php if (isset($_SESSION['user_nome'])): ?>
            <div class="amz-account-info" style="margin-left: auto; color: white; text-align: right; font-family: Arial, sans-serif;">
                <span style="font-size: 11px; display: block;">Ciao, <?php echo htmlspecialchars($_SESSION['user_nome']); ?></span>
                <a href="<?php echo url('logout.php'); ?>" style="color: #febd69; font-size: 12px; text-decoration: none; font-weight: bold;">Esci</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="amz-nav-container">
            <?php include BASE_PATH . 'includes/menu.php'; ?>
        </div>
    </header>