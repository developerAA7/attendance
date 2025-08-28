<?php
session_start();

// 🔐 Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'db.php';

$successMsg = '';
$errorMsg = '';

// ✅ Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && in_array($_POST['action'], ['Approved', 'Rejected']) && isset($_POST['request_id'])) {
        $request_id = (int)$_POST['request_id'];
        $action = $_POST['action'];

        $stmt = $conn->prepare("UPDATE substitute_guardians SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $request_id);

        if ($stmt->execute()) {
            $successMsg = "Request has been <strong>$action</strong> successfully.";
        } else {
            $errorMsg = "Database error. Please try again.";
        }
        $stmt->close();
    }

    // 🗑️ Handle Delete
    if (isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];

        // Get photo path to delete file
        $stmt = $conn->prepare("SELECT photo_path FROM substitute_guardians WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->bind_result($photo_path);
        if ($stmt->fetch()) {
            // Delete file if exists
            if (!empty($photo_path) && file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
        $stmt->close();

        // Delete from DB
        $stmt = $conn->prepare("DELETE FROM substitute_guardians WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $successMsg = "Request deleted successfully.";
        } else {
            $errorMsg = "Failed to delete request.";
        }
        $stmt->close();
    }
}

// 📂 Fetch ALL substitute guardian requests
$sql = "SELECT 
            sg.id, 
            sg.name AS guardian_name, 
            sg.relation, 
            sg.photo_path, 
            sg.from_date, 
            sg.to_date, 
            sg.created_at,
            sg.status,
            s.name AS student_name,
            s.student_id,
            b.branch_name
        FROM substitute_guardians sg
        JOIN students s ON sg.student_id = s.student_id
        JOIN branches b ON s.branch_id = b.branch_id
        ORDER BY 
            CASE WHEN sg.status = 'Pending' THEN 1
                 WHEN sg.status = 'Approved' THEN 2
                 ELSE 3 END,
            sg.created_at DESC";

$result = $conn->query($sql);
$requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🛡️ Admin: Manage Substitute Guardians</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset & Base */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #444;
            line-height: 1.6;
            padding: 10px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            background: white;
        }

        header {
            background: #e67e22;
            color: white;
            text-align: center;
            padding: 25px 20px;
        }

        header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Alert Messages */
        .msg {
            padding: 16px;
            margin: 0;
            text-align: center;
            font-weight: 600;
            border-radius: 8px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Card List for Mobile */
        .requests-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 16px;
        }

        .request-card {
            background: #fff9f4;
            border-left: 5px solid #e67e22;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .card-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .card-label {
            font-weight: 600;
            color: #e67e22;
            min-width: 90px;
        }

        .card-value {
            flex: 1;
            color: #333;
        }

        .card-value small {
            color: #666;
            font-size: 12px;
        }

        .card-photo {
            display: flex;
            justify-content: center;
            margin: 10px 0;
        }

        .guardian-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #ddd;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
            justify-content: center;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
            display: inline-block;
            margin: 4px 0;
        }

        .status-pending  { background: #f39c12; }
        .status-approved { background: #27ae60; }
        .status-rejected { background: #c0392b; }

        /* Back Button */
        .btn-back {
            display: block;
            text-align: center;
            background: #e67e22;
            color: white;
            padding: 14px;
            margin: 16px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            text-decoration: none;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(230, 126, 34, 0.3);
        }

        .btn-back:hover {
            background: #d35400;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
            font-style: italic;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Image Modal */
        #imageModal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            justify-content: center;
            align-items: center;
        }

        #modalImg {
            max-width: 90%;
            max-height: 90%;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }

        .close-modal {
            position: absolute;
            top: 30px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }

        /* Tablet/Desktop: Hide cards, show table */
        @media (min-width: 769px) {
            .requests-list {
                display: none;
            }

            .requests-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px;
            }

            .requests-table th {
                background: #e67e22;
                color: white;
                padding: 14px;
                text-align: center;
                font-weight: 600;
            }

            .requests-table td {
                padding: 14px;
                border-bottom: 1px solid #eee;
                text-align: center;
            }

            .requests-table tr:nth-child(even) {
                background: #fdf6f0;
            }

            .guardian-img {
                width: 60px;
                height: 60px;
            }
        }

        /* Mobile: Hide table, show cards */
        @media (max-width: 768px) {
            .requests-table {
                display: none;
            }

            .container {
                border-radius: 14px;
            }

            header h1 {
                font-size: 20px;
            }

            .card-row {
                font-size: 14px;
            }

            .btn {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🛡️ Manage Substitute Guardians</h1>
        </header>

        <!-- Success or Error Message -->
        <?php if ($successMsg): ?>
            <p class="msg success"><?= $successMsg ?></p>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <p class="msg error"><?= $errorMsg ?></p>
        <?php endif; ?>

        <!-- 📱 Mobile & Tablet: Card View -->
        <div class="requests-list">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <p>No substitute guardian requests found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <div class="request-card">
                        <div class="card-row">
                            <span class="card-label">Student</span>
                            <span class="card-value">
                                <?= htmlspecialchars($req['student_name']) ?>
                                <br><small>ID: <?= $req['student_id'] ?></small>
                            </span>
                        </div>

                        <div class="card-row">
                            <span class="card-label">Branch</span>
                            <span class="card-value"><?= htmlspecialchars($req['branch_name']) ?></span>
                        </div>

                        <div class="card-row">
                            <span class="card-label">Guardian</span>
                            <span class="card-value"><?= htmlspecialchars($req['guardian_name']) ?></span>
                        </div>

                        <div class="card-row">
                            <span class="card-label">Relation</span>
                            <span class="card-value"><?= htmlspecialchars($req['relation']) ?></span>
                        </div>

                        <div class="card-row">
                            <span class="card-label">Photo</span>
                            <div class="card-photo">
                                <?php if (!empty($req['photo_path']) && file_exists($req['photo_path'])): ?>
                                    <img src="<?= htmlspecialchars($req['photo_path']) ?>"
                                         alt="Guardian Photo"
                                         class="guardian-img"
                                         onclick="openModal('<?= htmlspecialchars($req['photo_path']) ?>')">
                                <?php else: ?>
                                    <small>-</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-row">
                            <span class="card-label">Dates</span>
                            <span class="card-value">
                                <?= date('d M', strtotime($req['from_date'])) ?> →
                                <?= date('d M', strtotime($req['to_date'])) ?>
                            </span>
                        </div>

                        <div class="card-row">
                            <span class="card-label">Requested</span>
                            <span class="card-value"><?= date('d M Y, h:i A', strtotime($req['created_at'])) ?></span>
                        </div>

                        <div class="card-row">
                            <span class="card-label">Status</span>
                            <span class="status-badge status-<?= strtolower($req['status']) ?>">
                                <?= htmlspecialchars($req['status']) ?>
                            </span>
                        </div>

                        <div class="card-actions">
                            <?php if ($req['status'] === 'Pending'): ?>
                                <form method="POST" style="display:inline; width:100%;">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <input type="hidden" name="action" value="Approved">
                                    <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this request?')">Approve</button>
                                </form>
                                <form method="POST" style="display:inline; width:100%;">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <input type="hidden" name="action" value="Rejected">
                                    <button type="submit" class="btn btn-reject" onclick="return confirm('Reject this request?')">Reject</button>
                                </form>
                            <?php endif; ?>

                            <a href="edit_substitute.php?id=<?= $req['id'] ?>" class="btn btn-edit">Edit</a>

                            <form method="POST" style="display:inline; width:100%;" onsubmit="return confirm('Delete permanently?')">
                                <input type="hidden" name="delete_id" value="<?= $req['id'] ?>">
                                <button type="submit" class="btn btn-delete">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 💻 Desktop: Table View -->
        <table class="requests-table">
            <tr>
                <th>Student</th>
                <th>Branch</th>
                <th>Guardian</th>
                <th>Relation</th>
                <th>Photo</th>
                <th>Dates</th>
                <th>Requested</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php if (empty($requests)): ?>
                <tr><td colspan="9" style="text-align:center; padding:30px; color:#7f8c8d;">No requests found.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($req['student_name']) ?><br>
                            <small>ID: <?= $req['student_id'] ?></small>
                        </td>
                        <td><?= htmlspecialchars($req['branch_name']) ?></td>
                        <td><?= htmlspecialchars($req['guardian_name']) ?></td>
                        <td><?= htmlspecialchars($req['relation']) ?></td>
                        <td>
                            <?php if (!empty($req['photo_path']) && file_exists($req['photo_path'])): ?>
                                <img src="<?= htmlspecialchars($req['photo_path']) ?>"
                                     alt="Guardian"
                                     class="guardian-img"
                                     onclick="openModal('<?= htmlspecialchars($req['photo_path']) ?>')">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= date('d M', strtotime($req['from_date'])) ?> →
                            <?= date('d M', strtotime($req['to_date'])) ?>
                        </td>
                        <td><?= date('d M Y, h:i A', strtotime($req['created_at'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower($req['status']) ?>">
                                <?= htmlspecialchars($req['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="card-actions" style="justify-content: center;">
                                <?php if ($req['status'] === 'Pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <input type="hidden" name="action" value="Approved">
                                        <button type="submit" class="btn btn-approve" onclick="return confirm('Approve?')">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <input type="hidden" name="action" value="Rejected">
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Reject?')">Reject</button>
                                    </form>
                                <?php endif; ?>
                                <a href="edit_substitute.php?id=<?= $req['id'] ?>" class="btn btn-edit">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete permanently?')">
                                    <input type="hidden" name="delete_id" value="<?= $req['id'] ?>">
                                    <button type="submit" class="btn btn-delete">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <!-- Back Button -->
        <a href="dashboard_admin.php" class="btn-back">← Back to Dashboard</a>
    </div>

    <!-- Image Modal -->
    <div id="imageModal">
        <span class="close-modal" onclick="document.getElementById('imageModal').style.display='none'">&times;</span>
        <img id="modalImg">
    </div>

    <script>
        function openModal(src) {
            document.getElementById("modalImg").src = src;
            document.getElementById("imageModal").style.display = "flex";
        }
    </script>
</body>
</html>