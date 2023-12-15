<?php
// Start the session, to load all the Session Variables
session_start();
// Connect to the database
require_once("db_connect.php");
// Checking the inactivity 
require_once("inactivity_check.php");

// Check if the user is not logged in, then redirect to the login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if the user wants to logout or not
if (isset($_GET['logout']) && ($_GET['logout']) === 'true') {
    // Terminate all the session
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get the user's role from the session.
$role = $_SESSION['userRole'];

// Check if the user's role for access permission
if ($role !== 'admin' && $role !== 'supervisor') {
    session_destroy();
    $error_message = "Access Denied.";
    header("Location: login.php?error=" . urlencode($error_message));
    exit();
}

// Retrieve the requested module's name from the URL parameters.
$moduleName = $_GET["moduleName"];

// Retrieve the requested module's ID from the URL parameters.
$moduleId = $_GET["moduleId"];

/* ================================================================================== */

// Prepare and execute the query to retrieve the count of modules
$stmt = $conn->prepare("SELECT COUNT(*) AS totalModules FROM module_questions WHERE module_id = ?");
// Bind the 'moduleId' parameter to the prepared statement as an integer ('i' type)
$stmt->bind_param("i", $moduleId);
// Execute the prepared statement to fetch the result from the database
$stmt->execute();
// Get the result set from the executed statement
$result = $stmt->get_result();
// Fetch the associative array representing the row from the result set
$row = $result->fetch_assoc();
// Extract the value of 'totalModules' column from the fetched row
$totalModules = $row['totalModules'];

// Close the statement
$stmt->close();

/* ================================================================================== */

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the questions and options submitted
    $moduleQuestions = $_POST['moduleQuestion'];
    $option1 = $_POST['option1'];
    $option2 = $_POST['option2'];
    $option3 = $_POST['option3'];
    $option4 = $_POST['option4'];
    $correctAnswer = $_POST['correctAnswer'];

    // Set a session variable to indicate successful question creation
    $_SESSION['question_created'] = true;


    // Prepare and execute the query to insert the data into the database
    $stmt = $conn->prepare("INSERT INTO module_questions (question, option1, option2, option3, option4, correct_answer, module_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters for safe insertion.
    // Parameters: question, option1, option2, option3, option4, correctAnswer, moduleId.
    for ($i = 0; $i < count($moduleQuestions); $i++) {
        $stmt->bind_param("ssssssi", $moduleQuestions[$i], $option1[$i], $option2[$i], $option3[$i], $option4[$i], $correctAnswer[$i], $moduleId);
        // Execute the insertion query.
        $stmt->execute();
    }

    // Close the statement
    $stmt->close();

    // Update the totalModules count after inserting the questions
    $totalModules += count($moduleQuestions);
}
$conn->close();
?>

<!-- ==================================================================================  -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Create Question</title>
</head>

<style>
    @media (max-width: 576px) {

        label,
        span {
            font-size: 12px;
        }

        #createQuestionBtn {
            font-size: 12px;
        }
    }
</style>
<!-- ==================================================================================  -->

