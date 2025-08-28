<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_GET['id'] ?? '';
if (!$student_id) {
    echo "Invalid Student ID";
    exit();
}

$branch_result = $conn->query("SELECT * FROM branches");
$branches = [];
while ($row = $branch_result->fetch_assoc()) {
    $branches[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $branch_id = $_POST['branch_id'];

    $stmt = $conn->prepare("UPDATE students SET name = ?, age = ?, branch_id = ? WHERE student_id = ?");
    $stmt->bind_param("siii", $name, $age, $branch_id, $student_id);
    $stmt->execute();

    header("Location: dashboard_admin.php?branch_id=$branch_id");
    exit();
}

// Get current student details
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
    echo "Student not found";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Student</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right, #74ebd5, #acb6e5);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .form-container {
      background-color: #fff;
      padding: 30px;
      border-radius: 12px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
    }

    form {
      width: 100%;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #333;
    }

    input[type="text"],
    input[type="number"],
    select {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }

    button {
      width: 100%;
      padding: 10px;
      background-color: #0072ff;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #005ec2;
    }

    a {
      display: block;
      text-align: center;
      margin-top: 15px;
      text-decoration: none;
      color: #0072ff;
      font-weight: bold;
    }

    a:hover {
      text-decoration: underline;
    }

    @media (max-width: 480px) {
      .form-container {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Edit Student</h2>
    <form method="POST">
      <label>Name:</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>

      <label>Age:</label>
      <input type="number" name="age" value="<?php echo $student['age']; ?>" required>

      <label>Branch:</label>
      <select name="branch_id" required>
        <?php foreach ($branches as $branch): ?>
          <option value="<?php echo $branch['branch_id']; ?>" <?php echo $branch['branch_id'] == $student['branch_id'] ? 'selected' : ''; ?>>
            <?php echo ucfirst($branch['branch_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Update Student</button>
    </form>
    <a href="dashboard_admin.php?branch_id=<?php echo $student['branch_id']; ?>">⬅ Back</a>
  </div>
</body>
</html>
