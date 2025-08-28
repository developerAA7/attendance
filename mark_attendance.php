<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['branch_name'])) {
    header("Location: index.html");
    exit();
}

$branch_name = $_SESSION['branch_name'];
$date_today = date('Y-m-d');

// Fetch students from the logged-in branch
$stmt = $conn->prepare("SELECT s.student_id, s.name 
                        FROM students s 
                        JOIN branches b ON s.branch_id = b.branch_id 
                        WHERE b.branch_name = ?");
$stmt->bind_param("s", $branch_name);
$stmt->execute();
$result = $stmt->get_result();

$guardian_data = [];
$marked_students = [];

// Load student and guardian data
while ($student = $result->fetch_assoc()) {
    $student_id = $student['student_id'];
    $student_folder = "uploads/student_" . $student_id;

    // Check if attendance is already marked for today
    $check = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? AND date = ?");
    $check->bind_param("is", $student_id, $date_today);
    $check->execute();
    $res_check = $check->get_result();
    if ($res_check->num_rows > 0) {
        $marked_students[$student_id] = $res_check->fetch_assoc();
    }
    $check->close();

    $guardian_data[$student_id] = [
        'name' => $student['name'],
        'photos' => []
    ];

    // Load guardian photos from DB
    $stmt_g = $conn->prepare("SELECT photo_path, relation FROM guardian_photos WHERE student_id = ?");
    $stmt_g->bind_param("i", $student_id);
    $stmt_g->execute();
    $res_g = $stmt_g->get_result();
    while ($row = $res_g->fetch_assoc()) {
        $guardian_data[$student_id]['photos'][] = [
            'path' => $row['photo_path'],
            'relation' => $row['relation']
        ];
    }
    $stmt_g->close();
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];

    foreach ($_POST['attendance'] as $student_id => $status) {
        if ($status === 'Nil') continue;

        $student_id = (int)$student_id;
        $student_folder = "uploads/student_" . $student_id;
        if (!is_dir($student_folder)) mkdir($student_folder, 0777, true);

        // Guardian In
        $g_in_relation = trim($_POST['guardian_in_relation'][$student_id] ?? '');
        $g_in_photo = null;

        if (!empty($_FILES['guardian_in_photo']['name'][$student_id])) {
            $file_name = $_FILES['guardian_in_photo']['name'][$student_id];
            $file_tmp = $_FILES['guardian_in_photo']['tmp_name'][$student_id];
            $g_in_photo = $student_folder . '/' . time() . '_in_' . basename($file_name);
            move_uploaded_file($file_tmp, $g_in_photo);

            // Save new photo + relation in guardian_photos table
            if (!empty($g_in_relation)) {
                $stmt_save = $conn->prepare("INSERT INTO guardian_photos (student_id, photo_path, relation) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE relation=VALUES(relation)");
                $stmt_save->bind_param("iss", $student_id, $g_in_photo, $g_in_relation);
                $stmt_save->execute();
                $stmt_save->close();
            }
        } elseif (!empty($_POST['selected_guardian_in_photo'][$student_id])) {
            $g_in_photo = $_POST['selected_guardian_in_photo'][$student_id];
            // Fetch saved relation from DB
            $stmt_fetch = $conn->prepare("SELECT relation FROM guardian_photos WHERE student_id = ? AND photo_path = ?");
            $stmt_fetch->bind_param("is", $student_id, $g_in_photo);
            $stmt_fetch->execute();
            $res = $stmt_fetch->get_result();
            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $g_in_relation = $row['relation'];
            }
            $stmt_fetch->close();
        }

        // Guardian Out
        $g_out_relation = trim($_POST['guardian_out_relation'][$student_id] ?? '');
        $g_out_photo = null;

        if (!empty($_FILES['guardian_out_photo']['name'][$student_id])) {
            $file_name = $_FILES['guardian_out_photo']['name'][$student_id];
            $file_tmp = $_FILES['guardian_out_photo']['tmp_name'][$student_id];
            $g_out_photo = $student_folder . '/' . time() . '_out_' . basename($file_name);
            move_uploaded_file($file_tmp, $g_out_photo);

            // Save new photo + relation in guardian_photos table
            if (!empty($g_out_relation)) {
                $stmt_save = $conn->prepare("INSERT INTO guardian_photos (student_id, photo_path, relation) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE relation=VALUES(relation)");
                $stmt_save->bind_param("iss", $student_id, $g_out_photo, $g_out_relation);
                $stmt_save->execute();
                $stmt_save->close();
            }
        } elseif (!empty($_POST['selected_guardian_out_photo'][$student_id])) {
            $g_out_photo = $_POST['selected_guardian_out_photo'][$student_id];
            // Fetch saved relation from DB
            $stmt_fetch = $conn->prepare("SELECT relation FROM guardian_photos WHERE student_id = ? AND photo_path = ?");
            $stmt_fetch->bind_param("is", $student_id, $g_out_photo);
            $stmt_fetch->execute();
            $res = $stmt_fetch->get_result();
            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $g_out_relation = $row['relation'];
            }
            $stmt_fetch->close();
        }

        // Insert or update attendance
        $stmt_insert = $conn->prepare("INSERT INTO attendance 
            (student_id, date, status, guardian_in_photo, guardian_in_relation, guardian_out_photo, guardian_out_relation)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                guardian_in_photo = VALUES(guardian_in_photo),
                guardian_in_relation = VALUES(guardian_in_relation),
                guardian_out_photo = VALUES(guardian_out_photo),
                guardian_out_relation = VALUES(guardian_out_relation)");
        $stmt_insert->bind_param("issssss", $student_id, $date, $status, $g_in_photo, $g_in_relation, $g_out_photo, $g_out_relation);
        $stmt_insert->execute();
        $stmt_insert->close();
    }

    // Reload to reflect updated status
    header("Location: mark_attendance.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: url('images/a.jpg') no-repeat center/cover;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            margin: 20px auto;
            width: 95%;
            max-width: 1200px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .date-note {
            text-align: center;
            color: #555;
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 2px solid #444;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #27ae60;
            color: white;
        }

        /* Status Icons */
        .status-icons {
            display: flex;
            justify-content: center;
            gap: 6px;
            font-size: 18px;
            margin-top: 4px;
        }

        .tick-in { color: green; font-weight: bold; }
        .tick-out { color: red; font-weight: bold; }
        .tick-absent { color: red; font-size: 20px; }

        /* Guardian Photos */
        .guardian-preview {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .guardian-preview label {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 11px;
            cursor: pointer;
            width: 70px;
        }

        .guardian-preview img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #aaa;
        }

        /* Form Inputs */
        select, input[type="text"], input[type="file"] {
            width: 100%;
            padding: 6px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        /* Buttons */
        .btn {
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 10px;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s ease;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .button-group {
            display: flex;
            gap: 12px;
            flex-direction: column;
            margin-top: 20px;
        }

        @media (min-width: 576px) {
            .button-group {
                flex-direction: row;
                justify-content: center;
            }
            .btn {
                width: auto;
                min-width: 180px;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead { display: none; }

            tr {
                margin-bottom: 25px;
                border: 1px solid #ddd;
                padding: 16px;
                border-radius: 12px;
                background-color: #f8f9fa;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            }

            td {
                padding: 10px;
                text-align: left;
                position: relative;
            }

            td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #2c3e50;
                display: block;
                margin-bottom: 4px;
                font-size: 0.9em;
            }

            .student-cell {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 10px;
                padding-bottom: 8px;
                border-bottom: 1px dashed #ccc;
            }

            .student-name {
                font-weight: 600;
                font-size: 1.1em;
            }

            input[type="file"], input[type="text"] {
                margin-top: 2px;
            }

            .guardian-preview {
                justify-content: flex-start;
                gap: 6px;
            }

            .guardian-preview label {
                width: 60px;
                font-size: 10px;
            }

            .guardian-preview img {
                width: 45px;
                height: 45px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Mark Attendance - <?php echo htmlspecialchars(ucfirst($branch_name)); ?></h2>
        <form method="POST" enctype="multipart/form-data">
            <label for="datepicker"><strong>Select Date:</strong></label>
            <input type="text" id="datepicker" name="date" required>
            <div class="date-note">Today: <?php echo date("d-m-Y"); ?></div>

            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Guardian In</th>
                        <th>Guardian Out</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guardian_data as $student_id => $data): ?>
                    <tr>
                        <!-- Student -->
                        <td data-label="Student" class="student-cell">
                            <span class="student-name"><?php echo htmlspecialchars($data['name']); ?></span>
                            <div class="status-icons">
                                <?php if (isset($marked_students[$student_id]) && $marked_students[$student_id]['status'] === 'Absent'): ?>
                                    <span class="tick-absent" title="Absent">❌</span>
                                <?php endif; ?>
                                <?php if (isset($marked_students[$student_id]) && $marked_students[$student_id]['status'] === 'Present' && !empty($marked_students[$student_id]['guardian_in_photo'])): ?>
                                    <span class="tick-in" title="Check In">✔️</span>
                                <?php endif; ?>
                                <?php if (isset($marked_students[$student_id]) && !empty($marked_students[$student_id]['guardian_out_photo'])): ?>
                                    <span class="tick-out" title="Check Out">🚪</span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Status -->
                        <td data-label="Status">
                            <select name="attendance[<?php echo $student_id; ?>]">
                                <option value="Nil" <?php echo (!isset($marked_students[$student_id]) || $marked_students[$student_id]['status'] === 'Nil') ? 'selected' : ''; ?>>Select</option>
                                <option value="Present" <?php echo (isset($marked_students[$student_id]) && $marked_students[$student_id]['status'] === 'Present') ? 'selected' : ''; ?>>Present</option>
                                <option value="Absent" <?php echo (isset($marked_students[$student_id]) && $marked_students[$student_id]['status'] === 'Absent') ? 'selected' : ''; ?>>Absent</option>
                            </select>
                        </td>

                        <!-- Guardian In -->
                        <td data-label="Guardian In">
                            <div class="guardian-preview">
                                <?php foreach ($data['photos'] as $photo): ?>
                                <label>
                                    <input type="radio" name="selected_guardian_in_photo[<?php echo $student_id; ?>]" 
                                           value="<?php echo htmlspecialchars($photo['path']); ?>" 
                                           <?php echo (isset($marked_students[$student_id]) && $marked_students[$student_id]['guardian_in_photo'] === $photo['path']) ? 'checked' : ''; ?>>
                                    <img src="<?php echo htmlspecialchars($photo['path']); ?>" alt="Guardian">
                                    <span><?php echo htmlspecialchars($photo['relation']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <input type="file" name="guardian_in_photo[<?php echo $student_id; ?>]">
                            <input type="text" name="guardian_in_relation[<?php echo $student_id; ?>]" 
                                   placeholder="Relation" 
                                   value="<?php echo htmlspecialchars($marked_students[$student_id]['guardian_in_relation'] ?? ''); ?>">
                        </td>

                        <!-- Guardian Out -->
                        <td data-label="Guardian Out">
                            <div class="guardian-preview">
                                <?php foreach ($data['photos'] as $photo): ?>
                                <label>
                                    <input type="radio" name="selected_guardian_out_photo[<?php echo $student_id; ?>]" 
                                           value="<?php echo htmlspecialchars($photo['path']); ?>" 
                                           <?php echo (isset($marked_students[$student_id]) && $marked_students[$student_id]['guardian_out_photo'] === $photo['path']) ? 'checked' : ''; ?>>
                                    <img src="<?php echo htmlspecialchars($photo['path']); ?>" alt="Guardian">
                                    <span><?php echo htmlspecialchars($photo['relation']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <input type="file" name="guardian_out_photo[<?php echo $student_id; ?>]">
                            <input type="text" name="guardian_out_relation[<?php echo $student_id; ?>]" 
                                   placeholder="Relation" 
                                   value="<?php echo htmlspecialchars($marked_students[$student_id]['guardian_out_relation'] ?? ''); ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Button Group -->
            <div class="button-group">
                <button type="submit" class="btn btn-primary">Submit Attendance</button>
                <a href="dashboard_staff.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </form>
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#datepicker", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d-m-Y",
            defaultDate: "<?php echo $date_today; ?>"
        });
    </script>
</body>
</html>