<?php
include 'connection.php';
session_start();

// Simple authentication
$admin_username = "momin";
$admin_password = "mominkhan@123";

// Check if user is logging in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
    } else {
        $error = "Invalid credentials!";
    }
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="card-title mb-0 text-center"><i class="fas fa-lock"></i> Admin Login</h4>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle actions
$action_message = '';
$action_type = '';

// Download Sample Excel File
if (isset($_GET['download_sample'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="sample_students_template.xls"');
    header('Cache-Control: max-age=0');
    
    // Get months dynamically
    $months_sql = "SELECT * FROM months ORDER BY id";
    $months_result = $conn->query($months_sql);
    $months = [];
    while ($month = $months_result->fetch_assoc()) {
        $months[] = $month;
    }
    
    // Create sample data
    echo "Sno\tName\tUniversity ID\tSemester\tCategory\t";
    foreach ($months as $month) {
        echo $month['month_name'] . "\t";
    }
    echo "\n";
    
    // Sample student data
    $sample_students = [
        [1, "John Doe", "UNI001", "5th", "Student"],
        [2, "Jane Smith", "UNI002", "4th", "Student"],
        [3, "Dr. Robert Brown", "UNI003", "N/A", "Faculty"]
    ];
    
    foreach ($sample_students as $student) {
        echo $student[0] . "\t"; // Sno
        echo $student[1] . "\t"; // Name
        echo $student[2] . "\t"; // University ID
        echo $student[3] . "\t"; // Semester
        echo $student[4] . "\t"; // Category
        
        // Add sample fee status (mix of Submitted and Pending)
        foreach ($months as $index => $month) {
            $status = ($index % 2 == 0) ? "Submitted" : "Pending";
            echo $status . "\t";
        }
        echo "\n";
    }
    
    // Add instructions row
    echo "\n\nINSTRUCTIONS:\t\t\t\t\t";
    foreach ($months as $month) {
        echo "\t";
    }
    echo "\n";
    echo "1. Do not change the column headers\t\t\t\t\t";
    foreach ($months as $month) {
        echo "\t";
    }
    echo "\n";
    echo "2. Status can be 'Submitted' or 'Pending'\t\t\t\t\t";
    foreach ($months as $month) {
        echo "\t";
    }
    echo "\n";
    echo "3. Keep the same format for data\t\t\t\t\t";
    foreach ($months as $month) {
        echo "\t";
    }
    echo "\n";
    echo "4. Save as .xls or .xlsx format\t\t\t\t\t";
    foreach ($months as $month) {
        echo "\t";
    }
    
    exit;
}

// Export to Excel
if (isset($_GET['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="students_data_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Get months dynamically
    $months_sql = "SELECT * FROM months ORDER BY id";
    $months_result = $conn->query($months_sql);
    $months = [];
    while ($month = $months_result->fetch_assoc()) {
        $months[] = $month;
    }
    
    // Get students data
    $students_sql = "SELECT s.* FROM students s ORDER BY s.sno";
    $students_result = $conn->query($students_sql);
    
    echo "Sno\tName\tUniversity ID\tSemester\tCategory\t";
    foreach ($months as $month) {
        echo $month['month_name'] . "\t";
    }
    echo "\n";
    
    while ($student = $students_result->fetch_assoc()) {
        // Get fee status for each month
        $fee_sql = "SELECT m.month_name, fp.status 
                   FROM fee_payments fp 
                   JOIN months m ON fp.month_id = m.id 
                   WHERE fp.student_id = ? 
                   ORDER BY m.id";
        $fee_stmt = $conn->prepare($fee_sql);
        $fee_stmt->bind_param("i", $student['id']);
        $fee_stmt->execute();
        $fee_result = $fee_stmt->get_result();
        
        $fee_status = [];
        while ($fee = $fee_result->fetch_assoc()) {
            $fee_status[$fee['month_name']] = $fee['status'];
        }
        $fee_stmt->close();
        
        echo $student['sno'] . "\t";
        echo $student['name'] . "\t";
        echo $student['university_id'] . "\t";
        echo $student['semester'] . "\t";
        echo $student['category'] . "\t";
        
        foreach ($months as $month) {
            echo isset($fee_status[$month['month_name']]) ? $fee_status[$month['month_name']] : 'Pending';
            echo "\t";
        }
        echo "\n";
    }
    exit;
}

// Import from Excel
// Import from Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_excel'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['excel_file']['tmp_name'];
        $file_name = $_FILES['excel_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Accept multiple file types
        if (in_array($file_ext, ['xls', 'xlsx', 'csv', 'txt'])) {
            
            try {
                // Read file based on type
                if ($file_ext === 'csv') {
                    $data = readCSVFile($file_tmp_path);
                } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
                    // Use PhpSpreadsheet for Excel files
                    $data = readExcelFile($file_tmp_path, $file_ext);
                } else {
                    // For text files
                    $data = readTextFile($file_tmp_path);
                }
                
                if (empty($data) || count($data) < 2) {
                    $action_message = "The file appears to be empty or could not be read. Please check the file format.";
                    $action_type = "danger";
                } else {
                    // Process the data
                    $result = processImportData($data, $conn);
                    $action_message = $result['message'];
                    $action_type = $result['type'];
                }
            } catch (Exception $e) {
                $action_message = "Error reading file: " . $e->getMessage();
                $action_type = "danger";
            }
            
        } else {
            $action_message = "Please upload a valid file (.xls, .xlsx, .csv, .txt)";
            $action_type = "danger";
        }
    } else {
        // ... (keep your existing error handling code)
    }
}

// Updated helper functions
function readCSVFile($file_path) {
    $data = [];
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Clean the row data
            $clean_row = array_map('trim', $row);
            $clean_row = array_map(function($value) {
                return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }, $clean_row);
            if (!empty(implode('', $clean_row))) {
                $data[] = $clean_row;
            }
        }
        fclose($handle);
    }
    return $data;
}

