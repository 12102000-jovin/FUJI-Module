<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("db_connect.php");

// Checking the inactivity 
require_once("inactivity_check.php");

$role = $_SESSION['userRole'];

$employee_id = $_SESSION['employeeId'] ?? 'N/A';

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
// var_dump($module_id);

$selectSql = "SELECT
wr.feedback,
wr.employee_id AS employee_id_wr,
wr.grader_id,
u_employee.full_name AS employee_full_name,
wr.graded_at,
wr.is_correct,
wa.written_answer,
u_grader.employee_id AS grader_employee_id,
u_grader.full_name AS grader_full_name,
wq.question
FROM
written_results wr
JOIN
written_answers wa ON wr.written_answer_id = wa.written_answer_id
JOIN
users u_employee ON wr.employee_id = u_employee.employee_id
JOIN
users u_grader ON wr.grader_id = u_grader.employee_id
JOIN
written_questions wq ON wa.written_question_id = wq.written_question_id
WHERE
wa.module_id = $module_id
AND wr.employee_id = $employee_id
AND wa.is_marked = 1";


$selectResult = $conn->query($selectSql);

$moduleNameSql = "SELECT module_name FROM modules WHERE module_id = ?";
$moduleNameResult = $conn->prepare($moduleNameSql);
$moduleNameResult->bind_param("i", $module_id);
$moduleNameResult->execute();

$result = $moduleNameResult->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $module_name = $row["module_name"];
}


$getNameSQL = "SELECT full_name FROM users WHERE employee_id = ?";
$stmt = $conn->prepare($getNameSQL);
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$nameResult = $stmt->get_result();
$stmt->close();

$employeeName = '';

if ($nameResult->num_rows > 0) {
    $row = $nameResult->fetch_assoc();
    $employeeName = $row['full_name'];
}


$getModuleNameSQL = "SELECT module_name FROM modules WHERE module_id = ?";
$stmtModuleName = $conn->prepare($getModuleNameSQL);
$stmtModuleName->bind_param("s", $module_id);
$stmtModuleName->execute();
$moduleNameResult = $stmtModuleName->get_result();
$stmtModuleName->close();

$moduleName = '';

if ($moduleNameResult->num_rows > 0) {
    $row = $moduleNameResult->fetch_assoc();
    $moduleName = $row['module_name'];
}

$countSql = "SELECT COUNT(DISTINCT wa.written_question_id) AS question_count
FROM written_answers wa
LEFT JOIN written_results wr ON wa.written_answer_id = wr.written_answer_id
WHERE wa.employee_id = $employee_id 
AND wa.module_id = $module_id      
AND wa.is_marked = 0";

$countResult = $conn->query($countSql);

if ($countResult->num_rows > 0) {
    $row = $countResult->fetch_assoc();
    $questionCount = $row["question_count"];
} else {
    $questionCount = 0;
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
    <title>Progress</title>
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
        }

        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>

    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>

        <div class="d-flex justify-content-center mb-2">
            <h1> <strong>Short Answer Quiz Progress</strong> </h1>
        </div>
        <p class="text-center">Module: <strong> <?php echo $module_name ?> </strong></p>

        <div class="d-flex justify-content-center mb-3">
            <p>Number of Unmarked Question: <strong> <?php echo $questionCount ?> </strong> <i class="fa-solid fa-circle-info text-danger tooltips" data-bs-placement="right" title="You need to ensure that the count of unmarked questions is 0. If it is not, the module still requires evaluation, and the current assessment may not represent the final result." ;></i></p>
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-content-center">
            <div class="mb-5 p-4 bg-light rounded-3 shadow-lg table-border">

                <?php
                if ($selectResult->num_rows > 0) {
                ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered mt-2 text-center">
                            <thead class="align-middle">
                                <tr>
                                    <th>Question</th>
                                    <th>Answer</th>
                                    <th>Feedback</th>
                                    <th>Grader</th>
                                    <th>Graded At</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                <?php
                                while ($row = $selectResult->fetch_assoc()) {
                                ?>
                                    <tr>
                                        <td><?php echo $row["question"] ?></td>
                                        <td><?php echo $row["written_answer"] ?></td>
                                        <td><?php echo $row["feedback"]; ?></td>
                                        <td><?php echo $row["grader_full_name"]; ?></td>
                                        <td><?php echo $row["graded_at"]; ?></td>
                                        <td>
                                            <?php
                                            if ($row["is_correct"] === "0") {
                                                echo "<span class='badge rounded-pill text-bg-danger' style='font-size:16px'>False</span>";
                                            } else if ($row['is_correct'] === "1") {
                                                echo "<span class='badge rounded-pill text-bg-success' style='font-size:16px'>True</span>";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-center">
                            <button class="btn signature-btn m-1 mb-2" onclick="exportToExcel()">Export to CSV</button>
                        </div>
                    <?php
                } else {
                    echo "<div class='container text-center alert alert-danger mt-3'><strong>You have not done any Short Answer quiz in this module, or the quiz that you have completed has not been marked.</strong></div>";
                }
                    ?>
                    </div>
            </div>
        </div>
    </div>

    <?php require_once("footer_logout.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t)
        })
    </script>
    <script>
        function exportToExcel() {
            const currentDate = new Date().toLocaleDateString('en-AU', {
                timeZone: 'Australia/Sydney'
            })

            const employeeId = "<?php echo $employee_id; ?>";
            const employeeName = "<?php echo $employeeName; ?>";
            const moduleName = "<?php echo $moduleName ?>"

            const rows = document.querySelectorAll('.table tbody tr');
            let csvContent = `Module Name: ${moduleName},Employee ID: ${employeeId} \n\nQuestion, Answer, Feedback, Grader, Graded At, Result\n`;


            rows.forEach(row => {
                const question = row.querySelector('td:nth-child(1)').textContent.trim();
                const answer = row.querySelector('td:nth-child(2)').textContent.trim();
                const feedback = row.querySelector('td:nth-child(3)').textContent.trim();
                const grader = row.querySelector('td:nth-child(4)').textContent.trim();
                const gradedAt = row.querySelector('td:nth-child(5)').textContent.trim();
                const result = row.querySelector('td:nth-child(6)').textContent.trim();

                const rowData = `${question}, ${answer}, ${feedback}, ${grader}, ${gradedAt}, ${result}`;
                csvContent += rowData + "\n";
            })

            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.download = `${employeeId} - ${employeeName}  - ${currentDate} - Short Answer Progress.csv`;
            link.click();
            URL.revokeObjectURL(url);
        }
    </script>
</body>

</html>