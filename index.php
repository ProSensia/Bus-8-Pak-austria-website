<?php
include 'connection.php';
session_start();

$message = '';
$message_type = '';
$student_info = null;
$selected_seat = null;
$fee_status = [];
$pending_months = [];

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
            $_SESSION['verified_student'] = $student_info;

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

            // Find pending months for voucher submission
            $months_order = ['September', 'October', 'November', 'December'];
            foreach ($months_order as $month) {
                if (!isset($fee_payments[$month]) || $fee_payments[$month] === 'Pending') {
                    $pending_months[] = $month;
                }
            }

            $fee_status = $fee_payments;
            $message = "Student verified successfully!";
            $message_type = "success";

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

// Handle fee voucher submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_voucher'])) {
    if (!isset($_SESSION['verified_student'])) {
        $message = "Please verify your University ID first!";
        $message_type = "warning";
    } else {
        $student_info = $_SESSION['verified_student'];
        $months_applied = implode(',', $_POST['months'] ?? []);
        $voucher_image = $_FILES['voucher_image'];

        // Validate input
        if (empty($months_applied)) {
            $message = "Please select at least one month!";
            $message_type = "warning";
        } elseif ($voucher_image['error'] !== UPLOAD_ERR_OK) {
            $message = "Please upload a valid voucher image!";
            $message_type = "warning";
        } else {
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($voucher_image['tmp_name']);

            if (!in_array($file_type, $allowed_types)) {
                $message = "Only JPG, PNG, and GIF images are allowed!";
                $message_type = "warning";
            } else {
                // Get device information
                $mac_address = getMacAddress();
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $device_info = json_encode([
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'browser' => get_browser(null, true)['browser'] ?? 'Unknown',
                    'platform' => get_browser(null, true)['platform'] ?? 'Unknown'
                ]);

                // Get location data (simplified - in production use geolocation API)
                $location_data = json_encode([
                    'ip' => $ip_address,
                    'city' => 'Unknown',
                    'country' => 'Unknown'
                ]);

                // Generate unique filename
                $file_extension = pathinfo($voucher_image['name'], PATHINFO_EXTENSION);
                $filename = 'voucher_' . $student_info['university_id'] . '_' . time() . '.' . $file_extension;
                $upload_path = 'uploads/' . $filename;

                // Move uploaded file
                if (move_uploaded_file($voucher_image['tmp_name'], $upload_path)) {
                    // Insert voucher record
                    $sql = "INSERT INTO fee_vouchers (student_id, months_applied, voucher_image, mac_address, ip_address, location_data, device_info) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issssss", $student_info['id'], $months_applied, $filename, $mac_address, $ip_address, $location_data, $device_info);

                    if ($stmt->execute()) {
                        $message = "Fee voucher submitted successfully! It will be verified within 24 hours.";
                        $message_type = "success";

                        // Update session to reflect pending status
                        foreach (explode(',', $months_applied) as $month) {
                            $fee_status[$month] = 'Pending Verification';
                        }
                    } else {
                        $message = "Error submitting voucher: " . $conn->error;
                        $message_type = "danger";
                        // Delete uploaded file if DB insert failed
                        unlink($upload_path);
                    }
                    $stmt->close();
                } else {
                    $message = "Error uploading image!";
                    $message_type = "danger";
                }
            }
        }
    }
}

