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

$select_query = "SELECT wa.*, wq.question, u.full_name, wr.feedback 
                FROM written_answers wa 
                INNER JOIN written_questions wq ON wa.written_question_id = wq.written_question_id
                INNER JOIN users u ON wa.employee_id = u.employee_id 
                LEFT JOIN written_results wr ON wa.written_answer_id = wr.written_answer_id
                ORDER BY wa.written_answer_id ASC";

$select_result = $conn->query($select_query);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['written-answer-id'])) {
    $feedback = $_POST['feedback'];
    $employeeId = $_POST['employee-id'];
    $grader_id = $_SESSION["employee_id"];
    $is_correct = isset($_POST['markQuestionToggle']) ? 1 : 0;
    $writtenAnswerId = $_POST['written-answer-id'];

    // var_dump($feedback);
    // var_dump($writtenAnswerId);
    // var_dump($employeeId);
    // var_dump($grader_id);
    // var_dump($is_correct);

    $insert_query = "INSERT INTO written_results (feedback, employee_id, grader_id, graded_at, is_correct, written_answer_id) 
                    VALUES (?, ?, ?, NOW(), ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("siiii", $feedback, $employeeId, $grader_id, $is_correct, $writtenAnswerId);

    if ($stmt->execute()) {

        $update_mark_status = "UPDATE written_answers SET is_marked = 1 WHERE written_answer_id = ? ";
        $update_mark_stmt = $conn->prepare($update_mark_status);
        $update_mark_stmt->bind_param("i", $writtenAnswerId);

        if ($update_mark_stmt->execute()) {
            // echo "status updated!";
        }

        // Redirect to the same page after inserting
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        echo "Error inserting data: " . $stmt->error;
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
    <title>Written Quiz List</title>
</head>

<style>
    /* Add custom CSS styles here */
    @media (max-width: 576px) {

        /* Adjust table styles for small screens */
        table {
            font-size: 10px;
        }

        table h3 {
            font-size: 14px;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .badge {
            font-size: 10px !important;
        }
    }

    .table thead th {
        background-color: #043f9d;
        color: white;
        border: 1px solid #043f9d !important;
    }
</style>

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card mt-5 mb-5 p-3">
                    <div class="card-body">
                        <h1 class="text-center">Written Question Marking List</h1>
                        <form method="POST">
                            <table class="table table-striped table-hover table-bordered mt-5">
                                <thead>
                                    <tr class="text-center align-middle">
                                        <th> Question </th>
                                        <th> Written Answer</th>
                                        <th> Status </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($select_result->num_rows > 0) {
                                        while ($row = $select_result->fetch_assoc()) { ?>
                                            <tr class="align-middle" data-bs-toggle='modal' data-bs-target='#writtenAnswerModal' data-written-answer="<?php echo htmlspecialchars($row['written_answer']); ?>" data-question="<?php echo $row['question'] ?>" data-is-marked="<?php echo $row['is_marked'] ?>" data-written-id="<?php echo $row["written_answer_id"] ?>" data-employee-id="<?php echo $row["employee_id"] ?>" data-feedback="<?php echo ($row['feedback'] !== null) ? htmlspecialchars($row['feedback']) : ''; ?>">
                                                <td class="pt-4 pb-4">
                                                    <h3><?php echo $row["full_name"] . " - " . $row["employee_id"] . "</h3> " . $row["question"] . "<br>" . $row["datetime"] ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    // Assuming you want to limit the text to 50 characters
                                                    $max_length = 4;
                                                    $written_answer = $row["written_answer"];

                                                    // Check if the length of $written_answer is greater than $max_length
                                                    if (strlen($written_answer) > $max_length) {
                                                        // If it is, truncate the text and append ellipsis (...), otherwise, use the original text
                                                        echo substr($written_answer, 0, $max_length) . '...';
                                                    } else {
                                                        echo $written_answer;
                                                    }
                                                    ?>
                                                </td>
                                                <?php if ($row['is_marked'] == 0) {
                                                    echo "<td class='text-center'> <span class='badge rounded-pill text-bg-danger' style='font-size:16px'>Not Marked</span></td>";
                                                } else if ($row['is_marked'] == 1) {
                                                    echo "<td class='text-center'> <span class='badge rounded-pill text-bg-success' style='font-size:16px'>Marked</span> </td>";
                                                }
                                                ?>
                                            </tr>
                                    <?php }
                                    } ?>
                                </tbody>
                            </table>
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

    <!-- Written Answer Modal -->
    <div class="modal fade" id="writtenAnswerModal" tabindex="-1" aria-labelledby="writtenAnswerModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="writtenAnswerModal">Result Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div id="writtenAnswerDetails"></div>

                        <label for="markQuestion" class="form-label"><strong>Feedback</strong></label>
                        <textarea type="text" class="form-control" name="feedback" value=""></textarea>

                        <label for="markQuestionToggle" class="form-label mt-3"><strong>Mark Question</strong></label>
                        <div class="form-check form-switch">
                            <input class="form-check-input form-check-lg mt-2" type="checkbox" id="markQuestionToggle" name="markQuestionToggle">
                            <label class="form-check-label" id="markQuestionLabel" for="markQuestionToggle">False</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn signature-btn markQuestionButton" id="markQuestionButton">Mark Question</button>
                        <button type="button" class="btn signature-btn" id="reMarkQuestionButton">Re-Mark Question</button>
                        <input type="hidden" name="written-answer-id">
                        <input type="hidden" name="employee-id">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let writtenAnswerModal = document.getElementById('writtenAnswerModal');
            let markQuestionButton = document.getElementById('markQuestionButton');
            let reMarkQuestionButton = document.getElementById('reMarkQuestionButton');
            let writtenAnswerIdField = document.querySelector('input[name="written-answer-id"]');
            let employeeId = document.querySelector('input[name="employee-id"]');
            let feedbackInput = document.querySelector('textarea[name="feedback"]');

            writtenAnswerModal.addEventListener('show.bs.modal', function(event) {
                let button = event.relatedTarget;
                let row = button.closest('tr');
                let question = row.getAttribute('data-question');
                let answer = row.getAttribute('data-written-answer');
                let isMarked = row.getAttribute('data-is-marked');
                let writtenAnswerId = row.getAttribute('data-written-id');
                let employee_id = row.getAttribute('data-employee-id');
                let feedback = row.getAttribute('data-feedback'); // Added this line

                // Set the value of the hidden input field and feedback input
                writtenAnswerIdField.value = writtenAnswerId;
                employeeId.value = employee_id;
                feedbackInput.value = feedback !== null ? feedback : ''; // Updated this line

                let writtenAnswerDetails = document.getElementById('writtenAnswerDetails');
                writtenAnswerDetails.innerHTML = `
            <p><strong>Question:</strong> ${question}</p>
            <p><strong>Answer:</strong> ${answer}</p>
        `;

                // Display the appropriate button based on the is_marked value
                if (isMarked == 1) {
                    reMarkQuestionButton.style.display = 'inline-block';
                    markQuestionButton.style.display = 'none';
                } else if (isMarked == 0) {
                    markQuestionButton.style.display = 'inline-block';
                    reMarkQuestionButton.style.display = 'none';
                }
            });
        });


        document.addEventListener('DOMContentLoaded', function() {
            const markQuestionToggle = document.getElementById('markQuestionToggle');
            const markQuestionLabel = document.getElementById('markQuestionLabel');

            // Set a default state
            const defaultState = false; // Set to true if you want the default state to be "True"
            markQuestionToggle.checked = defaultState;
            markQuestionLabel.textContent = defaultState ? 'True' : 'False';

            // Add the appropriate classes based on the default state
            markQuestionLabel.classList.add('badge', 'rounded-pill', defaultState ? 'text-bg-success' : 'text-bg-danger');

            markQuestionToggle.addEventListener('change', function() {
                const isChecked = markQuestionToggle.checked;
                markQuestionLabel.textContent = isChecked ? 'True' : 'False';

                // Remove existing classes
                markQuestionLabel.classList.remove('badge', 'rounded-pill', 'text-bg-success', 'text-bg-danger');

                // Add the appropriate classes based on the state
                if (isChecked) {
                    markQuestionLabel.classList.add('badge', 'rounded-pill', 'text-bg-success');
                } else {
                    markQuestionLabel.classList.add('badge', 'rounded-pill', 'text-bg-danger');
                }
            });
        });
    </script>

</body>

</html>