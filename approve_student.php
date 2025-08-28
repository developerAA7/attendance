<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    $sql = "UPDATE students SET approved = 1 WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?msg=Student Approved");
        exit();
    } else {
        echo "Error approving student.";
    }
}
?>
