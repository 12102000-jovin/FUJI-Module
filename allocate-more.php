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

/* ================================================================================== */

$employee_id = $_GET['employee_id'];

// (Retrieve the employee ID and role from the users table)
$emp_id_query = "SELECT full_name FROM users WHERE employee_id = $employee_id";
// Execute the SQL query and store the result in the $result variable
$result = $conn->query($emp_id_query);

// Check if the query result is not empty and contains one or more rows of data
if ($result && $result->num_rows > 0) {
    // Fetch the next row of data from the result set and store it in the $row variable.
    $row = $result->fetch_assoc();

    $full_name = $row['full_name'];

    // Set the full name value in session
    $_SESSION['full_name'] = $full_name;
} else {
    // Set a default value if the employee_id is not found
    $employee_id = 'N/A';
}

// Free up the memory used by the database query result
$result->free();

// ==================================================================================

$allocatedSql = "SELECT m.* FROM modules m
                    INNER JOIN module_allocation ma ON m.module_id = ma.module_id
                    WHERE ma.employee_id = '$employee_id'";

$allocatedResult = $conn->query($allocatedSql);

// ==================================================================================
// Fetch data from the "modules" table that are not allocated to the current employee
$unAllocatedSql = "SELECT * FROM modules
        WHERE module_id NOT IN (
            SELECT module_id FROM module_allocation WHERE employee_id = '$employee_id' 
        )";

$unAllocatedResult = $conn->query($unAllocatedSql);

// ==================================================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['module_id']) && isset($_POST['is_allocated'])) {
    $module_id = $_POST['module_id'];
    $is_allocated = $_POST['is_allocated'];

    if ($is_allocated == 1) {
        $insert_query = "INSERT INTO module_allocation (module_id, employee_id) VALUES ('$module_id', '$employee_id')";
        $conn->query($insert_query);
    } else {
        $delete_query = "DELETE FROM module_allocation WHERE module_id = '$module_id' AND employee_id = '$employee_id'";
        $conn->query($delete_query);
    }


    echo "Module allocation updated successfully.";
    exit; // Stop further processing of the page
}

// ==================================================================================

// Pagination settings
$availableRecordsPerPage = array(5, 10, 15);
$recordsPerPage = isset($_GET['recordsPerPage']) && in_array($_GET['recordsPerPage'], $availableRecordsPerPage) ? intval($_GET['recordsPerPage']) : 5;
$pageNumber = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($pageNumber - 1) * $recordsPerPage;

// Retrieve all modules from the 'modules' table with pagination
// Fetch data from the "modules" table  
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$moduleQuery = "
            SELECT * FROM modules 
            WHERE module_name LIKE '%$search_query%'
            LIMIT $offset, $recordsPerPage
        ";
$moduleResult = $conn->query($moduleQuery);

