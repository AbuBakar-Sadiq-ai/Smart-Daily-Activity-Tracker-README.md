<?php
include("../config/db.php");

$message = "";
$msg_type = ""; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    // Basic Validation Logic
    if (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
        $msg_type = "error";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "This email is already registered.";
            $msg_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $message = "Account created! Redirecting to login...";
                $msg_type = "success";
                // Meta redirect after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $message = "Something went wrong. Please try again.";
                $msg_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Productivity Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #6366f1;
            --bg-dark: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text: #f8fafc;
            --success: #10b981;
            --error: #ef4444;
        }

        body {
            background: radial-gradient(circle at bottom right, #1e1b4b, #0f172a);
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        /* Decorative Background Shapes */
        .shape { position: absolute; filter: blur(80px); z-index: -1; opacity: 0.5; border-radius: 50%; }
        .shape-1 { background: #ec4899; width: 300px; height: 300px; top: -50px; right: -50px; }
        .shape-2 { background: #6366f1; width: 250px; height: 250px; bottom: -50px; left: -50px; }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 24px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        h2 { color: var(--text); margin-bottom: 8px; font-weight: 600; }
        p.subtitle { color: #94a3b8; font-size: 0.9rem; margin-bottom: 25px; }

        .input-group { position: relative; margin-bottom: 15px; text-align: left; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }

        input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.6);
            color: #fff;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: 0.3s;
        }

        input:focus { outline: none; border-color: var(--primary); background: rgba(15, 23, 42, 0.9); }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        button:hover { transform: translateY(-2px); filter: brightness(1.1); }

        /* Status Messages */
        .msg {
            margin-top: 15px;
            padding: 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        .msg.error { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); }
        .msg.success { background: rgba(16, 185, 129, 0.1); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.2); }

        .links { margin-top: 25px; font-size: 0.9rem; color: #94a3b8; }
        .links a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>

<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="card">
        <h2>Join the Journey</h2>
        <p class="subtitle">Start tracking your prayers, studies, and skills.</p>

        <form method="POST" id="signupForm">
            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="name" placeholder="Full Name" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" id="pass" placeholder="Password (min 6 chars)" required>
            </div>

            <button type="submit" id="submitBtn">Create Account</button>
        </form>

        <?php if (!empty($message)): ?>
            <div class="msg <?php echo $msg_type; ?>">
                <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="links">
            <p>Already a member? <a href="login.php">Log in</a></p>
        </div>
    </div>

    <script>
        // Form Loading State
        document.getElementById('signupForm').onsubmit = function() {
            document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        };
    </script>
</body>
</html>