<?php
session_start();
require('fpdf/fpdf.php');
include 'db.php';

if (!isset($_SESSION['parent_id'])) {
    header("Location: parent_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT name FROM students WHERE student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc()['name'];

$stmt = $conn->prepare("SELECT 
    SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent_count
FROM attendance WHERE student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,"Attendance Report - $student",0,1,'C');
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,"Present: ".$data['present_count'],0,1);
$pdf->Cell(0,10,"Absent: ".$data['absent_count'],0,1);
$pdf->Output("D","Attendance_Report.pdf");
?>
