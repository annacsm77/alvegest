<?php
header('Content-Type: application/json');
require_once 'includes/config.php';

// Controlla il metodo POST e recupera il JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non valido.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['records']) || !is_array($data['records'])) {
    echo json_encode(['success' => false, 'message' => 'Dati JSON mancanti o non validi.']);
    exit;
}

$records = $data['records'];
$processed_count = 0;
$conn->begin_transaction();

// Prepara la query di inserimento per AT_INSATT
$sql = "INSERT INTO AT_INSATT (IA_DATA, IA_CodAr, IA_ATT, IA_NOTE, IA_PERI, IA_VREG, IA_OP1, IA_OP2) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Errore preparazione query: ' . $conn->error]);
    exit;
}

try {
    foreach ($records as $record) {
        // Sanificazione e allineamento tipi
        $data = $record['data'] ?? date('Y-m-d');
        $arnia_id = (int)($record['arnia_id'] ?? 0);
        $tipo_attivita = (int)($record['tipo_attivita'] ?? 0);
        $note = substr($record['note'] ?? '', 0, 1000);
        $ia_peri = (int)($record['ia_peri'] ?? 0);
        $ia_vreg = (int)($record['ia_vreg'] ?? 0);
        $ia_op1 = (int)($record['ia_op1'] ?? 0);
        $ia_op2 = (int)($record['ia_op2'] ?? 0);
        
        // Esecuzione
        $stmt->bind_param("siissiii", 
            $data, 
            $arnia_id, 
            $tipo_attivita, 
            $note, 
            $ia_peri, 
            $ia_vreg, 
            $ia_op1, 
            $ia_op2
        );
        
        if ($stmt->execute()) {
            $processed_count++;
        } else {
            // Se un record fallisce, loggalo ma non rompere il ciclo intero
            error_log("Failed to insert record: " . $stmt->error);
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'processed_count' => $processed_count]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Errore durante la transazione: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>