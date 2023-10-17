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

// ================================================================================== 

// Pagination settings
$availableRecordsPerPage = array(5, 10, 15);
$recordsPerPage = isset($_GET['recordsPerPage']) && in_array($_GET['recordsPerPage'], $availableRecordsPerPage) ? intval($_GET['recordsPerPage']) : 5;
$pageNumber = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($pageNumber - 1) * $recordsPerPage;

// Fetch data from the modules table
$modules = array();
$sql = "SELECT * FROM modules";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $modules[$row['module_id']] = array(
            'module_name' => $row['module_name'],
            'module_description' => $row['module_description']
        );
    }
}

// Fetch data from the users table
$users = array();
$sql = "SELECT * FROM users";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[$row['employee_id']] = array(
            'username' => $row['username'],
            'role' => $row['role'],
            'employee_id' => $row['employee_id'],
            'full_name' => $row['full_name']
        );
    }
}

// ==================================================================================

// Fetch data from the results table with search condition
$results = array();
$tries = array();
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : "";
$date = isset($_GET['date']) ? $_GET['date'] : null;

// Escape the search term
$escapedSearchTerm = mysqli_real_escape_string($conn, $searchTerm);

// Format the date to match the MySQL date format (YYYY-MM-DD)
$formattedDate = date("Y-m-d", strtotime($date));

$sql = "SELECT r.*, m.module_name 
    FROM results r
    JOIN modules m ON r.module_id = m.module_id
    JOIN users u ON r.employee_id = u.employee_id
    WHERE (r.module_id LIKE '%$escapedSearchTerm%'
    OR r.score LIKE '%$escapedSearchTerm%'
    OR r.duration LIKE '%$escapedSearchTerm'
    OR m.module_name LIKE '%$escapedSearchTerm%'
    OR u.full_name LIKE '%$escapedSearchTerm%'
    OR u.role LIKE '%$escapedSearchTerm%')
    " . ($date ? "AND DATE(r.timestamp) = '$formattedDate'" : "") . "
    ORDER BY r.timestamp DESC  
    LIMIT $offset, $recordsPerPage";

// Count the total number of records
$countQuery = "SELECT COUNT(*) AS total FROM results r
    JOIN modules m ON r.module_id = m.module_id
    JOIN users u ON r.employee_id = u.employee_id
    WHERE (r.module_id LIKE '%$escapedSearchTerm%'
    OR r.score LIKE '%$escapedSearchTerm%'
    OR m.module_name LIKE '%$escapedSearchTerm%'
    OR u.full_name LIKE '%$escapedSearchTerm%'
    OR u.role LIKE '%$escapedSearchTerm%')
    " . ($date ? "AND DATE(r.timestamp) = '$formattedDate'" : "");

$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// ==================================================================================

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $results[] = array(
            'result_id' => $row['result_id'],
            'employee_id' => $row['employee_id'],
            'module_id' => $row['module_id'],
            'module_name' => $row['module_name'],
            'score' => $row['score'],
            'timestamp' => $row['timestamp'],
            'duration' => $row['duration']
        );

        // Count tries per user and module combination
        $employeeId = $row['employee_id'];
        $moduleId = $row['module_id'];
        if (isset($tries[$employeeId][$moduleId])) {
            $tries[$employeeId][$moduleId]++;
        } else {
            $tries[$employeeId][$moduleId] = 1;
        }
    }
}

$conn->close();
?>

<!-- ================================================================================== -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <title>Report</title>
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

    <script>
        // Capture scroll position before page refresh or redirection
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });
    </script>
</head>