<body>

    <?php require_once("nav-bar.php"); ?>

    <!-- ==================================================================================  -->

    <div class="wrapper d-flex flex-column justify-content-center align-items-center" style="min-height: calc(100vh - 170px);">
        <div class="container">
            <div class="d-flex justify-content-start mt-5">
                <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
            </div>
            <h1 class="text-center" id="stepText"><strong>Create Questions</strong></h1>
        </div>

        <div class="container mb-5">
            <div class="p-5 text-white rounded-3 bg-gradient signature-bg-color shadow-lg">
                <div class="row justify-content-center">
                    <div class="col">
                        <div id="formContainer">
                            <div class="form-block">
                                <form id="moduleForm" method="post" class="needs-validation" novalidate>
                                    <div class="form-group mb-3" id="quesstionContainer">
                                        <label for="moduleQuestion" style="font-weight: bold;">Question</label>
                                        <textarea class="form-control " rows="3" id="moduleQuestion" name="moduleQuestion[]" required></textarea>
                                        <div class="invalid-feedback text-info">
                                            Please provide a question.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="option1" class="form-label" style="font-weight: bold;">Option 1</label>
                                        <input type="text" class="form-control" id="option1" name="option1[]" required>
                                        <div class="invalid-feedback text-info">
                                            Please provide Option 1.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="option2" class="form-label" style="font-weight: bold;">Option 2</label>
                                        <input type="text" class="form-control" id="option2" name="option2[]" required>
                                        <div class="invalid-feedback text-info">
                                            Please provide Option 2.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="option3" class="form-label" style="font-weight: bold;">Option 3</label>
                                        <input type="text" class="form-control" id="option3" name="option3[]" required>
                                        <div class="invalid-feedback text-info">
                                            Please provide Option 3.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="option4" class="form-label" style="font-weight: bold;">Option 4</label>
                                        <input type="text" class="form-control" id="option4" name="option4[]" required>
                                        <div class="invalid-feedback text-info">
                                            Please provide Option 4.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="correctAnswer" class="form-label" style="font-weight: bold;">Correct Answer</label>
                                        <input type="text" class="form-control" id="correctAnswer" name="correctAnswer[]" required>
                                        <div class="invalid-feedback text-info">
                                            Please provide the correct answer for the question.
                                        </div>
                                    </div>

                                    <!-- Total Blocks -->
                                    <div class="container">
                                        <div class="row justify-content-center mt-5">
                                            <div class="col-md-6"> <!-- Adjust the column width based on your preference -->
                                                <div class="text-center alert alert-info">
                                                    <?php if ($totalModules > 0) : ?>
                                                        <strong>
                                                            <span><?php echo $totalModules; ?> Question(s) in <?php echo $moduleName ?> module</span>
                                                        </strong>
                                                    <?php else : ?>
                                                        <span><strong>No question in <?php echo $moduleName ?> module</strong></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit buttons -->
                                    <div class="text-center mt-3">
                                        <a href="#myModal" role="button" id="createQuestionBtn" class="btn btn-dark">Create Question</a>
                                    </div>

                                    <!-- ================================================================================== -->

                                    <!-- Modal HTML -->
                                    <div id="myModal" class="modal fade text-black" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmation</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to create the question(s)?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn signature-btn">Confirm</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Add this modal after the other modals in the HTML -->
                                    <!-- Success Modal -->
                                    <div id="successModal" class="modal fade text-black" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Question successfully created!</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Okay</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Error Modal -->
                                    <div class="modal fade text-black" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                                                </div>
                                                <div class="modal-body" id="modalBody">
                                                    <!-- Error message will be displayed here -->
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Okay</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("footer_logout.php") ?>
    <!-- ================================================================================== -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ================================================================================== -->

    <script>
        // JavaScript validation logic
        document.getElementById("createQuestionBtn").addEventListener("click", function(event) {
            // Check if all required fields are filled
            var form = document.getElementById("moduleForm");
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add("was-validated");
            } else {
                // Get the option values
                var option1 = document.getElementById("option1").value.trim();
                var option2 = document.getElementById("option2").value.trim();
                var option3 = document.getElementById("option3").value.trim();
                var option4 = document.getElementById("option4").value.trim();

                // Check if any options have the same value
                if (option1 === option2 || option1 === option3 || option1 === option4 || option2 === option3 || option2 === option4 || option3 === option4) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.classList.add("was-validated");

                    // Display an error message in a modal
                    var modalBody = document.getElementById("modalBody");
                    modalBody.textContent = "Options cannot have the same value.";
                    $("#errorModal").modal("show");
                } else {
                    // Get the selected correct answer
                    var correctAnswer = document.getElementById("correctAnswer").value.trim();

                    // Check if the correct answer matches any of the options
                    if (correctAnswer !== option1 && correctAnswer !== option2 && correctAnswer !== option3 && correctAnswer !== option4) {
                        event.preventDefault();
                        event.stopPropagation();
                        form.classList.add("was-validated");

                        // Display an error message in a modal
                        var modalBody = document.getElementById("modalBody");
                        modalBody.textContent = "The correct answer should match one of the provided options.";
                        $("#errorModal").modal("show");
                    } else {
                        // Show the modal if all fields are filled and the correct answer matches an option
                        $("#myModal").modal("show");
                    }
                }
            }
        });

        // Check for the session variable on page load
        $(document).ready(function() {
            <?php if (isset($_SESSION['question_created']) && $_SESSION['question_created'] === true) : ?>
                // Show the success modal
                $("#successModal").modal("show");
                // Unset the session variable to avoid showing the modal on subsequent page loads
                <?php unset($_SESSION['question_created']); ?>
            <?php endif; ?>
        });
    </script>

    <!-- ==================================================================================  -->

</html>
</body>