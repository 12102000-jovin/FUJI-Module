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

if (isset($_GET['employee_id'])) {
    $employee_id = $_GET['employee_id'];

    // Create a prepared statement to prevent SQL injection
    $sql = "SELECT u.full_name, u.username, u.password, u.employee_id, u.role, d.department_name
                FROM users u
                JOIN department d ON u.department_id = d.department_id
                WHERE u.employee_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $full_name = $row['full_name'];
        $username = $row['username'];
        $password = $row['password'];
        $user_role = $row['role'];
        $department = $row['department_name'];
    } else {
        $full_name = "User Not Found";
        $username = "";
        $password = "";
        $user_role = "";
        $department = "";
    }
} else {
    echo "No employee_id";
}

// ==================================================================================

// Fetch department names from the database
$departmentQuery = "SELECT department_name FROM department";
$departmentResult = $conn->query($departmentQuery);

// Create an array to store department names
$departmentNames = array();
while ($deptRow = $departmentResult->fetch_assoc()) {
    $departmentNames[] = $deptRow['department_name'];
}

/* ================================================================================== */

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the updated values from the form 
    $updatedFullName = $_POST['full_name'];
    $updatedUsername = $_POST['username'];
    $updatedPassword = $_POST['password'];
    $updatedRole = $_POST['role'];
    $selectedDepartment = $_POST['department']; // This is the department name now
    $updatedEmployeeId = $_POST['employee_id']; // Capture the updated employee ID

    // Check if the updated employee_id already exists in the database
    $existingEmployeeQuery = "SELECT employee_id FROM users WHERE employee_id = ?";
    $existingStmt = $conn->prepare($existingEmployeeQuery);
    $existingStmt->bind_param("i", $updatedEmployeeId);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();

    // If the employee_id already exists, show the modal
    if ($existingResult->num_rows > 0) {
        echo '<script>
            window.addEventListener("load", function() {
                var myModal = new bootstrap.Modal(document.getElementById("employeeIdExistsModal"));
                myModal.show();
            });
        </script>';
    } else {
        // Get the department_id associated with the selected department_name
        $departmentIdQuery = "SELECT department_id FROM department WHERE department_name = ?";
        $deptStmt = $conn->prepare($departmentIdQuery);
        $deptStmt->bind_param("s", $selectedDepartment);
        $deptStmt->execute();
        $deptResult = $deptStmt->get_result();

        if ($deptResult->num_rows > 0) {
            $deptRow = $deptResult->fetch_assoc();
            $updatedDepartmentId = $deptRow['department_id'];

            // Create a prepared statement to prevent SQL injection
            $updateSql = "UPDATE users 
                            SET employee_id = ?, full_name = ?, username = ?, password = ?, role = ?, department_id = ? 
                            WHERE employee_id = ?";

            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("issssii", $updatedEmployeeId, $updatedFullName, $updatedUsername, $updatedPassword, $updatedRole, $updatedDepartmentId, $employee_id);

            if ($stmt->execute()) {
                $_SESSION["userUpdated"] = true;
            } else {
                echo "Error updating user: " . $stmt->error;
            }
        } else {
            echo "Selected department not found.";
        }
    }
}

/* ================================================================================== */
$conn->close();
?>

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

    <!-- Title of the page -->
    <title>Edit User</title>
</head>

<!-- ==================================================================================  -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <div class="wrapper d-flex flex-column justify-content-center align-items-center">
        <div class="container mb-5">
            <div class="d-flex justify-content-start mt-5">
                <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
            </div>
            <h1 class="text-center mb-3"><strong> Update User </strong></h1>

            <?php
            if (isset($_SESSION["userUpdated"]) && $_SESSION["userUpdated"]) {
                echo '<div class="container text-center">
                        <div class="d-flex justify-content-center mt-5 mb-5">
                            <div class="alert alert-success" role="alert" style="width: 75%">
                                User updated created successfully!
                            </div>
                        </div>
                        <a href="allocate.php"><button type="button" class="btn btn-secondary m-1">Back to Allocate</button></a>
                    </div>';
                // Unset the session variable to remove the success message on page refresh
                unset($_SESSION["userUpdated"]);
            } else {

            ?>
                <form method="post" id="updateUserForm" class="p-5 text-white rounded-3 shadow-lg bg-gradient signature-bg-color">
                    <div class="mb-3">
                        <label for="full_name" class="form-label" style="font-weight: bold;">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $full_name ?>" required>
                        <div class="invalid-feedback">
                            Please provide the full name.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label" style="font-weight: bold;">Username</label>
                        <input type="text" class="form-control" name="username" value="<?php echo $username ?>" required>
                        <div class="invalid-feedback">
                            Please provide a username.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label" style="font-weight: bold;">Password</label>
                        <input type="text" class="form-control" name="password" value="<?php echo $password ?>" required>
                        <div class="invalid-feedback">
                            Please provide a password.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="employee_id" class="form-label" style="font-weight: bold;">Employee ID</label>
                        <input type="text" class="form-control" name="employee_id" value="<?php echo $employee_id ?>" required>
                        <div class="invalid-feedback">
                            Please provide the Employee ID.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div class="col-md-5 mb-3">
                            <label for="role" class="form-label" style="font-weight: bold;">User Role</label>
                            <select class="form-select" name="role" required>
                                <option value="">Not selected</option>
                                <option value="user" <?php echo $user_role === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="supervisor" <?php echo $user_role === 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            </select>
                            <div class="invalid-feedback">
                                Please provide the user role.
                            </div>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label for="department" class="form-label" style="font-weight: bold;">Department</label>
                            <select class="form-select" name="department" required>
                                <option value="">Not selected</option>
                                <?php
                                foreach ($departmentNames as $dept) {
                                    $selected = ($department === $dept) ? 'selected' : '';
                                    echo "<option value='$dept' $selected>$dept</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Please provide the department.
                            </div>
                        </div>
                    </div>

                    <!-- Submit buttons -->
                    <div class="text-center mt-3">
                        <a href="#myModal" role="button" id="updateUserBtn" class="btn btn-dark">Update User</a>
                    </div>

                    <div id="myModal" class="modal fade text-black" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update User Confirmation</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Do you want to update the user?</p>
                                    <p class="text-secondary"><small>If you don't update, your changes will be lost.</small></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn signature-btn">Update User</button>
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
    </div>

    <div id="employeeIdExistsModal" class="modal fade text-black" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Error: Employee ID Already Exists</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    The Employee ID you entered already exists in the database.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn signature-btn" data-bs-dismiss="modal">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("footer_logout.php") ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById("updateUserBtn").addEventListener("click", function(event) {
            // Check if all required fields are filled and employee_id is numeric
            var form = document.getElementById("updateUserForm");
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add("was-validated");
                return;
            }
            $("#myModal").modal("show");
        });
    </script>

</body>

</html>