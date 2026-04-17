<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$view = $_GET['view'] ?? 'monthly';

/* ===== IMPROVED SQL LOGIC ===== */
// We use user_id=? to ensure data privacy
if ($view === "daily") {
    $query = "SELECT SUM(fajr+zuhar+asar+maghrib+isha) as done FROM prayers WHERE user_id=? AND prayer_date=CURDATE()";
    $total_possible = 5;
} 
elseif ($view === "weekly") {
    $query = "SELECT SUM(fajr+zuhar+asar+maghrib+isha) as done, COUNT(*) as days FROM prayers WHERE user_id=? AND YEARWEEK(prayer_date, 1) = YEARWEEK(CURDATE(), 1)";
} 
else { // monthly
    $query = "SELECT SUM(fajr+zuhar+asar+maghrib+isha) as done, COUNT(*) as days FROM prayers WHERE user_id=? AND MONTH(prayer_date)=MONTH(CURDATE()) AND YEAR(prayer_date)=YEAR(CURDATE())";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$done = $result["done"] ?? 0;
// If daily, total is 5. If weekly/monthly, it's 5 * number of days recorded.
$total = ($view === "daily") ? 5 : ($result["days"] ?? 0) * 5;
$missed = max(0, $total - $done);
$percent = $total > 0 ? round(($done / $total) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics | Productivity Hub</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg: #0f172a;
            --card: rgba(30, 41, 59, 0.7);
            --primary: #6366f1;
        }

        body {
            background: radial-gradient(circle at top left, #1e1b4b, #0f172a);
            color: #fff;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            background: var(--card);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 28px;
            width: 400px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .tabs {
            display: flex;
            background: rgba(15, 23, 42, 0.5);
            padding: 5px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .tab {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .tab.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .stats-container {
            position: relative;
            margin: 20px auto;
            width: 220px;
        }

        .percentage-overlay {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2.5rem;
            font-weight: 800;
        }

        .info-text {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-top: 15px;
        }

        .back-link {
            display: block;
            margin-top: 25px;
            color: #6366f1;
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Performance Insights</h2>
    
    <div class="tabs">
        <a class="tab <?= $view=='daily'?'active':'' ?>" href="?view=daily">Today</a>
        <a class="tab <?= $view=='weekly'?'active':'' ?>" href="?view=weekly">Week</a>
        <a class="tab <?= $view=='monthly'?'active':'' ?>" href="?view=monthly">Month</a>
    </div>

    <div class="stats-container">
        <canvas id="progressChart"></canvas>
        <div class="percentage-overlay"><?= $percent ?>%</div>
    </div>

    <p class="info-text">
        You've completed <strong><?= $done ?></strong> out of <strong><?= $total ?></strong> prayers 
        in this period.
    </p>

    <a href="namaz.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Tracker</a>
</div>

<script>
const ctx = document.getElementById('progressChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'Remaining'],
        datasets: [{
            data: [<?= $done ?>, <?= $missed ?>],
            backgroundColor: ['#6366f1', 'rgba(255,255,255,0.05)'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        cutout: '85%',
        plugins: { legend: { display: false } }
    }
});
</script>

</body>
</html>