// Handle seat booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_seat'])) {
    $seat_number = $_POST['seat_number'];
    $university_id = $_POST['booking_university_id'];
    $passenger_name = $_POST['passenger_name'];
    $gender = $_POST['gender'];

    // 1️⃣ Check if this user already has a booked seat
    $seat_check_sql = "SELECT * FROM seats WHERE university_id = ? AND is_booked = TRUE";
    $seat_check_stmt = $conn->prepare($seat_check_sql);
    $seat_check_stmt->bind_param("s", $university_id);
    $seat_check_stmt->execute();
    $seat_check_result = $seat_check_stmt->get_result();

    if ($seat_check_result->num_rows > 0) {
        $message = "You have already booked a seat. Only one seat allowed per person.";
        $message_type = "warning";
    } else {
        // 2️⃣ Check if seat is already booked
        $check_sql = "SELECT is_booked FROM seats WHERE seat_number = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $seat_number);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $seat_data = $check_result->fetch_assoc();
        $check_stmt->close();

        if ($seat_data && !$seat_data['is_booked']) {
            // 3️⃣ Check user category & fee
            $user_sql = "SELECT s.*, 
                     GROUP_CONCAT(CONCAT(m.month_name, ':', fp.status)) as fee_data
                     FROM students s 
                     LEFT JOIN fee_payments fp ON s.id = fp.student_id 
                     LEFT JOIN months m ON fp.month_id = m.id 
                     WHERE s.university_id = ? 
                     GROUP BY s.id";
            $user_stmt = $conn->prepare($user_sql);
            $user_stmt->bind_param("s", $university_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();

            if ($user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $category = strtolower($user_data['category']);
                $can_book = false;

                if ($category === 'faculty') {
                    $can_book = true;
                } else {
                    // Check fee payments for students
                    $fee_data = $user_data['fee_data'];
                    $fee_payments = [];
                    if ($fee_data) {
                        $payments = explode(',', $fee_data);
                        foreach ($payments as $payment) {
                            list($month, $status) = explode(':', $payment);
                            $fee_payments[$month] = $status;
                        }
                    }
                    $current_month = date('F');
                    $months_order = ['September', 'October', 'November', 'December'];
                    $current_month_index = array_search($current_month, $months_order);
                    if ($current_month_index !== false) {
                        for ($i = $current_month_index; $i < count($months_order); $i++) {
                            $month = $months_order[$i];
                            if (isset($fee_payments[$month]) && $fee_payments[$month] === 'Submitted') {
                                $can_book = true;
                                break;
                            }
                        }
                    }
                }

                if ($can_book) {
                    // 4️⃣ Book the seat
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
                $message = "User not found. Please verify University ID first.";
                $message_type = "danger";
            }
            $user_stmt->close();
        } else {
            $message = "Seat $seat_number is already booked!";
            $message_type = "warning";
        }
    }

    $seat_check_stmt->close();
}

// Fetch all seats
$sql = "SELECT * FROM seats ORDER BY 
        CAST(SUBSTRING(seat_number, 1, 1) AS UNSIGNED),
        seat_number";
$result = $conn->query($sql);
$seats = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $seats[] = $row;
    }
}

