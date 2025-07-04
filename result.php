<?php
session_start();
$mockId = $_GET['mockid'] ?? null;
$userId = $_SESSION['userId'];

if (!$mockId) {
    die("Mock ID not found in session.");
}

include("db.php");

$sql = "SELECT * FROM userAnswer WHERE mockIdRef = ? AND userEmail = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $mockId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$totalRating = 0;
$totalQuestions = 0;
$questionsData = [];

while ($row = $result->fetch_assoc()) {
    $totalRating += (int) $row['rating'];
    $totalQuestions++;
    $questionsData[] = $row;
}

$overallRating = ($totalQuestions > 0) ? round(($totalRating / $totalQuestions), 1) : 0;

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Feedback</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
        }

        .collapsible {
            margin: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
            background-color: white;
        }

        .collapsible-trigger {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f3f4f6;
            padding: 10px;
            cursor: pointer;
            font-weight: bold;
            border: 1px solid #0f2027;
            border-bottom:2px solid  black ;
            border-radius: 8px;
        }

        .collapsible-content {
            display: none;
            margin-top:2px;
            padding: 10px;
            background-color: white;
            border: 1px solid gray;
            border-radius: 8px;
        }

        .collapsible-content div {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .rating {
            color: red;
            padding: 10px;
            border: 1px solid red;
            border-radius: 8px;
        }

        .user-answer {
            background-color: #ffe5e5;
            color: darkred;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .correct-answer {
            background-color: #e5ffe5;
            color: green;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .feedback {
            background-color: #e5f1ff;
            color: blue;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .btn {
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }

        .purple-btn {
            float: right;
            width: 200px;
            margin: 30px 20px 0 0;
            background-color: #5a4fcf;
            color: white;
        }

        .purple-btn:hover {
            background-color: #4a3fba;
            font-weight: bold !important;
            box-shadow: 0px 0px 10px 2px #4a3fba  !important;
        }

        .collapsible + .collapsible {
            margin-top: 20px;
        }
    </style>

    <script>
        function toggleCollapsible(id) {
            var content = document.getElementById("content-" + id);
            content.style.display = (content.style.display === "block") ? "none" : "block";
        }
    </script>
</head>
<body>
    <?php include('header.php'); ?>

    <div style="padding: 20px;">
        <h1 style="margin: 10px 0 0 10px; color: green;">Congratulations!</h1>
        <h2 style="margin: 10px 0 0 10px; color: black;"><b>Here is your interview feedback</b></h2>
        <h2 style="margin: 10px 0 0 10px; font-size: 20px; color: blue;">
            Overall interview rating <strong><?php echo $overallRating; ?>/10</strong>
        </h2>
        <h3 style="margin: 10px 0 0 10px; font-size: 20px; color: gray;">
            Find below the interview questions with the correct answers, your answers, and feedback for improvement
        </h3>
    </div>

    <?php
    if (count($questionsData) > 0) {
        $count = 1;
        foreach ($questionsData as $row) {
    ?>
        <div class="collapsible">
            <div class="collapsible-trigger" onclick="toggleCollapsible(<?php echo $count; ?>)">
                Question <?php echo $count . " : "; echo htmlspecialchars($row['question']); ?>
                <span>⏷</span>
            </div>
            <div class="collapsible-content" id="content-<?php echo $count; ?>">
                <div>
                    <h4 class="rating"><strong>Rating:</strong> <?php echo $row['rating']; ?>/10</h4>
                    <h4 class="user-answer"><strong>Your Answer:</strong> <?php echo htmlspecialchars($row['userAns']); ?></h4>
                    <h4 class="correct-answer"><strong>Correct Answer:</strong> <?php echo htmlspecialchars($row['correctAns']); ?></h4>
                    <h4 class="feedback"><strong>Feedback:</strong> <?php echo htmlspecialchars($row['feedback']); ?></h4>
                </div>
            </div>
        </div>
    <?php
        $count++;
        }
    } else {
        echo "<p style='margin: 20px;'>No records found.</p>";
    }
    ?>

    <button id="startBtn" class="btn purple-btn" onclick="gotoDashboard()">Goto Dashboard</button>

    <script>
        function gotoDashboard() {
            window.location.href = "dashboard.php";
        }
    </script>
</body>
</html>
