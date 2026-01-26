<?php
require_once 'includes/config.php';

// Se l'utente è già loggato, lo reindirizziamo alla home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$errore = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    // Recuperiamo anche il ruolo (AC_ruolo) per gestire i permessi
    $stmt = $conn->prepare("SELECT AC_id, AC_username, AC_password, AC_nome, AC_attivo, AC_ruolo FROM TA_Account WHERE AC_username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['AC_attivo'] != 1) {
            $errore = "L'account è disattivato.";
        } 
        elseif ($pass === $row['AC_password']) {
            // SALVATAGGIO VARIABILI DI SESSIONE
            $_SESSION['user_id']   = $row['AC_id'];
            $_SESSION['user_nome'] = $row['AC_nome'];
            $_SESSION['AC_ruolo']  = $row['AC_ruolo']; // Questa serve per vedere i Log
            
            header("Location: index.php");
            exit();
        } else {
            $errore = "Password errata.";
        }
    } else {
        $errore = "Username non trovato.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlveGest - Accedi</title>
    <link rel="stylesheet" href="template/standard/styles.css">
    <link rel="stylesheet" href="template/standard/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Centratura Totale con Flexbox */
        body, html { 
            height: 100%; 
            margin: 0; 
        }
        body { 
            background-color: #f3f3f3; 
            display: flex; 
            justify-content: center; /* Centra orizzontalmente */
            align-items: center;     /* Centra verticalmente */
            font-family: Arial, sans-serif; 
        }
        .login-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        .login-card { 
            width: 350px; 
            background: #fff; 
            padding: 25px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .amz-logo-center { text-align: center; margin-bottom: 25px; text-decoration: none; display: block; }
        .login-card h2 { color: #111 !important; margin: 0 0 20px 0; font-weight: 500; font-size: 28px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #111; display: block; }
        .form-group input { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #a6a6a6; 
            border-radius: 3px; 
            font-size: 14px; 
            box-sizing: border-box;
        }
        .err-msg { color: #c40000; font-size: 13px; margin-bottom: 15px; border: 1px solid #c40000; padding: 10px; border-radius: 4px; background: #fffefe; }
        .btn-amazon { 
            width: 100%; 
            background: linear-gradient(to bottom, #f7dfa5, #f0c14b); 
            border: 1px solid #a88734; 
            color: #111; 
            padding: 10px; 
            border-radius: 3px; 
            cursor: pointer; 
            font-size: 13px;
            font-weight: bold;
        }
        .btn-amazon:hover { background: linear-gradient(to bottom, #f5d78e, #eeb933); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="amz-logo-center">
            <span style="color:#111; font-size:32px; font-weight:bold;">AlveGest</span><span style="color:#e47911; font-size:24px;">.it</span>
        </div>
        
        <div class="login-card">
            <h2>Accedi</h2>
            
            <?php if($errore): ?>
                <div class="err-msg">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $errore; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" class="btn-amazon">Continua</button>
            </form>

            <p style="font-size: 12px; margin-top: 20px; color: #555; line-height: 1.5;">
                Accedendo, accetti le Condizioni di AlveGest.
            </p>
        </div>
    </div>
</body>
</html>