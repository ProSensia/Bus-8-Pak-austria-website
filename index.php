<?php
include 'connection.php';
session_start();

$message = '';
$message_type = '';
$student_info = null;
$selected_seat = null;
$fee_status = [];

// Handle university ID verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_university_id'])) {
    $university_id = trim($_POST['university_id']);
    
    if (!empty($university_id)) {
        // Get student information
        $sql = "SELECT s.*, 
                GROUP_CONCAT(CONCAT(m.month_name, ':', fp.status)) as fee_data
                FROM students s 
                LEFT JOIN fee_payments fp ON s.id = fp.student_id 
                LEFT JOIN months m ON fp.month_id = m.id 
                WHERE s.university_id = ? 
                GROUP BY s.id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $university_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student_info = $result->fetch_assoc();
            
            // Parse fee data
            $fee_data = $student_info['fee_data'];
            $fee_payments = [];
            if ($fee_data) {
                $payments = explode(',', $fee_data);
                foreach ($payments as $payment) {
                    list($month, $status) = explode(':', $payment);
                    $fee_payments[$month] = $status;
                }
            }
            
            // Check if student has paid fees for current period
            $current_month = date('F');
            $has_paid_fees = false;
            $paid_until = '';
            
            // Check fee status for current and future months
            $months_order = ['September', 'October', 'November', 'December'];
            $current_month_index = array_search($current_month, $months_order);
            
            if ($current_month_index !== false) {
                for ($i = $current_month_index; $i < count($months_order); $i++) {
                    $month = $months_order[$i];
                    if (isset($fee_payments[$month]) && $fee_payments[$month] === 'Submitted') {
                        $has_paid_fees = true;
                        $paid_until = $month;
                        break;
                    }
                }
            }
            
            if ($has_paid_fees) {
                $message = "Student verified! Fee paid until " . $paid_until . " 2025";
                $message_type = "success";
                $fee_status = $fee_payments;
            } else {
                $message = "Student verified but no active fee payment found for current period.";
                $message_type = "warning";
                $fee_status = $fee_payments;
            }
        } else {
            $message = "University ID not found!";
            $message_type = "danger";
        }
        $stmt->close();
    } else {
        $message = "Please enter a University ID";
        $message_type = "warning";
    }
}

// Handle seat booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_seat'])) {
    $seat_number = $_POST['seat_number'];
    $university_id = $_POST['booking_university_id'];
    $passenger_name = $_POST['passenger_name'];
    $gender = $_POST['gender'];
    
    // Check if seat is already booked
    $check_sql = "SELECT is_booked FROM seats WHERE seat_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $seat_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $seat_data = $check_result->fetch_assoc();
        
        if (!$seat_data['is_booked']) {
            // Check if student exists and has paid fees
            $fee_sql = "SELECT s.*, 
                       GROUP_CONCAT(CONCAT(m.month_name, ':', fp.status)) as fee_data
                       FROM students s 
                       LEFT JOIN fee_payments fp ON s.id = fp.student_id 
                       LEFT JOIN months m ON fp.month_id = m.id 
                       WHERE s.university_id = ? 
                       GROUP BY s.id";
            $fee_stmt = $conn->prepare($fee_sql);
            $fee_stmt->bind_param("s", $university_id);
            $fee_stmt->execute();
            $fee_result = $fee_stmt->get_result();
            
            if ($fee_result->num_rows > 0) {
                $student_data = $fee_result->fetch_assoc();
                
                // Parse and check fee data
                $fee_data = $student_data['fee_data'];
                $fee_payments = [];
                if ($fee_data) {
                    $payments = explode(',', $fee_data);
                    foreach ($payments as $payment) {
                        list($month, $status) = explode(':', $payment);
                        $fee_payments[$month] = $status;
                    }
                }
                
                $current_month = date('F');
                $has_paid_fees = false;
                
                $months_order = ['September', 'October', 'November', 'December'];
                $current_month_index = array_search($current_month, $months_order);
                
                if ($current_month_index !== false) {
                    for ($i = $current_month_index; $i < count($months_order); $i++) {
                        $month = $months_order[$i];
                        if (isset($fee_payments[$month]) && $fee_payments[$month] === 'Submitted') {
                            $has_paid_fees = true;
                            break;
                        }
                    }
                }
                
                if ($has_paid_fees) {
                    // Book the seat
                    $update_sql = "UPDATE seats SET is_booked = TRUE, passenger_name = ?, university_id = ?, gender = ?, booking_time = NOW() WHERE seat_number = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ssss", $passenger_name, $university_id, $gender, $seat_number);
                    
                    if ($update_stmt->execute()) {
                        $message = "Seat $seat_number booked successfully for $passenger_name!";
                        $message_type = "success";
                    } else {
                        $message = "Error booking seat: " . $conn->error;
                        $message_type = "danger";
                    }
                    $update_stmt->close();
                } else {
                    $message = "Cannot book seat. No active fee payment found for current period.";
                    $message_type = "warning";
                }
            } else {
                $message = "Student not found. Please verify University ID first.";
                $message_type = "danger";
            }
            $fee_stmt->close();
        } else {
            $message = "Seat $seat_number is already booked!";
            $message_type = "warning";
        }
    }
    $check_stmt->close();
}

