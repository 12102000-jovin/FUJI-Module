<?php
// This code manages user authentication, session handling, and restoration of archived modules and questions.

// Start a session to manage user login state
session_start();

// Include the database connection file
require_once('db_connect.php');

// Check for user inactivity 
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

// Handle user logout: destroy the session and redirect to the login page
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// ==================================================================================

// Restore archived module and associated questions
if (isset($_GET['module_id']) && isset($_GET['restore'])) {
    // Get the module ID from the request parameters
    $moduleId = $_GET['module_id'];

    // Update the 'is_archived' status and 'archived_date' of the module to restore it
    $restoreModulesSql = "UPDATE modules SET is_archived = false, archived_date = NULL WHERE module_id = $moduleId";
    $restoreModulesResult = mysqli_query($conn, $restoreModulesSql);

    // Update the 'is_archived' status of associated questions to restore them
    $restoreQuestionsSql = "UPDATE module_questions SET is_archived = false WHERE module_id = $moduleId";
    $restoreQuestionsResult = mysqli_query($conn, $restoreQuestionsSql);

    // Check if the restoration was successful and redirect to the archive page
    if ($restoreModulesResult && $restoreQuestionsResult) {
        header("Location: archive.php");
        exit();
    } else {
        // Display an error message if restoration failed
        echo "Error restoring the module.";
    }
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
    <title>Archive</title>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Your JavaScript code here
        $(document).ready(function() {
            const deleteButtons = document.querySelectorAll('[data-bs-target="#deleteConfirmationModal"]');
            const moduleIdInput = document.getElementById('moduleIdToDelete');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const moduleId = this.getAttribute('data-module-id');
                    moduleIdInput.value = moduleId;
                });
            });
        });

        $(document).ready(function() {
            <?php if (isset($showSuccessModal) && $showSuccessModal) : ?>
                // Show the success modal if the flag is set
                $('#deleteSuccessModal').modal('show');
            <?php endif; ?>
        });
    </script>
</head>

<!-- ================================================================================== -->

