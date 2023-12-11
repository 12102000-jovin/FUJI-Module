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

// Pagination settings
$availableRecordsPerPage = array(5, 15, 20);
$recordsPerPage = isset($_GET['recordsPerPage']) && in_array($_GET['recordsPerPage'], $availableRecordsPerPage) ? intval($_GET['recordsPerPage']) : 5;
$pageNumber = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($pageNumber - 1) * $recordsPerPage;

$search_query = isset($_GET['select_query']) ? $_GET['select_query'] : '';

$query = "SELECT
wa.module_id,
m.module_name,
wr.feedback,
wr.employee_id AS employee_id_wr,
wr.grader_id,
u_employee.full_name AS employee_full_name,
wr.graded_at,
wr.is_correct,
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
JOIN
modules m ON wa.module_id = m.module_id
WHERE m.module_name LIKE '%$search_query%'
    OR wq.question LIKE '%$search_query%'
    OR wr.feedback LIKE '%$search_query%'
    OR u_employee.full_name LIKE '%$search_query%'
    OR u_grader.full_name LIKE '%$search_query%'
    OR wa.module_id LIKE '%$search_query%'
    OR (LOWER('$search_query') = 'true' AND wr.is_correct = '1' 
        OR LOWER('$search_query') = 'false' AND wr.is_correct = '0')
GROUP BY wa.module_id, wr.graded_at
ORDER BY wa.module_id, wr.graded_at DESC
LIMIT $offset, $recordsPerPage";

$result = mysqli_query($conn, $query);


$countQuery = "
    SELECT COUNT(*) AS total 
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
    JOIN
    modules m ON wa.module_id = m.module_id
    WHERE m.module_name LIKE '%$search_query%'
        OR wq.question LIKE '%$search_query%'
        OR wr.feedback LIKE '%$search_query%'
        OR u_employee.full_name LIKE '%$search_query%'
        OR u_grader.full_name LIKE '%$search_query%'
        OR wa.module_id LIKE '%$search_query%'
        OR (LOWER('$search_query') = 'true' AND wr.is_correct = '1' 
            OR LOWER('$search_query') = 'false' AND wr.is_correct = '0')";


$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);
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
    <title>Report (Essay)</title>

    <style>
        /* Add custom CSS styles here */
        @media (max-width: 576px) {

            /* Adjust table styles for small screens */
            table {
                font-size: 10px;
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

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border: 1px solid #043f9d;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>

    <div class="container-fluid mt-5 mb-4">
        <a href="index.php" style="color: black; text-decoration: none"><i class="fa-solid fa-arrow-left"></i>
            Back to Home</a>
        <h1 class="text-center">Essay Report</h1>
    </div>

    <div class="container mb-5 p-4 bg-light rounded-3 shadow-lg tableborder">
        <form method="GET">
            <div class="d-flex justify-content-between mb-3">
                <div class="col-md-8 d-flex align-items-center">
                    <div class="d-flex align-items-center col-md-8 ">
                        <input class="form-control mr-sm-2" type="search" name="select_query" placeholder="Search" aria-label="Search" style="height: 38px;">
                        <button class="btn btn-dark mx-2 my-2 my-sm-0" type="submit">Search</button>
                    </div>
                </div>

                <div class="col-md-3 d-flex align-items-center justify-content-end">
                    <label class="my-auto me-2">Show</label>
                    <select id="recordsPerPage" name="recordsPerPage" class="form-select me-2" style="width: 70px">
                        <option value="5" <?php echo $recordsPerPage == 5 ? 'selected' : ''; ?>>5</option>
                        <option value="15" <?php echo $recordsPerPage == 15 ? 'selected' : ''; ?>>15</option>
                        <option value="20" <?php echo $recordsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                    </select>
                    <label>entries</label>
                </div>
            </div>
        </form>

        <table class="table table-bordered table-striped table-hover text-center">
            <thead class="align-middle">
                <tr>
                    <th>Module ID</th>
                    <th>Module Name</th>
                    <th>Question</th>
                    <th>Employee</th>
                    <th>Feedback</th>
                    <th>Grader</th>
                    <th>Graded At</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody class="align-middle">
                <?php
                // Loop through the results and display data
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>{$row['module_id']}</td>";
                    echo "<td>{$row['module_name']}</td>";
                    echo "<td>{$row['question']}</td>";
                    echo "<td>{$row['employee_full_name']}</td>";
                    echo "<td>{$row['feedback']}</td>";
                    echo "<td>{$row['grader_full_name']}</td>";
                    echo "<td>{$row['graded_at']}</td>";
                    echo "<td>";
                    if ($row['is_correct'] === '0') {
                        echo '<span class=\'badge rounded-pill text-bg-danger\' style=\'font-size:16px\'>False</span>';
                    } else if ($row['is_correct'] === '1') {
                        echo '<span class=\'badge rounded-pill text-bg-success\' style=\'font-size:16px\'>True</span>';
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
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
            echo '<a class="page-link signature-color" href="?search_query=' . urlencode($search_query) . '&recordsPerPage=' . $recordsPerPage . '&page=' . ($pageNumber - 1) . '"><i class="fas fa-angle-double-left"></i></a>';
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
            echo '<a class="page-link signature-color" href="?search_query=' . urlencode($search_query) . '&recordsPerPage=' . $recordsPerPage . '&page=' . $i . '">' . $i . '</a>';
            echo '</li>';
        }

        // Next page link
        if ($pageNumber < $totalPages) {
            echo '<li class="page-item">';
            echo '<a class="page-link signature-color" href="?search_query=' . urlencode($search_query) . '&recordsPerPage=' . $recordsPerPage . '&page=' . ($pageNumber + 1) . '"><i class="fas fa-angle-double-right"></i></a>';
            echo '</li>';
        } else {
            echo '<li class="page-item disabled">';
            echo '<a class="page-link" href="#" tabindex="-1"><i class="fas fa-angle-double-right"></i></a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';
        ?>
    </div>

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
    <footer class="bg-light text-center py-4 mt-auto shadow-lg">
        <div class="container">
            <p class="mb-0 font-weight-bold" style="font-size: 1.5vh"><strong>&copy; <?php echo date('Y'); ?> FUJI Training Module. All rights reserved.</strong></p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</body>

</html>