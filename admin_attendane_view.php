<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Fetch branches for dropdown
$branch_result = $conn->query("SELECT * FROM branches");
$branches = [];
while ($row = $branch_result->fetch_assoc()) {
    $branches[] = $row;
}

$branch_id = $_GET['branch_id'] ?? '';
$date = $_GET['date'] ?? '';
$records = [];

if ($branch_id && $date) {
    $stmt = $conn->prepare("SELECT s.name, b.branch_name, a.date, a.status
                            FROM attendance a
                            JOIN students s ON a.student_id = s.student_id
                            JOIN branches b ON s.branch_id = b.branch_id
                            WHERE s.branch_id = ? AND a.date = ?");
    $stmt->bind_param("is", $branch_id, $date);
    $stmt->execute();
    $records = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance Viewer</title>
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <div class="dashboard">
    <h2>View Attendance</h2>
    <form method="GET">
      <label>Select Branch:</label>
      <select name="branch_id" required>
        <option value="">-- Select --</option>
        <?php foreach ($branches as $branch): ?>
          <option value="<?php echo $branch['branch_id']; ?>" <?php echo $branch_id == $branch['branch_id'] ? 'selected' : ''; ?>>
            <?php echo ucfirst($branch['branch_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Select Date:</label>
      <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" required>

      <button type="submit">Search</button>
    </form>

    <?php if ($records && $records->num_rows > 0): ?>
      <h3>Attendance on <?php echo htmlspecialchars($date); ?></h3>
      <table border="1" cellpadding="10">
        <tr>
          <th>Student Name</th>
          <th>Branch</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
        <?php while($row = $records->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo ucfirst($row['branch_name']); ?></td>
            <td><?php echo $row['status']; ?></td>
            <td>
              <form method="POST" action="update_attendance.php" style="display:inline-block">
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['name']); ?>">
                <input type="hidden" name="date" value="<?php echo $date; ?>">
                <select name="status">
                  <option value="Present" <?php echo $row['status'] === 'Present' ? 'selected' : ''; ?>>Present</option>
                  <option value="Absent" <?php echo $row['status'] === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                </select>
                <button type="submit">Update</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php elseif ($branch_id && $date): ?>
      <p>No attendance records found for this branch on selected date.</p>
    <?php endif; ?>
  </div>
</body>
</html>
