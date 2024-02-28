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

/* ================================================================================== */

$module_id = isset($_GET['module_id']) ? $_GET['module_id'] : '';
$employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';

// Pagination settings
$availableRecordsPerPage = array(10, 15, 20);
$recordsPerPage = isset($_GET['recordsPerPage']) && in_array($_GET['recordsPerPage'], $availableRecordsPerPage) ? intval($_GET['recordsPerPage']) : 10;
$pageNumber = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($pageNumber - 1) * $recordsPerPage;

$search_query = isset($_GET['select_query']) ? $_GET['select_query'] : '';
$select_query = "SELECT wa.*, wq.question, u.full_name, wr.feedback, wr.written_result_id, wr.is_correct, u.full_name
                FROM written_answers wa
                INNER JOIN users u ON wa.employee_id = u.employee_id
                INNER JOIN written_questions wq ON wa.written_question_id = wq.written_question_id
                LEFT JOIN written_results wr ON wa.written_answer_id = wr.written_answer_id
                WHERE wa.employee_id = '$employee_id' AND wq.module_id = $module_id AND (wa.employee_id LIKE '%$search_query%'
                OR wa.written_answer LIKE '%$search_query%'
                OR LOWER(TRIM(wq.question)) LIKE LOWER('%$search_query%')
                OR wr.feedback LIKE '%$search_query%'
                OR wa.datetime LIKE '%$search_query%'
                OR u.full_name LIKE '%$search_query%'
                OR (
                    (LOWER('$search_query') = 'marked' AND wa.is_marked = 1)
                    OR (LOWER('$search_query') = 'not marked' AND wa.is_marked = 0)
                    OR (LOWER('$search_query') = 'unmarked' AND wa.is_marked = 0)
                    OR (LOWER('$search_query') = 'notmarked' AND wa.is_marked = 0)
                ))
                ORDER BY wa.datetime DESC 
                LIMIT $offset, $recordsPerPage";


$select_result = $conn->query($select_query);

/* ================================================================================== */

// Count total number of records for pagination
$countQuery = "
    SELECT COUNT(*) AS total 
    FROM written_answers wa
    INNER JOIN users u ON wa.employee_id = u.employee_id
    INNER JOIN written_questions wq ON wa.written_question_id = wq.written_question_id
    LEFT JOIN written_results wr ON wa.written_answer_id = wr.written_answer_id
    WHERE wq.module_id = $module_id AND (wa.employee_id LIKE '%$search_query%'
        OR wa.written_answer LIKE '%$search_query%'
        OR LOWER(TRIM(wq.question)) LIKE LOWER('%$search_query%')
        OR wr.feedback LIKE '%$search_query%'
        OR wa.datetime LIKE '%$search_query%'
        OR u.full_name LIKE '%$search_query%'
        OR (
            (LOWER('$search_query') = 'marked' AND wa.is_marked = 1)
            OR (LOWER('$search_query') = 'not marked' AND wa.is_marked = 0)
            OR (LOWER('$search_query') = 'unmarked' AND wa.is_marked = 0)
            OR (LOWER('$search_query') = 'notmarked' AND wa.is_marked = 0)
        ))
";

$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

/* ================================================================================== */

$getNameSQL = "SElECT full_name FROM users WHERE employee_id = $employee_id";
$nameResult = $conn->query($getNameSQL);
$employeeName = '';

if ($nameResult->num_rows > 0) {
    $row = $nameResult->fetch_assoc();
    $employeeName = $row['full_name'];
}


$getModuleNameSQL = "SELECT module_name FROM modules WHERE module_id = $module_id";
$moduleNameResult = $conn->query($getModuleNameSQL);
$moduleName = '';