// Fetch all seats
$sql = "SELECT * FROM seats ORDER BY 
        CAST(SUBSTRING(seat_number, 1, 1) AS UNSIGNED),
        seat_number";
$result = $conn->query($sql);
$seats = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $seats[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coaster Bus Seat Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .bus-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .driver-section {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
        .seat {
            width: 70px;
            height: 70px;
            margin: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            border: 2px solid transparent;
        }
        .seat:hover:not(.booked) {
            transform: scale(1.05);
            box-shadow: 0 0 5px rgba(0,0,0,0.3);
        }
        .seat.available {
            background-color: #c0c0c0;
            color: #333;
        }
        .seat.male {
            background-color: #4d79ff;
            color: white;
        }
        .seat.female {
            background-color: #ff66b2;
            color: white;
        }
        .seat.booked {
            background-color: #6c757d;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .seat.selected {
            border: 3px solid #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        }
        .aisle {
            width: 40px;
            display: inline-block;
        }
        .door {
            width: 60px;
            height: 100px;
            background-color: #8B4513;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            border-radius: 5px;
            margin: 0 10px;
            writing-mode: vertical-rl;
            text-orientation: mixed;
        }
        .row-label {
            font-weight: bold;
            margin-right: 10px;
            width: 30px;
            display: inline-block;
            text-align: center;
        }
        .seat-row {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .legend {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin: 0 10px 10px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 5px;
        }
        .student-info {
            background-color: #e7f3ff;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .fee-status {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .fee-month {
            text-align: center;
        }
        .fee-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .badge-submitted {
            background-color: #28a745;
            color: white;
        }
        .badge-pending {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="bus-container">
            <h1 class="text-center mb-4"><i class="fas fa-bus"></i> Coaster Bus Seat Booking</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- University ID Verification Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-id-card"></i> Verify University ID</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-8">
                            <label for="university_id" class="form-label">University ID</label>
                            <input type="text" class="form-control" id="university_id" name="university_id" 
                                   placeholder="Enter your University ID" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" name="verify_university_id" class="btn btn-primary w-100">
                                <i class="fas fa-check"></i> Verify ID
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($student_info): ?>
                        <div class="student-info mt-3">
                            <h6>Student Information:</h6>
                            <p><strong>Name:</strong> <?php echo $student_info['name']; ?></p>
                            <p><strong>University ID:</strong> <?php echo $student_info['university_id']; ?></p>
                            <p><strong>Semester:</strong> <?php echo $student_info['semester']; ?></p>
                            <p><strong>Category:</strong> <?php echo $student_info['category']; ?></p>
                            
                            <h6 class="mt-3">Fee Status:</h6>
                            <div class="fee-status">
                                <?php
                                $months = ['September', 'October', 'November', 'December'];
                                foreach ($months as $month) {
                                    $status = isset($fee_status[$month]) ? $fee_status[$month] : 'Pending';
                                    $badge_class = $status === 'Submitted' ? 'badge-submitted' : 'badge-pending';
                                    echo "
                                    <div class='fee-month'>
                                        <div>$month</div>
                                        <span class='fee-badge $badge_class'>$status</span>
                                    </div>";
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Bus Layout -->
            <div class="driver-section">
                <i class="fas fa-user-tie fa-2x"></i>
                <p class="mt-2">Driver</p>
            </div>
            
            <div class="seat-map">
                <?php
                $current_row = '';
                foreach ($seats as $seat) {
                    $row = substr($seat['seat_number'], 0, 1);
                    $position = substr($seat['seat_number'], 1);
                    
                    if ($current_row !== $row) {
                        if ($current_row !== '') {
                            echo '</div>';
                        }
                        
                        echo '<div class="seat-row">';
                        echo '<span class="row-label">Row ' . $row . '</span>';
                        $current_row = $row;
                        
                        // Add door after row 3
                        if ($row == '3') {
                            echo '<div class="door">DOOR</div>';
                        }
                    }
                    
                    $seat_class = 'available';
                    if ($seat['is_booked']) {
                        $seat_class = 'booked';
                        if ($seat['gender'] == 'male') {
                            $seat_class .= ' male';
                        } else if ($seat['gender'] == 'female') {
                            $seat_class .= ' female';
                        }
                    }
                    
                    echo '<div class="seat ' . $seat_class . '" data-seat="' . $seat['seat_number'] . '" 
                         data-booked="' . ($seat['is_booked'] ? 'true' : 'false') . '">';
                    echo $seat['seat_number'];
                    if ($seat['is_booked']) {
                        echo '<i class="fas fa-lock ms-1"></i>';
                        if ($seat['passenger_name']) {
                            echo '<div class="passenger-name" style="font-size: 0.7em; margin-top: 2px;">' . substr($seat['passenger_name'], 0, 8) . '</div>';
                        }
                    }
                    echo '</div>';
                    
                    // Add aisle logic for different rows
                    if ($row == '1' && $position == 'A') {
                        echo '<div class="aisle"></div>';
                    } else if (in_array($row, ['2', '4', '5', '6', '7']) && $position == 'B') {
                        echo '<div class="aisle"></div>';
                    } else if ($row == '8' && $position == 'B') {
                        echo '<div class="aisle"></div>';
                    }
                }
                echo '</div>';
                ?>
            </div>
            
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #c0c0c0;"></div>
                    <span>Available Seat</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #4d79ff;"></div>
                    <span>Male Booked</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #ff66b2;"></div>
                    <span>Female Booked</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #6c757d;"></div>
                    <span>Booked</span>
                </div>
            </div>
            
            <!-- Booking Form (initially hidden) -->
            <div class="mt-4" id="booking-section" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-ticket-alt"></i> Book Selected Seat</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="booking-form">
                            <input type="hidden" name="seat_number" id="selected-seat">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="booking_university_id" class="form-label">University ID</label>
                                    <input type="text" class="form-control" id="booking_university_id" 
                                           name="booking_university_id" value="<?php echo $student_info ? $student_info['university_id'] : ''; ?>" 
                                           readonly required>
                                </div>
                                <div class="col-md-6">
                                    <label for="passenger_name" class="form-label">Passenger Name</label>
                                    <input type="text" class="form-control" id="passenger_name" 
                                           name="passenger_name" value="<?php echo $student_info ? $student_info['name'] : ''; ?>" 
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="male" value="male" required>
                                            <label class="form-check-label" for="male">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="female" value="female">
                                            <label class="form-check-label" for="female">Female</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="book_seat" class="btn btn-success">
                                        <i class="fas fa-check"></i> Confirm Booking
                                    </button>
                                    <button type="button" id="cancel-booking" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Admin Login Link -->
            <div class="text-center mt-4">
                <a href="admin.php" class="btn btn-outline-primary">
                    <i class="fas fa-cog"></i> Admin Panel
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const seats = document.querySelectorAll('.seat');
            const selectedSeatInput = document.getElementById('selected-seat');
            const bookingSection = document.getElementById('booking-section');
            const bookingUniversityId = document.getElementById('booking_university_id');
            const passengerName = document.getElementById('passenger_name');
            const cancelBtn = document.getElementById('cancel-booking');
            let selectedSeat = null;
            
            seats.forEach(seat => {
                seat.addEventListener('click', function() {
                    const isBooked = this.getAttribute('data-booked') === 'true';
                    
                    if (!isBooked) {
                        // Remove selection from all seats
                        seats.forEach(s => s.classList.remove('selected'));
                        
                        // Add selection to clicked seat
                        this.classList.add('selected');
                        selectedSeat = this.getAttribute('data-seat');
                        selectedSeatInput.value = selectedSeat;
                        
                        // Show booking section
                        bookingSection.style.display = 'block';
                        
                        // Scroll to booking section
                        bookingSection.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
            
            cancelBtn.addEventListener('click', function() {
                // Remove selection
                seats.forEach(s => s.classList.remove('selected'));
                selectedSeat = null;
                selectedSeatInput.value = '';
                
                // Hide booking section
                bookingSection.style.display = 'none';
            });
            
            // If student info is available, pre-fill the booking form
            <?php if ($student_info): ?>
                bookingUniversityId.value = '<?php echo $student_info['university_id']; ?>';
                passengerName.value = '<?php echo $student_info['name']; ?>';
            <?php endif; ?>
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>