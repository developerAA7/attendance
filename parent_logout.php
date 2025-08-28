<?php
session_start();

// Destroy all parent session data
session_unset();
session_destroy();

// Redirect to parent login page
header("Location: parent_login.php");
exit();
?>
