<?php
session_start();

// Define branches (branch_id => branch_name)
$branches = [
    1 => "pollachi",
    2 => "coimbatore",
    3 => "tirupur",
    4 => "chithode",
    5 => "kolathur",
    6 => "tambaram"
];

// Hardcoded parent credentials: username => [password, student_id, branch_id]
$parents = [
    // pollachi (po*) -> branch_id = 1
    "po1"  => ["password" => "1234", "student_id" => 48, "branch_id" => 1],
    "po2"  => ["password" => "1234", "student_id" => 49, "branch_id" => 1],
    "po3"  => ["password" => "1234", "student_id" => 50, "branch_id" => 1],
    "po4"  => ["password" => "1234", "student_id" => 51, "branch_id" => 1],
    "po5"  => ["password" => "1234", "student_id" => 52, "branch_id" => 1],
    "po6"  => ["password" => "1234", "student_id" => 53, "branch_id" => 1],
    "po7"  => ["password" => "1234", "student_id" => 54, "branch_id" => 1],
    "po8"  => ["password" => "1234", "student_id" => 55, "branch_id" => 1],
    "po9"  => ["password" => "1234", "student_id" => 56, "branch_id" => 1],
    "po10" => ["password" => "1234", "student_id" => 57, "branch_id" => 1],
    "po11" => ["password" => "1234", "student_id" => 58, "branch_id" => 1],
    "po12" => ["password" => "1234", "student_id" => 59, "branch_id" => 1],
    "po13" => ["password" => "1234", "student_id" => 60, "branch_id" => 1],
    "po14" => ["password" => "1234", "student_id" => 61, "branch_id" => 1],
    "po15" => ["password" => "1234", "student_id" => 72, "branch_id" => 1],
    "po16" => ["password" => "1234", "student_id" => 73, "branch_id" => 1],

    // coimbatore (co*) -> branch_id = 2
    "co1" => ["password" => "1234", "student_id" => 55, "branch_id" => 2],
    "co2" => ["password" => "1234", "student_id" => 56, "branch_id" => 2],

    // tirupur (tp*) -> branch_id = 3
    "tp1" => ["password" => "1234", "student_id" => 60, "branch_id" => 3],
    "tp2" => ["password" => "1234", "student_id" => 61, "branch_id" => 3],

    // chithode (ch*) -> branch_id = 4
    "ch1" => ["password" => "1234", "student_id" => 63, "branch_id" => 4],
    "ch2" => ["password" => "1234", "student_id" => 64, "branch_id" => 4],
    "ch3" => ["password" => "1234", "student_id" => 65, "branch_id" => 4],
    "ch4" => ["password" => "1234", "student_id" => 66, "branch_id" => 4],
    "ch5" => ["password" => "1234", "student_id" => 67, "branch_id" => 4],

    // kolathur (ko*) -> branch_id = 5
    "ko1" => ["password" => "1234", "student_id" => 42, "branch_id" => 5],
    "ko2" => ["password" => "1234", "student_id" => 43, "branch_id" => 5],
    "ko3" => ["password" => "1234", "student_id" => 44, "branch_id" => 5],
    "ko4" => ["password" => "1234", "student_id" => 45, "branch_id" => 5],
    "ko5" => ["password" => "1234", "student_id" => 46, "branch_id" => 5],
    "ko6" => ["password" => "1234", "student_id" => 62, "branch_id" => 5],

    // tambaram (tb*) -> branch_id = 6
    "tb1" => ["password" => "1234", "student_id" => 68, "branch_id" => 6],
    "tb2" => ["password" => "1234", "student_id" => 70, "branch_id" => 6],
    "tb3" => ["password" => "1234", "student_id" => 71, "branch_id" => 6],
];

// Map branch_id to username prefix
$branchPrefix = [
    1 => "po",
    2 => "co",
    3 => "tp",
    4 => "ch",
    5 => "ko",
    6 => "tb",
];

