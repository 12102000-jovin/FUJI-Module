<?php
session_start();
require_once("db_connect.php");

// Checking the inactivity 
require_once("inactivity_check.php");

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

$role = $_SESSION['userRole'];

// Check if the user's role for access permission
if ($role !== 'admin') {
    session_destroy();
    $error_message = "Access Denied.";
    header("Location: login.php?error=" . urlencode($error_message));
    exit();
}

// Retrieve the result_id from the query parameters
$resultId = isset($_GET['result_id']) ? $_GET['result_id'] : null;

// Fetch data from the user_answers table, joining with module_questions table
$userAnswers = array();
$sql = "SELECT ua.questions_id, ua.chosen_answer, mq.question, mq.option1, mq.option2, mq.option3, mq.option4, mq.correct_answer
            FROM user_answers AS ua
            INNER JOIN module_questions AS mq ON ua.questions_id = mq.questions_id
            WHERE ua.result_id = '$resultId'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userAnswers[] = array(
            'question_id' => $row['questions_id'],
            'question' => $row['question'],
            'answer' => $row['chosen_answer'],
            'option1' => $row['option1'],
            'option2' => $row['option2'],
            'option3' => $row['option3'],
            'option4' => $row['option4'],
            'correct_answer' => $row['correct_answer']
        );
    }
}

$conn->close();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Result Details</title>
</head>

<style>
    /* Add custom CSS styles here */
    @media (max-width: 576px) {

        /* Adjust table styles for small screens */
        table {
            font-size: 12px;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .navbar-nav {
            flex-direction: column;
        }

        .nav-link {
            padding: 0.5rem;
        }
    }
</style>

<!-- ================================================================================== -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container text-center mb-3">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
        <h1><strong>Result Details</strong></h1>
    </div>


    <!-- ================================================================================== -->

    <div class="container mb-5">
        <div class="text-white p-5 rounded-3 bg-gradient signature-bg-color shadow-lg">
            <?php
            foreach ($userAnswers as $userAnswer) {
                echo "<div class='question-answer'>";
                echo "<h4 class='mb-3' style='word-wrap: break-word;'>Question: " . $userAnswer['question'] . "</h4>";

                // Display options
                echo "<table class='table table-bordered table-responsive'>";
                echo "<tr>";
                echo "<th>Options</th>";
                echo "</tr>";

                $options = [$userAnswer['option1'], $userAnswer['option2'], $userAnswer['option3'], $userAnswer['option4']];
                $chosen_answer = $userAnswer['answer'];

                foreach ($options as $option) {
                    echo "<tr";
                    if (($userAnswer['answer'] == $userAnswer['correct_answer']) && ($userAnswer['answer'] == $option)) {
                        echo " class='table-success'";
                    } else if ($userAnswer['answer'] == $option) {
                        echo " class='table-primary'";
                    } else if ($userAnswer['correct_answer'] == $option) {
                        echo " class='table-success'";
                    }
                    echo ">";
                    echo "<td class='d-flex align-items-center text-break'>" . $option;
                    if (($userAnswer['answer'] == $userAnswer['correct_answer']) && ($userAnswer['answer'] == $option)) {
                        echo " <strong class='ms-auto text-white badge rounded-pill bg-success'>User chose the correct answer</strong>";
                    } else if ($userAnswer['answer'] == $option) {
                        echo " <strong class='ms-auto text-white badge rounded-pill bg-danger'>Chosen Answer</strong>";
                    } else if ($userAnswer['correct_answer'] == $option) {
                        echo " <strong class='ms-auto text-white badge rounded-pill bg-success'>Correct Answer</strong>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }

                if (($chosen_answer == null)) {
                    echo "<tr>";
                    echo "<td colspan='4' class='text-danger'>This question is not answered.</td>";
                    echo "</tr>";
                }

                echo "</table>";

                echo "</div>";
                echo "<hr>";
            }
            ?>
        </div>

    </div>

    <!-- ================================================================================== -->

    <?php require_once("footer_logout.php"); ?>

    <!-- ================================================================================== -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>