function readExcelFile($file_path, $file_ext) {
    // Load PhpSpreadsheet
    require_once 'vendor/autoload.php';
    
    $reader = null;
    
    if ($file_ext === 'xlsx') {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    } elseif ($file_ext === 'xls') {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
    }
    
    if (!$reader) {
        throw new Exception("Could not create reader for file type: $file_ext");
    }
    
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($file_path);
    $worksheet = $spreadsheet->getActiveSheet();
    
    $data = [];
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        
        $rowData = [];
        foreach ($cellIterator as $cell) {
            $rowData[] = $cell->getCalculatedValue();
        }
        
        // Skip completely empty rows
        if (!empty(implode('', $rowData))) {
            $data[] = $rowData;
        }
    }
    
    return $data;
}

function readTextFile($file_path) {
    $data = [];
    
    // Read file content
    $content = file_get_contents($file_path);
    
    // Detect encoding and convert to UTF-8
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    if ($encoding && $encoding != 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    }
    
    // Clean content - remove non-printable characters but keep tabs and newlines
    $content = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $content);
    
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            // Handle both tab-delimited and comma-delimited
            if (strpos($line, "\t") !== false) {
                $row = explode("\t", $line);
            } else {
                $row = str_getcsv($line, ",", '"', '\\');
            }
            
            // Clean each cell
            $clean_row = array_map(function($cell) {
                $cell = trim($cell);
                $cell = preg_replace('/[^\x20-\x7E]/', '', $cell);
                return $cell;
            }, $row);
            
            if (!empty(implode('', $clean_row))) {
                $data[] = $clean_row;
            }
        }
    }
    
    return $data;
}

// Add new student with fee management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $sno = $_POST['sno'];
    $name = $_POST['name'];
    $university_id = $_POST['university_id'];
    $semester = $_POST['semester'];
    $category = $_POST['category'];
    
    // Get selected months and their status
    $selected_months = isset($_POST['selected_months']) ? $_POST['selected_months'] : [];
    $month_status = isset($_POST['month_status']) ? $_POST['month_status'] : [];
    
    $sql = "INSERT INTO students (sno, name, university_id, semester, category) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $sno, $name, $university_id, $semester, $category);
    
    if ($stmt->execute()) {
        $student_id = $stmt->insert_id;
        
        // Add fee payments for selected months
        foreach ($selected_months as $month_id) {
            $status = isset($month_status[$month_id]) ? $month_status[$month_id] : 'Pending';
            $fee_sql = "INSERT INTO fee_payments (student_id, month_id, status) VALUES (?, ?, ?)";
            $fee_stmt = $conn->prepare($fee_sql);
            $fee_stmt->bind_param("iis", $student_id, $month_id, $status);
            $fee_stmt->execute();
            $fee_stmt->close();
        }
        
        $action_message = "Student added successfully with fee data!";
        $action_type = "success";
    } else {
        $action_message = "Error adding student: " . $conn->error;
        $action_type = "danger";
    }
    $stmt->close();
}

