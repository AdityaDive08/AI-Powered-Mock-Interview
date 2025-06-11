<?php
session_start();
if(!isset($_SESSION['userId'])) {
    header("Location: login.php");
}
$mockId = $_GET['mockid'] ?? null;

if (!$mockId) {
    die("Mock ID not found in session.");
}

include("db.php");
$sql = "SELECT jsonmockresponse FROM interviews WHERE mockid='$mockId'";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data = json_decode($row['jsonmockresponse'], true);
        $questionsJson = $data['candidates'][0]['content']['parts'][0]['text'];
        $tempArray = json_decode($questionsJson, true);
        $questionsArray = isset($tempArray['interview_questions']) ? $tempArray['interview_questions'] : $tempArray;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mock Interview</title>
    <link rel="stylesheet" href="css/interview.css">
</head>
<body>
<?php include('header.php'); ?>
<div class="container">
    <div class="question-section">
        <div class="question-tabs">
            <?php
            foreach ($questionsArray as $index => $item) {
                $activeClass = ($index === 0) ? 'active-tab' : '';
                echo "<button class='tab $activeClass' id='tab-$index' onclick='showQuestion($index)'>Question #" . ($index + 1) . "</button>";
            }
            ?>
        </div>
        <div class="question-box">
            <p class="question-text" id="questionBox">
                <strong><?php echo htmlspecialchars($questionsArray[0]['question'], ENT_QUOTES | ENT_HTML5); ?></strong>
            </p>
            <button class="audio-icon" onclick="readQuestion()"><img src="images/volume.png" alt="Volume" height="25" width="25" ></button>
        </div>
        <div class="note">
            <strong>💡 Note:</strong>
            <br>
            Click on <strong>"Record Answer"</strong> when you want to answer the question.<br>
            Please make sure your answer is in <strong>English</strong>.<br>  
            At the end of the interview, we will provide feedback along with the correct answer for comparison.
        </div>
    </div>

    <div class="webcam-section">
        <div class="webcam-box">
            <video id="webcam" autoplay></video>
            <img src="images/webcam.png" id="webcamPlaceholder" alt="Webcam Preview">
        </div>
        <button id="recordButton" class="record-button" onclick="toggleRecording()">
            <img src="images/mic.png" alt="Microphone" height="20" width="20" style="transform:translate(0,5px);">
            Record Answer
        </button>
        <!-- <button id="showAnswerButton" class="show-answer-button" onclick="showRecordedAnswer()">Show User Answer</button>
        <p id="userAnswerText"></p> -->
        <div class="navigation-buttons">
            <button class="btn-state" id="prevButton" onclick="changeQuestion(-1)" style="display: none;">Previous</button>
            <button class="btn-state" id="nextButton" onclick="changeQuestion(1)">Next</button>
            <button class="btn-state" id="endButton" onclick="endInterview()" style="display: none;">End</button>
        </div>
    </div>
</div>

<script>
let questions = <?php echo json_encode($questionsArray); ?>;
let activeButton = 0;
let isRecording = false;
let recordedAnswer = "";
let mediaRecorder;
let speechRecognition;
let stream;

