<?php
session_start();
include 'db.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_GET['id'] ?? '';

if ($student_id && is_numeric($student_id)) {
    // Step 1: Delete attendance records related to the student
    $stmt1 = $conn->prepare("DELETE FROM attendance WHERE student_id = ?");
    $stmt1->bind_param("i", $student_id);
    if (!$stmt1->execute()) {
        die("Error deleting attendance records: " . $stmt1->error);
    }
    $stmt1->close();

    // Step 2: Delete the student from students table
    $stmt2 = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt2->bind_param("i", $student_id);
    if (!$stmt2->execute()) {
        die("Error deleting student: " . $stmt2->error);
    }
    $stmt2->close();

    // Redirect to dashboard
    header("Location: dashboard_admin.php");
    exit();
} else {
    echo "Invalid or missing student ID.";
}
?>