// Update fee status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_fee'])) {
    $student_id = $_POST['student_id'];
    $month_id = $_POST['month_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE fee_payments SET status = ? WHERE student_id = ? AND month_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $student_id, $month_id);
    
    if ($stmt->execute()) {
        $action_message = "Fee status updated successfully!";
        $action_type = "success";
    } else {
        $action_message = "Error updating fee status: " . $conn->error;
        $action_type = "danger";
    }
    $stmt->close();
}

// Bulk update fees
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update_fees'])) {
    $student_id = $_POST['bulk_student_id'];
    $selected_months = isset($_POST['bulk_selected_months']) ? $_POST['bulk_selected_months'] : [];
    $month_status = isset($_POST['bulk_month_status']) ? $_POST['bulk_month_status'] : [];
    
    $success = true;
    foreach ($selected_months as $month_id) {
        $status = isset($month_status[$month_id]) ? $month_status[$month_id] : 'Pending';
        $sql = "UPDATE fee_payments SET status = ? WHERE student_id = ? AND month_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $status, $student_id, $month_id);
        if (!$stmt->execute()) {
            $success = false;
        }
        $stmt->close();
    }
    
    if ($success) {
        $action_message = "Fee status updated successfully!";
        $action_type = "success";
    } else {
        $action_message = "Error updating some fee status!";
        $action_type = "warning";
    }
}

// Add new month
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_month'])) {
    $month_name = $_POST['month_name'];
    
    $sql = "INSERT INTO months (month_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $month_name);
    
    if ($stmt->execute()) {
        $action_message = "Month '$month_name' added successfully!";
        $action_type = "success";
    } else {
        $action_message = "Error adding month: " . $conn->error;
        $action_type = "danger";
    }
    $stmt->close();
}

// Delete student
if (isset($_GET['delete_student'])) {
    $student_id = $_GET['delete_student'];
    
    // First delete fee payments
    $fee_sql = "DELETE FROM fee_payments WHERE student_id = ?";
    $fee_stmt = $conn->prepare($fee_sql);
    $fee_stmt->bind_param("i", $student_id);
    $fee_stmt->execute();
    $fee_stmt->close();
    
    // Then delete student
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    
    if ($stmt->execute()) {
        $action_message = "Student deleted successfully!";
        $action_type = "success";
    } else {
        $action_message = "Error deleting student: " . $conn->error;
        $action_type = "danger";
    }
    $stmt->close();
}

// Remove seat booking
if (isset($_GET['remove_seat'])) {
    $seat_number = $_GET['remove_seat'];
    
    $sql = "UPDATE seats SET is_booked = FALSE, passenger_name = NULL, university_id = NULL, gender = NULL, booking_time = NULL WHERE seat_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $seat_number);
    
    if ($stmt->execute()) {
        $action_message = "Seat $seat_number booking removed successfully!";
        $action_type = "success";
    } else {
        $action_message = "Error removing seat booking: " . $conn->error;
        $action_type = "danger";
    }
    $stmt->close();
}

