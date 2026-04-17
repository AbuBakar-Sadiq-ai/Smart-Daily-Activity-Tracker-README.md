<?php
session_start();
include("../config/db.php");

$message = "";
$msg_type = ""; // To style errors differently than success messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs (Good practice for Security)
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
        $msg_type = "error";
    } else {
        // Prepare Statement
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Verify Hash
            if (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                
                // Redirect
                header("Location: ../dashboard.php");
                exit();
            } else {
                $message = "Incorrect password.";
                $msg_type = "error";
            }
        } else {
            $message = "No account found with that email.";
            $msg_type = "error";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Productivity Hub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary: #6366f1;
        --bg-dark: #0f172a;
        --card-bg: rgba(30, 41, 59, 0.7); /* Translucent */
        --text: #f8fafc;
        --error: #ef4444;
    }

    body {
        background: radial-gradient(circle at top left, #1e1b4b, #0f172a);
        font-family: 'Inter', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
        overflow: hidden;
    }

    /* Background decoration shapes */
    .shape {
        position: absolute;
        filter: blur(80px);
        z-index: -1;
        opacity: 0.6;
    }
    .shape-1 { background: #6366f1; width: 300px; height: 300px; top: -50px; left: -50px; border-radius: 50%; }
    .shape-2 { background: #ec4899; width: 250px; height: 250px; bottom: -50px; right: -50px; border-radius: 50%; }

    /* Glassmorphism Card */
    .card {
        background: var(--card-bg);
        backdrop-filter: blur(12px); /* The Blur Effect */
        padding: 40px;
        border-radius: 24px;
        width: 100%;
        max-width: 380px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        transform: translateY(20px);
        opacity: 0;
        animation: floatUp 0.6s forwards;
    }

    @keyframes floatUp {
        to { opacity: 1; transform: translateY(0); }
    }

    h2 {
        color: var(--text);
        margin-bottom: 10px;
        font-weight: 600;
    }

    p.subtitle {
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 30px;
    }

    /* Input Group */
    .input-group {
        position: relative;
        margin-bottom: 20px;
        text-align: left;
    }

    .input-group i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    /* Toggle Password Icon */
    .toggle-password {
        left: auto !important;
        right: 15px;
        cursor: pointer;
    }
    .toggle-password:hover { color: #fff; }

    input {
        width: 100%;
        padding: 14px 14px 14px 45px; /* Space for icon */
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        background: rgba(15, 23, 42, 0.6);
        color: #fff;
        font-size: 0.95rem;
        box-sizing: border-box; /* Fixes padding width issues */
        transition: 0.3s;
    }

    input:focus {
        outline: none;
        border-color: var(--primary);
        background: rgba(15, 23, 42, 0.9);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    /* Button */
    button {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
    }

    button:active { transform: translateY(0); }

    /* Links & Messages */
    .msg {
        margin-top: 15px;
        padding: 10px;
        border-radius: 8px;
        font-size: 0.9rem;
        display: none; /* Hidden by default */
    }
    
    .msg.error {
        display: block;
        background: rgba(239, 68, 68, 0.1);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .links {
        margin-top: 25px;
        font-size: 0.9rem;
        color: #94a3b8;
    }

    .links a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }
    .links a:hover { text-decoration: underline; }

</style>
</head>

<body>

    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Enter your credentials to access your dashboard.</p>

        <form method="POST" onsubmit="document.getElementById('loginBtn').innerText = 'Logging in...'">
            
            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="passwordField" name="password" placeholder="Password" required>
                <i class="fa-solid fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>

            <button type="submit" id="loginBtn">Sign In</button>
        </form>

        <?php if (!empty($message)): ?>
            <div class="msg <?php echo $msg_type; ?>">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="links">
            <p>Don't have an account? <a href="signup.php">Sign up</a></p>
        </div>
    </div>

    <script>
        // Simple JS function to toggle password visibility
        function togglePassword() {
            var passwordInput = document.getElementById("passwordField");
            var icon = document.querySelector(".toggle-password");
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

</body>
</html>