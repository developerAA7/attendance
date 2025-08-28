<?php
session_start();
include 'db.php';

// Check if parent is logged in
if (!isset($_SESSION['parent']) || !isset($_SESSION['student_id'])) {
    header("Location: parent_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$username = $_SESSION['parent'];

// Handle Leave Application
$leave_success = $leave_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $leave_date = $_POST['leave_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($leave_date)) {
        $leave_error = "Please select a date for leave.";
    } elseif (strtotime($leave_date) < strtotime('today')) {
        $leave_error = "You cannot apply for a past date.";
    } else {
        // Prevent duplicate leave on same date
        $check = $conn->prepare("SELECT id FROM leaves WHERE student_id = ? AND leave_date = ?");
        $check->bind_param("is", $student_id, $leave_date);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $leave_error = "Leave already applied for this date.";
        } else {
            $stmt = $conn->prepare("INSERT INTO leaves (student_id, parent_username, leave_date, reason) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $student_id, $username, $leave_date, $reason);
            if ($stmt->execute()) {
                $leave_success = "✅ Leave application submitted successfully!";
            } else {
                $leave_error = "❌ Failed to submit leave. Try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Fetch leave history
$leaves = [];
$leave_sql = "SELECT * FROM leaves WHERE student_id = ? ORDER BY leave_date DESC";
$leave_stmt = $conn->prepare($leave_sql);
$leave_stmt->bind_param("i", $student_id);
$leave_stmt->execute();
$leaves_result = $leave_stmt->get_result();
if ($leaves_result) {
    while ($row = $leaves_result->fetch_assoc()) {
        $leaves[] = $row;
    }
}
$leave_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply Leave</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 10px;
            background: #f0f2f5;
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
            background: rgba(255, 255, 255, 0.4);
            z-index: -1;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            color: #222;
        }

        header {
            background: linear-gradient(135deg, #0072ff, #005bb5);
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 1.5em;
            font-weight: bold;
        }

        .form-section, .history-section {
            padding: 25px;
        }

        .alert {
            padding: 14px;
            margin: 15px 0;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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

        form {
            display: grid;
            gap: 16px;
        }

        label {
            font-weight: bold;
            color: #333;
            text-align: left;
        }

        input[type="date"],
        textarea {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            width: 100%;
            background: white;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        button {
            background: linear-gradient(135deg, #0072ff, #005bb5);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        button:hover {
            background: linear-gradient(135deg, #005bb5, #004494);
            transform: translateY(-2px);
        }

        /* Leave History */
        .history-section {
            border-top: 1px solid #eee;
        }

        .history-section h3 {
            color: #0072ff;
            margin-bottom: 15px;
            font-size: 1.3em;
            text-align: center;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 1em;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f1f1f1;
            color: #333;
            font-weight: bold;
        }

        td {
            background: #fafafa;
            color: #333;
        }

        /* Status Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .badge-pending {
            background: #ffc107;
            color: #111;
        }

        .badge-approved {
            background: #28a745;
            color: white;
        }

        .badge-rejected {
            background: #dc3545;
            color: white;
        }

        /* Back Link */
        .back-link {
            display: block;
            text-align: center;
            margin: 25px 0 15px;
            color: #0072ff;
            font-weight: bold;
            text-decoration: none;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: #e3f2fd;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .container {
                border-radius: 16px;
                margin: 10px;
            }

            header {
                font-size: 1.3em;
                padding: 18px;
            }

            .form-section, .history-section {
                padding: 20px 15px;
            }

            input, textarea, button {
                font-size: 15px;
            }

            table, th, td {
                font-size: 14px;
            }

            .back-link {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="container">
        <header>📝 Apply for Leave</header>

        <div class="form-section">
            <!-- Success/Error Messages -->
            <?php if ($leave_success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($leave_success) ?></div>
            <?php elseif ($leave_error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($leave_error) ?></div>
            <?php endif; ?>

            <!-- Leave Form -->
            <?php if (!$leave_success): ?>
                <form method="POST">
                    <label for="leave_date">Leave Date</label>
                    <input type="date" name="leave_date" id="leave_date" required min="<?= date('Y-m-d') ?>">

                    <label for="reason">Reason (Optional)</label>
                    <textarea name="reason" id="reason" placeholder="Describe the reason for leave..."></textarea>

                    <button type="submit" name="apply_leave">Submit Leave Application</button>
                </form>
            <?php endif; ?>

            
        </div>

        <!-- Leave History -->
        <div class="history-section">
            <h3>📋 Your Leave Applications</h3>
            <?php if (empty($leaves)): ?>
                <p style="text-align: center; color: #777; font-style: italic;">No leave applications found.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <tr>
                            <th>Date</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                        <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['leave_date']) ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($leave['applied_at']))) ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($leave['status']) ?>">
                                        <?= htmlspecialchars($leave['status']) ?>
                                    </span>
                                </td>
                                <td title="<?= htmlspecialchars($leave['reason'] ?: 'No reason') ?>">
                                    <?= strlen($leave['reason']) > 30 
                                        ? substr(htmlspecialchars($leave['reason']), 0, 30) . '...' 
                                        : htmlspecialchars($leave['reason'] ?: '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <a href="parent_dashboard.php" class="back-link"><button>← Back to Dashboard</button></a>
    </div>
</body>
</html>