// Replace seat booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['replace_seat'])) {
    $old_seat = $_POST['old_seat'];
    $new_seat = $_POST['new_seat'];
    $passenger_name = $_POST['passenger_name'];
    $university_id = $_POST['university_id'];
    $gender = $_POST['gender'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Free the old seat
        $free_sql = "UPDATE seats SET is_booked = FALSE, passenger_name = NULL, university_id = NULL, gender = NULL, booking_time = NULL WHERE seat_number = ?";
        $free_stmt = $conn->prepare($free_sql);
        $free_stmt->bind_param("s", $old_seat);
        $free_stmt->execute();
        $free_stmt->close();
        
        // Book the new seat
        $book_sql = "UPDATE seats SET is_booked = TRUE, passenger_name = ?, university_id = ?, gender = ?, booking_time = NOW() WHERE seat_number = ?";
        $book_stmt = $conn->prepare($book_sql);
        $book_stmt->bind_param("ssss", $passenger_name, $university_id, $gender, $new_seat);
        $book_stmt->execute();
        $book_stmt->close();
        
        $conn->commit();
        $action_message = "Seat successfully replaced from $old_seat to $new_seat for $passenger_name!";
        $action_type = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $action_message = "Error replacing seat: " . $e->getMessage();
        $action_type = "danger";
    }
}

// Fetch all months
$months_sql = "SELECT * FROM months ORDER BY id";
$months_result = $conn->query($months_sql);
$months = [];
while ($month = $months_result->fetch_assoc()) {
    $months[] = $month;
}

// Fetch all students with their fee status
$students_sql = "SELECT s.* FROM students s ORDER BY s.sno";
$students_result = $conn->query($students_sql);

// Fetch all booked seats
$seats_sql = "SELECT * FROM seats WHERE is_booked = TRUE ORDER BY seat_number";
$seats_result = $conn->query($seats_sql);

