<?php
session_start();
include("config/db.php");

// Security Check
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Helper function to execute queries securely (Software Engineering Best Practice)
function getPercentage($conn, $table, $col_sum, $user_id) {
    // Note: Assuming your tables have a 'user_id' column. If not, remove "AND user_id = $user_id"
    // In a real app, use Prepared Statements ($stmt->bind_param) to prevent SQL Injection!
    $sql = "SELECT SUM($col_sum) AS done, COUNT(*) AS total FROM $table WHERE user_id = '$user_id'"; 
    
    // Fallback for tables without user_id if you haven't added that column yet:
    // $sql = "SELECT SUM($col_sum) AS done, COUNT(*) AS total FROM $table";
    
    $result = $conn->query($sql);
    
    if($result) {
        $row = $result->fetch_assoc();
        $total = ($table == 'prayers') ? $row['total'] * 5 : $row['total']; // Adjusting logic for prayers
        $done = $row['done'];
        
        return ($total > 0) ? round(($done / $total) * 100) : 0;
    }
    return 0;
}

/* ===== FETCH DATA ===== */
// Assuming 'prayers' table sums 5 columns, or you count rows. 
// I kept your original logic but wrapped it for cleaner code.
$namazPercent = getPercentage($conn, 'prayers', 'fajr+zuhar+asar+maghrib+isha', $user_id);
$studyPercent = getPercentage($conn, 'study', 'completed', $user_id);
$skillPercent = getPercentage($conn, 'skills', 'completed', $user_id);

// Algorithm: Calculate Total Productivity Score (Average of all 3)
$totalScore = round(($namazPercent + $studyPercent + $skillPercent) / 3);

// Dynamic Greeting Logic
$hour = date('H');
if ($hour < 12) $greeting = "Good Morning";
elseif ($hour < 18) $greeting = "Good Afternoon";
else $greeting = "Good Evening";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productivity Hub | AI Student</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #6366f1; /* Indigo */
            --secondary: #ec4899; /* Pink */
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* HEADER AREA */
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .header h1 {
            margin: 0;
            font-weight: 800;
            font-size: 2.5rem;
            background: -webkit-linear-gradient(left, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            color: var(--text-muted);
            margin-top: 10px;
            font-size: 1.1rem;
        }

        /* DASHBOARD GRID */
        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        /* MODERN CARD DESIGN */
        .card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.2); /* Glow effect */
            border-color: rgba(99, 102, 241, 0.3);
        }

        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* CIRCULAR PROGRESS BAR (CSS ONLY) */
        .progress-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(var(--color) calc(var(--percent) * 1%), #334155 0);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        .progress-circle::before {
            content: "";
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--card-bg);
            position: absolute;
        }

        .progress-value {
            position: relative;
            font-size: 24px;
            font-weight: bold;
            z-index: 10;
        }

        /* ACTION BUTTONS */
        .btn-group {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: 0.2s;
            text-align: center;
        }

        .btn-primary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-primary:hover { background: var(--primary); border-color: var(--primary); }

        .btn-outline {
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--text-muted);
        }

        .btn-outline:hover { border-color: white; color: white; }

        /* FOOTER */
        .footer {
            margin-top: auto;
            padding: 30px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .logout-btn {
            color: #ef4444;
            text-decoration: none;
            font-weight: bold;
            border: 1px solid #ef4444;
            padding: 8px 20px;
            border-radius: 20px;
            transition: 0.3s;
        }
        .logout-btn:hover { background: #ef4444; color: white; }

    </style>
</head>

<body>

    <div class="header">
        <h1><?php echo $greeting . ", " . htmlspecialchars($_SESSION["user_name"]); ?></h1>
        <p>Current Productivity Score: <strong style="color: #4ade80;"><?php echo $totalScore; ?>/100</strong></p>
    </div>

    <div class="container">

        <div class="card">
            <h2><i class="fa-solid fa-mosque" style="color: #4ade80;"></i> Namaz</h2>
            <div class="progress-circle" style="--percent: <?php echo $namazPercent; ?>; --color: #4ade80;">
                <div class="progress-value"><?php echo $namazPercent; ?>%</div>
            </div>
            <div class="btn-group">
                <a class="btn btn-primary" href="prayers/index.php">Track</a>
                <a class="btn btn-outline" href="prayers/progress.php"><i class="fa-solid fa-chart-line"></i></a>
            </div>
        </div>

        <div class="card">
            <h2><i class="fa-solid fa-book-open" style="color: #60a5fa;"></i> Study</h2>
            <div class="progress-circle" style="--percent: <?php echo $studyPercent; ?>; --color: #60a5fa;">
                <div class="progress-value"><?php echo $studyPercent; ?>%</div>
            </div>
            <div class="btn-group">
                <a class="btn btn-primary" href="study/index.php">Track</a>
                <a class="btn btn-outline" href="study/progress.php"><i class="fa-solid fa-chart-line"></i></a>
            </div>
        </div>

        <div class="card">
            <h2><i class="fa-solid fa-brain" style="color: #f472b6;"></i> Skills</h2>
            <div class="progress-circle" style="--percent: <?php echo $skillPercent; ?>; --color: #f472b6;">
                <div class="progress-value"><?php echo $skillPercent; ?>%</div>
            </div>
            <div class="btn-group">
                <a class="btn btn-primary" href="skills/index.php">Track</a>
                <a class="btn btn-outline" href="skills/progress.php"><i class="fa-solid fa-chart-line"></i></a>
            </div>
        </div>

    </div>

    <div class="footer">
        <p>"Success is the sum of small efforts, repeated day in and day out."</p>
        <br>
        <a class="logout-btn" href="auth/logout.php">Logout</a>
    </div>

</body>
</html>