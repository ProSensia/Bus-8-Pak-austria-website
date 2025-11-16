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

// Add new student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $sno = $_POST['sno'];
    $name = $_POST['name'];
    $university_id = $_POST['university_id'];
    $semester = $_POST['semester'];
    $category = $_POST['category'];
    
    $sql = "INSERT INTO students (sno, name, university_id, semester, category) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $sno, $name, $university_id, $semester, $category);
    
    if ($stmt->execute()) {
        $student_id = $stmt->insert_id;
        
        // Add fee payments for all months as Pending
        $month_sql = "SELECT id FROM months";
        $month_result = $conn->query($month_sql);
        while ($month = $month_result->fetch_assoc()) {
            $fee_sql = "INSERT INTO fee_payments (student_id, month_id, status) VALUES (?, ?, 'Pending')";
            $fee_stmt = $conn->prepare($fee_sql);
            $fee_stmt->bind_param("ii", $student_id, $month['id']);
            $fee_stmt->execute();
            $fee_stmt->close();
        }
        
        $action_message = "Student added successfully!";
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

// Fetch all students with their fee status
$students_sql = "SELECT s.*, 
                MAX(CASE WHEN m.month_name = 'September' THEN fp.status END) as sep_status,
                MAX(CASE WHEN m.month_name = 'October' THEN fp.status END) as oct_status,
                MAX(CASE WHEN m.month_name = 'November' THEN fp.status END) as nov_status,
                MAX(CASE WHEN m.month_name = 'December' THEN fp.status END) as dec_status
                FROM students s
                LEFT JOIN fee_payments fp ON s.id = fp.student_id
                LEFT JOIN months m ON fp.month_id = m.id
                GROUP BY s.id
                ORDER BY s.sno";
$students_result = $conn->query($students_sql);

// Fetch all booked seats
$seats_sql = "SELECT * FROM seats WHERE is_booked = TRUE ORDER BY seat_number";
$seats_result = $conn->query($seats_sql);

// Fetch all available seats for replacement
$available_seats_sql = "SELECT * FROM seats WHERE is_booked = FALSE ORDER BY seat_number";
$available_seats_result = $conn->query($available_seats_sql);

// Get months for fee management
$months_sql = "SELECT * FROM months";
$months_result = $conn->query($months_sql);
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
            max-width: 1400px;
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
                                        <th>September</th>
                                        <th>October</th>
                                        <th>November</th>
                                        <th>December</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = $students_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $student['sno']; ?></td>
                                        <td><?php echo $student['name']; ?></td>
                                        <td><?php echo $student['university_id']; ?></td>
                                        <td><?php echo $student['semester']; ?></td>
                                        <td><?php echo $student['category']; ?></td>
                                        
                                        <!-- Fee Status Columns -->
                                        <?php
                                        $months = [
                                            'sep' => $student['sep_status'] ?: 'Pending',
                                            'oct' => $student['oct_status'] ?: 'Pending',
                                            'nov' => $student['nov_status'] ?: 'Pending',
                                            'dec' => $student['dec_status'] ?: 'Pending'
                                        ];
                                        
                                        foreach ($months as $month_key => $status):
                                            $badge_class = $status === 'Submitted' ? 'bg-success' : 'bg-danger';
                                            $month_id_map = ['sep' => 1, 'oct' => 2, 'nov' => 3, 'dec' => 4];
                                        ?>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?> fee-status-badge"
                                                  data-bs-toggle="modal" 
                                                  data-bs-target="#feeModal"
                                                  data-student-id="<?php echo $student['id']; ?>"
                                                  data-student-name="<?php echo $student['name']; ?>"
                                                  data-month-id="<?php echo $month_id_map[$month_key]; ?>"
                                                  data-month-name="<?php echo ucfirst($month_key); ?>"
                                                  data-current-status="<?php echo $status; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <?php endforeach; ?>
                                        
                                        <td>
                                            <a href="?delete_student=<?php echo $student['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this student?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
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
                        <h5 class="card-title mb-0"><i class="fas fa-plus"></i> Add New Student</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
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
                                <div class="col-12">
                                    <button type="submit" name="add_student" class="btn btn-success">
                                        <i class="fas fa-save"></i> Add Student
                                    </button>
                                </div>
                            </div>
                        </form>
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
                                <?php while ($available_seat = $available_seats_result->fetch_assoc()): ?>
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
            const statusSelect = document.querySelector('select[name="status"]');
            statusSelect.value = currentStatus;
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