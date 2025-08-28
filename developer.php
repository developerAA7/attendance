<?php
include 'db.php';

// Delete specific student
if (isset($_GET['delete_student'])) {
    $id = (int)$_GET['delete_student'];
    $conn->query("DELETE FROM students WHERE student_id = $id");
}

// Delete specific attendance record by student_id and date
if (isset($_GET['delete_attendance_student_id']) && isset($_GET['delete_attendance_date'])) {
    $sid = (int)$_GET['delete_attendance_student_id'];
    $date = $_GET['delete_attendance_date'];
    $stmt = $conn->prepare("DELETE FROM attendance WHERE student_id = ? AND date = ?");
    $stmt->bind_param("is", $sid, $date);
    $stmt->execute();
    $stmt->close();
}

// Delete specific leave application
if (isset($_GET['delete_leave_id'])) {
    $leave_id = (int)$_GET['delete_leave_id'];
    $conn->query("DELETE FROM leaves WHERE id = $leave_id");
}

// ✅ Delete specific substitute guardian (corrected table name)
if (isset($_GET['delete_substitute_id'])) {
    $sub_id = (int)$_GET['delete_substitute_id'];
    $conn->query("DELETE FROM substitute_guardians WHERE id = $sub_id");
}

// Delete all students
if (isset($_GET['delete_all_students'])) {
    $conn->query("DELETE FROM students");
}

// Delete all attendance
if (isset($_GET['delete_all_attendance'])) {
    $conn->query("DELETE FROM attendance");
}

// Delete all leaves
if (isset($_GET['delete_all_leaves'])) {
    $conn->query("DELETE FROM leaves");
}

// ✅ Delete all substitute guardians (corrected table name)
if (isset($_GET['delete_all_substitutes'])) {
    $conn->query("DELETE FROM substitute_guardians");
}