// Handle login
$error = null;
$selected_branch = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $selected_branch = $_POST['branch_id'] ?? '';

    if (empty($username) || empty($password) || empty($selected_branch)) {
        $error = "❌ All fields are required!";
    } elseif (!in_array($selected_branch, array_keys($branches))) {
        $error = "❌ Invalid branch selected!";
    } else {
        $prefix = $branchPrefix[$selected_branch] ?? '';

        // Check if username starts with correct prefix
        if (strtolower(substr($username, 0, strlen($prefix))) !== strtolower($prefix)) {
            $error = "❌ Username must start with '{$prefix}' for this branch!";
        } elseif (isset($parents[$username])) {
            if ($parents[$username]['password'] === $password) {
                $_SESSION['parent'] = $username;
                $_SESSION['student_id'] = $parents[$username]['student_id'];
                $_SESSION['branch_id'] = $parents[$username]['branch_id'];
                $_SESSION['branch_name'] = ucfirst($branches[$parents[$username]['branch_id']]);
                header("Location: parent_dashboard.php");
                exit();
            } else {
                $error = "❌ Invalid password!";
            }
        } else {
            $error = "❌ Invalid username!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('images/parent.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .overlay {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: 1;
        }
        .login-box {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            padding: 40px 30px;
            border-radius: 15px;
            border: 2px solid rgba(255,255,255,0.5);
            box-shadow: 0px 10px 30px rgba(0,0,0,0.4);
            width: 350px;
            text-align: center;
            animation: slideIn 1s ease-in-out;
        }
        .login-box h2 {
            margin-bottom: 20px;
            color: #fff;
            font-weight: 600;
        }
        .input-group {
            position: relative;
            margin: 15px 0;
        }
        .input-group select,
        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            font-size: 15px;
            background: rgba(255,255,255,0.2);
            color: #000;
            transition: 0.3s;
        }
        .input-group select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7'%3e%3cpath fill='%23555' d='M0 0l6 6 6-6z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 30px;
        }
        .input-group input::placeholder {
            color: #777;
        }
        .input-group input:focus,
        .input-group select:focus {
            border-color: #2575fc;
            box-shadow: 0px 0px 6px rgba(37,117,252,0.5);
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
            font-size: 18px;
            user-select: none;
        }
        .toggle-password:hover {
            color: #fff;
        }
        .login-box button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #2575fc, #6a11cb);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        .login-box button:hover {
            background: linear-gradient(45deg, #1a5edb, #5200a3);
            transform: scale(1.05);
        }
        .error {
            margin-top: 15px;
            color: #ff4d4d;
            font-size: 14px;
            font-weight: bold;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @media (max-width: 480px) {
            .login-box {
                width: 90%;
                padding: 25px;
            }
            .login-box h2 {
                font-size: 20px;
            }
            .input-group select,
            .input-group input,
            .login-box button {
                font-size: 14px;
                padding: 10px;
            }
            .toggle-password {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="login-box">
        <h2>👨‍👩‍👧 Parent Login</h2>
        <form method="POST">
            <!-- Branch Selection -->
            <div class="input-group">
                <select name="branch_id" id="branchSelect" required>
                    <option value="" disabled selected>Select Branch</option>
                    <?php foreach ($branches as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($selected_branch == $id) ? 'selected' : '' ?>>
                            <?= ucfirst($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Username Input (Text Field) -->
            <div class="input-group">
                <input type="text" name="username" placeholder="Enter Username" value="<?= htmlspecialchars($username) ?>" required>
            </div>

            <!-- Password -->
            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Enter Password" required>
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>

            <button type="submit">Login</button>
        </form>

        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById("password");
            const eye = document.querySelector(".toggle-password");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eye.textContent = "🙈";
            } else {
                passwordField.type = "password";
                eye.textContent = "👁️";
            }
        }
    </script>
</body>
</html>