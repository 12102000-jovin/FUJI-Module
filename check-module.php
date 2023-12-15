<?php
session_start();
// Assuming you have a database connection established
require_once('db_connect.php');

// Checking the inactivity 
require_once("inactivity_check.php");

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

// Logout script
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Check if the module_id is set
if (isset($_GET['module_id'])) {
    $module_id = $_GET['module_id'];

    // Retrieve module information from the 'modules' table
    $module_query = "SELECT * FROM modules WHERE module_id = '$module_id'";
    $module_result = mysqli_query($conn, $module_query);
    $module_row = mysqli_fetch_assoc($module_result);

    // Retrieve questions from the 'module_questions' table based on module_id
    $questions_query = "SELECT * FROM module_questions WHERE module_id = '$module_id'";
    $questions_result = mysqli_query($conn, $questions_query);
} else {
    // Redirect to the modules page if module_id is not set
    header("Location: modules.php");
    exit();
}

// Export data to CSV file
if (isset($_GET['export']) && $_GET['export'] === 'true') {
    $filename = 'module_questions.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');

    // Write module details to CSV
    fputcsv($output, ['Module Name', 'Module Description', 'Module Image', 'Module Video']);
    fputcsv($output, [
        $module_row['module_name'],
        $module_row['module_description'],
        $module_row['module_image'],
        $module_row['module_video']
    ]);

    // Write question details to CSV
    fputcsv($output, []); // Empty row for spacing
    fputcsv($output, ['Question', 'Option 1', 'Option 2', 'Option 3', 'Option 4', 'Correct Answer']);

    while ($question_row = mysqli_fetch_assoc($questions_result)) {
        fputcsv($output, [
            $question_row['question'],
            $question_row['option1'],
            $question_row['option2'],
            $question_row['option3'],
            $question_row['option4'],
            $question_row['correct_answer']
        ]);
    }

    fclose($output);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Modules</title>
    <style>
        @media (max-width: 576px) {
            p {
                font-size: 12px;
            }
        }
    </style>
</head>

<!-- ================================================================================== -->

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>
    <!-- ================================================================================== -->

    <!-- Module Details Section -->
    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
        <div class="text-white p-5 mt-5 rounded-3 shadow bg-gradient signature-bg-color">
            <div class="text-center">
                <h1>Module Details</h1>
            </div>

            <div class="mt-3">
                <!-- Display module details using PHP variables -->
                <p><strong>Module Name: </strong><?php echo $module_row['module_name']; ?></p>
                <p><strong>Module Description: </strong><?php echo $module_row['module_description']; ?></p>
                <p><strong>Module Image: </strong><?php echo $module_row['module_image']; ?></p>
                <p><strong>Module Video: </strong><?php echo $module_row['module_video']; ?></p>
            </div>
        </div>

        <div class="rounded-3 p-5 mt-5 mb-5 bg-light shadow-lg">
            <!-- Module Questions Section -->
            <div class="text-center mb-3">
                <h1>Module Questions</h1>
            </div>

            <div class="table-responsive">
                <?php
                // Check if there are any questions in the module
                if (mysqli_num_rows($questions_result) > 0) {
                ?>
                    <!-- Display module questions in a table -->
                    <table class="table table-hover table-striped">
                        <thead class="align-middle text-center">
                            <tr>
                                <th>Question</th>
                                <th>Option 1</th>
                                <th>Option 2</th>
                                <th>Option 3</th>
                                <th>Option 4</th>
                                <th>Correct Answer</th>
                            </tr>
                        </thead>
                        <tbody class="align-middle">
                            <?php while ($question_row = mysqli_fetch_assoc($questions_result)) { ?>
                                <tr class="question-row">
                                    <!-- Display individual question and options using PHP variables -->
                                    <td><?php echo $question_row['question']; ?></td>
                                    <td class="text-center"><?php echo $question_row['option1']; ?></td>
                                    <td class="text-center"><?php echo $question_row['option2']; ?></td>
                                    <td class="text-center"><?php echo $question_row['option3']; ?></td>
                                    <td class="text-center"><?php echo $question_row['option4']; ?></td>
                                    <td class="text-center"><?php echo $question_row['correct_answer']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php
                } else {
                    // If there are no questions, display the message
                    echo "<div class='text-center mb-5 mt-5'>";
                    echo "<h5 class='alert alert-danger'> There is no question in this module <h5>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal for Question Details -->
    <div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <!-- Modal title for question details -->
                    <h5 class="modal-title" id="questionModalLabel">Question Details</h5>
                    <!-- Button to close the modal -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Placeholder paragraphs for question details in the modal -->
                    <p id="question"></p>
                    <p id="option1"></p>
                    <p id="option2"></p>
                    <p id="option3"></p>
                    <p id="option4"></p>
                    <p id="correctAnswer"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="questionModalLabel">Question Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="question"></p>
                    <p id="option1"></p>
                    <p id="option2"></p>
                    <p id="option3"></p>
                    <p id="option4"></p>
                    <p id="correctAnswer"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Action Buttons Section -->
    <div class="text-center mb-5">
        <!-- Button to print the page -->
        <button class="btn btn-dark shadow" onclick="window.print()">Print</button>
        <!-- Link to export data to CSV file with specific module ID -->
        <a href="?module_id=<?php echo $module_id; ?>&export=true" class="btn signature-btn shadow">Export to CSV</a>
    </div>

    <!-- ================================================================================== -->

    <?php require_once("footer_logout.php"); ?>

    <!-- ================================================================================== -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Select all elements with the class "question-row" and store them in the variable 'questionRows'
        const questionRows = document.querySelectorAll('.question-row');

        // Create a new Bootstrap modal using the element with the ID 'questionModal'
        const questionModal = new bootstrap.Modal(document.getElementById('questionModal'));

        // Loop through each 'question-row' element and add a click event listener to each one
        questionRows.forEach(row => {
            row.addEventListener('click', () => {
                // Extract the text content from the cells of the clicked row and store them in variables
                const question = row.cells[0].textContent;
                const option1 = row.cells[1].textContent;
                const option2 = row.cells[2].textContent;
                const option3 = row.cells[3].textContent;
                const option4 = row.cells[4].textContent;
                const correctAnswer = row.cells[5].textContent;

                // Set the innerHTML of specific elements inside the modal to display the extracted data
                document.getElementById('question').innerHTML = `<strong>Question:</strong> ${question}`;
                document.getElementById('option1').innerHTML = `<strong>Option 1:</strong> ${option1}`;
                document.getElementById('option2').innerHTML = `<strong>Option 2:</strong> ${option2}`;
                document.getElementById('option3').innerHTML = `<strong>Option 3:</strong> ${option3}`;
                document.getElementById('option4').innerHTML = `<strong>Option 4:</strong> ${option4}`;
                document.getElementById('correctAnswer').innerHTML = `<strong>Correct Answer:</strong> ${correctAnswer}`;

                // Show the modal after updating its content
                questionModal.show();
            });
        });
    </script>

</body>

</html>