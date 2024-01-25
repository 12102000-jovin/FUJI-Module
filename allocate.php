<?php
// Start the session, to load all the Session Variables
session_start();
// Connect to the database
require_once("db_connect.php");
// Checking the inactivity 
require_once("inactivity_check.php");

// Get the user's role from the session data
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

// Check if the user wants to logout or not
if (isset($_GET['logout']) && ($_GET['logout']) === 'true') {
    // Terminate all the session
    session_destroy();
    header("Location: login.php");
    exit();
}

/* ================================================================================== */

// Retrieve the username from the session
$username = $_SESSION['username'] ?? '';
// Assign and set format of the date
$currentDate = date('F j, Y');

// (Retrieve the employee ID and role from the users table)
$emp_id_query = "SELECT employee_id, role FROM users WHERE username = '$username'";
// Execute the SQL query and store the result in the $result variable
$result = $conn->query($emp_id_query);

// Check if the query result is not empty and contains one or more rows of data
if ($result && $result->num_rows > 0) {
    // Fetch the next row of data from the result set and store it in the $row variable.
    $row = $result->fetch_assoc();

    // Assigning employee ID and role from the row data
    $employee_id = $row['employee_id'];
    $role = $row['role'];

    // Set the employee_id value in session
    $_SESSION['employeeId'] = $employee_id;

    // Set the role value in session
    $_SESSION['userRole'] = $role;
} else {
    // Set a default value if the employee_id is not found
    $employee_id = 'N/A';
}
// Free up the memory used by the database query result
$result->free();

/* ================================================================================== */

// Pagination settings
$availableRecordsPerPage = array(10, 15, 20);
$recordsPerPage = isset($_GET['recordsPerPage']) && in_array($_GET['recordsPerPage'], $availableRecordsPerPage) ? intval($_GET['recordsPerPage']) : 10;
$pageNumber = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($pageNumber - 1) * $recordsPerPage;

// Retrieve users from the 'users' table with pagination
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$usersQuery = "
        SELECT users.*, department.department_name
        FROM users
        LEFT JOIN department ON users.department_id = department.department_id
        WHERE username LIKE '%$search_query%'
        OR full_name LIKE '%$search_query%'
        OR department.department_name LIKE '%$search_query%'
        OR employee_id LIKE '%$search_query%'
        LIMIT $offset, $recordsPerPage
    ";
$usersResult = $conn->query($usersQuery);

// Count total number of records for pagination
$countQuery = "
        SELECT COUNT(*) AS total
        FROM users
        LEFT JOIN department ON users.department_id = department.department_id
        WHERE username LIKE '%$search_query%'
        OR full_name LIKE '%$search_query%'
        OR department.department_name LIKE '%$search_query%'
        OR employee_id LIKE '%$search_query%'
    ";

$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

/* ================================================================================== */

