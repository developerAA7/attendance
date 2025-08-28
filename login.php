<?php
session_start();

// Hardcoded branch staff credentials: username => [password, branch_name]
$staff_users = [
    "Staff@pollachi" => ["Happykids@123", "pollachi"],
    "Staff@coimbatore" => ["Happykids@123", "coimbatore"],
    "Staff@tirupur" => ["Happykids@123", "tirupur"],
    "Staff@chithode" => ["Happykids@123", "chithode"],
    "Staff@kolathur" => ["Happykids@123", "kolathur"],
    "Staff@tambaram" => ["Happykids@123", "tambaram"]
];

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$branch_input = $_POST['branch_id'] ?? '';

// Admin login
if ($username === 'admin' && $password === 'admin123') {
    $_SESSION['admin'] = true;
    header("Location: dashboard_admin.php");
    exit();
}

// Staff login check
if (isset($staff_users[$username])) {
    $correct_password = $staff_users[$username][0];
    $correct_branch = $staff_users[$username][1];

    if ($password !== $correct_password) {
        $_SESSION['login_error'] = "Incorrect password.";
    } elseif ($branch_input !== $correct_branch) {
        $_SESSION['login_error'] = "Incorrect branch selected.";
    } else {
        // Successful login
        $_SESSION['staff_id'] = $username;
        $_SESSION['branch_name'] = $correct_branch;
        header("Location: dashboard_staff.php");
        exit();
    }
} else {
    $_SESSION['login_error'] = "User ID not found.";
}

// Redirect back to login with error
header("Location: index.php");
exit();