// Function to get MAC address
function getMacAddress()
{
    $mac = 'Unknown';

    // For Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        @exec('ipconfig /all', $output);
        foreach ($output as $line) {
            if (preg_match('/Physical Address[^:]*: ([0-9A-F-]+)/i', $line, $matches)) {
                $mac = $matches[1];
                break;
            }
        }
    }
    // For Linux/Unix
    else {
        @exec('/sbin/ifconfig -a', $output);
        foreach ($output as $line) {
            if (preg_match('/ether (([0-9a-f]{2}[:]){5}([0-9a-f]{2}))/i', $line, $matches)) {
                $mac = $matches[1];
                break;
            }
        }
    }

    return $mac;
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .bus-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 20px 0;
        }

        .grid-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .row-label {
            width: 30px;
            text-align: center;
            font-weight: bold;
            color: #666;
        }

        .seat {
            width: 80px;
            height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #ccc;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            font-weight: bold;
        }

        .seat.available {
            background-color: #c0c0c0;
            border-color: #999;
        }

        .seat.booked {
            background-color: #6c757d;
            color: white;
            cursor: not-allowed;
        }

        .seat.booked.male {
            background-color: #4d79ff;
        }

        .seat.booked.female {
            background-color: #ff66b2;
        }

        .seat.selected {
            border-color: #28a745;
            box-shadow: 0 0 10px #28a745;
        }

        .seat:hover:not(.booked) {
            transform: scale(1.05);
            border-color: #007bff;
        }

        .passenger-name {
            font-size: 10px;
            margin-top: 5px;
            text-align: center;
        }

        .driver-area,
        .door-area {
            width: 100px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #ff6b6b;
            color: white;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
        }

        .walking-area {
            flex: 1;
            height: 10px;
            background-color: #f8f9fa;
            border: 1px dashed #dee2e6;
        }

        .legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .fee-status {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .fee-month {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .fee-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-submitted {
            background-color: #28a745;
            color: white;
        }

        .badge-pending {
            background-color: #ffc107;
            color: black;
        }

        .badge-verification {
            background-color: #17a2b8;
            color: white;
        }

        .voucher-section {
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }

        .month-checkbox {
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 5px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .month-checkbox.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }

        .month-checkbox:hover {
            border-color: #0056b3;
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
                                    $badge_class = 'badge-pending';
                                    if ($status === 'Submitted')
                                        $badge_class = 'badge-submitted';
                                    if ($status === 'Pending Verification')
                                        $badge_class = 'badge-verification';

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

            <!-- Fee Voucher Submission Section -->
            <?php if ($student_info && !empty($pending_months)): ?>
                <div class="card mb-4 voucher-section">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-receipt"></i> Submit Fee Voucher</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Select Months to Pay:</label>
                                    <div class="months-selection">
                                        <?php foreach ($pending_months as $month): ?>
                                            <div class="form-check month-checkbox">
                                                <input class="form-check-input" type="checkbox" name="months[]"
                                                    value="<?php echo $month; ?>" id="month_<?php echo $month; ?>">
                                                <label class="form-check-label w-100" for="month_<?php echo $month; ?>">
                                                    <strong><?php echo $month; ?> 2024</strong>
                                                    <span class="badge bg-warning float-end">Pending</span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="voucher_image" class="form-label">Upload Fee Voucher Image</label>
                                    <input type="file" class="form-control" id="voucher_image" name="voucher_image"
                                        accept="image/jpeg,image/jpg,image/png,image/gif" required>
                                    <div class="form-text">
                                        Upload clear image of your fee payment receipt/voucher (JPG, PNG, GIF)
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle"></i> Important Notes:</h6>
                                        <ul class="mb-0">
                                            <li>Your voucher will be verified within 24 hours</li>
                                            <li>Once approved, fee status will be updated to "Submitted"</li>
                                            <li>You can book seats only for months with "Submitted" status</li>
                                            <li>System tracks your device information for security</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" name="submit_voucher" class="btn btn-success">
                                        <i class="fas fa-upload"></i> Submit Voucher for Verification
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bus-grid">
                <?php
                $current_row = '';
                $rows = [];

                // Group seats by row
                foreach ($seats as $seat) {
                    $row = substr($seat['seat_number'], 0, 1);
                    $rows[$row][] = $seat;
                }

                // Generate grid layout
                foreach ($rows as $row_num => $row_seats) {
                    echo '<div class="grid-row grid-row-' . $row_num . '">';
                    echo '<div class="row-label">' . $row_num . '</div>';

                    // Row 1: Special layout with driver
                    if ($row_num == '1') {
                        // Left seats (1A, 1B) - span 1.5 columns each
                        foreach (array_slice($row_seats, 0, 2) as $seat) {
                            $seat_class = 'available';
                            if ($seat['is_booked']) {
                                $seat_class = 'booked';
                                if ($seat['gender'] == 'male')
                                    $seat_class .= ' male';
                                else if ($seat['gender'] == 'female')
                                    $seat_class .= ' female';
                            }

                            echo '<div class="seat ' . $seat_class . '" data-seat="' . $seat['seat_number'] . '" 
                     data-booked="' . ($seat['is_booked'] ? 'true' : 'false') . '">';
                            echo $seat['seat_number'];
                            if ($seat['is_booked']) {
                                echo '<i class="fas fa-lock"></i>';
                                if ($seat['passenger_name']) {
                                    $first_name = explode(' ', $seat['passenger_name'])[0];
                                    echo '<div class="passenger-name">' . htmlspecialchars($first_name) . '</div>';

                                }
                            }
                            echo '</div>';
                        }

                        // Driver area
                        echo '<div class="driver-area">DRIVER</div>';
                    }
                    // Row 3: Door layout
                    else if ($row_num == '3') {
                        // Door area
                        echo '<div class="door-area">DOOR</div>';

                        // Walking area
                        echo '<div class="walking-area"></div>';

                        // Right side seats (3A, 3B)
                        foreach (array_slice($row_seats, 0, 2) as $seat) {
                            $seat_class = 'available';
                            if ($seat['is_booked']) {
                                $seat_class = 'booked';
                                if ($seat['gender'] == 'male')
                                    $seat_class .= ' male';
                                else if ($seat['gender'] == 'female')
                                    $seat_class .= ' female';
                            }

                            echo '<div class="seat ' . $seat_class . '" data-seat="' . $seat['seat_number'] . '" 
                     data-booked="' . ($seat['is_booked'] ? 'true' : 'false') . '">';
                            echo $seat['seat_number'];
                            if ($seat['is_booked']) {
                                echo '<i class="fas fa-lock"></i>';
                                if ($seat['passenger_name']) {
                                    $first_name = explode(' ', $seat['passenger_name'])[0];
                                    echo '<div class="passenger-name">' . htmlspecialchars($first_name) . '</div>';
                                }
                            }
                            echo '</div>';
                        }
                    }
                    // Row 8: Back row with 5 seats
                    else if ($row_num == '9') {
                        // All 5 seats in one row
                        foreach ($row_seats as $seat) {
                            $seat_class = 'available';
                            if ($seat['is_booked']) {
                                $seat_class = 'booked';
                                if ($seat['gender'] == 'male')
                                    $seat_class .= ' male';
                                else if ($seat['gender'] == 'female')
                                    $seat_class .= ' female';
                            }

                            echo '<div class="seat ' . $seat_class . '" data-seat="' . $seat['seat_number'] . '" 
                     data-booked="' . ($seat['is_booked'] ? 'true' : 'false') . '">';
                            echo $seat['seat_number'];
                            if ($seat['is_booked']) {
                                echo '<i class="fas fa-lock"></i>';
                                if ($seat['passenger_name']) {
                                    $first_name = explode(' ', $seat['passenger_name'])[0];
                                    echo '<div class="passenger-name">' . htmlspecialchars($first_name) . '</div>';
                                }
                            }
                            echo '</div>';
                        }
                    }
                    // Regular rows (2, 4, 5, 6, 7)
                    else {
                        // Left side seats (A, B)
                        foreach (array_slice($row_seats, 0, 2) as $seat) {
                            $seat_class = 'available';
                            if ($seat['is_booked']) {
                                $seat_class = 'booked';
                                if ($seat['gender'] == 'male')
                                    $seat_class .= ' male';
                                else if ($seat['gender'] == 'female')
                                    $seat_class .= ' female';
                            }

                            echo '<div class="seat ' . $seat_class . '" data-seat="' . $seat['seat_number'] . '" 
             data-booked="' . ($seat['is_booked'] ? 'true' : 'false') . '">';
                            echo $seat['seat_number'];
                            if ($seat['is_booked']) {
                                echo '<i class="fas fa-lock"></i>';
                                if ($seat['passenger_name']) {
                                    $first_name = explode(' ', $seat['passenger_name'])[0];
                                    echo '<div class="passenger-name">' . htmlspecialchars($first_name) . '</div>';
                                }
                            }
                            echo '</div>';
                        }

                        // Walking area (must be here in DOM order)
                        echo '<div class="walking-area"></div>';

                        // Right side seats (C, D)
                        foreach (array_slice($row_seats, 2, 2) as $seat) {
                            $seat_class = 'available';
                            if ($seat['is_booked']) {
                                $seat_class = 'booked';
                                if ($seat['gender'] == 'male')
                                    $seat_class .= ' male';
                                else if ($seat['gender'] == 'female')
                                    $seat_class .= ' female';
                            }

                            echo '<div class="seat ' . $seat_class . '" data-seat="' . $seat['seat_number'] . '" 
             data-booked="' . ($seat['is_booked'] ? 'true' : 'false') . '">';
                            echo $seat['seat_number'];
                            if ($seat['is_booked']) {
                                echo '<i class="fas fa-lock"></i>';
                                if ($seat['passenger_name']) {
                                    $first_name = explode(' ', $seat['passenger_name'])[0];
                                    echo '<div class="passenger-name">' . htmlspecialchars($first_name) . '</div>';
                                }
                            }
                            echo '</div>';
                        }
                    }


                    echo '</div>';
                }
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
                                        name="booking_university_id"
                                        value="<?php echo $student_info ? $student_info['university_id'] : ''; ?>"
                                        readonly required>
                                </div>
                                <div class="col-md-6">
                                    <label for="passenger_name" class="form-label">Passenger Name</label>
                                    <input type="text" class="form-control" id="passenger_name" name="passenger_name"
                                        value="<?php echo $student_info ? $student_info['name'] : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="male"
                                                value="male" required>
                                            <label class="form-check-label" for="male">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="female"
                                                value="female">
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
        document.addEventListener('DOMContentLoaded', function () {
            const seats = document.querySelectorAll('.seat');
            const selectedSeatInput = document.getElementById('selected-seat');
            const bookingSection = document.getElementById('booking-section');
            const bookingUniversityId = document.getElementById('booking_university_id');
            const passengerName = document.getElementById('passenger_name');
            const cancelBtn = document.getElementById('cancel-booking');
            let selectedSeat = null;

            seats.forEach(seat => {
                seat.addEventListener('click', function () {
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

            cancelBtn.addEventListener('click', function () {
                // Remove selection
                seats.forEach(s => s.classList.remove('selected'));
                selectedSeat = null;
                selectedSeatInput.value = '';

                // Hide booking section
                bookingSection.style.display = 'none';
            });

            // Add selection styling to month checkboxes
            document.querySelectorAll('.month-checkbox').forEach(checkbox => {
                checkbox.addEventListener('click', function (e) {
                    if (e.target.type !== 'checkbox') {
                        const checkboxInput = this.querySelector('input[type="checkbox"]');
                        checkboxInput.checked = !checkboxInput.checked;
                    }
                    this.classList.toggle('selected', this.querySelector('input[type="checkbox"]').checked);
                });
            });

            // Get location data if available
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        console.log('Location captured:', position.coords);
                    },
                    function (error) {
                        console.log('Location access denied or unavailable');
                    }
                );
            }

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