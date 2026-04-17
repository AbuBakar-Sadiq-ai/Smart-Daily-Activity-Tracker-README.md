<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

/* ===== ADD NEW SKILL ===== */
if (isset($_POST["add_skill"])) {

    $skillName = trim($_POST["skill_name"]);

    if ($skillName !== "") {

        // avoid duplicate skill names
        $check = $conn->prepare("SELECT id FROM skills WHERE skill_name=? LIMIT 1");
        $check->bind_param("s", $skillName);
        $check->execute();
        $check->store_result();

        if ($check->num_rows == 0) {
            $stmt = $conn->prepare(
                "INSERT INTO skills (skill_date, skill_name, completed)
                 VALUES (CURDATE(), ?, 0)"
            );
            $stmt->bind_param("s", $skillName);
            $stmt->execute();

            $message = "New skill added!";
        } else {
            $message = "Skill already exists!";
        }
    }
}

/* ===== SAVE DAILY PROGRESS ===== */
if (isset($_POST["save_progress"])) {

    $date = $_POST["date"];

    $result = $conn->query("SELECT DISTINCT skill_name FROM skills");
    $skills = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($skills as $s) {

        $skillName = $s["skill_name"];
        $completed = isset($_POST["completed"][$skillName]) ? 1 : 0;

        // check existing row
        $check = $conn->prepare(
            "SELECT id FROM skills WHERE skill_date=? AND skill_name=?"
        );
        $check->bind_param("ss", $date, $skillName);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // update
            $stmt = $conn->prepare(
                "UPDATE skills SET completed=? WHERE skill_date=? AND skill_name=?"
            );
            $stmt->bind_param("iss", $completed, $date, $skillName);
            $stmt->execute();
        } else {
            // insert
            $stmt = $conn->prepare(
                "INSERT INTO skills (skill_date, skill_name, completed)
                 VALUES (?, ?, ?)"
            );
            $stmt->bind_param("ssi", $date, $skillName, $completed);
            $stmt->execute();
        }
    }

    $message = "Daily progress saved!";
}

/* ===== GET ALL SKILLS ===== */
$result = $conn->query("SELECT DISTINCT skill_name FROM skills ORDER BY skill_name");
$skills = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Skill Tracker</title>

<style>
body{background:#000;color:#fff;font-family:Arial;display:flex;justify-content:center;margin-top:40px;}
.card{background:#111;padding:25px;border-radius:12px;width:420px;text-align:center;box-shadow:0 0 12px rgba(255,255,255,0.1);}
input,button{width:100%;padding:10px;margin:8px 0;border:none;border-radius:6px;}
input{background:#222;color:#fff;}
button{background:#fff;color:#000;font-weight:bold;cursor:pointer;}
table{width:100%;margin-top:10px;border-collapse:collapse;}
td{padding:8px;border-bottom:1px solid #333;}
td:first-child{text-align:left;} td:last-child{text-align:right;}
.msg{color:#0f0;margin-top:10px;}
a{color:#fff;display:block;margin-top:15px;}
</style>
</head>

<body>

<div class="card">

<h2>🧠 Skill Tracker</h2>

<form method="POST">
<input type="text" name="skill_name" placeholder="Enter new skill" required>
<button name="add_skill">Add Skill</button>
</form>

<hr style="border-color:#333;">

<?php if (!empty($skills)): ?>
<form method="POST">

<input type="date" name="date" required>

<table>
<?php foreach ($skills as $s): ?>
<tr>
<td><?php echo $s["skill_name"]; ?></td>
<td><input type="checkbox" name="completed[<?php echo $s["skill_name"]; ?>]"></td>
</tr>
<?php endforeach; ?>
</table>

<button name="save_progress">Save Progress</button>
</form>
<?php else: ?>
<p>No skills added yet.</p>
<?php endif; ?>

<div class="msg"><?php echo $message; ?></div>

<a href="../dashboard.php">⬅ Back to Dashboard</a>
<a href="progress.php">📊 View Progress</a>

</div>
</body>
</html>