// Count total number of records for pagination
$countQuery = "
            SELECT COUNT(*) AS total FROM modules
            WHERE module_name LIKE '%$search_query%'
        ";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

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

            .module-image {
                width: 4rem;
            }
        }

        .custom-disabled {
            pointer-events: none !important;
            opacity: 1 !important;
            background-color: #043f9d !important;
            color: white !important;
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

    <!-- Title of the page -->
    <title>Allocate</title>
</head>

<!-- ================================================================================== -->

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <!-- Title -->
    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
        <h1 class="text-center"><strong> Module Allocation </strong></h1>
        <?php
        // Count the number of allocated modules
        $allocated_module_count = $allocatedResult->num_rows;
        ?>
        <h6 class="text-center">Employee: <u><strong><?php echo $full_name ?></strong></u></h6>
        <h6 class="text-center">Module Allocated to this user: <span id="allocatedModuleCount"><strong><?php echo $allocated_module_count; ?></strong></span></h6>
    </div>

    <!-- ================================================================================== -->

    <!-- Table  -->
    <div class="container mt-4 mb-5">
        <div class="p-4 bg-light rounded-3 shadow-lg">
            <div class="d-flex justify-content-center">
                <div class="col-12">
                    <form method="GET" class="d-flex align-items-center justify-content-end search-form">
                        <input type="search" id="search_query" class="form-control me-2" placeholder="Search modules" value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="btn signature-btn search">Search</button>
                    </form>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-hover border table-striped" id="moduleTable">
                    <thead>
                        <tr class="text-center">
                            <th colspan="2">Module Name</th>
                            <th>Allocation</th>
                            <th>Status</th>
                            <th>Archive</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $allocated_module_ids = [];
                        while ($allocatedRow = $allocatedResult->fetch_assoc()) {
                            $allocated_module_ids[] = $allocatedRow['module_id'];
                        }

                        while ($moduleRow = $moduleResult->fetch_assoc()) {
                            $module_image = $moduleRow['module_image'];
                            $module_name = $moduleRow['module_name'];
                            $module_id = $moduleRow['module_id'];
                            $is_archived = $moduleRow['is_archived'];
                            $is_allocated = in_array($module_id, $allocated_module_ids) ? 'checked' : '';

                        ?>
                            <tr class="align-middle">
                                <td>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <img src="<?php echo $module_image; ?>" alt="<?php echo $module_name; ?>" class="img-fluid module-image" style="max-width: 10vh; max-height: 8vh; object-fit: contain;">
                                    </div>
                                </td>
                                <td><?php echo $module_name; ?></td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input module-toggle" type="checkbox" data-module-id="<?php echo $module_id; ?>" id="module<?php echo $module_id; ?>" <?php echo $is_allocated; ?>>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <span class="badge badge-pill bg-<?php echo $is_allocated ? 'success' : 'danger'; ?> rounded-pill" id="badge<?php echo $module_id; ?>"><?php echo $is_allocated ? 'Allocated' : 'Not Allocated'; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <?php
                                        if ($is_archived == 0) {
                                            echo '<i class="fa-regular fa-folder-open signature-color tooltips" data-toggle="tooltip" data-placement="top" title="Active Module" ></i>';
                                        } else if ($is_archived == 1) {
                                            echo '<i class="fa-solid fa-folder-minus text-secondary tooltips" data-toggle="tooltip" data-placement="top" title="Archived Module" ></i>';
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>

                        <?php
                        }
                        ?>
                        <?php if ($moduleResult->num_rows === 0) : ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <h3><strong>No modules found.</strong></h3>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center pagepagination">
                    <div class="d-flex">
                        <label class="my-auto me-2"> Show </label>
                        <select id="recordsPerPage" name="recordsPerPage" class="form-select me-2 " style="width: 70px">
                            <option value="5" <?php echo $recordsPerPage == 5 ? 'selected' : ''; ?>>5</option>
                            <option value="10" <?php echo $recordsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="15" <?php echo $recordsPerPage == 15 ? 'selected' : ''; ?>>15</option>
                        </select>
                        <div class="d-flex align-items-center">
                            <label>entries</label>
                        </div>
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination mt-3">
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
                                    echo ' active" style="background-color: #f0f0f0 !important;';
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
    </div>

    <!-- ================================================================================== -->

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Operation completed successfully.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    An error occurred.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <?php require_once("footer_logout.php") ?>

    <!-- ================================================================================== -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ================================================================================== -->

    <script>
        // Tooltips Initialization
        function initializeTooltips() {
            const tooltips = document.querySelectorAll('.tooltips');
            tooltips.forEach(t => {
                new bootstrap.Tooltip(t);
            });
        }
    </script>

    <!-- ================================================================================== -->

    <script>
        // Records Per Page Change
        function onRecordsPerPageChange() {
            var selectedValue = document.getElementById("recordsPerPage").value;
            var currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set("page", 1);
            currentUrl.searchParams.set("recordsPerPage", selectedValue);
            window.location.href = currentUrl.toString();
        }

        document.getElementById("recordsPerPage").addEventListener("change", onRecordsPerPageChange);
    </script>

    <!-- ================================================================================== -->

    <script>
        $(document).ready(function() {
            // Allocation Toggle
            $(document).on('change', '.module-toggle', function() {
                var moduleId = $(this).data('module-id');
                var badge = $('#badge' + moduleId);
                var isAllocated = $(this).prop('checked');

                if (isAllocated) {
                    badge.removeClass('bg-danger').addClass('bg-success').text('Allocated');
                } else {
                    badge.removeClass('bg-success').addClass('bg-danger').text('Not Allocated');
                }

                var actionText = isAllocated ? 'Allocation' : 'Deallocation';

                $.ajax({
                    url: window.location.href,
                    method: "POST",
                    data: {
                        module_id: moduleId,
                        is_allocated: isAllocated ? 1 : 0
                    },
                    success: function(response) {
                        var newAllocatedModuleCount = parseInt($('#allocatedModuleCount').text()) + (isAllocated ? 1 : -1);
                        $('#allocatedModuleCount').text(newAllocatedModuleCount);

                        $('#successModal .modal-body').text(actionText + ' Successful.');
                        $("#successModal").modal("show");
                    },
                    error: function(xhr, status, error) {
                        $('#errorModal .modal-body').text(actionText + ' Not Successful.');
                        $("#errorModal").modal("show");
                    }
                });
            });

            initializeTooltips();


            // AJAX Search Submission
            $('.search-form').on('submit', function(e) {
                e.preventDefault();
                var searchQuery = $('#search_query').val();
                var recordsPerPage = $('#recordsPerPage').val();
                var url = 'allocate-more.php?employee_id=<?php echo $employee_id; ?>&recordsPerPage=' + recordsPerPage;

                if (searchQuery.trim() !== '') {
                    url += '&search_query=' + encodeURIComponent(searchQuery);
                }

                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(response) {
                        $('#moduleTable').html($(response).find('#moduleTable').html());
                        initializeTooltips(); // Reinitialize tooltips for the new elements

                        // Update pagination links to reflect the correct active page
                        var newPagination = $(response).find('.pagination').html();
                        $('.pagination').html(newPagination);
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            });
        });
    </script>

    <!-- ==================================================================================   -->

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