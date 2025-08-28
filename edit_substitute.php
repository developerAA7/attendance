<?php
session_start();
include 'db.php';

// 🔐 Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// 🔍 Get request ID
$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid request: No ID provided.");
}

// Fetch the current request
$stmt = $conn->prepare("SELECT * FROM substitute_guardians WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$req = $result->fetch_assoc();
$stmt->close();

if (!$req) {
    die("Request not found.");
}

$success = $error = '';

// ✅ Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $relation = trim($_POST['relation']);
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];
    $status = $_POST['status'];

    // Validate required fields
    if (empty($name) || empty($relation) || empty($from_date) || empty($to_date)) {
        $error = "All fields are required.";
    } elseif (!in_array($status, ['Pending', 'Approved', 'Rejected'])) {
        $error = "Invalid status selected.";
    } elseif (strtotime($to_date) < strtotime($from_date)) {
        $error = "End date cannot be before start date.";
    } else {
        // Update in database
        $stmt = $conn->prepare("UPDATE substitute_guardians SET name = ?, relation = ?, from_date = ?, to_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $relation, $from_date, $to_date, $status, $id);

        if ($stmt->execute()) {
            $success = "✅ Request updated successfully!";
            // Refresh data after update
            $req['name'] = $name;
            $req['relation'] = $relation;
            $req['from_date'] = $from_date;
            $req['to_date'] = $to_date;
            $req['status'] = $status;
        } else {
            $error = "❌ Database error: Could not update.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>✏️ Edit Substitute Guardian</title>
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
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
            padding: 10px;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        h2 {
            color: #e67e22;
            text-align: center;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: bold;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #e67e22;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            background: #fdfdfd;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #e67e22;
            outline: none;
            box-shadow: 0 0 5px rgba(230, 126, 34, 0.2);
        }

        /* Button */
        .btn {
            background: #e67e22;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover {
            background: #d35400;
            transform: translateY(-1px);
        }

        /* Back Link */
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #e67e22;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Messages */
        .msg {
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
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

        /* Responsive Adjustments */
        @media (max-width: 480px) {
            .container {
                padding: 20px;
                margin: 10px;
                border-radius: 12px;
            }

            h2 {
                font-size: 20px;
            }

            .form-group input, .form-group select {
                font-size: 15px;
                padding: 10px;
            }

            .btn {
                font-size: 16px;
                padding: 14px;
            }

            .back-link {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>✏️ Edit Substitute Guardian</h2>

        <!-- Success or Error Message -->
        <?php if ($success): ?>
            <p class="msg success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="msg error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Edit Form -->
        <form method="POST">
            <div class="form-group">
                <label for="name">Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="<?= htmlspecialchars($req['name']) ?>" 
                    required 
                >
            </div>

            <div class="form-group">
                <label for="relation">Relation</label>
                <input 
                    type="text" 
                    id="relation" 
                    name="relation" 
                    value="<?= htmlspecialchars($req['relation']) ?>" 
                    required 
                >
            </div>

            <div class="form-group">
                <label for="from_date">Valid From</label>
                <input 
                    type="date" 
                    id="from_date" 
                    name="from_date" 
                    value="<?= htmlspecialchars($req['from_date']) ?>" 
                    required 
                >
            </div>

            <div class="form-group">
                <label for="to_date">Valid To</label>
                <input 
                    type="date" 
                    id="to_date" 
                    name="to_date" 
                    value="<?= htmlspecialchars($req['to_date']) ?>" 
                    required 
                >
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Pending" <?= $req['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $req['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $req['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <button type="submit" class="btn">💾 Save Changes</button>
        </form>

        <a href="admin_approve_substitute.php" class="back-link">← Back to Requests</a>
    </div>
</body>
</html>