<body class="bg-gradient signature-bg-color d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container mb-3">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <!-- Search Form -->
                <form class="form-inline mt-4" method="GET" action="archive.php">
                    <div class="d-flex align-items-center shadow-lg">
                        <input class="form-control mr-sm-2" type="search" name="search" placeholder="Search" aria-label="Search" style="height: 38px;">
                        <button class="btn btn-outline-light mx-2 my-2 my-sm-0" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- ================================================================================== -->
    <?php
    // Custom exception class for FK errors
    class ForeignKeyException extends Exception
    {
        public function __construct($msg = "Foreign key constraint violation", $code = 0)
        {
            parent::__construct($msg, $code);
        }
    }

    $showModal = false; // Initialize the variable

    if (isset($_POST['delete_module_id'])) {
        $deleteModuleId = $_POST['delete_module_id'];

        // Delete module from the database
        $deleteSql = "DELETE FROM modules WHERE module_id = ?";
        $deleteResult = $conn->prepare($deleteSql);
        $deleteResult->bind_param("i", $deleteModuleId);

        try {
            if ($deleteResult->execute()) {
                // echo "Module deleted successfully!";

                $showSuccessModal = true;
            } else {
                echo "Module deletion failed!";
            }
        } catch (mysqli_sql_exception $e) {
            // Check if the error message contains specific keywords indicating a foreign key error
            if (strpos($e->getMessage(), "a foreign key constraint fails") !== false) {
                // Deletion failed due to dependencies, set the error message
                $errorMessage = "Error deleting module: This module has dependencies in other tables.";
                $showModal = true; // Set the flag to show the modal
            } else {
                // Handle other exceptions
                $errorMessage = "An error occurred: " . $e->getMessage();
            }
        }
        $deleteResult->close();
    }

    // Check if a search query is present
    if (isset($_GET['search'])) {
        $searchTerm = $_GET['search'];
        // Fetch data from the "modules" table based on the search term
        $sql = "SELECT module_id, module_name, module_description, module_image, archived_date FROM modules WHERE is_archived = true AND module_name LIKE '%$searchTerm%'";
    } else {
        // Fetch all data from the "modules" table where is_archived is true
        $sql = "SELECT module_id, module_name, module_description, module_image, archived_date FROM modules WHERE is_archived = true";
    }

    $result = mysqli_query($conn, $sql);

    // Check if any rows were returned
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $moduleId = $row['module_id'];
            $moduleName = $row['module_name'];
            $moduleDescription = $row['module_description'];
            $module_image = $row['module_image'];
            $archived_date = $row['archived_date'];

            // Limit the description to a maximum of 18 words
            $maxWords = 18;
            $wordsArray = explode(' ', $moduleDescription);
            $limitedDescription = implode(' ', array_slice($wordsArray, 0, $maxWords));
    ?>
            <div class="container mb-2 mt-2">
                <div class="card shadow-lg">
                    <div class="row g-0 p-3">
                        <div class="col-12 col-md-2 align-self-center text-center">
                            <!-- Module Image -->
                            <img src="<?php echo $module_image; ?>" alt="<?php echo $moduleName; ?>" class="img-fluid" style="max-height: 150px; max-width: 100%;">
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="card-body">
                                <!-- Module Name -->
                                <h3><?php echo $moduleName; ?></h3>
                                <p style="text-align: justify;">
                                    <?php
                                    // Check if the description is longer than the limited one
                                    if (count($wordsArray) > $maxWords) {
                                        echo $limitedDescription . ' ...';
                                    } else {
                                        echo $limitedDescription;
                                    }
                                    ?>
                                </p>
                                <!-- Archived Date -->
                                <p><strong>Archived Date: <?php echo $archived_date ?></strong></p>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-center justify-content-center mx-auto m-3">
                            <?php if ($role === "admin") : ?>
                                <!-- Admin Actions Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-dark dropdown-toggle m-1 shadow" type="button" id="editDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Edit
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="editDropdown">
                                        <li><a class="dropdown-item" href="edit-module.php?module_id=<?php echo $moduleId; ?>">Edit Module</a></li>
                                        <li><a class="dropdown-item" href="edit-questions.php?module_id=<?php echo $moduleId; ?>">Edit Questions</a></li>
                                    </ul>
                                </div>
                                <div class="dropdown">
                                    <button class="btn m-1 text-white dropdown-toggle trans-button signature-bg-color shadow" style="background-color:#043f9d" type="button" id="deleteDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-module-id="<?php echo $row['module_id']; ?>">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="deleteDropdown">
                                        <!-- Check Module Button -->
                                        <li><a class="dropdown-item" href="check-module.php?module_id=<?php echo $moduleId; ?>">Check Module</a></li>
                                        <!-- Archive Module Button -->
                                        <li><a type="button" class="dropdown-item" onclick="restoreModule(<?php echo $moduleId; ?>);">Restore Module</a></li>
                                        <!-- Delete Module Button -->
                                        <li><a class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#myModal-<?php echo $moduleId; ?>">Delete Module</a></li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================================================================== -->

            <!-- Delete Confirmation Modal -->
            <div id="myModal-<?php echo $moduleId; ?>" class="modal fade" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirmation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete the module?</p>
                            <p class="text-secondary"><small>If you delete it, the module will be permanently removed.</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="post" action="archive.php">
                                <input type="hidden" id="moduleIdToDelete" name="delete_module_id" value="<?php echo $moduleId; ?>">
                                <button type="submit" class="btn btn-danger" name="delete_module">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restore Confirmation Modal -->
            <div id="restoreModal" class="modal fade" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirmation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to restore the module?</p>
                            <p class="text-secondary"><small>The module will be restored to active status.</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="#" id="restoreModuleBtn" class="btn signature-btn">Restore Module</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Condition to trigger the error deletion modal -->
            <?php if ($showModal) : ?>
                <script>
                    $(document).ready(function() {
                        $('#deleteErrorModal').modal('show');
                    });
                </script>
            <?php endif; ?>

            <?php if (isset($showSuccessModal) && $showSuccessModal) : ?>
                <script>
                    $(document).ready(function() {
                        $('#deleteSuccessModal').modal('show');
                    });
                </script>
            <?php endif; ?>

            <!-- Bootstrap modal for displaying deletion error messages -->
            <div class="modal fade" id="deleteErrorModal" tabindex="-1" role="dialog" aria-labelledby="deleteErrorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-top">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteErrorModalLabel">Error</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <?php echo $errorMessage; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Success Modal -->
            <div id="deleteSuccessModal" class="modal fade" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Success</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            The module was deleted successfully!
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        }
    } else {
        ?>
        <!-- No Archive Modules Message -->
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <h3 class="text-white">No archive modules.</h3>
                </div>
            </div>
        </div>
    <?php
    }

    // Close the database connection
    mysqli_close($conn);
    ?>

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

    <script>
        // This function is called when the user wants to restore a module.
        function restoreModule(moduleId) {
            // Get a reference to the "Restore Module" button in the DOM.
            const restoreBtn = document.getElementById("restoreModuleBtn");

            // Set the 'href' attribute of the "Restore Module" button to point to the archive.php page
            // with the appropriate query parameters to specify the module ID and indicate that it should be restored.
            restoreBtn.href = `archive.php?module_id=${moduleId}&restore=true`;

            // Get a reference to the "Restore Modal" element in the DOM and create a new bootstrap modal object.
            const restoreModal = new bootstrap.Modal(document.getElementById("restoreModal"));

            // Show the "Restore Modal" to the user.
            restoreModal.show();
        }
    </script>
</body>

</html>