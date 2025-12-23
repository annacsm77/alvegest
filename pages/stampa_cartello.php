<?php
// Avvia il buffer di output (necessario per FPDF)
ob_start();

// Inclusione della libreria FPDF (Aggiorna il percorso se necessario)
require('../fpdf/fpdf.php');

// Inclusione della configurazione del database
require_once('../includes/config.php'); 

// Controlla l'input ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID Apiario non valido.");
}

$apiario_id = $_GET['id'];

// --- 1. RECUPERA I DATI DAL DB ---
$sql = "SELECT AI_CODICE, AI_LUOGO FROM TA_Apiari WHERE AI_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $apiario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $full_code = strtoupper(trim($row['AI_CODICE']));
    $luogo = $row['AI_LUOGO'];

    // --- 2. ESTRAZIONE DEI DATI RICHIESTI ---
    
    // Primi 10 caratteri di AI_CODICE
    $codice_10 = substr($full_code, 0, 10);
    
    // Ultimi 2 caratteri di AI_CODICE
    $apiario_n = substr($full_code, -2);
    
    if (strlen($full_code) < 12) {
        $codice_10 = $full_code;
        $apiario_n = "N/A"; 
    }

} else {
    die("Apiario non trovato o codice mancante.");
}
$stmt->close();
$conn->close();

// --- 3. GENERAZIONE DEL PDF ---

// Orientamento Landscape ('L')
$pdf = new FPDF('L', 'mm', 'A4'); 
$pdf->AddPage();

// Larghezza massima utilizzabile (297mm - 20mm margine = 277mm)
$w = 277; 
$margin_x = 10;
$page_width = 297;
$interlinea_base = 20; // Altezza riga per font 72 (ridotta a 20mm)

// *** SISTEMA I&R NAZIONALE (Times, 72, Regular) ***
$pdf->SetY(8); 
$pdf->SetFont('Times', '', 72); 
$pdf->Cell($w, $interlinea_base + 2, 'SISTEMA I&R', 0, 1, 'C'); // ALTEZZA AUMENTATA di 2mm
$pdf->SetY(30); 
$pdf->Cell($w, $interlinea_base + 2, 'NAZIONALE', 0, 1, 'C'); // ALTEZZA AUMENTATA di 2mm
$pdf->Ln(2); 


// *** DECRETO LEGISLATIVO (Times, 26, Regular) ***
$pdf->SetFont('Times', '', 26); 
$pdf->Cell($w, 8, 'DECRETO LEGISLATIVO 5 AGOSTO 2022, n. 134', 0, 1, 'C'); 
$pdf->Ln(2); 


// *** CODICE NAZIONALE I&R (Times, 150, Regular, SENZA RIQUADRO) ***
$pdf->SetFont('Times', '', 150); // FONT A 150
$pdf->SetTextColor(0, 0, 0); 
$pdf->Cell($w, 55, $codice_10, 0, 1, 'C'); // Altezza cella adeguata per font 150
$pdf->Ln(5); 


// *** APIARIO NUMERO (Times, 100, ALLINEAMENTO) ***
$y_apiario = $pdf->GetY(); 
$width_box = $page_width - (2 * $margin_x); 

// 1. "apiario n." (sinistra)
$pdf->SetY($y_apiario + 2); 
$pdf->SetX($margin_x);
$pdf->SetFont('Times', '', 100); 
$pdf->Cell($width_box / 2, 30, 'apiario n.', 0, 0, 'L'); // Altezza cella 30mm

// 2. Numero estratto (destra)
$pdf->SetY($y_apiario + 2); 
$pdf->SetX($margin_x + ($width_box / 2));
$pdf->SetFont('Times', '', 100); 
$pdf->Cell($width_box / 2, 30, $apiario_n, 0, 1, 'R'); // Altezza cella 30mm
$pdf->Ln(10); 

// *** RIFERIMENTI (Solo Numero) ***
$pdf->SetFont('Times', '', 14);
$pdf->Cell($w, 6, 'RIFERIMENTI (Proprietario/Responsabile):', 0, 1, 'L'); 
$pdf->SetFont('Times', '', 16); 
$pdf->MultiCell($w, 8, '3283260720', 1, 'C'); 
$pdf->Ln(2); 

// 7. Data di Stampa (Posizionato in basso a destra)
$pdf->SetFont('Times', '', 10);
$pdf->Cell($w, 4, 'Stampato dalla BDN Apistica in data ' . date('d/m/Y'), 0, 1, 'R');


// Output del PDF
$pdf->Output('I', 'cartello_' . $full_code . '.pdf'); 
ob_end_flush();
?>