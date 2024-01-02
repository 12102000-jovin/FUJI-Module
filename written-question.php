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

if (isset($_SESSION['employeeId'])) {
    $employeeId = $_SESSION['employeeId'];
    // var_dump($employeeId);
} else {
    echo "Session variable 'employee_id' is not set.";
}

$moduleId = $_GET["moduleId"];

// Query to select questions
$select_query = "SELECT * FROM written_questions WHERE module_id = $moduleId";
$select_result = $conn->query($select_query);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $questionNumber = 1; // Initialize the question counter outside the loop

    while ($row = $select_result->fetch_assoc()) {
        $answer = $_POST['answer' . $questionNumber];

        if (isset($row['written_question_id']) && isset($employeeId)) {
            // Check if an answer already exists for the current question and employee
            $check_query = "SELECT * FROM written_answers WHERE written_question_id = ? AND employee_id = ?";
            $check_stmt = $conn->prepare($check_query);
            if ($check_stmt) {
                $check_stmt->bind_param("ii", $row['written_question_id'], $employeeId);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    // If an answer exists, update it
                    $update_query = "UPDATE written_answers SET written_answer = ?, datetime = NOW(), is_marked = '0' WHERE written_question_id = ? AND employee_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    if ($update_stmt) {
                        $update_stmt->bind_param("sii", $answer, $row['written_question_id'], $employeeId);
                        if ($update_stmt->execute()) {
                            echo "Answer Updated Successfully";

                            // Update is_correct to NULL in written_results
                            $nullify_query = "UPDATE written_results SET is_correct = NULL, feedback = NULL WHERE written_answer_id IN (SELECT written_answer_id FROM written_answers WHERE written_question_id = ? AND employee_id = ?)";
                            $nullify_stmt = $conn->prepare($nullify_query);
                            if ($nullify_stmt) {
                                $nullify_stmt->bind_param("ii", $row['written_question_id'], $employeeId);
                                $nullify_stmt->execute();
                                $nullify_stmt->close();
                            } else {
                                echo "Nullify Prepared statement error: " . $conn->error;
                            }

                            header("Location: index.php");
                        } else {
                            echo "Update Not Successful: " . $update_stmt->error;
                        }

                        $update_stmt->close();
                    } else {
                        echo "Update Prepared statement error: " . $conn->error;
                    }
                } else {
                    // If no answer exists, insert a new answer
                    $insert_query = "INSERT INTO written_answers (written_answer, written_question_id, employee_id, module_id, datetime) VALUES (?, ?, ?, ?, NOW())";
                    $insert_stmt = $conn->prepare($insert_query);
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("siii", $answer, $row['written_question_id'], $employeeId, $moduleId);
                        if ($insert_stmt->execute()) {
                            // echo "Answer Inserted Successfully";
                            header("Location: index.php");
                        } else {
                            echo "Insert Not Successful: " . $insert_stmt->error;
                        }
                        $insert_stmt->close();
                    } else {
                        echo "Insert Prepared statement error: " . $conn->error;
                    }
                }

                $check_stmt->close();
            } else {
                echo "Check Prepared statement error: " . $conn->error;
            }
        } else {
            // Handle any error or log that 'written_question_id' or 'employee_id' is missing
            echo "Missing";
        }
        $questionNumber++; // Increment the question counter
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
    <title>Essay Quiz</title>
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

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card mt-5 mb-5">
                    <div class="card-body">
                        <form method="POST">
                            <?php
                            $questionNumber = 1;
                            echo "<h1 class='text-center'> <strong> Essay Quiz </strong> </h1>";
                            while ($row = $select_result->fetch_assoc()) {
                                echo "<p class='card-title mt-5'> Question $questionNumber: </p>";
                                // echo "<p class='card-text'> Question Id: " . $row['written_question_id'] . "</p>";
                                echo "<p class='card-text'><h4>" . $row['question'] . "</h4></p>";
                                echo "<div class='mb-3'>";
                                echo "<label for='answerInput$questionNumber' class='form-label'><strong>Your Answer:</strong></label>";
                                echo "<textarea class='form-control' name='answer$questionNumber' id='answerInput$questionNumber' rows='3' required></textarea>";
                                echo "</div>";
                                $questionNumber++;
                            }
                            ?>
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#confirmationModal">Submit Answers</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Confirmation -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to submit your answers?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn signature-btn" id="confirmSubmissionBtn">Submit Answers</button>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript">
        // Disable the back button
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
            alert("Access to the previous page in the quiz is restricted.");
        };
    </script>
    <script>
        document.getElementById('confirmSubmissionBtn').addEventListener('click', function() {
            // Trigger the actual form submission when the modal confirmation button is clicked
            document.forms[0].submit();
        });
    </script>

</body>

</html>