// Fetch students
$students = $conn->query("
    SELECT s.student_id, s.name, s.age, b.branch_name
    FROM students s
    JOIN branches b ON s.branch_id = b.branch_id
");

// Fetch attendance records
$attendances = $conn->query("
    SELECT 
        a.student_id,
        a.date,
        a.status,
        a.guardian_in_photo,
        a.guardian_in_relation,
        a.guardian_out_photo,
        a.guardian_out_relation,
        s.name AS student_name,
        b.branch_name
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    JOIN branches b ON s.branch_id = b.branch_id
    ORDER BY a.date DESC, s.name
");

// Fetch leave applications
$leaves = $conn->query("
    SELECT 
        l.id,
        l.student_id,
        l.parent_username,
        l.leave_date,
        l.reason,
        l.status,
        l.applied_at,
        s.name AS student_name,
        b.branch_name
    FROM leaves l
    JOIN students s ON l.student_id = s.student_id
    JOIN branches b ON s.branch_id = b.branch_id
    ORDER BY l.applied_at DESC
");

// ✅ Fetch substitute guardians (corrected table name)
$substitutes = $conn->query("
    SELECT 
        sg.id,
        sg.student_id,
        sg.name AS sub_name,
        sg.relation,
        sg.photo_path,
        sg.from_date,
        sg.to_date,
        sg.status,
        sg.created_at
    FROM substitute_guardians sg
    ORDER BY sg.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Developer Panel - Full Data</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            margin-top: 10px;
            color: #333;
        }
        .table-container {
            margin-bottom: 40px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #0072ff;
            color: white;
        }
        td img {
            max-width: 80px;
            cursor: pointer;
            border-radius: 5px;
            transition: transform 0.3s ease;
        }
        td img:hover {
            transform: scale(1.1);
        }
        a.btn {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 14px;
            text-decoration: none;
            margin: 0 4px;
            display: inline-block;
        }
        .edit-btn {
            background-color: #28a745;
            color: white;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .back-btn {
            display: block;
            text-align: center;
            margin-top: 30px;
            text-decoration: none;
            font-weight: bold;
            color: #0072ff;
        }

        /* Status badges */
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }

        /* Guardian cell styling */
        .guardian-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .relation {
            font-size: 12px;
            font-weight: bold;
            color: #17a2b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead { display: none; }
            tr {
                margin-bottom: 15px;
                border: 1px solid #ccc;
                border-radius: 8px;
                padding: 10px;
                background-color: #fff;
            }
            td {
                text-align: left;
                padding: 8px 10px;
                border: none;
                position: relative;
            }
            td::before {
                content: attr(data-label);
                font-weight: bold;
                display: block;
                color: #0072ff;
                margin-bottom: 4px;
            }
            td img {
                max-width: 100px;
            }
            a.btn {
                margin-top: 5px;
            }
        }

        /* Modal styles for image preview */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            padding-top: 60px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }
        .close {
            position: absolute;
            top: 30px;
            right: 40px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #ccc;
        }
    </style>
</head>
<body>

<h2>🔧 Developer Panel - Manage Students</h2>
<div class="table-container">
    <div style="text-align:right; margin-bottom: 10px;">
        <a class="btn delete-btn" href="?delete_all_students=1" onclick="return confirm('Are you sure you want to delete ALL students?')">🗑️ Delete All Students</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Age</th>
                <th>Branch</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $students->fetch_assoc()): ?>
                <tr>
                    <td data-label="Student ID"><?= $row['student_id']; ?></td>
                    <td data-label="Name"><?= htmlspecialchars($row['name']); ?></td>
                    <td data-label="Age"><?= $row['age']; ?></td>
                    <td data-label="Branch"><?= htmlspecialchars($row['branch_name']); ?></td>
                    <td data-label="Actions">
                        <a class="btn edit-btn" href="edit_student.php?id=<?= $row['student_id']; ?>">✏️ Edit</a>
                        <a class="btn delete-btn" href="?delete_student=<?= $row['student_id']; ?>" onclick="return confirm('Delete this student?')">🗑️ Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<h2>📊 Developer Panel - Manage Attendance (Clock In & Out)</h2>
<div class="table-container">
    <div style="text-align:right; margin-bottom: 10px;">
        <a class="btn delete-btn" href="?delete_all_attendance=1" onclick="return confirm('Are you sure you want to delete ALL attendance records?')">🗑️ Delete All Attendance</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Branch</th>
                <th>Status</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $attendances->fetch_assoc()): ?>
                <tr>
                    <td data-label="Date"><?= htmlspecialchars($row['date']); ?></td>
                    <td data-label="Student ID"><?= $row['student_id']; ?></td>
                    <td data-label="Student Name"><?= htmlspecialchars($row['student_name']); ?></td>
                    <td data-label="Branch"><?= htmlspecialchars($row['branch_name']); ?></td>
                    <td data-label="Status">
                        <span style="color: <?= $row['status'] === 'Present' ? 'green' : 'red'; ?>; font-weight: bold;">
                            <?= htmlspecialchars($row['status']); ?>
                        </span>
                    </td>

                    <!-- Clock In -->
                    <td data-label="Clock In">
                        <?php if (!empty($row['guardian_in_photo']) && file_exists($row['guardian_in_photo'])): ?>
                            <div class="guardian-cell">
                                <img src="<?= htmlspecialchars($row['guardian_in_photo']); ?>" 
                                     alt="Clock In" 
                                     onclick="openModal('<?= htmlspecialchars($row['guardian_in_photo']); ?>')">
                                <span class="relation"><?= htmlspecialchars($row['guardian_in_relation'] ?? 'Guardian'); ?></span>
                            </div>
                        <?php else: ?>
                            <span style="color:gray;">No Photo</span>
                        <?php endif; ?>
                    </td>

                    <!-- Clock Out -->
                    <td data-label="Clock Out">
                        <?php if (!empty($row['guardian_out_photo']) && file_exists($row['guardian_out_photo'])): ?>
                            <div class="guardian-cell">
                                <img src="<?= htmlspecialchars($row['guardian_out_photo']); ?>" 
                                     alt="Clock Out" 
                                     onclick="openModal('<?= htmlspecialchars($row['guardian_out_photo']); ?>')">
                                <span class="relation"><?= htmlspecialchars($row['guardian_out_relation'] ?? 'Guardian'); ?></span>
                            </div>
                        <?php else: ?>
                            <span style="color:gray;">No Photo</span>
                        <?php endif; ?>
                    </td>

                    <td data-label="Actions">
                        <a class="btn edit-btn" href="edit_attendance.php?student_id=<?= $row['student_id']; ?>&date=<?= urlencode($row['date']); ?>">✏️ Edit</a>
                        <a class="btn delete-btn" href="?delete_attendance_student_id=<?= $row['student_id']; ?>&delete_attendance_date=<?= urlencode($row['date']); ?>" onclick="return confirm('Delete this attendance record?')">🗑️ Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- NEW: Leaves Table -->
