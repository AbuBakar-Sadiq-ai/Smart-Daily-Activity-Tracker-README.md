<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";
$subjectsToday = [];
$dayName = "";

/* ===== TIMETABLE ARRAY ===== */
$timetable = [
    "Monday" => [
        "Database Systems",
        "Software Engineering",
        "Design and Analysis of Algorithms"
    ],
    "Tuesday" => [
        "Artificial Intelligence (Lab)",
        "Design and Analysis of Algorithms",
        "Software Engineering"
    ],
    "Wednesday" => [
        "Database Systems",
        "Artificial Intelligence (Lab)",
        "Artificial Intelligence"
    ],
    "Thursday" => [
        "Database Systems (Lab)",
        "Database Systems (Lab)",
        "Assembly Language (Lab)",
        "Assembly Language"
    ],
    "Friday" => [
        "Computer Organization and Assembly Language (Lab)",
        "Probability and Statistics",
        "Probability and Statistics"
    ],
    "Saturday" => [],
    "Sunday" => []
];

/* ===== DATE SELECT ===== */
if (isset($_POST["get_day"])) {
    $date = $_POST["date"];
    $dayName = date("l", strtotime($date));
    $subjectsToday = $timetable[$dayName] ?? [];
}

/* ===== SAVE STUDY ===== */
if (isset($_POST["save_study"])) {

    $date = $_POST["date"];
    $dayName = date("l", strtotime($date));
    $subjectsToday = $timetable[$dayName] ?? [];

    foreach ($subjectsToday as $subject) {

        $completed = isset($_POST["completed"][$subject]) ? 1 : 0;

        $stmt = $conn->prepare(
            "INSERT INTO study (study_date, subject_name, completed)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("ssi", $date, $subject, $completed);
        $stmt->execute();
    }

    $message = "Study saved for $dayName!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Study Tracker</title>

    <style>
        body {
            background: #000;
            color: #fff;
            font-family: Arial;
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }

        .card {
            background: #111;
            padding: 25px;
            border-radius: 12px;
            width: 420px;
            text-align: center;
            box-shadow: 0 0 12px rgba(255,255,255,0.1);
        }

        input[type="date"] {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: none;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #333;
        }

        td:first-child { text-align: left; }
        td:last-child { text-align: right; }

        button {
            background: #fff;
            color: #000;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }

        .msg { color: #0f0; margin-top: 10px; }

        a { color: #fff; display: block; margin-top: 15px; }
    </style>
</head>

<body>

<div class="card">

    <h2>📚 Smart Study Tracker</h2>

    <!-- DATE SELECT -->
    <form method="POST">
        <input type="date" name="date" required>
        <button type="submit" name="get_day">Show Today's Subjects</button>
    </form>

    <?php if ($dayName): ?>
        <h3><?php echo $dayName; ?> Classes</h3>
    <?php endif; ?>

    <!-- SUBJECT TABLE -->
    <?php if (!empty($subjectsToday)): ?>
    <form method="POST">
        <input type="hidden" name="date" value="<?php echo $_POST['date']; ?>">

        <table>
            <?php foreach ($subjectsToday as $subject): ?>
                <tr>
                    <td><?php echo $subject; ?></td>
                    <td>
                        <input type="checkbox" name="completed[<?php echo $subject; ?>]">
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <button type="submit" name="save_study">Save Study</button>
    </form>
    <?php elseif ($dayName && empty($subjectsToday)): ?>
        <p>No classes today 🎉</p>
    <?php endif; ?>

    <div class="msg"><?php echo $message; ?></div>

    <a href="../dashboard.php">⬅ Back to Dashboard</a>

</div>

</body>
</html>