// Fetch all available seats for replacement
$available_seats_sql = "SELECT * FROM seats WHERE is_booked = FALSE ORDER BY seat_number";
$available_seats_result = $conn->query($available_seats_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bus Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
        .fee-status-badge {
            cursor: pointer;
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .bg-pink {
            background-color: #ff66b2 !important;
        }
        .seat-actions {
            min-width: 200px;
        }
        .fee-card {
            transition: all 0.3s ease;
        }
        .fee-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .bulk-fee-btn {
            min-width: 120px;
        }
        .month-checkbox {
            min-height: 80px;
        }
        .export-import-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-cog"></i> Admin Panel - Bus Booking System</h1>
            <div>
                <span class="text-muted">Welcome, <?php echo $_SESSION['admin_username']; ?></span>
                <a href="?logout" class="btn btn-outline-danger btn-sm ms-2">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <a href="index.php" class="btn btn-outline-primary btn-sm ms-2">
                    <i class="fas fa-bus"></i> View Booking
                </a>
            </div>
        </div>

        <?php if ($action_message): ?>
            <div class="alert alert-<?php echo $action_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $action_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Export/Import Section -->
        <div class="export-import-section">
            <div class="row">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h6 class="card-title mb-0"><i class="fas fa-download"></i> Export Data</h6>
                        </div>
                        <div class="card-body">
                            <p>Export all student data with fee status to Excel format.</p>
                            <a href="?export_excel" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="card-title mb-0"><i class="fas fa-file-download"></i> Download Template</h6>
                        </div>
                        <div class="card-body">
                            <p>Download sample Excel template with proper format for importing.</p>
                            <a href="?download_sample" class="btn btn-warning">
                                <i class="fas fa-download"></i> Download Sample
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="card-title mb-0"><i class="fas fa-upload"></i> Import Data</h6>
                        </div>
                        <div class="card-body">
                            <p>Import student data from Excel file using the template format.</p>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="input-group">
                                    <input type="file" class="form-control" name="excel_file" accept=".xls,.xlsx" required>
                                    <button type="submit" name="import_excel" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Import
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Import Instructions:</h6>
                        <ul class="mb-0">
                            <li>Download the sample template first to understand the format</li>
                            <li>First 5 columns must be: Sno, Name, University ID, Semester, Category</li>
                            <li>Subsequent columns should be month names</li>
                            <li>Status values should be either 'Submitted' or 'Pending'</li>
                            <li>Keep the same column headers as in the template</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab">
                    <i class="fas fa-users"></i> Students & Fees
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="seats-tab" data-bs-toggle="tab" data-bs-target="#seats" type="button" role="tab">
                    <i class="fas fa-chair"></i> Booked Seats
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab">
                    <i class="fas fa-plus"></i> Add Student
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="months-tab" data-bs-toggle="tab" data-bs-target="#months" type="button" role="tab">
                    <i class="fas fa-calendar"></i> Manage Months
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabsContent">
            <!-- Students & Fees Tab -->
            <div class="tab-pane fade show active" id="students" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-list"></i> Student List & Fee Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>SNo</th>
                                        <th>Name</th>
                                        <th>University ID</th>
                                        <th>Semester</th>
                                        <th>Category</th>
                                        <?php foreach ($months as $month): ?>
                                            <th><?php echo $month['month_name']; ?></th>
                                        <?php endforeach; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = $students_result->fetch_assoc()): 
                                        // Get fee status for this student
                                        $fee_sql = "SELECT m.id, m.month_name, fp.status 
                                                   FROM fee_payments fp 
                                                   RIGHT JOIN months m ON fp.month_id = m.id AND fp.student_id = ?
                                                   ORDER BY m.id";
                                        $fee_stmt = $conn->prepare($fee_sql);
                                        $fee_stmt->bind_param("i", $student['id']);
                                        $fee_stmt->execute();
                                        $fee_result = $fee_stmt->get_result();
                                        
                                        $fee_status = [];
                                        while ($fee = $fee_result->fetch_assoc()) {
                                            $fee_status[$fee['id']] = $fee['status'] ?: 'Pending';
                                        }
                                        $fee_stmt->close();
                                    ?>
                                    <tr>
                                        <td><?php echo $student['sno']; ?></td>
                                        <td><?php echo $student['name']; ?></td>
                                        <td><?php echo $student['university_id']; ?></td>
                                        <td><?php echo $student['semester']; ?></td>
                                        <td><?php echo $student['category']; ?></td>
                                        
                                        <!-- Fee Status Columns -->
                                        <?php foreach ($months as $month): 
                                            $status = isset($fee_status[$month['id']]) ? $fee_status[$month['id']] : 'Pending';
                                            $badge_class = $status === 'Submitted' ? 'bg-success' : 'bg-danger';
                                        ?>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?> fee-status-badge"
                                                  data-bs-toggle="modal" 
                                                  data-bs-target="#feeModal"
                                                  data-student-id="<?php echo $student['id']; ?>"
                                                  data-student-name="<?php echo $student['name']; ?>"
                                                  data-month-id="<?php echo $month['id']; ?>"
                                                  data-month-name="<?php echo $month['month_name']; ?>"
                                                  data-current-status="<?php echo $status; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <?php endforeach; ?>
                                        
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-info bulk-fee-btn"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#bulkFeeModal"
                                                        data-student-id="<?php echo $student['id']; ?>"
                                                        data-student-name="<?php echo $student['name']; ?>">
                                                    <i class="fas fa-edit"></i> All Fees
                                                </button>
                                                <a href="?delete_student=<?php echo $student['id']; ?>" 
                                                   class="btn btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this student?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booked Seats Tab -->
            <div class="tab-pane fade" id="seats" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chair"></i> Currently Booked Seats - Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Seat Number</th>
                                        <th>Passenger Name</th>
                                        <th>University ID</th>
                                        <th>Gender</th>
                                        <th>Booking Time</th>
                                        <th class="seat-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($seat = $seats_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $seat['seat_number']; ?></strong></td>
                                        <td><?php echo $seat['passenger_name']; ?></td>
                                        <td><?php echo $seat['university_id']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $seat['gender'] === 'male' ? 'bg-primary' : 'bg-pink'; ?>">
                                                <?php echo ucfirst($seat['gender']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $seat['booking_time']; ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?remove_seat=<?php echo $seat['seat_number']; ?>" 
                                                   class="btn btn-danger"
                                                   onclick="return confirm('Are you sure you want to remove this seat booking?')">
                                                    <i class="fas fa-times"></i> Remove
                                                </a>
                                                <button type="button" class="btn btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#replaceModal"
                                                        data-seat-number="<?php echo $seat['seat_number']; ?>"
                                                        data-passenger-name="<?php echo $seat['passenger_name']; ?>"
                                                        data-university-id="<?php echo $seat['university_id']; ?>"
                                                        data-gender="<?php echo $seat['gender']; ?>">
                                                    <i class="fas fa-exchange-alt"></i> Replace
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Student Tab -->
            <div class="tab-pane fade" id="add" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-plus"></i> Add New Student with Fee Management</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <!-- Student Basic Information -->
                                <div class="col-md-2">
                                    <label for="sno" class="form-label">Serial No</label>
                                    <input type="number" class="form-control" id="sno" name="sno" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="university_id" class="form-label">University ID</label>
                                    <input type="text" class="form-control" id="university_id" name="university_id" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="semester" class="form-label">Semester</label>
                                    <input type="text" class="form-control" id="semester" name="semester" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="Student">Student</option>
                                        <option value="Faculty">Faculty</option>
                                    </select>
                                </div>
                                
                                <!-- Fee Status Section -->
                                <div class="col-12">
                                    <div class="card mt-3">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-money-bill-wave me-2"></i>Fee Payment Status Management
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <?php foreach ($months as $month): ?>
                                                <div class="col-md-3">
                                                    <div class="card h-100 fee-card border-warning month-checkbox">
                                                        <div class="card-header text-center py-2 bg-warning text-dark">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       name="selected_months[]" 
                                                                       value="<?php echo $month['id']; ?>" 
                                                                       id="month_<?php echo $month['id']; ?>"
                                                                       onchange="toggleMonthStatus(this, 'status_<?php echo $month['id']; ?>')">
                                                                <label class="form-check-label" for="month_<?php echo $month['id']; ?>">
                                                                    <strong><?php echo $month['month_name']; ?></strong>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="card-body text-center p-2">
                                                            <select class="form-select" name="month_status[<?php echo $month['id']; ?>]" 
                                                                    id="status_<?php echo $month['id']; ?>" disabled>
                                                                <option value="Pending">Pending</option>
                                                                <option value="Submitted">Submitted</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                                <div class="col-12">
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <button type="button" class="btn btn-success btn-sm" onclick="selectAllMonths(true)">
                                                            <i class="fas fa-check-square"></i> Select All Months
                                                        </button>
                                                        <button type="button" class="btn btn-warning btn-sm" onclick="selectAllMonths(false)">
                                                            <i class="fas fa-times-circle"></i> Deselect All
                                                        </button>
                                                        <button type="button" class="btn btn-info btn-sm" onclick="setAllSelectedMonths('Submitted')">
                                                            <i class="fas fa-check-circle"></i> Mark Selected as Paid
                                                        </button>
                                                        <button type="button" class="btn btn-secondary btn-sm" onclick="setAllSelectedMonths('Pending')">
                                                            <i class="fas fa-clock"></i> Mark Selected as Pending
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" name="add_student" class="btn btn-success btn-lg">
                                        <i class="fas fa-save"></i> Add Student with Fee Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Manage Months Tab -->
            <div class="tab-pane fade" id="months" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar"></i> Manage Months</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="card-title mb-0">Add New Month</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="month_name" placeholder="Enter month name" required>
                                                <button type="submit" name="add_month" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Add Month
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="card-title mb-0">Current Months</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php foreach ($months as $month): ?>
                                                <div class="col-md-6 mb-2">
                                                    <span class="badge bg-primary p-2 w-100"><?php echo $month['month_name']; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Status Modal -->
    <div class="modal fade" id="feeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Fee Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="modal_student_id">
                        <input type="hidden" name="month_id" id="modal_month_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="modal_student_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Month</label>
                            <input type="text" class="form-control" id="modal_month_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="Pending">Pending</option>
                                <option value="Submitted">Submitted</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_fee" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Fee Update Modal -->
    <div class="modal fade" id="bulkFeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Update All Fees</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="bulk_student_id" id="bulk_student_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="bulk_student_name" readonly>
                        </div>
                        
                        <div class="row g-3">
                            <?php foreach ($months as $month): ?>
                            <div class="col-md-6">
                                <div class="card h-100 fee-card">
                                    <div class="card-header text-center py-2 bg-light">
                                        <strong><?php echo $month['month_name']; ?></strong>
                                    </div>
                                    <div class="card-body text-center p-2">
                                        <select class="form-select" name="bulk_month_status[<?php echo $month['id']; ?>]" required>
                                            <option value="Pending">Pending</option>
                                            <option value="Submitted">Submitted</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-success btn-sm" onclick="setBulkAllFees('Submitted')">
                                Mark All as Submitted
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" onclick="setBulkAllFees('Pending')">
                                Mark All as Pending
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="bulk_update_fees" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update All Fees
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Replace Seat Modal -->
    <div class="modal fade" id="replaceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Replace Seat Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="old_seat" id="replace_old_seat">
                    <input type="hidden" name="passenger_name" id="replace_passenger_name">
                    <input type="hidden" name="university_id" id="replace_university_id">
                    <input type="hidden" name="gender" id="replace_gender">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Seat</label>
                            <input type="text" class="form-control" id="replace_current_seat" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Passenger</label>
                            <input type="text" class="form-control" id="replace_current_passenger" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="new_seat" class="form-label">New Seat</label>
                            <select class="form-select" id="new_seat" name="new_seat" required>
                                <option value="">Select new seat...</option>
                                <?php 
                                // Reset pointer for available seats
                                $available_seats_result->data_seek(0);
                                while ($available_seat = $available_seats_result->fetch_assoc()): ?>
                                    <option value="<?php echo $available_seat['seat_number']; ?>">
                                        <?php echo $available_seat['seat_number']; ?> (Available)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> This will move the passenger from the current seat to the new selected seat.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="replace_seat" class="btn btn-warning">
                            <i class="fas fa-exchange-alt"></i> Replace Seat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fee modal functionality
        const feeModal = document.getElementById('feeModal');
        feeModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            document.getElementById('modal_student_id').value = button.getAttribute('data-student-id');
            document.getElementById('modal_student_name').value = button.getAttribute('data-student-name');
            document.getElementById('modal_month_id').value = button.getAttribute('data-month-id');
            document.getElementById('modal_month_name').value = button.getAttribute('data-month-name');
            
            // Set current status in select
            const currentStatus = button.getAttribute('data-current-status');
            const statusSelect = document.querySelector('#feeModal select[name="status"]');
            statusSelect.value = currentStatus;
        });

        // Bulk fee modal functionality
        const bulkFeeModal = document.getElementById('bulkFeeModal');
        bulkFeeModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            document.getElementById('bulk_student_id').value = button.getAttribute('data-student-id');
            document.getElementById('bulk_student_name').value = button.getAttribute('data-student-name');
        });

        // Replace seat modal functionality
        const replaceModal = document.getElementById('replaceModal');
        replaceModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            const seatNumber = button.getAttribute('data-seat-number');
            const passengerName = button.getAttribute('data-passenger-name');
            const universityId = button.getAttribute('data-university-id');
            const gender = button.getAttribute('data-gender');
            
            document.getElementById('replace_old_seat').value = seatNumber;
            document.getElementById('replace_passenger_name').value = passengerName;
            document.getElementById('replace_university_id').value = universityId;
            document.getElementById('replace_gender').value = gender;
            document.getElementById('replace_current_seat').value = seatNumber;
            document.getElementById('replace_current_passenger').value = passengerName;
        });

        // Month management functions
        function toggleMonthStatus(checkbox, statusId) {
            const statusSelect = document.getElementById(statusId);
            statusSelect.disabled = !checkbox.checked;
        }

        function selectAllMonths(select) {
            const checkboxes = document.querySelectorAll('input[name="selected_months[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = select;
                toggleMonthStatus(checkbox, 'status_' + checkbox.value);
            });
        }

        function setAllSelectedMonths(status) {
            const checkboxes = document.querySelectorAll('input[name="selected_months[]"]:checked');
            checkboxes.forEach(checkbox => {
                const statusSelect = document.getElementById('status_' + checkbox.value);
                statusSelect.value = status;
            });
        }

        function setBulkAllFees(status) {
            const selects = document.querySelectorAll('#bulkFeeModal select');
            selects.forEach(select => {
                select.value = status;
            });
        }

        // Logout confirmation
        document.querySelector('a[href="?logout"]').addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$conn->close();
?>