<h2>📝 Developer Panel - Manage Leave Applications</h2>
<div class="table-container">
    <div style="text-align:right; margin-bottom: 10px;">
        <a class="btn delete-btn" href="?delete_all_leaves=1" onclick="return confirm('Are you sure you want to delete ALL leave applications?')">🗑️ Delete All Leaves</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Branch</th>
                <th>Parent</th>
                <th>Leave Date</th>
                <th>Applied On</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($leaves->num_rows == 0): ?>
                <tr>
                    <td colspan="10" style="text-align:center; color: #777;">No leave applications found.</td>
                </tr>
            <?php else: ?>
                <?php while ($row = $leaves->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?= $row['id']; ?></td>
                        <td data-label="Student ID"><?= $row['student_id']; ?></td>
                        <td data-label="Student Name"><?= htmlspecialchars($row['student_name']); ?></td>
                        <td data-label="Branch"><?= htmlspecialchars($row['branch_name']); ?></td>
                        <td data-label="Parent"><?= htmlspecialchars($row['parent_username']); ?></td>
                        <td data-label="Leave Date"><?= htmlspecialchars($row['leave_date']); ?></td>
                        <td data-label="Applied On"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($row['applied_at']))); ?></td>
                        <td data-label="Reason" title="<?= htmlspecialchars($row['reason']); ?>">
                            <?= strlen($row['reason']) > 50 ? substr(htmlspecialchars($row['reason']), 0, 50) . '...' : htmlspecialchars($row['reason'] ?: '—'); ?>
                        </td>
                        <td data-label="Status">
                            <span class="status-<?= strtolower($row['status']) ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td data-label="Actions">
                            <a class="btn delete-btn" href="?delete_leave_id=<?= $row['id']; ?>" onclick="return confirm('Delete this leave application?')">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ✅ Updated: Substitute Guardians Table -->
<h2>👥 Developer Panel - Manage Substitute Guardians</h2>
<div class="table-container">
    <div style="text-align:right; margin-bottom: 10px;">
        <a class="btn delete-btn" href="?delete_all_substitutes=1" onclick="return confirm('Are you sure you want to delete ALL substitute guardian records?')">🗑️ Delete All</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Relation</th>
                <th>Photo</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($substitutes->num_rows == 0): ?>
                <tr>
                    <td colspan="10" style="text-align:center; color: #777;">No substitute guardian records found.</td>
                </tr>
            <?php else: ?>
                <?php while ($row = $substitutes->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?= $row['id']; ?></td>
                        <td data-label="Student ID"><?= $row['student_id']; ?></td>
                        <td data-label="Name"><?= htmlspecialchars($row['sub_name']); ?></td>
                        <td data-label="Relation"><?= htmlspecialchars($row['relation']); ?></td>
                        <td data-label="Photo">
                            <?php if (!empty($row['photo_path']) && file_exists($row['photo_path'])): ?>
                                <img src="<?= htmlspecialchars($row['photo_path']); ?>" 
                                     alt="Substitute Guardian Photo" 
                                     onclick="openModal('<?= htmlspecialchars($row['photo_path']); ?>')">
                            <?php else: ?>
                                <span style="color:gray;">No Photo</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="From Date"><?= htmlspecialchars($row['from_date']); ?></td>
                        <td data-label="To Date"><?= htmlspecialchars($row['to_date']); ?></td>
                        <td data-label="Status">
                            <span class="status-<?= strtolower($row['status']) ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td data-label="Created At"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($row['created_at']))); ?></td>
                        <td data-label="Actions">
                            <a class="btn delete-btn" href="?delete_substitute_id=<?= $row['id']; ?>" onclick="return confirm('Delete this substitute guardian record?')">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal for enlarged image -->
<div id="imageModal" class="modal" onclick="closeModal()">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<a href="index.php" class="back-btn">← Back to Index</a>

<script>
    function openModal(src) {
        document.getElementById("modalImage").src = src;
        document.getElementById("imageModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("imageModal").style.display = "none";
    }

    // Close modal when clicking on X
    document.querySelector(".close").addEventListener("click", closeModal);

    // Close modal when clicking outside image
    document.getElementById("imageModal").addEventListener("click", function(e) {
        if (e.target === this) closeModal();
    });
</script>

</body>
</html>