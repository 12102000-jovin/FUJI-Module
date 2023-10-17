<?php
// Start the session, to load all the session variables
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

// ==================================================================================

// Retrieve the username from the session
$username = $_SESSION['username'] ?? '';

// (Retrieve the employee ID and role from the users table)
$emp_id_query = "SELECT u.*, d.department_name FROM users u
        JOIN department d ON u.department_id = d.department_id
        WHERE u.username= '$username'";
$result = $conn->query($emp_id_query);

// Check if the query result is not empty and contains one or more rows of data
if ($result && $result->num_rows > 0) {
    // Fetch the next row of data from the result set and store it in the $row variable.
    $row = $result->fetch_assoc();

    // Assigning employee ID and role from the row data
    $employee_id = $row['employee_id'];
    $role = $row['role'];
    $department = $row['department_name'];
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

// ==================================================================================

$departmentNamesSql = "SELECT DISTINCT department_name FROM department";
$departmentNamesResult = $conn->query($departmentNamesSql);
$departmentNames = array();

if ($departmentNamesResult->num_rows > 0) {
    while ($row = $departmentNamesResult->fetch_assoc()) {
        $departmentNames[] = $row["department_name"];
    }
}

// ==================================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedGroup = $_POST["selected_group"];
    $selectedModules = $_POST["selected_modules"];

    // Fetch department ID based on the selected group
    $departmentIdQuery = "SELECT department_id FROM department WHERE department_name = '$selectedGroup'";
    $departmentIdResult = $conn->query($departmentIdQuery);

    if ($departmentIdResult->num_rows > 0) {
        $departmentIdRow = $departmentIdResult->fetch_assoc();
        $departmentId = $departmentIdRow["department_id"];

        // Fetch all employee IDs in the selected department
        $employeeIdQuery = "SELECT employee_id FROM users WHERE department_id = '$departmentId'";
        $employeeIdResult = $conn->query($employeeIdQuery);
        $employeeIds = array();

        if ($employeeIdResult->num_rows > 0) {
            while ($row = $employeeIdResult->fetch_assoc()) {
                $employeeIds[] = $row["employee_id"];
                // var_dump($employeeIds);
            }
        }

        // Insert selected module IDs for all employees in the department
        foreach ($selectedModules as $moduleName) {
            // Retrieve the module_id from the modules table based on the module name
            $moduleIdQuery = "SELECT module_id FROM modules WHERE module_name = '$moduleName'";
            $moduleIdResult = $conn->query($moduleIdQuery);

            if ($moduleIdResult->num_rows > 0) {
                $moduleIdRow = $moduleIdResult->fetch_assoc();
                $moduleId = $moduleIdRow["module_id"];
                // var_dump($moduleId);

                // Insert the module_id for all employees in the department
                foreach ($employeeIds as $employeeId) {
                    $insertQuery = "INSERT IGNORE INTO module_allocation (module_id, employee_id)
                                            VALUES ('$moduleId', '$employeeId')";
                    if ($conn->query($insertQuery)) {
                        $_SESSION['allocationSuccess'] = true; // Set the success session variable
                    }
                }
            }
        }
    }
}

// Fetch department names from the database
$departmentNamesSql = "SELECT DISTINCT department_name FROM department";
$departmentNamesResult = $conn->query($departmentNamesSql);
$departmentNames = array();

if ($departmentNamesResult->num_rows > 0) {
    while ($row = $departmentNamesResult->fetch_assoc()) {
        $departmentNames[] = $row["department_name"];
    }
}

// Fetch module names from the database
$moduleSql = "SELECT module_id, module_name, is_archived FROM modules";
$moduleResult = $conn->query($moduleSql);
$modules = array();

if ($moduleResult->num_rows > 0) {
    while ($row = $moduleResult->fetch_assoc()) {
        $modules[] = $row;
    }
}

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
        .checkbox-lg {
            scale: 1.5;
        }
    </style>

    <!-- Title of the page -->
    <title>Allocate by Department</title>
</head>

