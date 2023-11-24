<?php
session_start();
// Assuming you have a database connection established
require_once('db_connect.php');
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

// Archive Function
if (isset($_GET['module_id']) && isset($_GET['archive'])) {
    $moduleId = $_GET['module_id'];

    // Set the time zone to Australia/Sydney
    date_default_timezone_set('Australia/Sydney');

    $archivedDate = date('Y-m-d H:i:s');

    $updateModulesSql = "UPDATE modules SET is_archived = true, archived_date = '$archivedDate' WHERE module_id = $moduleId";
    $updateModulesResult = mysqli_query($conn, $updateModulesSql);

    $updateQuestionsSql = "UPDATE module_questions SET is_archived = true WHERE module_id = $moduleId";
    $updateQuestionsResult = mysqli_query($conn, $updateQuestionsSql);

    if ($updateModulesResult && $updateQuestionsResult) {
        header("Location: modules.php");
        exit();
    } else {
        echo "Error archiving the module.";
    }
}

// ==================================================================================

// Retrieve the username from the session
$username = $_SESSION['username'] ?? '';
// Assign and set format of the date
$currentDate = date('F j, Y');

// (Retrieve the employee ID and role from the users table)
$emp_id_query = "SELECT employee_id, role, full_name FROM users WHERE username = '$username'";
// Execute the SQL query and store the result in the $result variable
$result = $conn->query($emp_id_query);

