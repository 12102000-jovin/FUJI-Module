<?php
session_start();

require_once("db_connect.php");

// Checking the inactivity 
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

// Fetch data from the module_questions table
$totalQuestionsPerModule = array();
$sqlTotalQuestions = "SELECT module_id, COUNT(*) as total_questions FROM module_questions GROUP BY module_id";
$resultTotalQuestions = $conn->query($sqlTotalQuestions);
if ($resultTotalQuestions->num_rows > 0) {
    while ($rowTotalQuestions = $resultTotalQuestions->fetch_assoc()) {
        $totalQuestionsPerModule[$rowTotalQuestions['module_id']] = $rowTotalQuestions['total_questions'];
    }
}

$employee_id = $_SESSION['employeeId'] ?? 'N/A';

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

<!-- ================================================================================== -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
    </div>

    <div class="container text-center mb-3">
        <h1><strong>Your Progress</strong></h1>
    </div>

    <!-- ================================================================================== -->

    <div class="container mb-5">
        <?php


        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Query to select columns from multiple tables
        $query = "SELECT 
                results.employee_id, 
                users.username, 
                users.full_name,
                modules.module_name, 
                results.score, 
                results.timestamp,
                module_allocation.module_id,
                module_allocation.employee_id,
                results.duration
            FROM 
                results
            JOIN 
                users ON results.employee_id = users.employee_id
            JOIN 
                modules ON results.module_id = modules.module_id
            JOIN
                module_allocation ON results.module_id = module_allocation.module_id AND results.employee_id = module_allocation.employee_id
            WHERE 
                results.employee_id = $employee_id;";

        // Execute the query
        $result = $conn->query($query);

        // Check if the query was successful
        if ($result) {
            // Initialize an empty associative array to store grouped data
            $groupedData = array();

            // Loop through each row in the result set
            while ($row = $result->fetch_assoc()) {
                // Get the module ID for the current row
                $moduleId = $row['module_name'];

                // If the module ID doesn't exist in the grouped data array, create a new entry for it
                if (!isset($groupedData[$moduleId])) {
                    $groupedData[$moduleId] = array();
                }

                // Add the row data to the corresponding module ID group
                $groupedData[$moduleId][] = $row;
            }

            // Check if there are any rows returned
            if (!empty($groupedData)) {

                // Loop through each module ID group
                foreach ($groupedData as $moduleId => $rows) {
                    echo '<div class="container mb-2 p-4 bg-light rounded-3 shadow-lg tableborder">';
                    echo '<h5>Module: <strong> ' . $moduleId . ' </strong></h5>';

                    // Start table markup for the current module ID group
                    echo '<div class="table-responsive text-center">';
                    echo '<table class="table table-hover table-striped table-bordered">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th scope="col" class="col-md-3">Score</th>';
                    echo '<th scope="col" class="col-md-6">Date</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    // Loop through each row in the current module ID group
                    foreach ($rows as $row) {

                        // Get the total number of questions for the current module
                        $moduleTotalQuestions = isset($totalQuestionsPerModule[$row['module_id']]) ? $totalQuestionsPerModule[$row['module_id']] : 0;

                        // Calculate the percentage of correct answers
                        $score = $row['score'];
                        $correctAnswers = round(($moduleTotalQuestions * $score) / 100, 0);

                        // Display the data for each row
                        echo '<tr class="table-row text-center" data-toggle="modal" data-target="#myModal">';
                        if ($correctAnswers == $moduleTotalQuestions) {
                            // If all answers are correct, use green badge
                            echo "<td class='text-center align-middle'>
                                    <div class='d-flex justify-content-center align-items-center'>
                                        <span class='badge rounded-pill bg-success'>" . $correctAnswers . " out of " . $moduleTotalQuestions . "</span>
                                    </div>
                                  </td>";
                        } else {
                            // For other cases, use red badge
                            echo "<td class='text-center align-middle'>
                                    <div class='d-flex justify-content-center align-items-center'>
                                        <span class='badge rounded-pill bg-danger'>" . $correctAnswers . " out of " . $moduleTotalQuestions . "</span>
                                    </div>
                                  </td>";
                        }

                        echo '<td>' . $row['timestamp'] . '</td>';
                        echo '</tr>';
                    }

                    // End table markup for the current module ID group
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';

                    echo '</div>'; // Close the container for the table
                }

                echo '<div class="d-flex justify-content-center">';

                // Add a print button
                echo '<button class="btn btn-dark m-1 mb-2" onclick="window.print()">Print</button>';

                // Add an export to Excel button
                echo '<button class="btn signature-btn m-1 mb-2" onclick="exportToExcel()">Export to CSV</button>';
                echo '</div>';
            } else {
                echo '<div class="d-flex justify-content-center">';
                echo '<h4 style="width: 70%" class="alert alert-danger text-center"> You have not done any module. </h4>';
                echo '</div>';
            }
        } else {
            echo 'Error executing the query: ' . $conn->error;
        }

        // Close the database connection
        $conn->close();
        ?>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Row Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Row details will be dynamically added here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <?php require_once("footer_logout.php"); ?>

    <!-- ================================================================================== -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript code to show data in a modal popup -->
    <script>
        const tableRows = document.querySelectorAll(".table-row");
        tableRows.forEach(row => {
            row.addEventListener("click", () => {
                const score = row.querySelector("td:nth-child(1)").textContent;
                const date = row.querySelector("td:nth-child(2)").textContent;
                const duration = row.querySelector("td:nth-child(3)").textContent;

                const modalBody = document.querySelector(".modal-body");
                modalBody.innerHTML = `
                    <p><strong>Score:</strong> ${score}</p>
                    <p><strong>Date:</strong> ${date}</p>
                    <p><strong>Duration:</strong> ${duration}</p>
                `;

                const modal = new bootstrap.Modal(document.getElementById("myModal"));
                modal.show();
            });
        });
    </script>

    <!-- ================================================================================== -->
    <!-- JavaScript function to export the table to Excel -->
    <script>
        function exportToExcel() {
            const containers = document.querySelectorAll('.container.mb-2');

            const currentDate = new Date().toLocaleDateString('en-AU', {
                timeZone: 'Australia/Sydney'
            });

            const employeeId = "<?php echo $employee_id; ?>";

            const fullName = "<?php echo $row['full_name'] ?? ''; ?>";

            let csvContent = `Employee ID: ${employeeId}, Full Name: ${fullName}\n`; // Header for the first table

            csvContent += "\n"; // Separate tables with an empty line

            containers.forEach(container => {
                const moduleTitle = container.querySelector('h5 strong').textContent.trim();
                const rows = Array.from(container.querySelectorAll('tbody tr'));

                // Header for the second table
                csvContent += `Module,Score,Date\n`;

                let isFirstRow = true;

                rows.forEach(row => {
                    const score = row.querySelector('td:nth-child(1)').textContent.trim();
                    const date = row.querySelector('td:nth-child(2)').textContent.trim();

                    // Print module name only for the first row of each module
                    const moduleName = isFirstRow ? moduleTitle : '';

                    // Add module, score, and date to the CSV content
                    csvContent += `${moduleName},${score},${date}\n`;

                    isFirstRow = false;
                });

                csvContent += "\n"; // Separate tables with an empty line
            });

            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.download = `${employeeId} - ${fullName} - ${currentDate}  - MCQ Progress.csv`;
            link.click();
            URL.revokeObjectURL(url);
        }
    </script>
</body>

</html>