<!-- ================================================================================== -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php") ?>

    <!-- ================================================================================== -->

    <div class="container mt-5">
        <h1 class="text-center"><strong> Module Allocation </strong></h1>
    </div>

    <div class="container">
        <div class="mb-5 mt-5">
            <div class="row justify-content-center">
                <form method="POST" class="col-md-5 col-sm-8 bg-light shadow p-4 rounded-3 mt-5 m-1">
                    <div class="form-group row">
                        <label for="groupSelection" class="col-sm-12 col-form-label mb-2"><strong>Department</strong></label>
                        <div class="col-sm-12">
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">
                                    Select a Department
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <h5 class="dropdown-header">Department</h5>
                                    </li>
                                    <?php foreach ($departmentNames as $departmentName) : ?>
                                        <li>
                                            <a class="dropdown-item dropdown-item-group" href="#" data-value="<?php echo $departmentName ?>"><?php echo $departmentName; ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <input type="hidden" id="selected_group" name="selected_group" value="">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row align-items-start">
                        <label for="groupSelection" class="col-sm-12 col-form-label mb-2"><strong>Module</strong></label>
                        <div class="col-sm-12">
                            <div class="dropdown">
                                <button class="btn btn-outline-dark dropdown-toggle mb-2" type="button" id="moduleDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Select Modules
                                </button>
                                <div class="dropdown-menu" aria-labelledby="moduleDropdown">
                                    <input type="search" class="form-control focus-ring focus-ring-light " id="moduleSearch" placeholder="Search modules" style="border: none">
                                    <div class="dropdown-divider"></div>
                                    <div class="module-checkboxes">
                                        <?php foreach ($modules as $module) : ?>
                                            <div class="form-check m-1">
                                                <input class="form-check-input module-checkbox" type="checkbox" name="selected_modules[]" value="<?php echo $module['module_name']; ?>" id="module_<?php echo $module['module_id']; ?>" data-module-name="<?php echo strtolower($module['module_name']); ?>">
                                                <label class="form-check-label" for="module_<?php echo $module['module_id']; ?>">
                                                    <?php echo $module['module_name']; ?>
                                                </label>
                                                <?php if ($module['is_archived'] == 1) : ?>
                                                    <i class="fa-solid fa-folder-minus text-secondary tooltips" data-toggle="tooltip" data-placement="top" title="Archived Module"></i>
                                                <?php elseif ($module['is_archived'] == 0) : ?>
                                                    <i class="fa-regular fa-folder-open signature-color tooltips" data-toggle="tooltip" data-placement="top" title="Active Module"></i>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="selected_department" value="" id="selected_department">
                </form>

                <div class="col-md-5 col-sm-8 text-white signature-bg-color bg-gradient shadow-lg p-4 rounded-3 mt-5 m-1">
                    <div id="selectedGroupContainer"><strong>No Department Selected</strong></div>
                    <div class="mt-3" id="selectedModulesContainer"><strong>No Module Selected</strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <div class="text-center mt-5 mb-5 col-sm-12">
        <a class="btn btn-secondary" href="allocate.php" role="button">Back</a>
        <input class="btn btn-dark" type="submit" id="allocateButton" value="Allocate">
    </div>

    <!-- ================================================================================== -->

    <!-- Modal for No Value Selected -->
    <div class="modal fade" id="noValueModal" tabindex="-1" role="dialog" aria-labelledby="noValueModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="noValueModalLabel">No Value Selected</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Please select both options from the dropdown menu.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Modal Confirmation for Allocation -->
    <div class="modal fade" id="allocateConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="noValueModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="allocateConfirmationLabel">Confirmation</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to allocate to this group?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn signature-btn" id="submitAllocate">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Modal for Allocation Success -->
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Allocation Successful</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Module allocation was successful.
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

    <!-- ================================================================================== -->

    <!-- Footer Section -->
    <footer class="bg-light text-center py-4 mt-auto shadow-lg">
        <div class="container">
            <p class="mb-0 font-weight-bold" style="font-size: 1.5vh"><strong>&copy; <?php echo date('Y'); ?> FUJI Training Module. All rights reserved.</strong></p>
        </div>
    </footer>

    <!-- ================================================================================== -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t)
        })
    </script>

    <!-- ================================================================================== -->

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dropdownItems = document.querySelectorAll(".dropdown-item-group");
            const selectedGroupInput = document.getElementById("selected_group");
            const selectedGroupContainer = document.getElementById("selectedGroupContainer");
            const selectedModulesContainer = document.getElementById("selectedModulesContainer");
            const moduleCheckboxes = document.querySelectorAll(".module-checkbox");
            const allocateButton = document.getElementById("allocateButton");

            dropdownItems.forEach(item => {
                item.addEventListener("click", function(event) {
                    event.preventDefault();
                    const selectedValue = event.target.getAttribute("data-value");
                    selectedGroupInput.value = selectedValue;
                    document.querySelector(".btn.dropdown-toggle").textContent = selectedValue;
                    selectedGroupContainer.innerHTML = "<strong> Selected Department: </strong><br>" + selectedValue;
                });
            });

            moduleCheckboxes.forEach(checkbox => {
                checkbox.addEventListener("change", function() {
                    const selectedModules = Array.from(document.querySelectorAll(".module-checkbox:checked"))
                        .map(checkbox => checkbox.value);

                    if (selectedModules.length === 0) {
                        selectedModulesContainer.innerHTML = "<strong> Selected Module:<br> </strong>None";
                    } else if (selectedModules.length === 1) {
                        selectedModulesContainer.innerHTML = "<strong> Selected Module:<br> </strong>" + selectedModules[0];
                    } else {
                        selectedModulesContainer.innerHTML = "<strong> Selected Modules:<br> </strong>" + selectedModules.join("<br><hr>");
                    }
                });
            });

            allocateButton.addEventListener("click", function(event) {
                const selectedGroupValue = selectedGroupInput.value;

                if (!selectedGroupValue) {
                    event.preventDefault();
                    $('#noValueModal').modal('show');
                } else if (selectedGroupValue) {
                    $('#allocateConfirmationModal').modal('show');
                }
            });
        });
    </script>

    <!-- ================================================================================== -->

    <script>
        document.getElementById("submitAllocate").addEventListener("click", function() {
            document.querySelector("form").submit();
        });
    </script>

    <!-- ================================================================================== -->

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if (isset($_SESSION['allocationSuccess']) && $_SESSION['allocationSuccess']) : ?>
                // Show the success modal
                $('#successModal').modal('show');

                // Clear the success session variable
                <?php unset($_SESSION['allocationSuccess']); ?>
            <?php endif; ?>
        });
    </script>

    <!-- ================================================================================== -->

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const moduleCheckboxes = document.querySelectorAll(".module-checkbox");
            const moduleSearchInput = document.getElementById("moduleSearch");

            moduleSearchInput.addEventListener("input", function() {
                const searchText = moduleSearchInput.value.toLowerCase();
                moduleCheckboxes.forEach(checkbox => {
                    const moduleName = checkbox.getAttribute("data-module-name").toLowerCase();
                    if (moduleName.includes(searchText)) {
                        checkbox.parentElement.style.display = "block";
                    } else {
                        checkbox.parentElement.style.display = "none";
                    }
                });
            });
        });
    </script>

</body>

</html>