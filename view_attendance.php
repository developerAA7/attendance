<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.html");
    exit();
}

$student_id = $_GET['id'] ?? '';
if (!$student_id) {
    echo "Invalid student ID.";
    exit();
}

// Get student info
$stmt = $conn->prepare("SELECT s.name, s.branch_id, b.branch_name FROM students s JOIN branches b ON s.branch_id = b.branch_id WHERE s.student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    echo "Student not found.";
    exit();
}

// Attendance filter
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$filter_query = "SELECT date, status FROM attendance WHERE student_id = ?";
$params = [$student_id];
$types = "i";

if ($from && $to) {
    $filter_query .= " AND date BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types .= "ss";
}

$filter_query .= " ORDER BY date DESC";
$stmt = $conn->prepare($filter_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$attendance = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance Report - <?php echo htmlspecialchars($student['name']); ?></title>
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

    .dashboard {
      background-color: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      width: 95%;
      max-width: 800px;
    }

    h2 {
      text-align: center;
      color: #333;
    }

    p {
      text-align: center;
      color: #444;
    }

    form {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 10px;
      margin: 20px 0;
    }

    label {
      font-weight: bold;
      color: #333;
    }

    input[type="date"] {
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    button {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      background-color: #0072ff;
      color: white;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #005ec2;
    }

    .table-container {
      overflow-x: auto;
      margin-top: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 400px;
    }

    table, th, td {
      border: 1px solid #ccc;
    }

    th, td {
      padding: 12px;
      text-align: center;
    }

    th {
      background-color: #0072ff;
      color: white;
    }

    td {
      background-color: #f9f9f9;
    }

    .back-links {
      text-align: center;
      margin-top: 30px;
    }

    .back-links a {
      display: inline-block;
      margin: 5px 10px;
      text-decoration: none;
    }

    .back-links button {
      background-color:;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    .back-links button:hover {
      background-color: #333;
    }
@media screen and (max-width: 600px) and (min-width: 320px) {
  .dashboard {
    padding: 10px;
    text-align:center;
  }

  form {
    flex-direction: column;
    align-items: center;
  }

  label {
    text-align:left;
    margin-bottom: 5px;
    font-size: 13px;
  }

  input[type="date"] {
    width: 100%;
    font-size: 13px;
    padding: 6px;
  }

  .back-links button {
    width: 100%;
    margin: 5px 0;
    background-color: #0072ff;
    font-size: 13px;
    padding: 8px;
  }

  .table-container {
    width: 100%;
    overflow-x: hidden;
  }

  table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
  }

  th, td {
    padding: 6px 3px;
    font-size: 12px;
    word-wrap: break-word;
  }

  th {
    font-weight: bold;
  }
 
}



  </style>
</head>
<body>
  <div class="dashboard">
    <h2>Attendance Report</h2>
    <p>
      <strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?><br>
      <strong>Branch:</strong> <?php echo htmlspecialchars(ucfirst($student['branch_name'])); ?>
    </p>

    <form method="GET">
      <input type="hidden" name="id" value="<?php echo $student_id; ?>">
      <label>From:
        <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>">
      </label>
      <label>To:
        <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>">
      </label>
      <button type="submit">Filter</button>
    </form>

    <?php if ($attendance->num_rows > 0): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $attendance->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['date']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p style="text-align:center; color:red;">No attendance records found.</p>
    <?php endif; ?>

    <div class="back-links">
      <a href="dashboard_admin.php?branch_id=<?php echo urlencode($student['branch_id']); ?>">
        <button>⬅ Back to Branch Dashboard</button>
      </a>
      <a href="dashboard_admin.php">
        <button>⬅ Back to Admin Dashboard</button>
      </a>
    </div>
  </div>
</body>
</html>
