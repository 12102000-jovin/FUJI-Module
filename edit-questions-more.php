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
if ($role !== 'admin' && $role !== 'supervisor') {
    session_destroy();
    $error_message = "Access Denied.";
    header("Location: login.php?error=" . urlencode($error_message));
    exit();
}

$questionId = $_GET['questions_id'];
$moduleId = $_GET["module_id"];

/* ================================================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if the form has been submitted

    // Retrieve the updated question data from the form
    $updatedQuestion = $_POST['moduleQuestion'][0]; // Assuming only one question is being updated
    $updatedOption1 = $_POST['option1'][0];
    $updatedOption2 = $_POST['option2'][0];
    $updatedOption3 = $_POST['option3'][0];
    $updatedOption4 = $_POST['option4'][0];
    $updatedCorrectAnswer = $_POST['correctAnswer'][0];

    $moduleId = $_GET["module_id"];

    // Update the corresponding record in the database
    $updateQuery = "UPDATE module_questions SET question=?, option1=?, option2=?, option3=?, option4=?, correct_answer=? WHERE questions_id = $questionId";
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "ssssss", $updatedQuestion, $updatedOption1, $updatedOption2, $updatedOption3, $updatedOption4, $updatedCorrectAnswer);
    $updateResult = mysqli_stmt_execute($stmt);

    // Check if the update was successful
    if ($updateResult) {
        // echo "Question updated successfully!";
        // You may want to redirect the user back to the same page or to a different page after the update.
        header("Location: edit-questions.php?module_id=$moduleId");
        // exit;
    } else {
        echo "Error updating the question: " . mysqli_error($conn);
    }
}

// Retrieve the data from the database
$query = "SELECT * FROM module_questions WHERE questions_id = $questionId";
$result = mysqli_query($conn, $query);

// Check if the query was successful
if ($result) {
    // Fetch the data into an associative array
    $row = mysqli_fetch_assoc($result);
    $question = $row['question'];
    $option1 = $row['option1'];
    $option2 = $row['option2'];
    $option3 = $row['option3'];
    $option4 = $row['option4'];
    $correctAnswer = $row['correct_answer'];

    // Free the result variable
    mysqli_free_result($result);
} else {
    // Query was not successful, handle the error
    echo "Error executing the query: " . mysqli_error($conn);
}

// Close the database connection
mysqli_close($conn);
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
    <title>Edit Questions</title>
</head>

<!-- ================================================================================== -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
    </div>

    <div class="wrapper d-flex flex-column justify-content-center align-items-center mb-5" style="min-height: calc(100vh - 170px);">
        <div class="container">
            <h1 class="mb-3 text-center" id="stepText"><strong> Edit Questions </strong></h1>

            <form method="post" id="moduleForm" class="p-5 text-white rounded-3 shadow-lg bg-gradient signature-bg-color">
                <div class="form-group" id="questionContainer">
                    <label for="moduleQuestion" style="font-weight: bold;">Question</label>
                    <textarea class="form-control" rows="3" id="moduleQuestion" name="moduleQuestion[]" required><?php echo $question; ?></textarea>
                    <div class="invalid-feedback">
                        Please provide a question.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="option1" class="form-label" style="font-weight: bold;">Option 1</label>
                    <input type="text" class="form-control" id="option1" name="option1[]" required value="<?php echo $option1; ?>">
                    <div class="invalid-feedback">
                        Please provide Option 1.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="option2" class="form-label" style="font-weight: bold;">Option 2</label>
                    <input type="text" class="form-control" id="option2" name="option2[]" required value="<?php echo $option2; ?>">
                    <div class="invalid-feedback">
                        Please provide Option 2.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="option3" class="form-label" style="font-weight: bold;">Option 3</label>
                    <input type="text" class="form-control" id="option3" name="option3[]" required value="<?php echo $option3; ?>">
                    <div class="invalid-feedback">
                        Please provide Option 3.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="option4" class="form-label" style="font-weight: bold;">Option 4</label>
                    <input type="text" class="form-control" id="option4" name="option4[]" required value="<?php echo $option4; ?>">
                    <div class="invalid-feedback">
                        Please provide Option 4.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="correctAnswer" class="form-label" style="font-weight: bold;">Correct Answer</label>
                    <input type="text" class="form-control" id="correctAnswer" name="correctAnswer[]" required value="<?php echo $correctAnswer; ?>">
                    <div class="invalid-feedback">
                        Please provide the correct answer for the question.
                    </div>
                </div>

                <!-- Submit buttons -->
                <div class="text-center mt-3">
                    <a href="#myModal" role="button" id="updateQuestionBtn" class="btn btn-dark">Update Question</a>
                </div>

                <!-- Modal HTML -->
                <div id="myModal" class="modal fade text-black" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Do you want to update the question?</p>
                                <p class="text-secondary"><small>If you don't update, your changes will be lost.</small></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn signature-btn" id="moduleForm">Update Question</button>
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

    <!-- ================================================================================== -->

    <?php require_once("footer_logout.php") ?>

    <!-- ================================================================================== -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // JavaScript validation logic
        document.getElementById("updateQuestionBtn").addEventListener("click", function(event) {
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
    </script>

</body>

</html>