if ($moduleNameResult->num_rows > 0) {
    $row = $moduleNameResult->fetch_assoc();
    $moduleName = $row['module_name'];
}
/* ================================================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['written-answer-id']) && isset($_POST['markQuestion'])) {
    $feedback = $_POST['feedback'];
    $employeeId = $_POST['employee-id'];
    $grader_id = $_SESSION["employeeId"];
    $is_correct = isset($_POST['markQuestionToggle']) ? 1 : 0;
    $writtenAnswerId = $_POST['written-answer-id'];

    // Check if an answer has already been marked
    $check_query = "SELECT * FROM written_results WHERE written_answer_id = ? AND employee_id = ? AND module_id = ?";
    $check_stmt = $conn->prepare($check_query);

    if ($check_stmt) {
        $check_stmt->bind_param("iii", $writtenAnswerId, $employeeId, $module_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // If an answer has already been marked, then don't mark it again
            $update_query = "UPDATE written_results SET feedback = ?, grader_id = ?, is_correct = ?, graded_at = NOW() WHERE written_answer_id = ? AND employee_id = ? AND module_id = ?";
            $update_stmt = $conn->prepare($update_query);

            if ($update_stmt) {
                $update_stmt->bind_param("siiiii", $feedback, $grader_id, $is_correct, $writtenAnswerId, $employeeId, $module_id);

                if ($update_stmt->execute()) {
                    echo "Mark Updated Successfully";

                    // Update status in written_answers table
                    $update_mark_status = "UPDATE written_answers SET is_marked = 1 WHERE written_answer_id = ? ";
                    $update_mark_stmt = $conn->prepare($update_mark_status);

                    if ($update_mark_stmt) {
                        $update_mark_stmt->bind_param("i", $writtenAnswerId);

                        if ($update_mark_stmt->execute()) {
                            echo "Status updated!";
                        } else {
                            echo "Error updating status after insertion: " . $update_mark_stmt->error;
                        }

                        // Redirect to the same page after updating
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit();
                    } else {
                        echo "Error preparing status update statement: " . $conn->error;
                    }
                } else {
                    echo "Update Not Successful: " . $update_stmt->error;
                }

                $update_stmt->close();
            } else {
                echo "Update Prepared statement failed: " . $conn->error;
            }
        } else {
            // Answer hasn't been marked, so insert a new record
            $insert_query = "INSERT INTO written_results (feedback, employee_id, grader_id, graded_at, is_correct, written_answer_id, module_id) 
            VALUES (?, ?, ?, NOW(), ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);

            if ($stmt) {
                $stmt->bind_param("siiiii", $feedback, $employeeId, $grader_id, $is_correct, $writtenAnswerId, $module_id);

                if ($stmt->execute()) {
                    echo "Answer Marked Successfully";

                    // Update status in written_answers table
                    $update_mark_status = "UPDATE written_answers SET is_marked = 1 WHERE written_answer_id = ? ";
                    $update_mark_stmt = $conn->prepare($update_mark_status);

                    if ($update_mark_stmt) {
                        $update_mark_stmt->bind_param("i", $writtenAnswerId);

                        if ($update_mark_stmt->execute()) {
                            echo "Status updated!";
                        } else {
                            echo "Error updating status after insertion: " . $update_mark_stmt->error;
                        }

                        // Redirect to the same page after updating
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit();
                    } else {
                        echo "Error preparing status update statement: " . $conn->error;
                    }
                } else {
                    echo "Insert Not Successful: " . $stmt->error;
                }

                $stmt->close();
            } else {
                echo "Insert Prepared statement error: " . $conn->error;
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['written-result-id']) && isset($_POST['reMarkQuestion'])) {
    $feedback = $_POST['feedback'];
    $employeeId = $_POST['employee-id'];
    $grader_id = $_SESSION["employeeId"];
    $is_correct = isset($_POST['markQuestionToggle']) ? 1 : 0;
    $writtenResultId = $_POST['written-result-id'];

    var_dump($feedback);
    var_dump($writtenResultId);
    var_dump($employeeId);
    var_dump($grader_id);
    var_dump($is_correct);

    $update_query = "UPDATE written_results SET grader_id = ?, feedback = ?, graded_at = NOW(), is_correct = ? WHERE written_result_id = ?";
    $update_query_stmt = $conn->prepare($update_query);
    $update_query_stmt->bind_param("isii", $grader_id, $feedback,  $is_correct, $writtenResultId);

    if ($update_query_stmt->execute()) {
        // echo "feedback updated!";
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else {
        echo "Error updating data: " . $update_query_stmt->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['written-result-id']) && isset($_POST['deleteQuestion'])) {
    $writtenResultId = $_POST['written-result-id'];
    $writtenAnswerId = $_POST['written-answer-id'];

    $delete_query = "DELETE FROM written_results WHERE written_result_id = ?";
    $delete_query_stmt = $conn->prepare($delete_query);
    $delete_query_stmt->bind_param("i", $writtenResultId);

    if ($delete_query_stmt->execute()) {
        echo "Successfully Deleted!";

        $unmark_question_query = "UPDATE written_answers SET is_marked = 0 WHERE written_answer_id = ?";
        $unmark_question_stmt = $conn->prepare($unmark_question_query);
        $unmark_question_stmt->bind_param("i", $writtenAnswerId);

        if ($unmark_question_stmt->execute()) {
            echo "Question successfully unmarked";
            header("Location: " . $_SERVER['REQUEST_URI']);
        } else {
            echo "Error unmarking the question" . $unmark_question_stmt->error;
        }
    } else {
        echo "Error deleting data: " . $delete_query_stmt->error;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Short Answer Marking List</title>
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

            .form-select {
                width: 50px !important;
                padding: 5px;
                font-size: 12px;
            }

            label {
                font-size: 12px;
            }

            .searchBtn {
                font-size: 12px;
                padding-top: 10px;
                padding-bottom: 10px;
            }
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border: 1px solid #043f9d;
        }

        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }
    </style>
</head>

<script>
    // Capture scroll position before page refresh or redirection
    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('scrollPosition', window.scrollY);
    });
</script>

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>

    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
        <h1 class="text-center"><strong>Short Answer Marking List</strong></h1>

    </div>

    <div class="container d-flex justify-content-center">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="d-flex justify-content-center bg-black p-4 rounded-2">
                    <div class="d-flex flex-column align-items-start me-5">
                        <h6 class="mb-2 text-white">Employee</h6>
                        <h4 class="mb-2 text-white"><?php echo $employeeName ?></h4>
                    </div>

                    <div class="d-flex flex-column align-items-start ms-5">
                        <h6 class="mb-2 text-white">Module</h6>
                        <h4 class="mb-2 text-white"><?php echo $moduleName ?></h4>
                    </div>
                </div>
                <div class="card mt-3 mb-5 p-3" style="border: none">

                    <div class="card-body">
                        <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                            <input type="hidden" name="module_id" value="<?php echo $module_id; ?>">
                            <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                            <input type="hidden" name="module_id" value="<?php echo htmlspecialchars($module_id); ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center col-md-8">
                                    <input class="form-control mr-sm-2 searchBtn" type="search" name="select_query" placeholder="Search" aria-label="Search" style="height: 38px;">
                                    <button class="btn btn-dark mx-2 my-2 my-sm-0 searchBtn" type="submit">Search</button>
                                </div>
                                <div class="col-md-3 d-flex align-items-center justify-content-end">
                                    <label class="my-auto me-2">Show</label>
                                    <select id="recordsPerPage" name="recordsPerPage" class="form-select me-2" style="width: 70px">
                                        <option value="10" <?php echo $recordsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="15" <?php echo $recordsPerPage == 15 ? 'selected' : ''; ?>>15</option>
                                        <option value="20" <?php echo $recordsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                                    </select>
                                    <label>entries</label>
                                </div>
                            </div>
                        </form>
                        <form method="POST">
                            <table class="table table-striped table-hover table-bordered mt-2">
                                <thead>
                                    <tr class="text-center align-middle">
                                        <th> Question </th>
                                        <th> Written Answer</th>
                                        <th> Status </th>
                                        <th> Feedback </th>
                                        <th> Result </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($select_result->num_rows > 0) {
                                        while ($row = $select_result->fetch_assoc()) { ?>
                                            <tr class="align-middle" data-bs-toggle='modal' data-bs-target='#writtenAnswerModal' data-written-answer="<?php echo htmlspecialchars($row['written_answer']); ?>" data-question="<?php echo $row['question'] ?>" data-is-marked="<?php echo $row['is_marked'] ?>" data-written-id="<?php echo $row["written_answer_id"] ?>" data-employee-id="<?php echo $row["employee_id"] ?>" data-feedback="<?php echo ($row['feedback'] !== null) ? htmlspecialchars($row['feedback']) : ''; ?>" data-written-result-id="<?php echo $row["written_result_id"] ?>">
                                                <td class="pt-4 pb-4">
                                                    <h3><?php echo $row["full_name"] . " - " . $row["employee_id"] . "</h3> " . $row["question"] . "<br>" . $row["datetime"] ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    // Assuming you want to limit the text to 50 characters
                                                    $max_length = 15;
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
                                                <td class="text-center"> <?php echo ($row['feedback'] !== null) ? htmlspecialchars($row['feedback']) : 'No feedback available'; ?> </td>
                                                <td class="text-center">
                                                    <?php
                                                    if ($row['is_correct'] === "0") {
                                                        echo "<span class='badge rounded-pill text-bg-danger' style='font-size:16px'>False</span>";
                                                    } else if ($row['is_correct'] === "1") {
                                                        echo "<span class='badge rounded-pill text-bg-success' style='font-size:16px'>True</span>";
                                                    } else {
                                                        echo "<span class='badge rounded-pill text-bg-secondary' style='font-size:16px'> N/A </span>";
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                    <?php }
                                    } else {
                                        // Display a message when there are no rows in the table
                                        echo '<tr><td col-md-6 colspan="5" class="text-center">No Answers</td></tr>';
                                    } ?>

                                </tbody>
                            </table>
                            <?php

                            echo '<div class="d-flex justify-content-center">';
                            // Pagination controls
                            echo '<ul class="pagination">';

                            // Calculate the start and end page numbers for the limited pagination
                            $startPage = max(1, $pageNumber - 1);
                            $endPage = min($totalPages, $pageNumber + 1);

                            // Previous page link
                            if ($pageNumber > 1) {
                                echo '<li class="page-item">';
                                echo '<a class="page-link signature-color" href="?search_query=' . urlencode($search_query) . '&recordsPerPage=' . $recordsPerPage . '&page=' . ($pageNumber - 1) . '&module_id=' . $module_id . '"><i class="fas fa-angle-double-left"></i></a>';
                                echo '</li>';
                            } else {
                                echo '<li class="page-item disabled">';
                                echo '<a class="page-link" href="#" tabindex="-1"><i class="fas fa-angle-double-left"></i></a>';
                                echo '</li>';
                            }

                            // Page numbers
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                echo '<li class="page-item';
                                if ($i === $pageNumber) {
                                    echo ' active';
                                }
                                echo '">';
                                echo '<a class="page-link signature-color" href="?search_query=' . urlencode($search_query) . '&recordsPerPage=' . $recordsPerPage . '&page=' . $i . '&module_id=' . $module_id . '">' . $i . '</a>';
                                echo '</li>';
                            }

                            // Next page link
                            if ($pageNumber < $totalPages) {
                                echo '<li class="page-item">';
                                echo '<a class="page-link signature-color" href="?search_query=' . urlencode($search_query) . '&recordsPerPage=' . $recordsPerPage . '&page=' . ($pageNumber + 1) . '&module_id=' . $module_id . '"><i class="fas fa-angle-double-right"></i></a>';
                                echo '</li>';
                            } else {
                                echo '<li class="page-item disabled">';
                                echo '<a class="page-link" href="#" tabindex="-1"><i class="fas fa-angle-double-right"></i></a>';
                                echo '</li>';
                            }

                            echo '</ul>';
                            echo '</div>';
                            ?>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("footer_logout.php") ?>

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
                        <textarea type="text" class="form-control" name="feedback" value="" required></textarea>

                        <label for="markQuestionToggle" class="form-label mt-3"><strong>Mark Question</strong></label>
                        <div class="form-check form-switch d-flex align-items-center">
                            <input class="form-check-input form-check-lg mb-1 p-2" type="checkbox" id="markQuestionToggle" name="markQuestionToggle">
                            <label class="form-check-label m-2" id="markQuestionLabel" for="markQuestionToggle">False</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn signature-btn markQuestionButton" id="markQuestionButton" name="markQuestion">Mark Question</button>
                        <button type="submit" class="btn signature-btn bg-danger" id="deleteQuestionButton" name="deleteQuestion">Delete Mark</button>
                        <button type="submit" class="btn signature-btn" id="reMarkQuestionButton" name="reMarkQuestion">Re-Mark Question</button>
                        <input type="hidden" name="written-answer-id">
                        <input type="hidden" name="employee-id">
                        <input type="hidden" name="written-result-id">
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
            let writtenResultIdField = document.querySelector('input[name="written-result-id"]');

            writtenAnswerModal.addEventListener('show.bs.modal', function(event) {
                let button = event.relatedTarget;
                let row = button.closest('tr');
                let question = row.getAttribute('data-question');
                let answer = row.getAttribute('data-written-answer');
                let isMarked = row.getAttribute('data-is-marked');
                let writtenAnswerId = row.getAttribute('data-written-id');
                let employee_id = row.getAttribute('data-employee-id');
                let feedback = row.getAttribute('data-feedback'); // Added this line
                let writtenResultId = row.getAttribute('data-written-result-id');

                // Set the value of the hidden input field and feedback input
                writtenAnswerIdField.value = writtenAnswerId;
                employeeId.value = employee_id;
                feedbackInput.value = feedback !== null ? feedback : ''; // Updated this line
                writtenResultIdField.value = writtenResultId;

                let writtenAnswerDetails = document.getElementById('writtenAnswerDetails');
                writtenAnswerDetails.innerHTML = `
            <p><strong>Question:</strong> ${question}</p>
            <p><strong>User's Answer:</strong> ${answer}</p>
        `;

                // Display the appropriate button based on the is_marked value
                if (isMarked == 1) {
                    reMarkQuestionButton.style.display = 'inline-block';
                    deleteQuestionButton.style.display = 'inline-block';
                    markQuestionButton.style.display = 'none';
                } else if (isMarked == 0) {
                    markQuestionButton.style.display = 'inline-block';
                    reMarkQuestionButton.style.display = 'none';
                    deleteQuestionButton.style.display = 'none';
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
                markQuestionLabel.classList.remove('text-bg-success', 'text-bg-danger');

                // Add the appropriate classes based on the state
                if (isChecked) {
                    markQuestionLabel.classList.add('text-bg-success');
                } else {
                    markQuestionLabel.classList.add('text-bg-danger');
                }
            });
        });
    </script>

    <script>
        // Restore scroll position after page reload
        window.addEventListener('load', function() {
            const scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, scrollPosition);
                sessionStorage.removeItem('scrollPosition'); // Remove after restoring
            }
        });
    </script>

    <script>
        // Function to handle the onchange event of the recordsPerPage drop-down
        function onRecordsPerPageChange() {
            // Get the selected value of recordsPerPage
            var selectedValue = document.getElementById("recordsPerPage").value;

            // Set the "page" to 1
            var currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set("page", 1);

            // Update the URL to include the selected recordsPerPage value
            currentUrl.searchParams.set("recordsPerPage", selectedValue);

            // Redirect to the updated URL
            window.location.href = currentUrl.toString();
        }

        // Add an event listener to the recordsPerPage drop-down
        document.getElementById("recordsPerPage").addEventListener("change", onRecordsPerPageChange);
    </script>

</body>

</html>