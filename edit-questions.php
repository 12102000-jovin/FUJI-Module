<?php
session_start();

require_once("db_connect.php");
// Checking the inactivity 
require_once("inactivity_check.php");
$role = $_SESSION['userRole'];

// Check if the user's role for access permission
if ($role !== 'admin' && $role !== 'supervisor') {
    session_destroy();
    $error_message = "Access Denied.";
    header("Location: login.php?error=" . urlencode($error_message));
    exit();
}

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

$moduleId = $_GET["module_id"];
$sql = "SELECT * FROM module_questions WHERE module_id = $moduleId";
$result = mysqli_query($conn, $sql);
$totalQuestions = mysqli_num_rows($result);

// Fetch the module name
$moduleSql = "SELECT module_name FROM modules WHERE module_id = $moduleId";
$moduleNameResult = mysqli_query($conn, $moduleSql);
$moduleName = "";
if ($moduleNameRow = mysqli_fetch_assoc($moduleNameResult)) {
    $moduleName = $moduleNameRow['module_name'];
}

// Custom exception class for FK errors
class ForeignKeyException extends Exception
{
    public function __construct($msg = "Foreign key constraint violation", $code = 0)
    {
        parent::__construct($msg, $code);
    }
}

// Check if the delete button is clicked
if (isset($_POST['delete_question_id'])) {
    $deleteQuestionId = $_POST['delete_question_id'];

    // Delete the question from the database
    $deleteSql = "DELETE FROM module_questions WHERE questions_id = ?";
    $deleteResult = $conn->prepare($deleteSql);
    $deleteResult->bind_param("i", $deleteQuestionId);

    try {
        if ($deleteResult->execute()) {
            $_SESSION['delete_success'] = true;
            header("Location: " . $_SERVER['PHP_SELF'] . '?module_id=' . $moduleId);
            exit();
        } else {
            echo "Question deletion failed!";
        }
    } catch (mysqli_sql_exception $e) {
        // Check if the error message contains specific keywords indicating a foreign key error
        if (strpos($e->getMessage(), "a foreign key constraint fails") !== false) {
            // Deletion failed due to dependencies, set the error message
            $errorMessage = "Error deleting question: This question has dependencies in other tables.";
            $_SESSION['delete_constraint'] = true;
        } else {
            // Handle other exceptions
            $errorMessage = "An error occurred: " . $e->getMessage();
        }
    }
    $deleteResult->close();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Edit Questions</title>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<style>
    @media (max-width: 576px) {
        h5 {
            font-size: 18px;
        }

        p,
        li,
        .editBtn,
        .deleteBtn {
            font-size: 12px;
        }
    }
</style>

<!-- ================================================================================== -->

<body class=" bg-gradient d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container">
        <h1 class="mt-5 text-center" id="stepText">
            <div class="d-flex justify-content-start mt-5">
                <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
            </div>
            <strong>Edit Questions</strong>
        </h1>
        <p class="text-center">Total Questions in <u><strong><?php echo $moduleName ?> Module</strong></u>: <strong><?php echo mysqli_num_rows($result); ?></strong></p>
        <div class="d-flex justify-content-center">
            <a class="btn btn-dark" href="create-question.php?moduleId=<?php echo $moduleId; ?>&moduleName=<?php echo urlencode($moduleName); ?>" role="button">Create Questions</a>
        </div>
    </div>

    <!-- ================================================================================== -->

    <div class="container mb-5">
        <div class="row">
            <?php
            if ($totalQuestions == 0) {
                echo '
                <div class="col-12 d-flex align-items-center justify-content-center">
                    <div class="alert alert-danger mt-5 text-center" role="alert" style="max-width: 400px; margin: auto;">
                        <h4><strong>No questions in this module</strong></h4>
                    </div>
                </div>';
            } else {
                while ($row = mysqli_fetch_assoc($result)) {
                    $questionId = $row['questions_id'];
                    $question = $row['question'];
                    $option1 = $row['option1'];
                    $option2 = $row['option2'];
                    $option3 = $row['option3'];
                    $option4 = $row['option4'];
                    $correctAnswer = $row['correct_answer'];
                    $moduleId = $row['module_id'];
            ?>
                    <div class="col-sm-6 mt-3">
                        <div class="card mb-3 h-1000">
                            <div class="card-body">
                                <h5 class="card-text"><?php echo $question; ?></h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><?php echo $option1; ?></li>
                                    <li class="list-group-item"><?php echo $option2; ?></li>
                                    <li class="list-group-item"><?php echo $option3; ?></li>
                                    <li class="list-group-item"><?php echo $option4; ?></li>
                                </ul>
                                <p class="card-text"><strong>Correct Answer: </strong><?php echo $correctAnswer; ?></p>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn signature-btn m-1 editBtn" onclick="window.location.href='edit-questions-more.php?questions_id=<?php echo $questionId; ?>&module_id=<?php echo $moduleId; ?>';" style="text-decoration: none;">Edit Question</button>
                                    <a href="<?php echo $_SERVER['PHP_SELF'] . '?module_id=' . $moduleId . '&delete_question_id=' . $questionId; ?>" class="btn btn-danger m-1 deleteBtn" style="text-decoration: none;" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal-<?php echo $questionId; ?>">Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Confirmation Modal -->
                    <div class="modal fade text-black" id="deleteConfirmationModal-<?php echo $questionId; ?>" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel-<?php echo $questionId; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteConfirmationModalLabel-<?php echo $questionId; ?>">Confirmation</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete this question?</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <form method="post">
                                        <input type="hidden" name="delete_question_id" value="<?php echo $questionId; ?>">
                                        <input type="hidden" name="module_id" value="<?php echo $moduleId; ?>">
                                        <button type="submit" class="btn btn-danger" name="delete_question">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                }
            }
            ?>
        </div>
    </div>

    <!-- ================================================================================== -->

    <?php
    if (isset($_SESSION['delete_success']) && $_SESSION['delete_success']) {
        echo '
    <script>
        $(document).ready(function() {
            $("#successModal").modal("show");
        });
    </script>';
        // Reset the session variable to avoid showing the modal on page refresh
        $_SESSION['delete_success'] = false;
    }
    ?>

    <?php
    if (isset($_SESSION['delete_constraint']) && $_SESSION['delete_constraint']) {
        echo '
    <script>
        $(document).ready(function() {
            $("#deleteErrorModal").modal("show");
        });
    </script>';
        // Reset the session variable to avoid showing the modal on page refresh
        $_SESSION['delete_constraint'] = false;
    }
    ?>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Question deleted successfully.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap modal for displaying deletion error messages -->
    <div class="modal fade" id="deleteErrorModal" tabindex="-1" role="dialog" aria-labelledby="deleteErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-top">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteErrorModalLabel">Error</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo $errorMessage; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php require_once("footer_logout.php") ?>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</html>