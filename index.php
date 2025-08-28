<?php
session_start();
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- ✅ ADDED FOR RESPONSIVENESS -->
  <title>Staff Login - Play School Attendance</title>
  <link rel="stylesheet" href="css/index.css">
  <style>
    .password-wrapper {
      position: relative;
    }

    .toggle-password {
      position: absolute;
      top: 50%;
      right: 12px;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 16px;
      color: #555;
    }

    .error {
      color: red;
      text-align: center;
      margin-bottom: 15px;
      font-weight: bold;
    }

    /* ✅ ADDED MEDIA QUERY FOR SMALL SCREEN */
    @media (max-width: 500px) {
      .login-container {
        margin-top:50px;
        margin-bottom:0;
        padding: 20px;
      }

      input, select, button {
        font-size: 16px;
      }

      h2 {
        font-size: 22px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Staff Login</h2>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
      <label>User ID</label>
      <input type="text" name="username" required>

      <label>Password</label>
      <div class="password-wrapper">
        <input type="password" name="password" id="password" required>
        <span class="toggle-password" onclick="togglePassword()">👁️</span>
      </div>

      <label>Select Branch</label>
      <select name="branch_id" required>
        <option value="">-- Select Branch --</option>
        <option value="pollachi">Pollachi</option>
        <option value="coimbatore">Coimbatore</option>
        <option value="tirupur">Tirupur</option>
        <option value="chithode">Chithode</option>
        <option value="kolathur">Kolathur</option>
        <option value="tambaram">Tambaram</option>
      </select>

      <button type="submit">Login</button>
    </form>

    <form action="admin.html">
      <button type="submit" class="secondary-btn">Admin Login</button>
    </form>
  </div>

  <script>
    function togglePassword() {
      const passwordField = document.getElementById("password");
      passwordField.type = passwordField.type === "password" ? "text" : "password";
    }
  </script>
</body>
</html>
