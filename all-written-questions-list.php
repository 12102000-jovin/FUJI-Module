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

$module_id = $_GET["module_id"];

$select_query =  "SELECT written_question_id,question FROM written_questions WHERE module_id = $module_id";
$select_result = $conn->query($select_query);

$select_module = "SELECT module_name FROM modules WHERE module_id = $module_id";
$select_module_result = $conn->query($select_module);

if ($select_module_result && $select_module_result->num_rows > 0) {
    $row_module = $select_module_result->fetch_assoc();
    $module_name = $row_module['module_name'];
} else {
    // Handle the case where the module is not found
    $module_name = "Module Not Found";
}

// Edit Question
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["question_name"]) && isset($_POST["question_id"])) {
    $question = $_POST["question_name"];
    $question_id = $_POST["question_id"];
    var_dump($question);
    var_dump($question_id);

    $updateQuestionSql = "UPDATE written_questions SET question = ? WHERE written_question_id = ?";
    $stmt = $conn->prepare($updateQuestionSql);
    $stmt->bind_param("si", $question, $question_id);

    if ($stmt->execute()) {
        // Redirect to the same page after updating
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        echo "Error updating data: " . $stmt->error;
    }

    $stmt->close();
}

$showModal = false; // Initialize variable to control modal display

if (isset($_POST['delete_question_id'])) {
    $deleteQuestionId = $_POST['delete_question_id'];

    // Check for dependencies before attempting to delete
    $checkDependenciesSql = "SELECT COUNT(*) FROM written_answers WHERE written_question_id = ?";
    $stmtCheck = $conn->prepare($checkDependenciesSql);
    $stmtCheck->bind_param("i", $deleteQuestionId);
    $stmtCheck->execute();
    $stmtCheck->bind_result($dependencyCount);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($dependencyCount > 0) {
        // Set the variable to show the modal
        $showModal = true;
    } else {
        // No dependencies, proceed with deletion
        $deleteQuestionSql = "DELETE FROM written_questions WHERE written_question_id = ?";
        $stmt = $conn->prepare($deleteQuestionSql);
        $stmt->bind_param("i", $deleteQuestionId);

        if ($stmt->execute()) {
            // Redirect to the same page after updating
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            echo "Error deleting data: " . $stmt->error;
        }
    }
}

if (isset($_POST['add_question'])) {
    // Sanitise and get the input values
    $writtenQuestion = htmlspecialchars($_POST['add_question']);

    // Insert the question into the database
    $add_query = "INSERT INTO written_questions (question, module_id) VALUES ('$writtenQuestion', '$module_id')";
    $add_result = $conn->query($add_query);

    if ($add_result) {
        echo "Question added successfully!";
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else {
        echo "Error adding question: " . $conn->error;
    }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Short Answer Questions</title>
</head>

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>

    <div class="mt-5"></div>
    <div class="container">
        <div class="text-center mb-3 mt-5">
            <h1> <strong>Short Answer Questions</strong></h1>
            <p>Module: <strong> <?php echo $module_name ?> </strong></p>
            <a data-bs-toggle='modal' data-bs-target='#addQuestionModal' type="button" class="btn btn-dark addQuestionBtn ">+ Add Question</a>
        </div>
        <div class="p-4 bg-light rounded-3 shadow-lg">
            <?php
            if ($select_result && $select_result->num_rows > 0) {
                echo '<table class="table table-bordered table-striped border table-hover mt-3">';
                echo '<thead>';
                echo '<tr>';
                echo '<th class="text-center">Questions</th>';
                echo '<th class="text-center">Actions</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                while ($row = $select_result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $row['question'] . '</td>';
                    echo "<td class='text-center align-middle'>
                            <a>
                                <i class='fa-regular fa-pen-to-square edit-question signature-color tooltips m-1' role='button' data-bs-toggle='modal' data-bs-target='#editQuestionModal' data-bs-toggle='tooltip' data-bs-placement='top' title='Edit Question' data-question='" . $row['question'] . "' data-question-id='" . $row['written_question_id'] . "'></i>
                            </a>
                            <a>
                            <i class='fa-regular fa-trash-can delete-question text-danger tooltips m-1' role='button' data-bs-toggle='modal' data-bs-target='#deleteConfirmationModal' data-bs-toggle='tooltip' data-bs-placement='top' title='Delete Question' data-question-id='" . $row['written_question_id'] . "'></i>
                            </a>
                        </td>";
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p>No questions found for this module.</p>';
            }
            ?>
        </div>
        <div class="d-flex justify-content-center mt-3">
            <a class="btn btn-secondary" href="modules.php">Back</a>
        </div>
    </div>

    <?php require_once("footer_logout.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add Question Modal -->
    <div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addQuestionModalLabel">Add Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="addForm">
                        <div class="mb-3">
                            <label for="add_question" class="form-label">New Question </label>
                            <textarea type="text" class="form-control" id="addQuestionName" name="add_question"> </textarea>
                            <input type="hidden" id="addQuestionId" name="question_id">
                        </div>
                        <div class="modal-footer justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" form="addForm" class="btn signature-btn">Add Question</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Question Modal -->
    <div class="modal fade" id="editQuestionModal" tabindex="-1" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editQuestionModalLabel">Edit Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editForm">
                        <div class="mb-3">
                            <label for="editQuestionName" class="form-label">Question Name</label>
                            <textarea type="text" class="form-control" id="editQuestionName" name="question_name"> </textarea>
                            <input type="hidden" id="editQuestionId" name="question_id">
                        </div>
                        <div class="modal-footer justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" form="editForm" class="btn signature-btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Deletion</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this question?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post">
                        <input type="hidden" id="deleteQuestionId" name="delete_question_id" value="">
                        <button type="submit" class="btn btn-danger" name="delete_question">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($showModal) : ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var myModal = new bootstrap.Modal(document.getElementById('dependencyModal'));
                myModal.show();
            });
        </script>
    <?php endif; ?>

    <!-- Dependency Modal -->
    <div class="modal fade" id="dependencyModal" tabindex="-1" role="dialog" aria-labelledby="dependencyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dependencyModalLabel">Dependency Warning</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    This question cannot be deleted due to existing dependencies.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enabling the tooltipi
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t)
        });

        // Populate modal input with question when the edit icon is clicked
        document.querySelectorAll('.edit-question').forEach(item => {
            item.addEventListener('click', function(event) {
                const question = this.getAttribute('data-question');
                const questionId = this.getAttribute('data-question-id');
                document.getElementById('editQuestionName').value = question;
                document.getElementById('editQuestionId').value = questionId;
            });
        });

        // Get the question id when the delete icon is clicked
        document.querySelectorAll('.delete-question').forEach(item => {
            item.addEventListener('click', function(event) {
                const questionId = this.getAttribute('data-question-id');

                // Set the question id for deletion when the modal confirms
                document.getElementById('deleteQuestionId').value = questionId;
            });
        });
    </script>

</body>

</html>