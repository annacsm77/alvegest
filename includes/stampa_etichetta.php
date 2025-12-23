<?php

error_reporting(0); // Disabilita tutti gli errori e gli avvisi
ini_set('display_errors', 0); // Nasconde gli errori
ob_start(); // Avvia il buffer di output

require('../fpdf/fpdf.php');

// Aggiungi il font generato
require('../fpdf/font/free3of9.php');

// Recupera i dati dall'URL
$codice = $_GET["codice"];
$nome = urldecode($_GET["nome"]);
$proprietario = urldecode($_GET["proprietario"]);

// Connessione al database
include '../includes/config.php';

// Recupera il codice dell'apicoltore (AP_codap) dalla tabella Apicoltore
$sql = "SELECT AP_codap FROM TA_Apicoltore WHERE AP_Nome = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $proprietario);
$stmt->execute();
$stmt->bind_result($ap_codap);
$stmt->fetch();
$stmt->close();

// Se il codice non è trovato, usa un valore predefinito
if (empty($ap_codap)) {
    $ap_codap = "N/A"; // Valore predefinito se il codice non è trovato
}

// Crea un nuovo documento PDF in formato A6 orizzontale (105mm x 148mm)
$pdf = new FPDF('L', 'mm', array(105, 148));
$pdf->AddPage();

// Imposta i margini ridotti
$pdf->SetMargins(5, 5, 5); // Margini sinistro, superiore, destro
$pdf->SetAutoPageBreak(false); // Disabilita l'interruzione automatica della pagina

// Imposta il colore blu per il testo
$pdf->SetTextColor(0, 0, 255); // Blu

// Aggiungi il nome dell'arnia (dimensione 36 e colore blu)
$pdf->SetFont('Arial', 'B', 36);
$pdf->Cell(0, 15, $nome, 0, 1, 'C'); // Riduci l'interlinea a 15

// Aggiungi il codice a barre Code 39 (dimensione 150 e colore nero)
$pdf->SetTextColor(0, 0, 0); // Nero
$pdf->AddFont('Free3of9', '', 'free3of9.php'); // Carica il font Code 39
$pdf->SetFont('Free3of9', '', 150); // Dimensione 150
$pdf->Cell(0, 45, '*' . $codice . '*', 0, 1, 'C'); // Riduci l'interlinea a 45

// Aggiungi il codice alfanumerico (dimensione 36 e colore nero)
$pdf->SetFont('Arial', '', 36);
$pdf->Cell(0, 15, $codice, 0, 1, 'C'); // Riduci l'interlinea a 15

// Aggiungi il codice dell'apicoltore (AP_codap) (dimensione 36 e colore blu)
$pdf->SetTextColor(0, 0, 255); // Blu
$pdf->SetFont('Arial', 'B', 36);
$pdf->Cell(0, 15,  $ap_codap, 0, 1, 'C'); // Riduci l'interlinea a 15

// Aggiungi un riquadro blu attorno all'etichetta (orizzontale)
$pdf->SetDrawColor(0, 0, 255); // Colore blu per il bordo
$pdf->SetLineWidth(1); // Spessore del bordo
$pdf->Rect(5, 5, 138, 95); // Rettangolo con margine di 5mm (orizzontale)

// Output del PDF
$pdf->Output('I', 'etichetta_arnia.pdf'); // 'I' per visualizzare direttamente nel browser
ob_end_flush(); // Invia il buffer di output
?>