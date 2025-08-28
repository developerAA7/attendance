<?php
session_start();
include 'db.php';

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['branch_name'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $age = $_POST['age'] ?? '';
    $branch_name = $_SESSION['branch_name'];

    // Get branch ID
    $stmt = $conn->prepare("SELECT branch_id FROM branches WHERE branch_name = ?");
    $stmt->bind_param("s", $branch_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $branch = $result->fetch_assoc();

    if ($branch) {
        $branch_id = $branch['branch_id'];

        // Insert student
        $insert = $conn->prepare("INSERT INTO students (name, age, branch_id) VALUES (?, ?, ?)");
        $insert->bind_param("sii", $name, $age, $branch_id);
        $insert->execute();

        header("Location: dashboard_staff.php");
        exit();
    } else {
        echo "Invalid branch.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- ✅ Added for mobile responsiveness -->
  <title>Add Student</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: url('images/k1.jpg') no-repeat center center/cover;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .form-container {
      background-color: rgba(207, 199, 199, 0.9);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
      width: 350px;
      text-align: center;
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    label {
      text-align: left;
      margin: 10px 0 5px;
      font-weight: bold;
      color: #333;
    }

    input {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      margin-bottom: 15px;
    }

    button {
      background-color: #0072ff;
      color: white;
      padding: 10px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }

    button:hover {
      background-color: #005ec2;
    }

    .back-link {
      display: inline-block;
      margin-top: 15px;
      text-decoration: none;
      color: #0072ff;
      font-weight: bold;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    /* ✅ Mobile responsive media query */
    @media (max-width: 500px) {
      .form-container {
        width: 90%;
        margin-left:20px;
        margin-right:20px;
        padding: 20px;
      }

      h2 {
        font-size: 22px;
      }

      input, button {
        font-size: 16px;
      }
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Add New Student</h2>
    <form method="POST">
      <label>Name:</label>
      <input type="text" name="name" required>

      <label>Age:</label>
      <input type="number" name="age" required>

      <button type="submit">Add Student</button>
    </form>
    <a href="dashboard_staff.php" class="back-link">Back to Dashboard</a>
  </div>
</body>
</html>
