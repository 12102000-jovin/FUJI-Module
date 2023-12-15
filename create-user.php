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
if ($role !== 'admin') {
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

$departmentNamesSql = "SELECT DISTINCT department_name, department_id FROM department";  // Query to get department names and IDs
$departmentNamesResult = $conn->query($departmentNamesSql);  // Execute the query
$departmentNames = array();  // Initialize an array for department names
$departmentId = array();  // Initialize an array for department IDs

if ($departmentNamesResult->num_rows > 0) {
    while ($row = $departmentNamesResult->fetch_assoc()) {
        $departmentNames[] = $row["department_name"];  // Store department names in the array
        $departmentId[] = $row["department_id"];  // Store department IDs in the array
    }
}

// ================================================================================== 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve data from the submitted form
    $username = $_POST['username'];
    $role = $_POST['role'];
    $employee_id = $_POST['employee_id'];
    $password = $_POST['password'];
    $full_name = $_POST['full_name'];
    $selectedDepartmentId = $_POST['department'];

    // Check if the employee_id already exists in the database
    $checkExistingSql = "SELECT COUNT(*) AS count FROM users WHERE employee_id = ?";
    $checkStmt = $conn->prepare($checkExistingSql);
    $checkStmt->bind_param("i", $employee_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $existingCount = $row['count'];

    // Check if the username already exists in the database
    $checkExistingUsernameSql = "SELECT COUNT(*) AS count FROM users WHERE username = ?";
    $checkStmt = $conn->prepare($checkExistingUsernameSql);
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $existingUsernameCount = $row['count'];

    if ($existingCount > 0 || $existingUsernameCount > 0) {
        echo '<script>
            window.addEventListener("load", function() {
                var myModal = new bootstrap.Modal(document.getElementById("employeeIdExistsModal"));
                myModal.show();
            });
        </script>';
    } else {
        // Insert the new user into the database
        $createUserSql = "INSERT INTO users (username, role, employee_id, password, full_name, department_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($createUserSql);
        $stmt->bind_param("ssissi", $username, $role, $employee_id, $password, $full_name, $selectedDepartmentId);

        if ($stmt->execute()) {
            $_SESSION["userRegistered"] = true;
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
    $conn->close();
}
?>

<!-- ==================================================================================  -->

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

    <!-- Title of the page -->
    <title>Create User</title>
</head>

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="wrapper d-flex flex-column justify-content-center align-items-center">
        <div class=" container">
            <div class="d-flex justify-content-start mt-5">
                <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
            </div>
            <h1 class="text-center mb-3"><strong>User Registration</strong></h1>

            <?php
            if (isset($_SESSION["userRegistered"]) && $_SESSION["userRegistered"]) {
                echo '<div class="container text-center">
                        <div class="d-flex justify-content-center mt-5 mb-5">
                            <div class="alert alert-success" role="alert" style="width: 75%">
                                User registered created successfully!
                            </div>
                        </div>
                        <a href="allocate.php"><button type="button" class="btn btn-secondary m-1">Back to Allocate</button></a>
                    </div>
                    </div>';
                // Unset the session variable to remove the success message on page refresh
                unset($_SESSION["userRegistered"]);
            } else {

            ?>
                <form method="post" id="createUserForm" class="p-5 text-white rounded-3 shadow-lg bg-gradient signature-bg-color">
                    <div class="mb-3">
                        <label for="full_name" class="form-label" style="font-weight: bold;">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? $_POST['full_name'] : ''; ?>" required>
                        <div class="invalid-feedback">
                            Please provide the full name.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label" style="font-weight: bold;">Username</label>
                        <input type="text" class="form-control" name="username" value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>" required>
                        <div class="invalid-feedback">
                            Please provide a username.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label" style="font-weight: bold;">Password</label>
                        <input type="text" class="form-control" name="password" value="<?php echo isset($_POST['password']) ? $_POST['password'] : '' ?>" required>
                        <div class="invalid-feedback">
                            Please provide a password.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="employee_id" class="form-label" style="font-weight: bold;">Employee Id</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?php echo isset($_POST['employee_id']) ? $_POST['employee_id'] : '' ?>" required>
                        <div class="invalid-feedback" id="employeeIdEmpty">
                            Please provide an employee id.
                        </div>
                        <div id="employeeIdError" class="invalid-feedback" style="display: none;">
                            Please input numbers only.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div class="col-md-5 mb-3">
                            <label for="role" class="form-label" style="font-weight: bold;">User Role</label>
                            <select class="form-select" name="role" required>
                                <option value="" <?php echo isset($_POST['role']) && empty($_POST['role']) ? 'selected' : ''; ?>>Not selected</option>
                                <option value="user" <?php echo isset($_POST['role']) && $_POST['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo isset($_POST['role']) && $_POST['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="supervisor" <?php echo isset($_POST['role']) && $_POST['role'] === 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            </select>
                            <div class="invalid-feedback">
                                Please provide the user role.
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="department" class="form-label" style="font-weight: bold;">Department</label>
                            <select name="department" class="form-select" required>
                                <option value="">Not selected</option>
                                <?php foreach ($departmentNames as $index => $departmentName) : ?>
                                    <option value="<?php echo $departmentId[$index]; ?>"> <?php echo $departmentName; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please provide the department.
                            </div>
                        </div>
                    </div>

                    <!-- Submit buttons -->
                    <div class="text-center mt-3">
                        <a href="#myModal" role="button" id="createUserBtn" class="btn btn-dark">Create New User</a>
                    </div>

                    <div id="myModal" class="modal fade text-black" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">User Registration Confirmation</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Do you want to register the user?</p>
                                    <p class="text-secondary"><small>If you don't register, your changes will be lost.</small></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn signature-btn">Register User</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
        </div>
    <?php
            }
    ?>
    </div>

    <div class="mb-5"></div>

    <!-- Employee Exist Modal -->
    <div id="employeeIdExistsModal" class="modal fade text-black" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Error: Employee ID or Username Exists</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Record with the provided Employee ID or Username already exists in the database!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn signature-btn" data-bs-dismiss="modal"> Okay </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================================================================================  -->

    <?php require_once("footer_logout.php"); ?>

    <!-- ==================================================================================  -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function validateEmployeeId() {
            const employeeIdInput = document.getElementById("employee_id");
            const employeeIdError = document.getElementById("employeeIdError");

            const isNumeric = /^\d+$/.test(employeeIdInput.value);
            if (!isNumeric) {
                employeeIdInput.classList.add("is-invalid");
                employeeIdError.style.display = "block";
            } else {
                employeeIdInput.classList.remove("is-invalid");
                employeeIdError.style.display = "none";
            }
        }

        document.getElementById("createUserBtn").addEventListener("click", function(event) {
            // Check if all required fields are filled and employee_id is numeric
            var form = document.getElementById("createUserForm");
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add("was-validated");
                return;
            }

            // Additional check for employee_id
            if (!(/^\d+$/.test(document.getElementById("employee_id").value))) {
                event.preventDefault();
                event.stopPropagation();
                document.getElementById("employee_id").classList.add("is-invalid");
                document.getElementById("employeeIdEmpty").style.display = "none";
                document.getElementById("employeeIdError").style.display = "block";
                return;
            }

            $("#myModal").modal("show");
        });
    </script>
</body>

</html>