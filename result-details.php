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

    <div class="container text-center mt-5">
        <h1>Result Details</h1>
    </div>

    <!-- ================================================================================== -->

    <div class="container">
        <div class="text-white mt-5 p-5 rounded-3 bg-gradient signature-bg-color shadow-lg">
            <?php
            foreach ($userAnswers as $userAnswer) {
                echo "<div class='question-answer'>";
                echo "<h4>Question ID: " . $userAnswer['question_id'] . "</h4>";
                echo "<p>Question: " . $userAnswer['question'] . "</p>";

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
                    echo "<td>" . $option;
                    if (($userAnswer['answer'] == $userAnswer['correct_answer']) && ($userAnswer['answer'] == $option)) {
                        echo " <strong class='float-end text-white bg-success'>User chose the correct answer</strong>";
                    } else if ($userAnswer['answer'] == $option) {
                        echo " <strong class='float-end text-white bg-primary'>Chosen Answer</strong>";
                    } else if ($userAnswer['correct_answer'] == $option) {
                        echo " <strong class='float-end text-white bg-success'>Correct Answer</strong>";
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
        <div class="text-center mt-5 mb-5">
            <button class="btn btn-secondary shadow" onclick="window.print()">Print</button>
            <a href="report.php" class="btn btn-dark shadow">Back to Reports</a>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to logout?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="?logout=true" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="bg-light text-center py-4 mt-auto">
        <div class="container">
            <p class="mb-0 font-weight-bold" style="font-size: 1.5vh"><strong>&copy; <?php echo date('Y'); ?> FUJI Training Module. All rights reserved.</strong></p>
        </div>
    </footer>

    <!-- ================================================================================== -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>