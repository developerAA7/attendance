<?php
session_start();
include 'db.php';

// Check if parent is logged in
if (!isset($_SESSION['parent']) || !isset($_SESSION['student_id'])) {
    header("Location: parent_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$error = '';
$successMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_substitute'])) {
    $name = trim($_POST['name']);
    $relation = trim($_POST['relation']);
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];

    // Validation
    if (empty($name) || empty($relation) || empty($from_date) || empty($to_date)) {
        $error = "All fields are required.";
    } elseif (strtotime($to_date) < strtotime($from_date)) {
        $error = "End date cannot be before start date.";
    } elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid photo.";
    } else {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $fileType = $_FILES['photo']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Only JPG and PNG images are allowed.";
        } else {
            // Upload directory
            $uploadDir = "uploads/substitutes/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $photoName = uniqid('sub_') . "_" . basename($_FILES['photo']['name']);
            $photoPath = $uploadDir . $photoName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                // Save to DB
                $sql = "INSERT INTO substitute_guardians (student_id, name, relation, photo_path, from_date, to_date, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssss", $student_id, $name, $relation, $photoPath, $from_date, $to_date);

                if ($stmt->execute()) {
                    $successMsg = "Your request has been submitted successfully!";
                    header("Location: add_substitute_guardian.php");
                    exit();
                } else {
                    $error = "Database error. Please try again.";
                }
                $stmt->close();
            } else {
                $error = "File upload failed. Check folder permissions.";
            }
        }
    }
}

// Fetch substitute guardian requests for this student
$sql = "SELECT * FROM substitute_guardians WHERE student_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$substitutes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Show success message from redirect
if (isset($_SESSION['msg'])) {
    $successMsg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Substitute Guardian</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            background: white;
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        header {
            background: #007bff;
            color: white;
            text-align: center;
            padding: 25px 20px;
        }

        header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 600;
        }

        .content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px;
        }

        .form-section, .list-section {
            flex: 1;
            min-width: 300px;
        }

        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        h2 {
            color: #007bff;
            margin-bottom: 18px;
            font-size: 20px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #444;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #0056b3;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Table Section */
        .list-section h3 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
            text-align: left;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background: #007bff;
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

        td img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
            cursor: pointer;
            transition: transform 0.2s;
        }

        td img:hover {
            transform: scale(1.1);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
        }

        .badge-pending {
            background: #ffc107;
        }

        .badge-approved {
            background: #28a745;
        }

        .badge-rejected {
            background: #dc3545;
        }

        /* Modal */
        #imageModal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
        }

        #imageModal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content {
                flex-direction: column;
                padding: 15px;
            }

            .form-section, .list-section {
                min-width: 100%;
            }

            header h1 {
                font-size: 22px;
            }

            .btn {
                font-size: 15px;
                padding: 11px 20px;
            }

            table, th, td {
                font-size: 13px;
            }

            .form-group input {
                font-size: 14px;
                padding: 10px;
            }

            .back-link {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .container {
                border-radius: 10px;
            }

            header {
                padding: 20px 15px;
            }

            .form-container {
                padding: 15px;
            }

            h2 {
                font-size: 18px;
            }

            .badge {
                font-size: 11px;
                padding: 4px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Add Substitute Guardian</h1>
        </header>

        <div class="content">
            <!-- Left: Add Form -->
            <div class="form-section">
                <div class="form-container">
                    <h2>Submit New Request</h2>

                    <?php if ($error): ?>
                        <p class="error"><?= htmlspecialchars($error); ?></p>
                    <?php endif; ?>

                    <?php if ($successMsg): ?>
                        <p class="success"><?= htmlspecialchars($successMsg); ?></p>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="submit_substitute" value="1">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Relation (e.g., Aunt, Uncle)</label>
                            <input type="text" name="relation" value="<?= htmlspecialchars($_POST['relation'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Photo</label>
                            <input type="file" name="photo" accept="image/*" required>
                        </div>

                        <div class="form-group">
                            <label>Valid From</label>
                            <input type="date" name="from_date" value="<?= htmlspecialchars($_POST['from_date'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Valid To</label>
                            <input type="date" name="to_date" value="<?= htmlspecialchars($_POST['to_date'] ?? '') ?>" required>
                        </div>

                        <button type="submit" class="btn">Submit Request</button>
                    </form>

                    <p><a href="parent_dashboard.php" class="back-link"><button style="background-color: blue; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
  ← Back to Dashboard
</button>
</a></p>
                </div>
            </div>

            <!-- Right: Request List -->
            <div class="list-section">
                <h3>🛡️ Your Requests</h3>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Relation</th>
                        <th>Photo</th>
                        <th>Dates</th>
                        <th>Status</th>
                    </tr>
                    <?php if (empty($substitutes)): ?>
                        <tr>
                            <td colspan="5" style="color: #6c757d; font-style: italic;">No requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($substitutes as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars($req['name']); ?></td>
                                <td><?= htmlspecialchars($req['relation']); ?></td>
                                <td>
                                    <?php if (file_exists($req['photo_path'])): ?>
                                        <img src="<?= htmlspecialchars($req['photo_path']); ?>"
                                             onclick="openModal('<?= htmlspecialchars($req['photo_path']); ?>')"
                                             alt="Guardian Photo">
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($req['from_date']); ?> → <?= htmlspecialchars($req['to_date']); ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($req['status']) ?>">
                                        <?= htmlspecialchars($req['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imageModal">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <img id="modalImg" alt="Enlarged Photo">
    </div>

    <script>
        function openModal(src) {
            document.getElementById("modalImg").src = src;
            document.getElementById("imageModal").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("imageModal").style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById("imageModal");
            if (event.target === modal) {
                closeModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                closeModal();
            }
        });
    </script>
</body>
</html>