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

    <div class="container text-center mt-5">
        <h1><strong>Your Progress</strong></h1>
    </div>

    <!-- ================================================================================== -->

    <div class="container mb-5">
        <div class="mt-5">
            <?php
            $employee_id = $_SESSION['employeeId'] ?? 'N/A';

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Query to select columns from multiple tables
            $query = "SELECT 
                results.employee_id, 
                users.username, 
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
                            // Display the data for each row
                            echo '<tr class="table-row text-center" data-toggle="modal" data-target="#myModal">';
                            echo '<td> <strong> ' . $row['score'] . ' </strong> ' . 'out of 100' . '</td>';
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
                    echo '<button class="btn btn-secondary m-1 mb-2" onclick="window.location.href=\'index.php\'" role="button">Back</button>';

                    // Add a print button
                    echo '<button class="btn btn-dark m-1 mb-2" onclick="window.print()">Print</button>';

                    // Add an export to Excel button
                    echo '<button class="btn signature-btn m-1 mb-2" onclick="exportToExcel()">Export to CSV</button>';
                    echo '</div>';
                } else {
                    echo '<div class="text-center">';
                    echo '<h3> You have not done any module. </h3>';
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
    </div>

    <!-- ================================================================================== -->

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
            const table = document.querySelector("table");
            const rows = Array.from(table.querySelectorAll("tr"));
            const csvContent = rows.map(row => Array.from(row.querySelectorAll("th, td")).map(cell => cell.textContent).join(",")).join("\n");
            const blob = new Blob([csvContent], {
                type: "text/csv;charset=utf-8;"
            });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.download = "progress.csv";
            link.click();
            URL.revokeObjectURL(url);
        }
    </script>
</body>

</html>