function escapeHTML(str) {
    return str.replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

function showQuestion(index) {
    if (isRecording) return;

    const escapedQuestion = escapeHTML(questions[index].question);
    document.getElementById("questionBox").innerHTML = "<p><strong>" + escapedQuestion + "</strong></p>";

    if (activeButton !== null) {
        document.getElementById("tab-" + activeButton).classList.remove("active-tab");
    }
    document.getElementById("tab-" + index).classList.add("active-tab");
    activeButton = index;
    updateNavigationButtons(index);
}

function readQuestion() {
    let questionText = document.getElementById("questionBox").innerText;
    let speech = new SpeechSynthesisUtterance();
    speech.text = questionText;
    speech.lang = "en-US";
    speech.rate = 1;
    speech.volume = 1;
    speech.pitch = 1;
    window.speechSynthesis.speak(speech);
}

async function toggleRecording() {
    let recordButton = document.getElementById("recordButton");

    if (!isRecording) {
        recordButton.innerHTML = "Stop Recording";
        recordButton.classList.add("recording");
        isRecording = true;

        document.querySelectorAll(".tab").forEach(tab => tab.disabled = true);

        window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        speechRecognition = new SpeechRecognition();
        speechRecognition.continuous = true;
        speechRecognition.interimResults = false;
        speechRecognition.lang = "en-US";
        
        speechRecognition.onresult = event => {
            recordedAnswer = event.results[0][0].transcript;
        };

        speechRecognition.start();
    } else {
        recordButton.innerHTML = "Record Answer";
        recordButton.classList.remove("recording");
        isRecording = false;

        document.querySelectorAll(".tab").forEach(tab => tab.disabled = false);

        if (speechRecognition) {
            speechRecognition.stop();
        }

        setTimeout(() => {
            let wordCount = recordedAnswer.trim().split(/\s+/).length;
            if (wordCount < 10) {
                alert("Your answer is too short. Please record again and provide a more detailed response.");
                recordedAnswer = "";
            } else {
                sendAnswerToAPI(recordedAnswer);
            }
        }, 500);
    }
}

function showRecordedAnswer() {
    let userAnswerText = document.getElementById("userAnswerText");
    userAnswerText.innerText = recordedAnswer ? `Recorded Answer: ${recordedAnswer}` : "No answer recorded.";
    userAnswerText.style.display = "block";
}

function disableButtons() {
    document.querySelectorAll(".tab").forEach(tab => tab.disabled = true);
    document.getElementById("prevButton").disabled = true;
    document.getElementById("nextButton").disabled = true;
    document.getElementById("endButton").disabled = true;
}

function enableButtons() {
    document.querySelectorAll(".tab").forEach(tab => tab.disabled = false);
    document.getElementById("prevButton").disabled = false;
    document.getElementById("nextButton").disabled = false;
    document.getElementById("endButton").disabled = false;
}

function sendAnswerToAPI(userAnswer) {
    let questionText = questions[activeButton]?.question;
    let answerText = questions[activeButton]?.answer;

    disableButtons();

    fetch("feedback.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            question: questionText,
            correctAnswer: answerText,
            answer: userAnswer,
            mockId: "<?php echo $mockId; ?>"
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log("Feedback stored:", data);
        enableButtons();
    })
    .catch(error => {
        console.error("Error storing feedback:", error);
        enableButtons();
    });
}

function updateNavigationButtons(index) {
    let prevButton = document.getElementById("prevButton");
    let nextButton = document.getElementById("nextButton");
    let endButton = document.getElementById("endButton");

    if (index === 0) {
        
        prevButton.style.display = "inline-block";
        document.getElementById("prevButton").disabled = true;
        nextButton.style.display = "inline-block";
        endButton.style.display = "none";
    } else if (index === questions.length - 1) {
        
        prevButton.style.display = "inline-block";
        nextButton.style.display = "none";
        endButton.style.display = "inline-block";
    } else {
        prevButton.style.display = "inline-block";
        document.getElementById("prevButton").disabled = false;
        nextButton.style.display = "inline-block";
        endButton.style.display = "none";
    }
}

function changeQuestion(direction) {
    let newIndex = activeButton + direction;
    if (newIndex >= 0 && newIndex < questions.length) {
        showQuestion(newIndex);
    }
}

function endInterview() {
    alert("Interview Ended. Thank you!");
    window.location.href = "result.php?mockid=<?php echo $mockId; ?>";
}

// Keep webcam open
async function initWebcam() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        const webcam = document.getElementById("webcam");
        webcam.srcObject = stream;
        webcam.style.display = "block";
        document.getElementById("webcamPlaceholder").style.display = "none";
    } catch (error) {
        console.error("Webcam initialization failed:", error);
    }
}

// Init on load
window.onload = () => {
    initWebcam();
    showQuestion(0);
};
</script>
</body>
</html>
