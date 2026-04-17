<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";
$msg_type = "";

// Default date is today
$selected_date = date('Y-m-d');

// If the user changes the date via GET (for viewing past records)
if (isset($_GET['date'])) {
    $selected_date = $_GET['date'];
}

// HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST["date"];
    $fajr = isset($_POST["fajr"]) ? 1 : 0;
    $zuhar = isset($_POST["zuhar"]) ? 1 : 0;
    $asar = isset($_POST["asar"]) ? 1 : 0;
    $maghrib = isset($_POST["maghrib"]) ? 1 : 0;
    $isha = isset($_POST["isha"]) ? 1 : 0;
    $qaza = isset($_POST["qaza"]) ? 1 : 0;

    // Check if record exists for THIS user and THIS date
    $check = $conn->prepare("SELECT id FROM prayers WHERE user_id=? AND prayer_date=?");
    $check->bind_param("is", $user_id, $date);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE prayers SET fajr=?, zuhar=?, asar=?, maghrib=?, isha=?, qaza=? WHERE user_id=? AND prayer_date=?");
        $stmt->bind_param("iiiiiiss", $fajr, $zuhar, $asar, $maghrib, $isha, $qaza, $user_id, $date);
        $message = $stmt->execute() ? "Record updated successfully!" : "Error updating record.";
    } else {
        $stmt = $conn->prepare("INSERT INTO prayers (user_id, prayer_date, fajr, zuhar, asar, maghrib, isha, qaza) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiiiiii", $user_id, $date, $fajr, $zuhar, $asar, $maghrib, $isha, $qaza);
        $message = $stmt->execute() ? "Record saved for $date!" : "Error saving record.";
    }
    $msg_type = "success";
    $selected_date = $date; // Keep the date consistent after post
}

// FETCH CURRENT STATUS FOR THE SELECTED DATE
$status = ['fajr'=>0, 'zuhar'=>0, 'asar'=>0, 'maghrib'=>0, 'isha'=>0, 'qaza'=>0];
$fetch = $conn->prepare("SELECT fajr, zuhar, asar, maghrib, isha, qaza FROM prayers WHERE user_id=? AND prayer_date=?");
$fetch->bind_param("is", $user_id, $selected_date);
$fetch->execute();
$res = $fetch->get_result();
if ($row = $res->fetch_assoc()) {
    $status = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Namaz Tracker | Productivity Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text: #f8fafc;
        }

        body {
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            padding: 35px;
            border-radius: 24px;
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        h2 { font-weight: 600; margin-bottom: 20px; color: #fff; }

        .date-selector {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 12px;
            border-radius: 12px;
            width: 100%;
            font-size: 1rem;
            margin-bottom: 25px;
            box-sizing: border-box;
            cursor: pointer;
        }

        table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        tr {
            background: rgba(255, 255, 255, 0.03);
            transition: 0.3s;
        }

        tr:hover { background: rgba(255, 255, 255, 0.08); }

        td {
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        td:first-child {
            border-left: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px 0 0 12px;
            text-align: left;
            font-weight: 500;
        }

        td:last-child {
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 0 12px 12px 0;
            text-align: right;
        }

        /* Custom Checkbox Styling */
        input[type="checkbox"] {
            transform: scale(1.5);
            accent-color: var(--primary);
            cursor: pointer;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3); }

        .msg {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6ee7b7;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body>

<div class="card">
    <h2><i class="fa-solid fa-mosque"></i> Namaz Tracker</h2>

    <form method="GET" id="dateForm">
        <input type="date" name="date" class="date-selector" 
               value="<?php echo $selected_date; ?>" 
               onchange="document.getElementById('dateForm').submit()">
    </form>

    <form method="POST">
        <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
        
        <table>
            <?php 
            $prayers = ['Fajr', 'Zuhar', 'Asar', 'Maghrib', 'Isha', 'Qaza'];
            foreach($prayers as $p): 
                $key = strtolower($p);
                $checked = ($status[$key] == 1) ? "checked" : "";
            ?>
            <tr>
                <td><?php echo $p; ?></td>
                <td><input type="checkbox" name="<?php echo $key; ?>" <?php echo $checked; ?>></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <button type="submit">Update Records</button>
    </form>

    <?php if ($message): ?>
        <div class="msg"><i class="fa-solid fa-check-circle"></i> <?php echo $message; ?></div>
    <?php endif; ?>

    <a href="../dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>

</body>
</html>