<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("db_connect.php");
require_once("inactivity_check.php");

$role = $_SESSION['userRole'];

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

// Query to select questions
$select_query = "SELECT * FROM written_questions WHERE module_id = 1";
$select_result = $conn->query($select_query);

$_SESSION['employee_id'] = 111;
$employee_id = $_SESSION['employee_id'];

// Process form submission
if (isset($_POST['submitAnswers'])) {
    $questionNumber = 1;
    $select_result = $conn->query($select_query); // Execute the query again to reset the pointer

    while ($row = $select_result->fetch_assoc()) {
        $answer = $_POST['answer' . $questionNumber];

        // Check if 'written_question_id' exists in the $row array
        if (isset($row['written_question_id']) && isset($_SESSION['employee_id'])) {
            $insert_query = "INSERT INTO written_answers (written_answer, written_question_id, employee_id, module_id) VALUES (?, ?, ?, 1)";
            $stmt = $conn->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param("sii", $answer, $row['written_question_id'], $_SESSION['employee_id']);
                if ($stmt->execute()) {
                    echo "Successful";
                } else {
                    echo "Not Successful: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Prepared statement error: " . $conn->error;
            }
        } else {
            // Handle any error or log that 'written_question_id' or 'employee_id' is missing
        }
        $questionNumber++;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Written Quiz</title>
</head>

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10"> <!-- Adjust the column width as needed -->
                <div class="card mb-5 mt-5">
                    <div class="card-body">
                        <form method="post">
                            <?php
                            $questionNumber = 1;
                            echo "<h1 class='text-center'>Written Quiz</h1>";
                            while ($row = $select_result->fetch_assoc()) {
                                echo "<h5 class='card-title mt-5'>Question $questionNumber:</h5>";
                                echo "<p class='card-text'>" . $row['question'] . "</p>";
                                echo "<div class='mb-3'>";
                                echo "<label for='answerInput$questionNumber' class='form-label'><strong>Your Answer:</strong></label>";
                                echo "<textarea class='form-control' name='answer$questionNumber' id='answerInput$questionNumber' rows='3'></textarea>";
                                echo "</div>";
                                $questionNumber++;
                            }
                            ?>
                            <div class="d-flex justify-content-center">
                                <button type="submit" name="submitAnswers" class="btn btn-dark">Submit Answers</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="bg-light text-center py-4 mt-auto shadow-lg">
        <div class="container">
            <p class="mb-0 font-weight-bold" style="font-size: 1.5vh"><strong>&copy; <?php echo date('Y'); ?> FUJI Training Module. All rights reserved.</strong></p>
        </div>
    </footer>

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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>