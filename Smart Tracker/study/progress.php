<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

/* ===== VIEW MODE ===== */
$view = $_GET['view'] ?? 'monthly';

/* ===== QUERY SELECT ===== */
if ($view === "daily") {

    $query = "
    SELECT 
    SUM(completed) AS total_done,
    COUNT(*) AS total_records
    FROM study
    WHERE study_date = CURDATE()
    ";

}
elseif ($view === "weekly") {

    $query = "
    SELECT 
    SUM(completed) AS total_done,
    COUNT(*) AS total_records
    FROM study
    WHERE YEARWEEK(study_date, 1) = YEARWEEK(CURDATE(), 1)
    ";

}
else { // monthly default

    $month = date("m");
    $year = date("Y");

    $query = "
    SELECT 
    SUM(completed) AS total_done,
    COUNT(*) AS total_records
    FROM study
    WHERE MONTH(study_date)=? AND YEAR(study_date)=?
    ";
}

/* ===== EXECUTION ===== */
if ($view === "monthly") {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $month, $year);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
} else {
    $data = $conn->query($query)->fetch_assoc();
}

$done = $data["total_done"] ?? 0;
$total = $data["total_records"] ?? 0;
$missed = $total - $done;

$percent = $total > 0 ? round(($done/$total)*100) : 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Study Progress</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    background:#000;
    color:#fff;
    font-family:Arial;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card{
    background:#111;
    padding:30px;
    border-radius:18px;
    width:450px;
    text-align:center;
    box-shadow:0 0 20px rgba(255,255,255,0.08);
}

.percent{
    font-size:42px;
    font-weight:bold;
    margin:10px 0 20px;
}

/* ===== TABS ===== */
.tabs{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-bottom:15px;
}

.tab{
    padding:8px 14px;
    border-radius:8px;
    background:#222;
    color:#aaa;
    text-decoration:none;
    font-size:14px;
}

.tab.active{
    background:#fff;
    color:#000;
    font-weight:bold;
}

canvas{ margin-top:15px; }

a.back{
    color:#aaa;
    display:block;
    margin-top:20px;
    text-decoration:none;
}

a.back:hover{ color:#fff; }
</style>
</head>

<body>

<div class="card">

<h2>📚 Study Progress</h2>

<!-- ===== TABS ===== -->
<div class="tabs">
    <a class="tab <?php if($view=='daily') echo 'active'; ?>" href="?view=daily">Daily</a>
    <a class="tab <?php if($view=='weekly') echo 'active'; ?>" href="?view=weekly">Weekly</a>
    <a class="tab <?php if($view=='monthly') echo 'active'; ?>" href="?view=monthly">Monthly</a>
</div>

<div class="percent"><?php echo $percent; ?>%</div>

<canvas id="barChart"></canvas>

<a class="back" href="index.php">⬅ Back to Study</a>

</div>

<script>
const done = <?php echo $done ?>;
const missed = <?php echo $missed ?>;

new Chart(document.getElementById("barChart"), {
    type: "bar",
    data: {
        labels: ["Completed", "Missed"],
        datasets: [{
            data: [done, missed],
            backgroundColor: ["#4CAF50", "#F44336"],
            borderRadius: 10,
            barThickness: 60
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: "#fff" } },
            y: { ticks: { color: "#fff" }, beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
