<?php
session_start();

require_once("db_connect.php");

// Checking the inactivity 
require_once("inactivity_check.php");

$role = $_SESSION['userRole'];

// Set the default time zone to Sydney
date_default_timezone_set('Australia/Sydney');

// Check if the user is not logged in, then redirect to the login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: login.php");
  exit();
}

// Logout script
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
  session_destroy();
  header("Location: login.php");
  exit();
}
?>

<!-- ================================================================================== -->

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
  <link rel="stylesheet" type="text/css" href="style.css">
  <title>Quiz</title>
  <style>
    /* to remove the highlight for the input group */
    .form-control:focus {
      box-shadow: none;
      /* Remove the box shadow */
    }
  </style>
</head>

<body class="d-flex flex-column min-vh-100">
  <!-- navigation bar -->
  <nav class="navbar navbar-expand-lg shadow-sm bg-light" style="height: 55px;">
    <div class="container-fluid">
      <!-- Image visible on small screens (Hamburger Menu Button is there) -->
      <div class="d-block d-lg-none">
        <div class="d-flex align-items-center hstack gap-3">
          <a href="index.php">
            <img src="Images/FE-logo.png" alt="Logo" class="img-fluid" style="max-height: 30px;">
          </a>
          <div class="vr signature-color" style="border: 1px solid"></div>
          <div>
            <h3 class="my-auto signature-color fw-bold">Module Training</h3>
          </div>
        </div>
      </div>

      <!-- Collapsible Navigation Links -->
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <!-- Website Logo -->
        <div class="d-none d-lg-block">
          <div class="d-flex align-items-center hstack gap-3">
            <a href="index.php">
              <img src="Images/FE-logo.png" alt="Logo" class="img-fluid" style="max-height: 30px;">
            </a>
            <div class="vr signature-color" style="border: 1px solid"></div>
            <div>
              <h3 class=" my-auto signature-color fw-bold">Module Training</h3>
            </div>
          </div>
        </div>
      </div>
  </nav>

  <!-- ================================================================================== -->

  <div class="container d-flex flex-column justify-content-center align-items-center flex-grow-1">
    <div class="question-box" style="display: grid; place-items:center;">
      <form method="post" class="text-white p-5 rounded-3 bg-gradient signature-bg-color">
        <?php

        // Check if the quiz session has timed out (e.g., 30 minutes timeout)
        $sessionTimeout = 5 * 60;

        if (isset($_SESSION['quiz_start_time']) && (time() - $_SESSION['quiz_start_time']) > $sessionTimeout) {
          // Session has timed out, reset the quiz start time
          unset($_SESSION['quiz_start_time']);
        }

        if (!isset($_SESSION['quiz_start_time'])) {
          $_SESSION['quiz_start_time'] = time(); // Store the quiz start time in the session
        }

        // Before updating elapsed time
        // echo "Start Time: " . date("Y-m-d H:i:s", $_SESSION['quiz_start_time']) . "<br>";

        $moduleId = $_GET['module_id'];

        // Check connection
        if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
        }

        if (isset($_POST['submit'])) {
          if (isset($_POST['answer'])) {
            // Process the submitted answer
            $submittedAnswer = $_POST['answer'];

            if (isset($_POST['correct_answer'])) {
              $correctAnswer = $_POST['correct_answer'];

              // Check if the submitted answer is correct using case-insensitive comparison
              if (strcasecmp($submittedAnswer, $correctAnswer) === 0) {
                $_SESSION['correct_answers']++;
              }
            } else {
              echo "Error: Correct answer not found.";
            }

            // Increase the current question ID
            $_SESSION['current_question_id']++;

            // Store the chosen answer in the user_answers table
            $employee_id = $_SESSION['employeeId'] ?? 'N/A';
            $result_id = $_SESSION['result_id'];
            $chosenAnswer = $submittedAnswer;
            $questionId = $_SESSION['current_question']['questions_id'];

            // Prepare the query to insert the user's answer into the "user_answers" table
            $stmt = $conn->prepare("INSERT INTO user_answers (answer_id, employee_id, module_id, questions_id, chosen_answer) VALUES (DEFAULT, ?, ?, ?, ?)");
            $stmt->bind_param("iiis", $employee_id, $moduleId, $questionId, $chosenAnswer);

            // Execute the query
            $stmt->execute();

            // Check if the query was successful
            if ($stmt->affected_rows <= 0) {
              echo "<p>Error storing user's answer for question ID: $questionId</p>";
            }
          }
        }

        // Initialize the current question ID and correct answers count
        if (!isset($_SESSION['current_question_id'])) {
          $_SESSION['current_question_id'] = 1;
          $_SESSION['correct_answers'] = 0;
        }

        // Fetch all the questions from the database if it's the first question
        if ($_SESSION['current_question_id'] === 1) {
          $sql = "SELECT * FROM module_questions WHERE module_id = $moduleId";
          $result = $conn->query($sql);

          // Check if there are questions available
          if ($result->num_rows > 0) {
            // Fetch all the questions into an array
            $questions = $result->fetch_all(MYSQLI_ASSOC);

            // Shuffle the questions to randomize the order
            shuffle($questions);

            // Store the shuffled questions in the session
            $_SESSION['shuffled_questions'] = $questions;
          } else {
            echo "<p>No questions found.</p>";
          }
        }

        // Retrieve the shuffled questions from the session
        $questions = $_SESSION['shuffled_questions'];

        // Check if there are more questions to display
        if ($_SESSION['current_question_id'] <= count($questions)) {
          // Get the current question index
          $currentQuestionIndex = $_SESSION['current_question_id'] - 1;

          // Get the current question from the shuffled array
          $question = $questions[$currentQuestionIndex];

          $_SESSION['current_question'] = $question;

          $questionText = $question['question'];
          $options = array($question['option1'], $question['option2'], $question['option3'], $question['option4']);
          $correctAnswer = $question['correct_answer'];
          $questionId = $question['questions_id'];

          // Display the question number and question text
          echo "<h4 class='question'>$questionText</h4>";

          foreach ($options as $option) {
            echo "<div class='input-group mb-2'>";
            echo "<div class='input-group-text'>";
            echo "<input class='form-check-input mt-0 sr-only' type='radio' name='answer' value='$option' aria-label='Radio button for following text input' onclick='enableNextButton()'>";
            echo "</div>";
            echo "<input type='text' class='form-control' value='$option' aria-label='Text input with radiobutton' style='background-color: white;' readonly>";
            echo "</div>";
          }

          // Display the question number and total number of questions at the bottom
          echo '<div class="text-center mt-3">';
          echo 'Question ' . $_SESSION['current_question_id'] . ' of ' . count($questions);
          echo '</div>';

          // Store the question_id and correct answer as hidden input fields
          echo "<input type='hidden' name='correct_answer' value='$correctAnswer'>";

          // Add the submit button for the current question
          echo '<button id="nextButton" class="btn btn-dark mx-auto d-block mt-5" type="submit" name="submit" style="width:50%" disabled onclick="disableConfirmationAlert()">Next Question</button>';
        } else {
          // Display the quiz result
          $correctAnswers = $_SESSION['correct_answers'];
          $totalQuestions = count($questions);

          echo "<div class='text-center'>";
          echo "<p>You have answered all the questions.</p>";
          echo "<p>You got $correctAnswers correct out of $totalQuestions questions.</p>";

          $score = round(($correctAnswers / $totalQuestions) * 100, 2);

          echo "<p><strong>Your score is $score%</strong></p>";

          // Reset the session variables for a new quiz
          unset($_SESSION['current_question_id']);
          unset($_SESSION['correct_answers']);
          unset($_SESSION['shuffled_questions']);

          // Calculate and display the elapsed time
          $endTime = time();
          $startTime = $_SESSION['quiz_start_time'];
          $elapsedTimeInSeconds = $endTime - $startTime;
          // Calculate the elapsed time in minutes and seconds
          $elapsedMinutes = floor($elapsedTimeInSeconds / 60);
          $elapsedSeconds = $elapsedTimeInSeconds % 60;

          // Format and display the elapsed time
          $formattedElapsedTime = "$elapsedMinutes" . "m " . "$elapsedSeconds" . "s";
          echo "Total Time: <strong> $formattedElapsedTime </strong><br><br>";

          // echo "End Time: " . date("Y-m-d H:i:s", $_SESSION['$endTime']) . "<br>";

          unset($_SESSION['quiz_start_time']);

          // Add a button to start a new quiz
          echo '<div class="container text-center">';
          echo '<button class="btn btn-secondary mr-3" type="submit" name="try_again" onclick="disableConfirmationAlert()">Try Again</button>';
          echo '<span style="margin-right: 10px;"></span>';
          echo '<a href="index.php" class="btn btn-dark" onclick="disableConfirmationAlert()">Home</a>';
          echo '<span style="margin-right: 10px;"></span>';
          echo '<a href="written-question.php?moduleId=' . $moduleId . '" class="btn btn-info" onclick="disableConfirmationAlert()">Continue to Essay</a>';

          // Store the quiz results in the database
          $employee_id = $_SESSION['employeeId'] ?? 'N/A';

          // Prepare the query to insert the quiz results into the database
          $stmt = $conn->prepare("INSERT INTO results (employee_id, module_id, score, timestamp, duration) VALUES (?, ?, ?, NOW(), ?)");
          $stmt->bind_param("iisi", $employee_id, $moduleId, $score, $elapsedTimeInSeconds);

          // Execute the query
          $stmt->execute();

          // Check if the query was successful
          if ($stmt->affected_rows > 0) {
            $result_id = $stmt->insert_id;
            $_SESSION['result_id'] = $result_id;

            $stmt = $conn->prepare("UPDATE user_answers SET result_id = ? WHERE employee_id = ? AND module_id = ? AND result_id IS NULL");
            $stmt->bind_param("iii", $result_id, $employee_id, $moduleId);
            $stmt->execute();

            // Check if the query was successful
            if ($stmt->affected_rows <= 0) {
              echo "<p>Error updating result_id in user_answers table</p>";
            }
          } else {
            echo "<p>Error storing quiz results: " . $conn->error . "</p>";
          }

          // Close the statement
          $stmt->close();
        }

        $conn->close();
        ?>
      </form>
    </div>
  </div>

  <!-- ================================================================================== -->

  <!-- Footer Section -->
  <footer class="bg-light text-center py-4 fixed-bottom shadow-lg">
    <div class="container">
      <p class="mb-0 font-weight-bold" style="font-size: 1.5vh"><strong>&copy; <?php echo date('Y'); ?> FUJI Training Module. All rights reserved.</strong></p>
    </div>
  </footer>

  <!-- ================================================================================== -->

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script type="text/javascript">
    window.addEventListener('load', function() {
      // Disable the back button
      history.pushState(null, null, location.href);
      window.onpopstate = function() {
        history.go(1);
        alert("Access to the previous page in the quiz is restricted.");
      };
    });
  </script>


  <script>
    // Get references to the radio buttons and their parent input groups
    var options = document.querySelectorAll('input[name="answer"]');
    var inputGroups = document.querySelectorAll('.input-group');

    // Add event listeners to the input groups
    inputGroups.forEach(function(inputGroup) {
      inputGroup.addEventListener('click', handleInputGroupClick);
    });

    // Function to handle the input group click event
    function handleInputGroupClick(event) {
      console.log('Input group clicked');
      // Find the radio button within the clicked input group and select it
      var selectedOption = event.currentTarget.querySelector('input[name="answer"]');
      if (selectedOption) {
        selectedOption.checked = true;
      }

      // Reset the background color for all input groups
      inputGroups.forEach(function(inputGroup) {
        resetInputGroupBackgroundColor(inputGroup);
      });

      // Change the background color for the clicked input group
      var clickedInputGroup = event.currentTarget;
      changeInputGroupBackgroundColor(clickedInputGroup);

      // Enable or disable the Next Question button
      enableNextButton();
    }

    // Function to reset the background color for the input group
    function resetInputGroupBackgroundColor(inputGroup) {
      var formControl = inputGroup.querySelector('.form-control');
      var inputGroupText = inputGroup.querySelector('.input-group-text');
      var formControlText = inputGroup.querySelector('.form-control');
      formControl.style.backgroundColor = 'white';
      inputGroupText.style.backgroundColor = '';
      formControlText.style.color = '';
    }

    // Function to change the background color for the input group
    function changeInputGroupBackgroundColor(inputGroup) {
      var formControl = inputGroup.querySelector('.form-control');
      var inputGroupText = inputGroup.querySelector('.input-group-text');
      var formControlText = inputGroup.querySelector('.form-control');
      formControl.style.backgroundColor = '#c8e8ff';
      inputGroupText.style.backgroundColor = '#c8e8ff';
      formControlText.style.color = '#000000';
    }

    // Function to enable or disable the Next Question button
    function enableNextButton() {
      var radioButtons = document.getElementsByName('answer');
      var nextButton = document.getElementById('nextButton');
      var checked = false;

      for (var i = 0; i < radioButtons.length; i++) {
        if (radioButtons[i].checked) {
          checked = true;
          break;
        }
      }

      nextButton.disabled = !checked;
    }
  </script>



</body>

</html>