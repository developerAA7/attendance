<?php
session_start();
include 'db.php';

// Redirect if not admin
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Fetch all branches
$branch_result = $conn->query("SELECT * FROM branches");
$branches = [];
while ($row = $branch_result->fetch_assoc()) {
    $branches[] = $row;
}

// Get selected branch and mode
$selected_branch_id = $_GET['branch_id'] ?? '';
$show_attendance = isset($_GET['attendance_summary']);

$students_data = []; // Store data as array
$present_count = 0;
$absent_count = 0;

if ($selected_branch_id) {
    if ($show_attendance) {
        // Fetch latest attendance for each student
        $sql = "
            SELECT 
                s.student_id, 
                s.name, 
                s.age, 
                b.branch_name,
                a.status, 
                a.date AS latest_date,
                a.guardian_in_photo,
                a.guardian_out_photo,
                a.guardian_in_relation,
                a.guardian_out_relation
            FROM students s
            JOIN branches b ON s.branch_id = b.branch_id
            LEFT JOIN (
                SELECT a1.student_id, a1.status, a1.date,
                       a1.guardian_in_photo, a1.guardian_out_photo,
                       a1.guardian_in_relation, a1.guardian_out_relation
                FROM attendance a1
                INNER JOIN (
                    SELECT student_id, MAX(date) AS max_date
                    FROM attendance
                    GROUP BY student_id
                ) a2 ON a1.student_id = a2.student_id AND a1.date = a2.max_date
            ) a ON s.student_id = a.student_id
            WHERE s.branch_id = ?
        ";
    } else {
        // Just fetch students
        $sql = "
            SELECT s.student_id, s.name, s.age, b.branch_name 
            FROM students s 
            JOIN branches b ON s.branch_id = b.branch_id 
            WHERE s.branch_id = ?
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_branch_id);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result) {
        $students_data = $result->fetch_all(MYSQLI_ASSOC);

        // Count present/absent only in attendance mode
        if ($show_attendance) {
            foreach ($students_data as $row) {
                if ($row['status'] === 'Present') {
                    $present_count++;
                } elseif ($row['status'] === 'Absent') {
                    $absent_count++;
                }
            }
        }
    } else {
        // Fallback (unlikely if mysqlnd enabled)
        $stmt->bind_result($student_id, $name, $age, $branch_name);
        while ($stmt->fetch()) {
            $students_data[] = [
                'student_id' => $student_id,
                'name' => $name,
                'age' => $age,
                'branch_name' => $branch_name,
                'status' => null,
                'latest_date' => null,
                'guardian_in_photo' => null,
                'guardian_out_photo' => null,
                'guardian_in_relation' => null,
                'guardian_out_relation' => null
            ];
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-image: url('images/admin1.jpg'); /* Local background image */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #333;
            min-height: 100vh;
            padding: 0;
            position: relative;
        }

        /* Dark overlay for readability */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* 60% black overlay */
            z-index: -1;
        }

        .dashboard {
            max-width: 1000px;
            margin: 40px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 18px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow-x: auto;
        }

        h2 {
            text-align: center;
            color: #fff;
            margin: 0 0 20px 0;
            font-size: 2em;
            font-weight: bold;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }

        label {
            font-weight: bold;
            color: #fff;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        select {
            padding: 14px;
            font-size: 16px;
            border-radius: 10px;
            border: 1px solid #ccc;
            width: 100%;
            max-width: 340px;
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: center;
            margin-top: 15px;
        }

        .btn {
            padding: 12px 20px;
            font-size: 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            color: white;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 140px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-3px);
            opacity: 0.95;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .view-btn { 
            background: linear-gradient(135deg, #0072ff, #005bb5); 
        }
        .attendance-btn { 
            background: linear-gradient(135deg, #28a745, #218838); 
        }
        .approve-leaves-btn { 
            background: linear-gradient(135deg, #ffc107, #e0a800); 
            color: #000; 
        }
        .approve-guardian-btn { 
            background: linear-gradient(135deg, #d35400, #c0392b); 
            color: white; 
        }
        .back-btn { 
            background: linear-gradient(135deg, #6c757d, #545b62); 
        }

        h3 {
            text-align: center;
            color: #2c3e50;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }

        th, td {
            padding: 14px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        th {
            background: linear-gradient(135deg, #0072ff, #005bb5);
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 15px;
            letter-spacing: 0.5px;
        }

        td {
            background-color: #fdfdfd;
            color: #333;
        }

        .status-present {
            color: #28a745;
            font-weight: bold;
        }

        .status-absent {
            color: #dc3545;
            font-weight: bold;
        }

        img.guardian-img {
            width: 60px;
            border-radius: 8px;
            border: 2px solid #eee;
            margin: 4px 0;
        }

        .relation {
            font-weight: bold;
            color: #17a2b8;
            font-size: 0.9em;
        }

        .btn-action {
            display: inline-block;
            padding: 6px 12px;
            margin: 2px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            color: white;
            transition: 0.3s;
        }

        .edit-btn { background-color: #17a2b8; }
        .delete-btn { background-color: #dc3545; }
        .att-btn { background-color: #ffc107; color: #000; }

        .btn-action:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }

        .summary-block {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 25px 0;
            backdrop-filter: blur(10px);
        }

        .chart-container {
            width: 100%;
            max-width: 300px;
            margin-bottom: 15px;
        }

        .summary {
            display: flex;
            justify-content: center;
            gap: 25px;
            font-size: 1.1em;
            font-weight: bold;
        }

        .summary div {
            padding: 8px 16px;
            border-radius: 8px;
            color: white;
            font-size: 1em;
        }

        .present { background: #28a745; }
        .absent { background: #dc3545; }

        .no-students {
            text-align: center;
            color: #e74c3c;
            font-size: 1.1em;
            font-weight: bold;
            margin: 20px 0;
        }

        .back-link {
            display: block;
            width: 200px;
            margin: 30px auto 0;
            text-align: center;
            padding: 12px;
            background: linear-gradient(135deg, #6c757d, #545b62);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard {
                margin: 20px 15px;
                padding: 20px;
                border-radius: 14px;
            }

            h2 {
                font-size: 1.6em;
            }

            select, .btn {
                font-size: 15px;
                padding: 12px;
            }

            .btn-group {
                gap: 10px;
            }

            .btn {
                min-width: 120px;
                font-size: 14px;
            }

            h3 {
                font-size: 1.3em;
            }

            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead {
                display: none;
            }

            tr {
                margin-bottom: 20px;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                padding: 12px;
            }

            td {
                text-align: right;
                padding-left: 60%;
                position: relative;
                border: none;
                border-bottom: 1px solid #ddd;
            }

            td::before {
                content: attr(data-label);
                position: absolute;
                left: 12px;
                top: 12px;
                font-weight: bold;
                color: #0072ff;
                white-space: nowrap;
            }

            img.guardian-img {
                width: 70px;
            }

            .summary {
                flex-direction: column;
                gap: 12px;
            }

            .chart-container {
                max-width: 250px;
            }

            .back-link {
                width: 180px;
                padding: 10px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 1.4em;
            }

            .btn {
                font-size: 14px;
                padding: 10px;
            }

            .summary {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h2 style="color: black;">📋 Admin Dashboard</h2>

        <form method="GET">
            <label for="branch_id">Select Branch</label>
            <select name="branch_id" id="branch_id" required>
                <option value="">-- Select Branch --</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= htmlspecialchars($branch['branch_id']); ?>" 
                        <?= $selected_branch_id == $branch['branch_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($branch['branch_name'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="btn-group">
                <button type="submit" name="view" class="btn view-btn">View Students</button>
                <button type="submit" name="attendance_summary" class="btn attendance-btn">Attendance Summary</button>
                <a href="approve_leaves.php" class="btn approve-leaves-btn">Approve Leaves</a>
                <a href="admin_approve_substitute.php" class="btn approve-guardian-btn">🛡️ Approve Guardians</a>
            </div>
        </form>

        <!-- Attendance Chart -->
        <?php if ($show_attendance && !empty($students_data)): ?>
            <div class="summary-block">
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
                <div class="summary">
                    <div class="present">Present: <?= $present_count; ?></div>
                    <div class="absent">Absent: <?= $absent_count; ?></div>
                </div>
            </div>

            <script>
                const ctx = document.getElementById('attendanceChart').getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['Present', 'Absent'],
                        datasets: [{
                            data: [<?= $present_count; ?>, <?= $absent_count; ?>],
                            backgroundColor: ['#28a745', '#dc3545']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            </script>
        <?php endif; ?>

        <!-- Student Table -->
        <?php if (!empty($students_data)): ?>
            <h3><?= $show_attendance ? "Latest Attendance Summary" : "Student List" ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Branch</th>
                        <?php if ($show_attendance): ?>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                        <?php else: ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students_data as $row): ?>
                        <tr>
                            <td data-label="ID"><?= htmlspecialchars($row['student_id']); ?></td>
                            <td data-label="Name"><?= htmlspecialchars($row['name']); ?></td>
                            <td data-label="Age"><?= htmlspecialchars($row['age']); ?></td>
                            <td data-label="Branch"><?= htmlspecialchars(ucfirst($row['branch_name'])); ?></td>

                            <?php if ($show_attendance): ?>
                                <td data-label="Date"><?= $row['latest_date'] ? htmlspecialchars($row['latest_date']) : '-' ?></td>
                                <td data-label="Status">
                                    <?php if ($row['status'] === 'Present'): ?>
                                        <span class="status-present">Present</span>
                                    <?php elseif ($row['status'] === 'Absent'): ?>
                                        <span class="status-absent">Absent</span>
                                    <?php else: ?>
                                        <span style="color:#999;">No Record</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Clock In -->
                                <td data-label="Clock In">
                                    <?php if (!empty($row['guardian_in_photo']) && file_exists($row['guardian_in_photo'])): ?>
                                        <img src="<?= htmlspecialchars($row['guardian_in_photo']); ?>" alt="In" class="guardian-img">
                                        <div class="relation"><?= htmlspecialchars($row['guardian_in_relation'] ?? 'Guardian'); ?></div>
                                    <?php else: ?>
                                        <span style="color:gray;">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Clock Out -->
                                <td data-label="Clock Out">
                                    <?php if (!empty($row['guardian_out_photo']) && file_exists($row['guardian_out_photo'])): ?>
                                        <img src="<?= htmlspecialchars($row['guardian_out_photo']); ?>" alt="Out" class="guardian-img">
                                        <div class="relation"><?= htmlspecialchars($row['guardian_out_relation'] ?? 'Guardian'); ?></div>
                                    <?php else: ?>
                                        <span style="color:gray;">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php else: ?>
                                <td data-label="Actions">
                                    <a href="edit_student.php?id=<?= urlencode($row['student_id']); ?>" class="btn-action edit-btn">Edit</a>
                                    <a href="delete_student.php?id=<?= urlencode($row['student_id']); ?>" class="btn-action delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
                                    <a href="view_attendance.php?id=<?= urlencode($row['student_id']); ?>" class="btn-action att-btn">Attendance</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (isset($_GET['view']) || $show_attendance): ?>
            <p class="no-students">❌ No students found in this branch.</p>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Back to Home</a>
    </div>
</body>
</html>