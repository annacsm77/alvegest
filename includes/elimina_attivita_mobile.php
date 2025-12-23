<?php
require_once 'config.php';

if (isset($_GET["id"]) && isset($_GET["arnia_id"])) {
    $id_att = (int)$_GET["id"];
    $arnia_id = (int)$_GET["arnia_id"];
    $from = $_GET["from"] ?? 'mobile';

    // 1. Elimina scarico magazzino sincronizzato
    $causale = "%(IA_ID: $id_att)%";
    $stmt_m = $conn->prepare("DELETE FROM MA_MOVI WHERE MV_Descrizione LIKE ?");
    $stmt_m->bind_param("s", $causale);
    $stmt_m->execute();
    $stmt_m->close();

    // 2. Elimina attività
    $stmt_a = $conn->prepare("DELETE FROM AT_INSATT WHERE IA_ID = ?");
    $stmt_a->bind_param("i", $id_att);
    $stmt_a->execute();
    $stmt_a->close();

    // 3. RITORNO AL PERCORSO CORRETTO
    if ($from === 'desktop') {
        // Torna alla pagina desktop in pages
        header("Location: /alvegest/pages/gestatt.php?status=del_success&arnia_id=" . $arnia_id . "&tab=attivita");
    } else {
        // Torna alla pagina mobile nella root
        header("Location: /alvegest/mobile.php?status=del_success&arnia_id=" . $arnia_id);
    }
    exit();
}
?>