// Check if the deactivate parameter is set
if (isset($_GET['deactivate']) && $_GET['deactivate'] === 'true') {
    // Get the employee_id from the query parameters
    if (isset($_GET['employee_id']) && is_numeric($_GET['employee_id'])) {
        $deactivateEmployeeId = $_GET['employee_id'];

        // Prepare the SQL statement with a parameter placeholder
        $deactivateUserQuery = "
                UPDATE users SET is_active = '0' WHERE employee_id = ?
            ";

        // Prepare and execute the statement
        $stmt = $conn->prepare($deactivateUserQuery);
        $stmt->bind_param("i", $deactivateEmployeeId);

        $deactivateUserResult = $stmt->execute();

        // Check if the query was successful
        if ($deactivateUserResult) {
            $_SESSION['deactivateUser'] = true;

            // Close the statement
            $stmt->close();

            // Redirect back to the same page after deactivating the user
            header("Location: allocate.php?page=$pageNumber");
            exit();
        } else {
            // Handle any errors that occurred during query execution
            echo "Error deactivating user: " . $conn->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        // Handle invalid or missing employee_id parameter
        echo "Invalid or missing employee_id parameter";
    }
}

/* ================================================================================== */

// Check if the activate parameter is set
if (isset($_GET['activate']) && $_GET['activate'] === 'true') {
    // Get the employee_id from the query parameters
    if (isset($_GET['employee_id']) && is_numeric($_GET['employee_id'])) {
        $activateEmployeeId = $_GET['employee_id'];

        // Prepare the SQL statement with a parameter placeholder
        $activateUserQuery = "
                UPDATE users SET is_active = '1' WHERE employee_id = ?
            ";

        // Prepare and execute the statement
        $stmt = $conn->prepare($activateUserQuery);
        $stmt->bind_param("i", $activateEmployeeId);

        $activateUserResult = $stmt->execute();

        // Check if the query was successful
        if ($activateUserResult) {
            $_SESSION['activateUser'] = true;

            // Close the statement
            $stmt->close();

            // Redirect back to the same page after activating the user
            header("Location: allocate.php?page=$pageNumber");
            exit();
        } else {
            // Handle any errors that occurred during query execution
            echo "Error activating user: " . $conn->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        // Handle invalid or missing employee_id parameter
        echo "Invalid or missing employee_id parameter";
    }
}

// ==================================================================================

// Close the database connection
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
    <!-- Linking external CSS stylesheet to apply styles to the HTML document -->
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <!-- Internal CSS for the HTML -->
    <style>
        .pagination .page-item {
            color: #043f9d;
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border-color: #043f9d;
        }

        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }

        .action-column {
            width: 200px;
        }

        /* Add custom CSS styles here */
        @media (max-width: 576px) {
            .pagepagination label {
                font-size: 12px;
            }

            .form-select {
                font-size: 12px;
                width: 65px !important;
            }

            .search {
                font-size: 12px;
                padding: 9px;
                margin-top: 4px;
            }

            .createNewUserBtn {
                font-size: 12px;
            }
        }

        .no-wrap {
            white-space: nowrap;
        }
    </style>

    <script>
        // Capture scroll position before page refresh or redirection
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });
    </script>

    <!-- Title of the page -->
    <title>Allocate</title>
</head>

