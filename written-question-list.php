<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("db_connect.php");
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

$select_query = "SELECT wa.*, wq.question, u.full_name 
                FROM written_answers wa 
                INNER JOIN written_questions wq ON wa.written_question_id = wq.written_question_id
                INNER JOIN users u ON wa.employee_id = u.employee_id 
                ORDER BY wa.written_answer_id ASC";
$select_result = $conn->query($select_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Written Quiz List</title>
</head>

<style>
    /* Add custom CSS styles here */
    @media (max-width: 576px) {

        /* Adjust table styles for small screens */
        table {
            font-size: 10px;
        }

        table h3 {
            font-size: 14px;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .badge {
            font-size: 10px !important;
        }
    }

    .table thead th {
        background-color: #043f9d;
        color: white;
        border: 1px solid #043f9d !important;
    }
</style>

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card mt-5 mb-5 p-3">
                    <div class="card-body">
                        <h1 class="text-center">Written Question List</h1>
                        <form method="POST">
                            <table class="table table-striped table-hover table-bordered mt-5">
                                <thead>
                                    <tr class="text-center">
                                        <th> Question </th>
                                        <th> Status </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($select_result->num_rows > 0) {
                                        while ($row = $select_result->fetch_assoc()) { ?>
                                            <tr class="align-middle" data-bs-toggle='modal' data-bs-target='#writtenAnswerModal'>
                                                <td class="pt-4 pb-4">
                                                    <h3><?php echo $row["full_name"] . " - " . $row["employee_id"] . "</h3>" . $row["question"] . "<br>" . $row["datetime"] ?>
                                                </td>
                                                <?php if ($row['is_marked'] == 0) {
                                                    echo "<td class='text-center'> <span class='badge rounded-pill text-bg-danger' style='font-size:16px'>Not Marked</span></td>";
                                                } else if ($row['is_marked'] == 1) {
                                                    echo "<td class='text-center'> <span class='badge rounded-pill text-bg-success' style='font-size:16px'>Marked</span> </td>";
                                                }
                                                ?>
                                            </tr>
                                    <?php }
                                    } ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
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

    <!-- Written Answer Modal -->
    <div class="modal fade" id="writtenAnswerModal" tabindex="-1" aria-labelledby="writtenAnswerModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="writtenAnswerModal">Result Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="writtenAnswerDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let writtenAnswerModal = document.getElementById('writtenAnswerModal');
            writtenAnswerModal.addEventListener('show.bs.modal', function(event) {
                let button = event.relatedTarget;
                let row = button.closest('tr');
                let question = row.cells[0].textContent;

                let writtenAnswerDetails = document.getElementById('writtenAnswerDetails');
                writtenAnswerDetails.innerHTML = `
                <p class='text-center '><strong>Question:</strong> ${question.match(/(.*?) -/)[1]}</p>
            `;
            });
        });
    </script>

</body>

</html>