<!-- ================================================================================== -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container text-center mt-5">
        <h1><strong> Report </strong></h1>
    </div>

    <div class="container mt-5">
        <div class="rounded-3 p-5 bg-light shadow-lg">
            <form class="form-inline" method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="input-group mb-3 mt-3">
                            <div class="input-group-prepend">
                                <button class="btn signature-btn tooltips" type="button" id="toggleButton" data-bs-toggle="tooltip" data-bs-placement="top" title="Search by Date">
                                    <i class="fas fa-calendar"></i>
                                </button>
                            </div>
                            <div class="col-md-8 form-group" id="filterGroup">
                                <input type="search" class="form-control" id="filterInput" name="searchTerm" placeholder="Search (Module/Name/Result)">
                            </div>
                            <div class="col-md-8 form-group" id="dateGroup" style="display: none;">
                                <input type="date" class="form-control" id="dateInput" name="date">
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <button type="submit" class="btn signature-btn" style="margin-left: 10px;">Search</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-center justify-content-end">
                        <label class="my-auto me-2">Show</label>
                        <select id="recordsPerPage" name="recordsPerPage" class="form-select me-2" style="width: 70px">
                            <option value="5" <?php echo $recordsPerPage == 5 ? 'selected' : ''; ?>>5</option>
                            <option value="10" <?php echo $recordsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="15" <?php echo $recordsPerPage == 15 ? 'selected' : ''; ?>>15</option>
                        </select>
                        <label>entries</label>
                    </div>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-bordered table-striped table-hover" id="resultsTable">
                    <thead class="align-middle">
                        <tr class="text-center">
                            <th style="width: 30vw">Module</th>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Score</th>
                            <th>Date</th>
                            <th>Duration</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        <?php
                        foreach ($results as $result) {
                            echo "<tr data-bs-toggle='modal' data-bs-target='#resultModal'>";
                            echo "<td>" . $result['module_name'] . "</td>";
                            echo "<td class='text-center align-middle'>" . $users[$result['employee_id']]['employee_id'] . "</td>";
                            echo "<td class='text-center align-middle'>" . $users[$result['employee_id']]['full_name'] . "</td>";
                            echo "<td class='text-center align-middle'>" . $result['score'] . "</td>";
                            echo "<td class='text-center align-middle'>" . date("Y-m-d", strtotime($result['timestamp'])) . "</td>"; // Display only the date

                            // Calculate the elapsed time in minutes and seconds
                            $durationMinutes = floor($result['duration'] / 60);
                            $durationSeconds = $result['duration'] % 60;

                            // Format and display the elapsed time
                            $formattedDuration = "$durationMinutes" . "m " . "$durationSeconds" . "s";

                            if ($durationMinutes == 0 && $durationSeconds > 0) {
                                echo "<td class='text-center align-middle'>" . $durationSeconds . "s" . "</td>";
                            } else if ($durationMinutes > 0 && $durationSeconds > 0) {
                                echo "<td class='text-center align-middle'>" . $formattedDuration  . "</td>";
                            } else if ($durationMinutes > 0 && $durationSeconds == 0) {
                                echo "<td class='text-center align-middle'>" . $durationMinutes . "m" . "</td>";
                            }

                            echo "<td class='text-center align-middle'><a href='result-details.php?result_id=" . $result['result_id'] . "'><i class='fa-regular fa-rectangle-list signature-color tooltips data-bs-toggle='tooltip' data-bs-placement='top' title='See Answers'></i></a></td>";
                            echo "</tr>";
                        }
                        if (empty($results)) {
                            echo "<tr><td colspan='8' class='text-center'>No results found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                <button class="btn signature-btn mb-2" onclick="exportResultsToCSV()">Export to CSV</button>
            </div>

            <div class="mt-5 text-center">
                <h6>User Attempt History</h6>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="triesTable">
                    <thead class="align-middle text-center">
                        <tr>
                            <th>Module</th>
                            <th>Full Name</th>
                            <th>Tries</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        <?php
                        foreach ($tries as $employeeId => $moduleTries) {
                            foreach ($moduleTries as $moduleId => $tryCount) {
                                echo "<tr data-bs-toggle='modal' data-bs-target='#triesModal'>";
                                echo "<td>" . $modules[$moduleId]['module_name'] . "</td>";
                                echo "<td class='text-center'>" . $users[$employeeId]['full_name'] . "</td>";
                                echo "<td class='text-center'>" . $tryCount . "</td>";
                                echo "</tr>";
                            }
                        }

                        if (empty($tries)) {
                            echo "<tr><td colspan='4' class='text-center'>No tries found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                <button class="btn btn-secondary m-1 mb-2" onclick="window.print()">Print</button>
                <button class="btn signature-btn m-1 mb-2" onclick="exportTriesToCSV()">Export to CSV</button>
            </div>

            <div class="d-flex justify-content-center">
                <nav aria-label="Page navigation example">
                    <ul class="pagination mt-2">
                        <?php
                        // Calculate the start and end page numbers for the limited pagination
                        $startPage = max(1, $pageNumber - 1);
                        $endPage = min($totalPages, $pageNumber + 1);

                        // Adjust start and end page numbers to show a maximum of 3 page links
                        if ($endPage - $startPage + 1 > 3) {
                            if ($startPage === 1) {
                                $endPage = $startPage + 2;
                            } elseif ($endPage === $totalPages) {
                                $startPage = $endPage - 2;
                            } else {
                                $startPage = $pageNumber - 1;
                                $endPage = $pageNumber + 1;
                            }
                        }
                        ?>

                        <!-- Previous page link -->
                        <?php if ($pageNumber > 1) : ?>
                            <li class="page-item">
                                <a class="page-link signature-color" href="?employee_id=<?php echo $employee_id; ?>&recordsPerPage=<?php echo $recordsPerPage; ?>&page=<?php echo ($pageNumber - 1); ?>"><i class="fas fa-angle-double-left"></i></a>
                            </li>
                        <?php else : ?>
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1"><i class="fas fa-angle-double-left"></i></a>
                            </li>
                        <?php endif; ?>

                        <!-- Page numbers -->
                        <?php
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            echo '<li class="page-item ';
                            if ($i === $pageNumber) {
                                echo ' active" style="background-color: #f0f0f0 !important;'; // Replace with your desired background color
                            }
                            echo '">';
                            echo '<a class="page-link signature-color " href="?employee_id=' . $employee_id . '&recordsPerPage=' . $recordsPerPage . '&page=' . $i . '&search_query=' . urlencode($search_query) . '">' . $i . '</a>';
                            echo '</li>';
                        }
                        ?>

                        <!-- Next page link -->
                        <?php if ($pageNumber < $totalPages) : ?>
                            <li class="page-item">
                                <a class="page-link signature-color" href="?employee_id=<?php echo $employee_id; ?>&recordsPerPage=<?php echo $recordsPerPage; ?>&page=<?php echo ($pageNumber + 1); ?>"><i class="fas fa-angle-double-right"></i></a>
                            </li>
                        <?php else : ?>
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1"><i class="fas fa-angle-double-right"></i></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">
        <a href="index.php" class="btn btn-dark"> Back to Home </a>
    </div>

    <!-- ================================================================================== -->

    <!-- Result Modal -->
    <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resultModalLabel">Result Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="resultDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tries Modal -->
    <div class="modal fade" id="triesModal" tabindex="-1" aria-labelledby="triesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="triesModalLabel">Tries Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="triesDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

    <div class="mt-5"></div>

    <!-- Footer Section -->
    <footer class="bg-light text-center py-4 mt-auto ">
        <div class="container">
            <p class="mb-0 font-weight-bold" style="font-size: 1.5vh"><strong>&copy; <?php echo date('Y'); ?> FUJI Training Module. All rights reserved.</strong></p>
        </div>
    </footer>

    <!-- ================================================================================== -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to export results to CSV
        function exportResultsToCSV() {
            let table = document.getElementById("resultsTable");
            let rows = Array.from(table.getElementsByTagName("tr"));

            // Remove the last row if it is the "No results found" message
            let lastRow = rows[rows.length - 1];
            if (lastRow.textContent === "No results found.") {
                rows.pop();
            }

            let csvContent = "data:text/csv;charset=utf-8,";
            let headers = Array.from(rows.shift().getElementsByTagName("th")).map(header => header.textContent);
            let data = rows.map(row => Array.from(row.getElementsByTagName("td")).map(cell => cell.textContent));

            csvContent += headers.join(",") + "\n";
            csvContent += data.map(row => row.join(",")).join("\n");

            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "results.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Function to export tries to CSV
        function exportTriesToCSV() {
            let table = document.getElementById("triesTable");
            let rows = Array.from(table.getElementsByTagName("tr"));

            // Remove the last row if it is the "No tries found" message
            let lastRow = rows[rows.length - 1];
            if (lastRow.textContent === "No tries found.") {
                rows.pop();
            }

            let csvContent = "data:text/csv;charset=utf-8,";
            let headers = Array.from(rows.shift().getElementsByTagName("th")).map(header => header.textContent);
            let data = rows.map(row => Array.from(row.getElementsByTagName("td")).map(cell => cell.textContent));

            csvContent += headers.join(",") + "\n";
            csvContent += data.map(row => row.join(",")).join("\n");

            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "tries.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Show result details in modal
        let resultModal = document.getElementById('resultModal');
        resultModal.addEventListener('show.bs.modal', function(event) {
            let button = event.relatedTarget;
            let row = button.closest('tr');

            let module = row.cells[0].textContent;
            let employeeId = row.cells[1].textContent;
            let name = row.cells[2].textContent;
            let score = row.cells[3].textContent;
            let date = row.cells[4].textContent;
            let duration = row.cells[5].textContent;


            let resultDetails = document.getElementById('resultDetails');
            resultDetails.innerHTML = `
                <p><strong> Module: </strong> ${module}</p>
                <p><strong>ID: </strong>${employeeId}</p>
                <p><strong>Name: </strong>${name}</p>
                <p><strong>Score:</strong> ${score}</p>
                <p><strong>Date: </strong>${date}</p>
                <p><strong>Duration: </strong>${duration}</p>
            `;
        });

        // Show tries details in modal
        let triesModal = document.getElementById('triesModal');
        triesModal.addEventListener('show.bs.modal', function(event) {
            let button = event.relatedTarget;
            let row = button.closest('tr');

            let module = row.cells[0].textContent;
            let fullName = row.cells[1].textContent;
            let tries = row.cells[2].textContent;

            let triesDetails = document.getElementById('triesDetails');
            triesDetails.innerHTML = `
                <p> <strong>Module: </strong>${module}</p>
                <p> <strong>Full Name: </strong>${fullName}</p>
                <p> <strong>Tries: </strong>${tries}</p>
            `;
        });

        // Function to toggle between filter and date inputs
        function toggleSearchInputs() {
            var filterGroup = document.getElementById("filterGroup");
            var dateGroup = document.getElementById("dateGroup");
            var toggleButton = document.getElementById("toggleButton");
            var toggleIcon = document.getElementById("toggleIcon");

            if (filterGroup.style.display === "none") {
                filterGroup.style.display = "block";
                dateGroup.style.display = "none";
                toggleButton.innerHTML = '<i class="fas fa-calendar"></i>';
                toggleButton.setAttribute("data-bs-original-title", "Search by Date");
            } else {
                filterGroup.style.display = "none";
                dateGroup.style.display = "block";
                toggleButton.innerHTML = '<i class="fas fa-font"></i>';
                toggleButton.setAttribute("data-bs-original-title", "Search by Word");
            }

            // Destroy the existing tooltip instance
            var tooltip = bootstrap.Tooltip.getInstance(toggleButton);
            if (tooltip) {
                tooltip.dispose();
            }

            // Create a new tooltip instance with the updated text
            tooltip = new bootstrap.Tooltip(toggleButton);
        }

        // Attach event listener to the toggle button
        var toggleButton = document.getElementById("toggleButton");
        toggleButton.addEventListener("click", toggleSearchInputs);
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

    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t)
        })
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