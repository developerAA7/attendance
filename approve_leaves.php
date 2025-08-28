<?php
session_start();
include 'db.php';

// Only admin can access
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Handle approve/reject/delete
$message = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = $_POST['leave_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'Approved' || $action === 'Rejected') {
        if ($leave_id && in_array($action, ['Approved', 'Rejected'])) {
            $stmt = $conn->prepare("UPDATE leaves SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $action, $leave_id);
            if ($stmt->execute()) {
                $message = "Leave application has been $action successfully.";
            } else {
                $error = "Failed to update status. Please try again.";
            }
            $stmt->close();
        }
    } elseif ($action === 'Delete') {
        if ($leave_id) {
            $stmt = $conn->prepare("DELETE FROM leaves WHERE id = ?");
            $stmt->bind_param("i", $leave_id);
            if ($stmt->execute()) {
                $message = "🗑️ Leave application deleted successfully.";
            } else {
                $error = "❌ Failed to delete leave application.";
            }
            $stmt->close();
        }
    }
}

// Handle Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_leave'])) {
    $leave_id = (int)$_POST['leave_id'];
    $leave_date = $_POST['leave_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $status = $_POST['status'] ?? 'Pending';

    if (empty($leave_date)) {
        $error = "Please select a valid leave date.";
    } else {
        $stmt = $conn->prepare("UPDATE leaves SET leave_date = ?, reason = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $leave_date, $reason, $status, $leave_id);
        if ($stmt->execute()) {
            $message = "✅ Leave application updated successfully!";
        } else {
            $error = "❌ Failed to update leave application.";
        }
        $stmt->close();
    }
}

// Fetch all leave applications
$leaves_query = "
    SELECT l.id, l.student_id, l.parent_username, l.leave_date, l.reason, l.applied_at, l.status,
           s.name AS student_name, b.branch_name
    FROM leaves l
    JOIN students s ON l.student_id = s.student_id
    JOIN branches b ON s.branch_id = b.branch_id
    ORDER BY l.applied_at DESC
";
$leaves_result = $conn->query($leaves_query);
$leaves = [];
if ($leaves_result) {
    while ($row = $leaves_result->fetch_assoc()) {
        $leaves[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>📝 Approve Leaves - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset & Base */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
            padding: 10px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        header {
            background: #0072ff;
            color: white;
            text-align: center;
            padding: 25px 20px;
            border-radius: 16px 16px 0 0;
        }

        header h2 {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
        }

        /* Alert Messages */
        .alert {
            padding: 16px;
            margin: 0;
            text-align: center;
            font-weight: bold;
            border-radius: 8px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Card List (Mobile) */
        .leaves-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 20px;
        }

        .leave-card {
            background: #f9f9ff;
            border-left: 5px solid #0072ff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .card-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .card-label {
            font-weight: 600;
            color: #0072ff;
            min-width: 80px;
        }

        .card-value {
            flex: 1;
            color: #333;
        }

        .card-value small {
            color: #666;
            font-size: 12px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
            display: inline-block;
        }

        .status-pending  { background: #e67e22; }
        .status-approved { background: #27ae60; }
        .status-rejected { background: #c0392b; }

        .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
            justify-content: center;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            flex: 1;
            min-width: 70px;
            text-align: center;
        }

        .btn-approve { background: #27ae60; color: white; }
        .btn-reject  { background: #e74c3c; color: white; }
        .btn-edit    { background: #3498db; color: white; }
        .btn-delete  { background: #95a5a6; color: white; }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Table (Desktop) */
        .leaves-table {
            display: none;
            width: 100%;
            border-collapse: collapse;
            margin: 20px;
        }

        .leaves-table th {
            background: #0072ff;
            color: white;
            padding: 14px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        .leaves-table td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .leaves-table tr:hover {
            background: #f0f7ff;
        }

        /* Back Button */
        .btn-back {
            display: block;
            text-align: center;
            background: #0072ff;
            color: white;
            padding: 14px;
            margin: 20px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            text-decoration: none;
            font-size: 16px;
        }

        .btn-back:hover {
            background: #005bb5;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
            font-style: italic;
        }

        /* Edit Modal */
        #editModal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .modal-content h3 {
            color: #0072ff;
            margin-bottom: 20px;
            text-align: center;
        }

        .modal-content form {
            display: grid;
            gap: 12px;
        }

        .modal-content label {
            font-weight: 600;
            color: #333;
        }

        .modal-content input, .modal-content textarea, .modal-content select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        .modal-content button[type="submit"] {
            margin-top: 15px;
            background: #0072ff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
        }

        /* Responsive */
        @media (min-width: 769px) {
            .leaves-list { display: none; }
            .leaves-table { display: table; }
        }

        @media (max-width: 768px) {
            .leaves-table { display: none; }
            .leaves-list { display: flex; }

            .container {
                margin: 10px;
                border-radius: 12px;
            }

            header h2 {
                font-size: 20px;
            }

            .btn {
                font-size: 14px;
                padding: 10px;
            }

            .card-row {
                font-size: 14px;
            }

            .btn-back {
                margin: 15px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h2>📝 Leave Applications</h2>
        </header>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Mobile: Card View -->
        <div class="leaves-list">
            <?php if (empty($leaves)): ?>
                <div class="empty-state">
                    <p>📄 No leave applications found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($leaves as $l): ?>
                    <div class="leave-card">
                        <div class="card-row">
                            <span class="card-label">Student</span>
                            <span class="card-value">
                                <?= htmlspecialchars($l['student_name']) ?>
                                <br><small>ID: <?= $l['student_id'] ?></small>
                            </span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Branch</span>
                            <span class="card-value"><?= htmlspecialchars(ucfirst($l['branch_name'])) ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Date</span>
                            <span class="card-value"><?= htmlspecialchars($l['leave_date']) ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Applied</span>
                            <span class="card-value"><?= date('d M Y, h:i A', strtotime($l['applied_at'])) ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Reason</span>
                            <span class="card-value" style="word-break: break-word;">
                                <?php
                                $reason = trim($l['reason']);
                                echo !empty($reason)
                                    ? (strlen($reason) > 80
                                        ? htmlspecialchars(substr($reason, 0, 80)) . '...'
                                        : htmlspecialchars($reason)
                                    )
                                    : '—';
                                ?>
                            </span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Status</span>
                            <span class="status-badge status-<?= strtolower(htmlspecialchars($l['status'])) ?>">
                                <?= htmlspecialchars($l['status']) ?>
                            </span>
                        </div>
                        <div class="card-actions">
                            <?php if ($l['status'] === 'Pending'): ?>
                                <form method="POST" style="width:100%;">
                                    <input type="hidden" name="leave_id" value="<?= (int)$l['id'] ?>">
                                    <input type="hidden" name="action" value="Approved">
                                    <button type="submit" class="btn btn-approve">Approve</button>
                                </form>
                                <form method="POST" style="width:100%;">
                                    <input type="hidden" name="leave_id" value="<?= (int)$l['id'] ?>">
                                    <input type="hidden" name="action" value="Rejected">
                                    <button type="submit" class="btn btn-reject">Reject</button>
                                </form>
                            <?php endif; ?>
                            <button type="button" class="btn btn-edit" onclick='openEditModal(
                                <?= (int)$l['id'] ?>,
                                "<?= htmlspecialchars($l['leave_date']) ?>",
                                `<?= addslashes(htmlspecialchars($l['reason'])) ?>`,
                                "<?= htmlspecialchars($l['status']) ?>"
                            )'>
                                ✏️ Edit
                            </button>
                            <form method="POST" style="width:100%;" onsubmit="return confirm('Delete this application?');">
                                <input type="hidden" name="leave_id" value="<?= (int)$l['id'] ?>">
                                <input type="hidden" name="action" value="Delete">
                                <button type="submit" class="btn btn-delete">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Desktop: Table View -->
        <table class="leaves-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>ID</th>
                    <th>Branch</th>
                    <th>Leave Date</th>
                    <th>Applied On</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:30px; color:#7f8c8d;">No applications found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaves as $l): ?>
                        <tr>
                            <td style="font-weight:bold;"><?= htmlspecialchars($l['student_name']) ?></td>
                            <td><?= $l['student_id'] ?></td>
                            <td><?= htmlspecialchars(ucfirst($l['branch_name'])) ?></td>
                            <td><?= htmlspecialchars($l['leave_date']) ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($l['applied_at'])) ?></td>
                            <td title="<?= htmlspecialchars($l['reason'] ?: 'No reason') ?>">
                                <?php
                                $reason = trim($l['reason']);
                                echo !empty($reason)
                                    ? (strlen($reason) > 60
                                        ? htmlspecialchars(substr($reason, 0, 60)) . '...'
                                        : htmlspecialchars($reason)
                                    )
                                    : '—';
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower(htmlspecialchars($l['status'])) ?>">
                                    <?= htmlspecialchars($l['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; flex-wrap:wrap; gap:6px; justify-content:center;">
                                    <?php if ($l['status'] === 'Pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="leave_id" value="<?= (int)$l['id'] ?>">
                                            <button type="submit" name="action" value="Approved" class="btn btn-approve">Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="leave_id" value="<?= (int)$l['id'] ?>">
                                            <button type="submit" name="action" value="Rejected" class="btn btn-reject">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-edit" onclick='openEditModal(
                                        <?= (int)$l['id'] ?>,
                                        "<?= htmlspecialchars($l['leave_date']) ?>",
                                        `<?= addslashes(htmlspecialchars($l['reason'])) ?>`,
                                        "<?= htmlspecialchars($l['status']) ?>"
                                    )'>
                                        ✏️
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                        <input type="hidden" name="leave_id" value="<?= (int)$l['id'] ?>">
                                        <button type="submit" name="action" value="Delete" class="btn btn-delete">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Back Button -->
        <a href="dashboard_admin.php" class="btn-back">← Back to Dashboard</a>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="edit-modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h3>Edit Leave</h3>
            <form method="POST">
                <input type="hidden" name="leave_id" id="edit_leave_id">
                <label>Leave Date</label>
                <input type="date" name="leave_date" id="edit_leave_date" required>
                <label>Reason</label>
                <textarea name="reason" id="edit_reason" placeholder="Edit reason..."></textarea>
                <label>Status</label>
                <select name="status" id="edit_status">
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
                <button type="submit" name="edit_leave">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, date, reason, status) {
            document.getElementById('edit_leave_id').value = id;
            document.getElementById('edit_leave_date').value = date;
            document.getElementById('edit_reason').value = reason;
            document.getElementById('edit_status').value = status;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        };
    </script>
</body>
</html>  
             