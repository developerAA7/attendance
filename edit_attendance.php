<?php
include 'db.php';

if (!isset($_GET['student_id']) || !isset($_GET['date'])) {
    echo "Invalid access.";
    exit();
}

$student_id = $_GET['student_id'];
$date = $_GET['date'];

// Fetch existing attendance record
$stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? AND date = ?");
$stmt->bind_param("is", $student_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "Attendance record not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_relation = trim($_POST['guardian_relation']);

    // Handle new image upload
    $new_photo_path = $data['guardian_photo']; // default to old image
    if (isset($_FILES['guardian_photo']) && $_FILES['guardian_photo']['name']) {
        $upload_dir = "uploads/student_" . $student_id;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = time() . '_' . basename($_FILES['guardian_photo']['name']);
        $target_file = $upload_dir . "/" . $filename;

        if (move_uploaded_file($_FILES['guardian_photo']['tmp_name'], $target_file)) {
            $new_photo_path = $target_file;
        }
    }

    $stmt = $conn->prepare("UPDATE attendance SET guardian_photo = ?, guardian_relation = ? WHERE student_id = ? AND date = ?");
    $stmt->bind_param("ssis", $new_photo_path, $new_relation, $student_id, $date);
    $stmt->execute();

    echo "<script>alert('Attendance updated successfully.'); window.location.href='developer.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Attendance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f0f0;
      padding: 20px;
    }

    .form-container {
      max-width: 500px;
      margin: auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: #333;
    }

    label {
      display: block;
      margin: 15px 0 5px;
      font-weight: bold;
    }

    input[type="text"], input[type="file"] {
      width: 100%;
      padding: 8px;
    }

    img {
      max-width: 150px;
      display: block;
      margin-top: 10px;
      border-radius: 5px;
    }

    button {
      width: 100%;
      margin-top: 20px;
      padding: 10px;
      background-color: #0072ff;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
    }

    a {
      display: block;
      margin-top: 15px;
      text-align: center;
      color: #0072ff;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Edit Attendance</h2>
    <form method="POST" enctype="multipart/form-data">
      <label>Current Guardian Photo:</label>
      <?php if (!empty($data['guardian_photo']) && file_exists($data['guardian_photo'])): ?>
        <img src="<?= $data['guardian_photo']; ?>" alt="Guardian">
      <?php else: ?>
        <p style="color: gray;">No photo available.</p>
      <?php endif; ?>

      <label>Upload New Photo (optional):</label>
      <input type="file" name="guardian_photo" accept="image/*">

      <label>Guardian Relation:</label>
      <input type="text" name="guardian_relation" value="<?= htmlspecialchars($data['guardian_relation']); ?>" required>

      <button type="submit">Update Attendance</button>
    </form>
    <a href="developer.php">← Back to Developer Panel</a>
  </div>
</body>
</html>