<!-- ==================================================================================  -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <div class="container">
        <div class="mt-5">
            <div class="d-flex justify-content-start mt-5">
                <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
            </div>
            <div class="container text-center mb-4">
                <h1><strong>All Users</strong></h1>
            </div>

        </div>
        <?php
        if ($role === 'admin') {
            echo '<div class="d-flex justify-content-center">';
            echo '<a class="btn btn-dark mb-3 createNewUserBtn" href="create-user.php" role="button"> + Create New User </a>';
            echo '</div>';
        }
        ?>

        <div class="container p-2 shadow-lg rounded-2 bg-light mb-5">
            <div class="container mb-4">
                <div class="row mt-4 mb-4">
                    <div class="col-md-6 col-sm-6">
                        <form method="GET" action="allocate.php">
                            <div class="d-flex align-items-center">
                                <input type="search" name="search_query" class="form-control me-2 test" placeholder="Search users" value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="btn signature-btn search">Search</button>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-6 col-sm-6 d-flex align-items-center justify-content-center justify-content-sm-end mt-2 mt-sm-0 pagepagination">
                        <label class="test my-auto me-2">Show</label>
                        <select id="recordsPerPage" name="recordsPerPage" class="form-select me-2" style="width: 70px">
                            <option value="10" <?php echo $recordsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="15" <?php echo $recordsPerPage == 15 ? 'selected' : ''; ?>>15</option>
                            <option value="20" <?php echo $recordsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                        </select>
                        <label>entries</label>
                    </div>
                </div>

            </div>

            <?php
            if ($usersResult && $usersResult->num_rows > 0) {
                echo '<div class="container table-responsive">';
                echo '<table class="table table-hover table-striped mb-4 border">';
                echo '<thead class="align-middle">';
                echo '<tr class="text-center">';
                echo '<th class="border">Profile</th>';
                echo '<th class="border">Employee ID</th>';
                echo '<th class="border">Username</th>';
                echo '<th class="border">Full Name</th>';
                echo '<th class="border">Department</th>';
                echo '<th class="border">Role</th>';
                echo '<th class="border">Status</th>';
                echo '<th class="border action-column">Action</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                while ($user = $usersResult->fetch_assoc()) {
                    $employee_id = $user['employee_id'];
                    $username = $user['username'];
                    $full_name = $user['full_name'];
                    $department = $user['department_name'];
                    $roleTable = $user['role'];
                    $profile_image = $user['profile_image'];
                    $capitaliseRole = ucwords($roleTable);

                    // Print the user details in a table row
                    echo '<tr class="text-center align-middle">';
                    if ($profile_image) {
                        echo "<td><div><img src='$profile_image' alt='Profile Image' class='profile-pic' style='max-width: 5vh'></div></td>";
                    } else {
                        $name_parts = explode(" ", $full_name);
                        $initials = "";
                        foreach ($name_parts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                        echo "<td><strong > $initials </str</td>";
                    }
                    echo "<td>$employee_id</td>";
                    echo "<td>$username</td>";
                    echo "<td>$full_name</td>";
                    echo "<td>$department</td>";
                    echo "<td>$capitaliseRole</td>";
                    echo '<td>  
                                <div class="d-flex align-items-center justify-content-center">';
                    if ($user['is_active'] == '1') {
                        echo '<span class="badge badge-pill bg-success rounded-pill">Active</span>';
                    } else {
                        echo '<span class="badge badge-pill bg-danger rounded-pill">Not Active</span>';
                    }
                    echo '</div> </td>';
                    echo '<td class="no-wrap">';
                    if ($role === 'admin') {
                        echo ' <a href="edit-user.php?employee_id=' . $employee_id . '"><i class="fa-regular fa-pen-to-square signature-color m-2 tooltips" data-toggle="tooltip" data-placement="top" title="Edit User"></i></a>';
                    }

                    echo '<a href="allocate-more.php?employee_id=' . $employee_id . '"><i class="fa-solid fa-list-check signature-color m-2 tooltips" data-toggle="tooltip" data-placement="top" title="Allocate Module"></i></a>
                                <a href="manage-license.php?employee_id=' . $employee_id . '"><i class="fa-regular fa-id-card signature-color m-2 tooltips" data-toggle="tooltip" data-placement="top" title="Manage License"></i></a>';

                    if ($role === 'admin') {
                        if ($user['is_active'] == '1') {
                            echo '<a href="allocate.php?deactivate=true&employee_id=' . $employee_id . '&page=' . $pageNumber . '" style="text-decoration:none">
                                            <i class="text-danger fa-solid fa-user-slash m-2 tooltips"
                                            data-toggle="tooltip" data-placement="top" title="Deactivate User">
                                            </i>
                                          </a>';
                        } else {
                            echo '<a href="allocate.php?activate=true&employee_id=' . $employee_id . '&page=' . $pageNumber . '" style="text-decoration:none">
                                            <i class="text-success fa-solid fa-user-plus m-2 tooltips"
                                            data-toggle="tooltip" data-placement="top" title="Activate User">
                                            </i>
                                          </a>';
                        }
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<div class="container text-center mt-5 mb-5">';
                echo "<h3><strong> No users found. <h3></strong>";
                echo '</div>';
                echo '<div class="d-flex justify-content-center">';
                echo '<a class="btn btn-dark mb-3 createNewUserBtn" href="create-user.php" role="button"> + Create New User </a>';
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
        <?php
        // Pagination controls
        echo '<div class="d-flex justify-content-center align-items-center">';
        echo '<nav aria-label="Page navigation example">';
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
        echo '</nav>';
        echo '</div>';
        echo '</div>';
        ?>
    </div>
    </div>

    <!-- ================================================================================== -->

    <?php require_once("footer_logout.php"); ?>

    <!-- ================================================================================== -->

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

    <!-- ================================================================================== -->

    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t)
        })
    </script>

    <!-- ================================================================================== -->

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