// Check if the query result is not empty and contains one or more rows of data
if ($result && $result->num_rows > 0) {
    // Fetch the next row of data from the result set and store it in the $row variable.
    $row = $result->fetch_assoc();

    // Assigning employee ID and role from the row data
    $employee_id = $row['employee_id'];
    $role = $row['role'];
    $full_name = $row['full_name'];

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
    <!-- Linking external CSSS stylesheet to apply styles to the HTML document -->
    <link rel="stylesheet" type="text/css" href="style.css">

    <!-- Title of the page -->
    <title> Modules </title>
</head>

<!-- ================================================================================== -->

<body class="bg-gradient d-flex flex-column min-vh-100 signature-bg-color">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container mb-3">
        <form class="row form-inline mt-4 justify-content-center align-items-center" method="GET" action="modules.php">
            <?php if ($role === 'admin') { ?>
                <div class="col-md-8">
                <?php } else { ?>
                    <div class="col-md-8 text-center">
                    <?php } ?>
                    <div class="d-flex align-items-center">
                        <input class="form-control mr-sm-2" type="search" name="search" placeholder="Search Module" aria-label="Search" style="height: 38px;">
                        <button class="btn btn-outline-light mx-2 my-2 my-sm-0" type="submit">Search</button>
                    </div>
                    </div>
                    <?php if ($role === 'admin' ||  $role === 'supervisor') { ?>
                        <div class="col-md-4 text-md-end">
                            <a href="create-module.php" class="btn mt-md-0 mt-2 text-white bg-dark CTA-btn">+ Create New Module</a>
                        </div>
                    <?php } ?>
        </form>
    </div>

    <!-- ================================================================================== -->

    <?php

    // Check if a search query is present
    if (isset($_GET['search'])) {
        $searchTerm = $_GET['search'];
        $searchSql = "SELECT module_id, module_name, module_description, module_image FROM modules WHERE is_archived = false AND module_name LIKE '%$searchTerm%'";
        $modulesQuery = "
        SELECT 
            m.module_id, 
            m.module_name, 
            m.module_description, 
            m.module_image
        FROM modules m
        LEFT JOIN module_allocation ma ON m.module_id = ma.module_id AND ma.employee_id = '$employee_id'
        WHERE m.is_archived = '0' AND ma.module_id IS NOT NULL AND m.module_name LIKE '%$searchTerm%'
        GROUP BY m.module_id, m.module_name, m.module_description, m.module_image
        ORDER BY m.module_id, m.module_name ASC;
        ";
    } else {
        $searchSql = "SELECT module_id, module_name, module_description, module_image FROM modules WHERE is_archived = false";
        $modulesQuery = "
        SELECT 
            m.module_id, 
            m.module_name, 
            m.module_description, 
            m.module_image
        FROM modules m
        LEFT JOIN module_allocation ma ON m.module_id = ma.module_id AND ma.employee_id = '$employee_id'
        WHERE m.is_archived = '0' AND ma.module_id IS NOT NULL
        GROUP BY m.module_id, m.module_name, m.module_description, m.module_image
        ORDER BY m.module_id;
        ";
    }

    $searchResult = mysqli_query($conn, $searchSql);
    $moduleResult = mysqli_query($conn, $modulesQuery);

    // ====================================== USER ====================================== 

    if ($role === 'user') {
        if (mysqli_num_rows($moduleResult) > 0) {
            while ($row = mysqli_fetch_assoc($moduleResult)) {
                $moduleId = $row['module_id'];
                $moduleName = $row['module_name'];
                $moduleDescription = $row['module_description'];
                $module_image = $row['module_image'];

                $maxCharacters = 150;
                $charactersArray = str_split($moduleDescription);
                $limitedDescription = implode('', array_slice($charactersArray, 0, $maxCharacters));
    ?>
                <div class="container mb-2 mt-2">
                    <div class="card shadow-lg">
                        <div class="row g-0 p-3 ">
                            <div class="col-12 col-md-2 align-self-center text-center">
                                <img src="<?php echo $module_image; ?>" alt="<?php echo $moduleName; ?>" class="img-fluid" style="max-height: 150px; max-width: 100%;">
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="card-body">
                                    <h3><?php echo $moduleName; ?></h3>
                                    <p style="text-align: justify;" class="text-wrap">
                                        <?php
                                        if (count($charactersArray) > $maxCharacters) {
                                            echo $limitedDescription . ' ...';
                                        } else {
                                            echo $limitedDescription;
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-center justify-content-center mx-auto m-3">
                                <div class="text-center">
                                    <button type="button" class="btn m-1 signature-btn shadow" onclick="window.location.href='module-video.php?module_id=<?php echo $moduleId; ?>';" style="text-decoration: none;">Start Module</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }
        } else {
            echo "<div class='container'>";
            echo "<div class='d-flex justify-content-center align-items-center text-white'>";
            echo "<h2> No modules found. </h2>";
            echo "</div>";
            echo "</div>";
        }

        // ====================================== ADMIN ====================================== 

    } elseif ($role === "admin" || "supervisor") {
        if (mysqli_num_rows($searchResult) > 0) {
            while ($row = mysqli_fetch_assoc($searchResult)) {
                $moduleId = $row['module_id'];
                $moduleName = $row['module_name'];
                $moduleDescription = $row['module_description'];
                $module_image = $row['module_image'];

                $maxCharacters = 150;
                $charactersArray = str_split($moduleDescription);
                $limitedDescription = implode('', array_slice($charactersArray, 0, $maxCharacters));
            ?>
                <div class="container mb-2 mt-2">
                    <div class="card shadow-lg">
                        <div class="row g-0 p-3 ">
                            <div class="col-12 col-md-2 align-self-center text-center">
                                <img src="<?php echo $module_image; ?>" alt="<?php echo $moduleName; ?>" class="img-fluid" style="max-height: 150px; max-width: 100%;">
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="card-body">
                                    <h3><?php echo $moduleName; ?></h3>
                                    <p style="text-align: justify;" class="text-wrap">
                                        <?php
                                        if (count($charactersArray) > $maxCharacters) {
                                            echo $limitedDescription . ' ...';
                                        } else {
                                            echo $limitedDescription;
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-center justify-content-center mx-auto m-3">
                                <button type="button" class="btn signature-btn m-1 shadow" onclick="window.location.href='module-video.php?module_id=<?php echo $moduleId; ?>';" style="text-decoration: none;">Start</button>
                                <div class="dropdown">
                                    <button class="btn btn-dark dropdown-toggle m-1 shadow" type="button" id="editDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Edit
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="editDropdown">
                                        <li><a class="dropdown-item" href="edit-module.php?module_id=<?php echo $moduleId; ?>">Edit Module</a></li>
                                        <li><a class="dropdown-item" href="edit-questions.php?module_id=<?php echo $moduleId; ?>">Edit Questions</a></li>
                                        <li><a class="dropdown-item" href="all-written-questions-list.php?module_id=<?php echo $moduleId; ?>">Edit Written Questions</a></li>
                                    </ul>
                                </div>

                                <?php if ($role === 'admin') { ?>
                                    <!-- Archive Module Button -->
                                    <button type="button" class="btn text-white btn-info m-1 shadow" onclick="archiveModule(<?php echo $row['module_id']; ?>);">Archive</button>
                                <?php } ?>

                            </div>
                        </div>
                    </div>
                </div>
    <?php
            }
        } else {
            echo "<div class='container'>";
            echo "<div class='d-flex justify-content-center align-items-center text-white'>";
            echo "<h2> No modules found. </h2>";
            echo "</div>";
            echo "</div>";
        }
    }

    // Close the database connection
    mysqli_close($conn);
    ?>

    <!-- Modal HTML -->
    <div id="archiveConfirmationModal" class="modal fade text-black" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Do you want to archive the module?</p>
                    <p class="text-secondary"><small>If you archive the module, it will be marked as archived and won't be visible to users.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn signature-btn" id="archiveButton">Archive</button>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5"></div>

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

    <script>
        // JavaScript function to handle the "Archive" button click from the modal
        function archiveModule(moduleId) {
            // Set the archive URL with the module_id parameter
            const archiveUrl = "modules.php?module_id=" + moduleId + "&archive=true";

            // Set the "Archive" button's click event to navigate to the archive URL
            document.getElementById("archiveButton").onclick = function() {
                window.location.href = archiveUrl;
            };

            // Show the confirmation modal
            const archiveConfirmationModal = new bootstrap.Modal(document.getElementById('archiveConfirmationModal'));
            archiveConfirmationModal.show();
        }
    </script>
</body>

</html>