<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/dbconnection.php';
require_once __DIR__ . '/../includes/functions.php';

// Incluir FPDF
require_once(__DIR__ . '/../includes/fpdf/fpdf.php');

$codigo = $_GET['codigo'] ?? '';

// Buscar inscripción
$inscripcion = null;
$error = '';
if ($codigo) {
    $stmt = $dbh->prepare("SELECT i.id, i.proceso_admision, i.modalidad_id, p.dni, p.nombres, p.apellido_paterno, p.apellido_materno, p.email, p.telefono, p.foto, p.estado_inscripcion
        FROM inscripcion i
        JOIN postulante p ON i.postulante_id = p.id
        WHERE CONCAT(:pref, LPAD(i.id, 3, '0')) = :codigo");
    $codigo_prefix = CODIGO_PREFIX . CODIGO_YEAR . '-';
    $codigo_num = str_replace($codigo_prefix, '', $codigo);
    $stmt->execute([
        ':pref' => '',
        ':codigo' => $codigo_num
    ]);
    $inscripcion = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($inscripcion) {
        // Carrera principal
        $stmt2 = $dbh->prepare("SELECT p.nombre_programa FROM inscripcion_programa ip JOIN programa p ON ip.programa_id = p.id WHERE ip.inscripcion_id = :ins AND ip.orden = 1");
        $stmt2->execute([':ins' => $inscripcion['id']]);
        $carrera = $stmt2->fetchColumn();
        // Modalidad
        $stmt3 = $dbh->prepare("SELECT nombre FROM modalidad WHERE id = :id");
        $stmt3->execute([':id' => $inscripcion['modalidad_id']]);
        $modalidad = $stmt3->fetchColumn();
    } else {
        $error = 'No se encontró la inscripción con el código proporcionado.';
    }
} else {
    $error = 'No se proporcionó un código de inscripción.';
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,utf8_decode('Ficha de Inscripción'),0,1,'C');
$pdf->Ln(5);

if ($error) {
    $pdf->SetFont('Arial','',12);
    $pdf->SetTextColor(220,50,50);
    $pdf->MultiCell(0,10,utf8_decode($error),0,'C');
} else {
    $pdf->SetFont('Arial','',12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(50,10,'Código:',0,0);
    $pdf->Cell(0,10,utf8_decode($codigo),0,1);
    $pdf->Cell(50,10,'Nombres:',0,0);
    $pdf->Cell(0,10,utf8_decode($inscripcion['nombres']),0,1);
    $pdf->Cell(50,10,'Apellidos:',0,0);
    $pdf->Cell(0,10,utf8_decode($inscripcion['apellido_paterno'].' '.$inscripcion['apellido_materno']),0,1);
    $pdf->Cell(50,10,'DNI:',0,0);
    $pdf->Cell(0,10,utf8_decode($inscripcion['dni']),0,1);
    $pdf->Cell(50,10,'Carrera:',0,0);
    $pdf->Cell(0,10,utf8_decode($carrera ?? '-'),0,1);
    $pdf->Cell(50,10,'Modalidad:',0,0);
    $pdf->Cell(0,10,utf8_decode($modalidad ?? '-'),0,1);
    $pdf->Cell(50,10,'Email:',0,0);
    $pdf->Cell(0,10,utf8_decode($inscripcion['email']),0,1);
    $pdf->Cell(50,10,'Teléfono:',0,0);
    $pdf->Cell(0,10,utf8_decode($inscripcion['telefono']),0,1);
    $pdf->Cell(50,10,'Estado:',0,0);
    $pdf->Cell(0,10,utf8_decode($inscripcion['estado_inscripcion'] == 0 ? 'Inscrito' : 'Otro'),0,1);
}

$pdf->Output('D','ficha_inscripcion_'.$codigo.'.pdf');
exit; 