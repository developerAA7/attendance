<?php
session_start();
include 'db.php';

// Check if parent is logged in
if (!isset($_SESSION['parent']) || !isset($_SESSION['student_id'])) {
    header("Location: parent_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student details
$sql = "SELECT s.student_id, s.name, s.age, b.branch_name
        FROM students s
        JOIN branches b ON s.branch_id = b.branch_id
        WHERE s.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch full attendance history
$sql = "SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count present/absent
$present_count = 0;
$absent_count = 0;
foreach ($attendance as $row) {
    if ($row['status'] === 'Present') $present_count++;
    if ($row['status'] === 'Absent') $absent_count++;
}

// Fetch substitute guardian requests
$sql = "SELECT * FROM substitute_guardians WHERE student_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$substitutes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            background: #f5f5f5;
        }

        /* Background Image with Blur */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: url('images/dashboard-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            filter: blur(8px);
            transform: scale(1.1);
            z-index: -2;
        }

        .overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(255,255,255,0.4);
            z-index: -1;
        }

        .dashboard {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            max-width: 1000px;
            width: 100%;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            color: #222;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
            color: #0072ff;
        }
        h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #444;
            font-size: 16px;
        }

        .summary {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 18px;
        }
        .present { color: green; }
        .absent { color: red; }

        /* Buttons at Top */
        .btn-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 160px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .apply-btn {
            background: #28a745;
            color: white;
        }
        .apply-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .add-guardian-btn {
            background: #007bff;
            color: white;
        }
        .add-guardian-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .logout-btn {
            background: #dc3545;
            color: white;
        }
        .logout-btn:hover {
            background: #a71d2a;
            transform: translateY(-2px);
        }

        /* Filter Dropdown */
        .filter-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .filter-container select {
            padding: 10px 15px;
            font-size: 16px;
            border: 2px solid #0072ff;
            border-radius: 8px;
            background: white;
            color: #333;
            outline: none;
            width: 200px;
        }
        .filter-container select:focus {
            border-color: #0050b3;
            box-shadow: 0 0 5px rgba(0,114,255,0.3);
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            margin-top: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        th {
            background: #0072ff;
            color: #fff;
        }
        td {
            background: #f9f9f9;
            color: #222;
        }
        tr:nth-child(even) td {
            background: #f1f1f1;
        }

        /* Guardian Image */
        img.guardian-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ccc;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        img.guardian-img:hover {
            transform: scale(1.1);
        }

        .relation {
            font-size: 12px;
            font-weight: bold;
            color: #0072ff;
            display: block;
            margin-top: 5px;
        }

        /* Status Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            display: inline-block;
        }
        .badge-pending { background: #ffc107; }
        .badge-approved { background: #28a745; }
        .badge-rejected { background: #dc3545; }

        /* Image Modal */
        .img-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px;
        }

        .img-modal-content {
            max-width: 90%;
            max-height: 90vh;
            border-radius: 8px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease-in-out;
        }

        .img-close {
            color: white;
            font-size: 40px;
            font-weight: bold;
            position: absolute;
            top: 20px;
            right: 30px;
            cursor: pointer;
            transition: 0.3s;
            background: rgba(0,0,0,0.5);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }

        .img-close:hover {
            color: #bbb;
            transform: scale(1.1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .summary { flex-direction: column; gap: 10px; text-align: center; }
            .btn-container { flex-direction: column; align-items: center; }
            .filter-container { margin-bottom: 15px; }
            .filter-container select { width: 180px; font-size: 15px; }
            table, th, td { font-size: 14px; }
            img.guardian-img { width: 50px; height: 50px; }
            .img-modal-content { max-width: 95%; }
        }
        @media (max-width: 480px) {
            h2, h3 { font-size: 18px; }
            .dashboard { padding: 15px; }
            .btn { font-size: 15px; padding: 10px 18px; }
            .summary { font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="dashboard">
        <h2>Welcome <?= htmlspecialchars($_SESSION['parent']); ?></h2>
        <h3>Tracking Child: <?= htmlspecialchars($student['name']); ?> (Branch: <?= htmlspecialchars($student['branch_name']); ?>)</h3>

        <!-- Summary -->
        <div class="summary">
            <div class="present">✅ Total Present: <?= $present_count; ?></div>
            <div class="absent">❌ Total Absent: <?= $absent_count; ?></div>
        </div>

        <!-- Buttons -->
        <div class="btn-container">
            <a href="apply_leave.php" class="btn apply-btn">📝 Apply Leave</a>
            <a href="add_substitute_guardian.php" class="btn add-guardian-btn">👤 Add Substitute Guardian</a>
            <a href="parent_logout.php" class="btn logout-btn">🔚 Logout</a>
        </div>

        <!-- Filter & Attendance -->
        <div class="filter-container">
            <select id="statusFilter">
                <option value="all">All Records</option>
                <option value="Present">Only Present</option>
                <option value="Absent">Only Absent</option>
            </select>
        </div>

        <h3>📅 Attendance History</h3>
        <div class="table-container">
            <table id="attendanceTable">
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Clock In (Guardian)</th>
                    <th>Clock Out (Guardian)</th>
                </tr>
                <?php foreach ($attendance as $row): ?>
                    <tr class="attendance-row" data-status="<?= htmlspecialchars($row['status']); ?>">
                        <td><?= htmlspecialchars($row['date']); ?></td>
                        <td>
                            <?php if ($row['status'] === 'Present'): ?>
                                <span class="present">Present</span>
                            <?php elseif ($row['status'] === 'Absent'): ?>
                                <span class="absent">Absent</span>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['guardian_in_photo']) && file_exists($row['guardian_in_photo'])): ?>
                                <img src="<?= htmlspecialchars($row['guardian_in_photo']); ?>" 
                                     class="guardian-img" 
                                     onclick="openImageModal('<?= htmlspecialchars($row['guardian_in_photo']); ?>')">
                                <span class="relation"><?= htmlspecialchars($row['guardian_in_relation'] ?? 'Guardian'); ?></span>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['guardian_out_photo']) && file_exists($row['guardian_out_photo'])): ?>
                                <img src="<?= htmlspecialchars($row['guardian_out_photo']); ?>" 
                                     class="guardian-img" 
                                     onclick="openImageModal('<?= htmlspecialchars($row['guardian_out_photo']); ?>')">
                                <span class="relation"><?= htmlspecialchars($row['guardian_out_relation'] ?? 'Guardian'); ?></span>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imageModal" class="img-modal">
        <span class="img-close" onclick="closeImageModal()">&times;</span>
        <img class="img-modal-content" id="modalImage">
    </div>

    <script>
        function openImageModal(src) {
            const modal = document.getElementById("imageModal");
            const img = document.getElementById("modalImage");
            img.src = src;
            modal.style.display = "flex"; // Use flex to center
            document.body.style.overflow = "hidden"; // Prevent background scroll
        }

        function closeImageModal() {
            document.getElementById("imageModal").style.display = "none";
            document.body.style.overflow = "auto"; // Re-enable scroll
        }

        // Close modal on click outside image
        window.onclick = function(event) {
            const modal = document.getElementById("imageModal");
            if (event.target === modal) {
                closeImageModal();
            }
        };

        // Close modal on Escape key
        document.addEventListener("keydown", function(event) {
            if (event.key === "Escape") {
                closeImageModal();
            }
        });

        // Filter Attendance
        document.getElementById("statusFilter").addEventListener("change", function () {
            const filter = this.value;
            const rows = document.querySelectorAll(".attendance-row");
            rows.forEach(row => {
                const status = row.getAttribute("data-status");
                if (filter === "all" || status === filter) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>