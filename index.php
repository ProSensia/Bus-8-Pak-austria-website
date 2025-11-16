<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Coaster Bus Seat Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bus-color: #2c3e50;
            --seat-available: #95a5a6;
            --seat-male: #3498db;
            --seat-female: #e84393;
            --seat-booked: #7f8c8d;
            --seat-selected: #2ecc71;
            --floor-color: #34495e;
            --window-color: #3498db;
            --aisle-color: #ecf0f1;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .bus-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .bus-3d {
            background: var(--bus-color);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            transform: perspective(1000px) rotateX(5deg);
            transition: transform 0.5s;
        }
        
        .bus-3d:hover {
            transform: perspective(1000px) rotateX(2deg);
        }
        
        .bus-front {
            background: linear-gradient(to bottom, #e74c3c, #c0392b);
            height: 80px;
            border-radius: 15px 15px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-bottom: 20px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .bus-front:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 20%;
            width: 60%;
            height: 10px;
            background: #2c3e50;
            border-radius: 0 0 5px 5px;
        }
        
        .bus-body {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .bus-row {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }
        
        .row-label {
            width: 30px;
            text-align: center;
            color: white;
            font-weight: bold;
            background: rgba(0,0,0,0.3);
            padding: 5px;
            border-radius: 5px;
        }
        
        .seats-container {
            display: flex;
            gap: 10px;
            flex: 1;
        }
        
        .left-seats, .right-seats {
            display: flex;
            gap: 8px;
        }
        
        .seat {
            width: 50px;
            height: 60px;
            background: var(--seat-available);
            border-radius: 8px 8px 3px 3px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transform: perspective(200px) rotateX(10deg);
            overflow: hidden;
        }
        
        .seat:hover:not(.booked) {
            transform: perspective(200px) rotateX(10deg) translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.3);
        }
        
        .seat.selected {
            background: var(--seat-selected);
            box-shadow: 0 0 15px rgba(46, 204, 113, 0.7);
        }
        
        .seat.male {
            background: var(--seat-male);
        }
        
        .seat.female {
            background: var(--seat-female);
        }
        
        .seat.booked {
            background: var(--seat-booked);
            cursor: not-allowed;
            opacity: 0.8;
        }
        
        .seat-number {
            font-size: 12px;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .passenger-name {
            font-size: 9px;
            color: white;
            text-align: center;
            margin-top: 2px;
            line-height: 1;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            max-width: 90%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .aisle {
            flex: 1;
            height: 60px;
            background: var(--aisle-color);
            border-radius: 5px;
            margin: 0 10px;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
        }
        
        .door {
            width: 70px;
            height: 100px;
            background: linear-gradient(to bottom, #8B4513, #654321);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            margin-left: 20px;
        }
        
        .bus-floor {
            height: 20px;
            background: var(--floor-color);
            border-radius: 0 0 10px 10px;
            margin-top: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .bus-windows {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }
        
        .window {
            position: absolute;
            background: var(--window-color);
            border-radius: 5px;
            opacity: 0.2;
        }
        
        .window-left {
            left: 10px;
            top: 20%;
            width: 5px;
            height: 60%;
        }
        
        .window-right {
            right: 10px;
            top: 20%;
            width: 5px;
            height: 60%;
        }
        
        .legend {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.9);
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 8px;
        }
        
        .student-info {
            background: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            color: white;
        }
        
        .badge-submitted {
            background-color: #28a745;
        }
        
        .badge-pending {
            background-color: #dc3545;
        }
        
        .booking-form {
            background: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .seat-preview {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="bus-container">
        <h1 class="text-center mb-4 text-white"><i class="fas fa-bus"></i> 3D Coaster Bus Seat Booking</h1>
        
        <!-- University ID Verification -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-id-card"></i> Verify University ID</h5>
            </div>
            <div class="card-body">
                <form id="verify-form" class="row g-3">
                    <div class="col-md-8">
                        <label for="university_id" class="form-label">University ID</label>
                        <input type="text" class="form-control" id="university_id" 
                               placeholder="Enter your University ID" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check"></i> Verify ID
                        </button>
                    </div>
                </form>
                
                <div id="student-info" class="student-info mt-3" style="display: none;">
                    <h6>Student Information:</h6>
                    <p><strong>Name:</strong> <span id="info-name">-</span></p>
                    <p><strong>University ID:</strong> <span id="info-university-id">-</span></p>
                    <p><strong>Semester:</strong> <span id="info-semester">-</span></p>
                    <p><strong>Category:</strong> <span id="info-category">-</span></p>
                    
                    <h6 class="mt-3">Fee Status:</h6>
                    <div class="fee-status">
                        <div class="fee-month">
                            <div>September</div>
                            <span id="fee-sep" class="fee-badge badge-pending">Pending</span>
                        </div>
                        <div class="fee-month">
                            <div>October</div>
                            <span id="fee-oct" class="fee-badge badge-pending">Pending</span>
                        </div>
                        <div class="fee-month">
                            <div>November</div>
                            <span id="fee-nov" class="fee-badge badge-pending">Pending</span>
                        </div>
                        <div class="fee-month">
                            <div>December</div>
                            <span id="fee-dec" class="fee-badge badge-pending">Pending</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 3D Bus Layout -->
        <div class="bus-3d">
            <div class="bus-front">
                <i class="fas fa-user-tie me-2"></i> DRIVER
            </div>
            
            <div class="bus-body">
                <!-- Row 1: 2 seats -->
                <div class="bus-row">
                    <div class="row-label">1</div>
                    <div class="seats-container">
                        <div class="left-seats">
                            <div class="seat available" data-seat="1A">
                                <div class="seat-number">1A</div>
                            </div>
                            <div class="seat available" data-seat="1B">
                                <div class="seat-number">1B</div>
                            </div>
                        </div>
                        <div class="aisle"></div>
                        <div class="right-seats"></div>
                    </div>
                </div>
                
                <!-- Row 2: 4 seats -->
                <div class="bus-row">
                    <div class="row-label">2</div>
                    <div class="seats-container">
                        <div class="left-seats">
                            <div class="seat available" data-seat="2A">
                                <div class="seat-number">2A</div>
                            </div>
                            <div class="seat available" data-seat="2B">
                                <div class="seat-number">2B</div>
                            </div>
                        </div>
                        <div class="aisle"></div>
                        <div class="right-seats">
                            <div class="seat available" data-seat="2C">
                                <div class="seat-number">2C</div>
                            </div>
                            <div class="seat available" data-seat="2D">
                                <div class="seat-number">2D</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Row 3: 2 seats + door -->
                <div class="bus-row">
                    <div class="row-label">3</div>
                    <div class="seats-container">
                        <div class="left-seats">
                            <div class="seat available" data-seat="3A">
                                <div class="seat-number">3A</div>
                            </div>
                            <div class="seat available" data-seat="3B">
                                <div class="seat-number">3B</div>
                            </div>
                        </div>
                        <div class="aisle"></div>
                        <div class="right-seats">
                            <div class="door">DOOR</div>
                        </div>
                    </div>
                </div>
                
                <!-- Row 4: 4 seats -->
                <div class="bus-row">
                    <div class="row-label">4</div>
                    <div class="seats-container">
                        <div class="left-seats">
                            <div class="seat available" data-seat="4A">
                                <div class="seat-number">4A</div>
                            </div>
                            <div class="seat available" data-seat="4B">
                                <div class="seat-number">4B</div>
                            </div>
                        </div>
                        <div class="aisle"></div>
                        <div class="right-seats">
                            <div class="seat available" data-seat="4C">
                                <div class="seat-number">4C</div>
                            </div>
                            <div class="seat available" data-seat="4D">
                                <div class="seat-number">4D</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Row 5: 4 seats -->
                <div class="bus-row">
                    <div class="row-label">5</div>
                    <div class="seats-container">
                        <div class="left-seats">
                            <div class="seat available" data-seat="5A">
                                <div class="seat-number">5A</div>
                            </div>
                            <div class="seat available" data-seat="5B">
                                <div class="seat-number">5B</div>
                            </div>
                        </div>
                        <div class="aisle"></div>
                        <div class="right-seats">
                            <div class="seat available" data-seat="5C">
                                <div class="seat-number">5C</div>
                            </div>
                            <div class="seat available" data-seat="5D">
                                <div class="seat-number">5D</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Row 6: 4 seats -->
                <div class="bus-row">
                    <div class="row-label">6</div>
                    <div class="seats-container">
                        <div class="left-seats">
                            <div class="seat available" data-seat="6A">
                                <div class="seat-number">6A</div>
                            </div>
                            <div class="seat available" data-seat="6B">
                                <div class="seat-number">6B</div>
                            </div>
                        </div>
                        <div class="aisle"></div>
                        <div class="right-seats">
                            <div class="seat available" data-seat="6C">
                                <div class="seat-number">6C</div>
                            </div>
                            <div class="seat available" data-seat="6D">
                                <div class="seat-number">6D</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Row 7: 4 seats -->
                <div class="bus-row">
                    <div class="row-label">7</div>
                    <div class="seats-container">
                        <div class="left-seats">
                            <div class="seat available" data-seat="7A">
                                <div class="seat-number">7A</div>
                            </div>
                            <div class="seat available" data-seat="7B">
                                <div class="seat-number">7B</div>
                            </div>
                        </div>
                        <div class="aisle"></div>
                        <div class="right-seats">
                            <div class="seat available" data-seat="7C">
                                <div class="seat-number">7C</div>
                            </div>
                            <div class="seat available" data-seat="7D">
                                <div class="seat-number">7D</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Row 8: 5 seats -->
                <div class="bus-row">
                    <div class="row-label">8</div>
                    <div class="seats-container">
                        <div class="left-seats">
                            <div class="seat available" data-seat="8A">
                                <div class="seat-number">8A</div>
                            </div>
                            <div class="seat available" data-seat="8B">
                                <div class="seat-number">8B</div>
                            </div>
                        </div>
                        <div class="aisle"></div>
                        <div class="right-seats">
                            <div class="seat available" data-seat="8C">
                                <div class="seat-number">8C</div>
                            </div>
                            <div class="seat available" data-seat="8D">
                                <div class="seat-number">8D</div>
                            </div>
                            <div class="seat available" data-seat="8E">
                                <div class="seat-number">8E</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bus-floor"></div>
            <div class="bus-windows">
                <div class="window window-left"></div>
                <div class="window window-right"></div>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background-color: var(--seat-available);"></div>
                <span>Available Seat</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: var(--seat-male);"></div>
                <span>Male Booked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: var(--seat-female);"></div>
                <span>Female Booked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: var(--seat-booked);"></div>
                <span>Booked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: var(--seat-selected);"></div>
                <span>Selected</span>
            </div>
        </div>
        
        <!-- Booking Form -->
        <div id="booking-form" class="booking-form" style="display: none;">
            <h5 class="text-center mb-4"><i class="fas fa-ticket-alt"></i> Book Your Seat</h5>
            
            <div class="row">
                <div class="col-md-4 text-center">
                    <div id="seat-preview" class="seat-preview" style="background-color: var(--seat-available);">
                        <div class="seat-number" id="preview-seat-number">-</div>
                        <div class="passenger-name" id="preview-passenger-name">Your Name</div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <form id="booking-details">
                        <input type="hidden" id="selected-seat-number">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="booking-university-id" class="form-label">University ID</label>
                                <input type="text" class="form-control" id="booking-university-id" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="passenger-name" class="form-label">Passenger Name</label>
                                <input type="text" class="form-control" id="passenger-name" required>
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
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-check"></i> Confirm Booking
                                </button>
                                <button type="button" id="cancel-booking" class="btn btn-secondary w-100 mt-2">
                                    <i class="fas fa-times"></i> Cancel Selection
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Admin Login Link -->
        <div class="text-center mt-4">
            <a href="admin.php" class="btn btn-outline-light">
                <i class="fas fa-cog"></i> Admin Panel
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const seats = document.querySelectorAll('.seat.available');
            const studentInfo = document.getElementById('student-info');
            const bookingForm = document.getElementById('booking-form');
            const seatPreview = document.getElementById('seat-preview');
            const previewSeatNumber = document.getElementById('preview-seat-number');
            const previewPassengerName = document.getElementById('preview-passenger-name');
            const selectedSeatNumber = document.getElementById('selected-seat-number');
            const bookingUniversityId = document.getElementById('booking-university-id');
            const passengerName = document.getElementById('passenger-name');
            const cancelBtn = document.getElementById('cancel-booking');
            const verifyForm = document.getElementById('verify-form');
            const bookingDetails = document.getElementById('booking-details');
            
            let selectedSeat = null;
            let currentStudent = null;
            
            // University ID verification
            verifyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const universityId = document.getElementById('university_id').value.trim();
                
                if (universityId) {
                    // Simulate API call - replace with actual backend call
                    simulateVerification(universityId);
                }
            });
            
            // Seat selection
            seats.forEach(seat => {
                seat.addEventListener('click', function() {
                    if (!currentStudent) {
                        alert('Please verify your University ID first!');
                        return;
                    }
                    
                    // Remove selection from all seats
                    seats.forEach(s => s.classList.remove('selected'));
                    
                    // Add selection to clicked seat
                    this.classList.add('selected');
                    selectedSeat = this;
                    
                    // Update preview
                    const seatNum = this.getAttribute('data-seat');
                    previewSeatNumber.textContent = seatNum;
                    selectedSeatNumber.value = seatNum;
                    
                    // Show booking form
                    bookingForm.style.display = 'block';
                    bookingForm.scrollIntoView({ behavior: 'smooth' });
                });
            });
            
            // Gender selection updates preview
            document.querySelectorAll('input[name="gender"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        if (this.value === 'male') {
                            seatPreview.style.backgroundColor = 'var(--seat-male)';
                        } else {
                            seatPreview.style.backgroundColor = 'var(--seat-female)';
                        }
                    }
                });
            });
            
            // Passenger name updates preview
            passengerName.addEventListener('input', function() {
                previewPassengerName.textContent = this.value || 'Your Name';
            });
            
            // Cancel booking
            cancelBtn.addEventListener('click', function() {
                if (selectedSeat) {
                    selectedSeat.classList.remove('selected');
                    selectedSeat = null;
                }
                bookingForm.style.display = 'none';
            });
            
            // Booking submission
            bookingDetails.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!selectedSeat) {
                    alert('Please select a seat first!');
                    return;
                }
                
                const gender = document.querySelector('input[name="gender"]:checked');
                if (!gender) {
                    alert('Please select your gender!');
                    return;
                }
                
                // Simulate booking - replace with actual backend call
                simulateBooking();
            });
            
            // Simulate verification (replace with actual API call)
            function simulateVerification(universityId) {
                // Sample student data - replace with actual data from backend
                const sampleStudents = {
                    'B22F1181A1056': {
                        name: 'Monnin Khan',
                        university_id: 'B22F1181A1056',
                        semester: '7',
                        category: 'Student',
                        fees: {
                            september: 'Submitted',
                            october: 'Submitted',
                            november: 'Submitted',
                            december: 'Submitted'
                        }
                    },
                    'B24S0950A1005': {
                        name: 'Umanna Jadoon',
                        university_id: 'B24S0950A1005',
                        semester: '4',
                        category: 'Student',
                        fees: {
                            september: 'Submitted',
                            october: 'Submitted',
                            november: 'Submitted',
                            december: 'Submitted'
                        }
                    }
                };
                
                if (sampleStudents[universityId]) {
                    currentStudent = sampleStudents[universityId];
                    
                    // Update student info display
                    document.getElementById('info-name').textContent = currentStudent.name;
                    document.getElementById('info-university-id').textContent = currentStudent.university_id;
                    document.getElementById('info-semester').textContent = currentStudent.semester;
                    document.getElementById('info-category').textContent = currentStudent.category;
                    
                    // Update fee status
                    document.getElementById('fee-sep').textContent = currentStudent.fees.september;
                    document.getElementById('fee-sep').className = `fee-badge ${currentStudent.fees.september === 'Submitted' ? 'badge-submitted' : 'badge-pending'}`;
                    
                    document.getElementById('fee-oct').textContent = currentStudent.fees.october;
                    document.getElementById('fee-oct').className = `fee-badge ${currentStudent.fees.october === 'Submitted' ? 'badge-submitted' : 'badge-pending'}`;
                    
                    document.getElementById('fee-nov').textContent = currentStudent.fees.november;
                    document.getElementById('fee-nov').className = `fee-badge ${currentStudent.fees.november === 'Submitted' ? 'badge-submitted' : 'badge-pending'}`;
                    
                    document.getElementById('fee-dec').textContent = currentStudent.fees.december;
                    document.getElementById('fee-dec').className = `fee-badge ${currentStudent.fees.december === 'Submitted' ? 'badge-submitted' : 'badge-pending'}`;
                    
                    // Update booking form
                    bookingUniversityId.value = currentStudent.university_id;
                    passengerName.value = currentStudent.name;
                    previewPassengerName.textContent = currentStudent.name;
                    
                    // Show student info
                    studentInfo.style.display = 'block';
                    
                    alert('University ID verified successfully!');
                } else {
                    alert('University ID not found!');
                }
            }
            
            // Simulate booking (replace with actual API call)
            function simulateBooking() {
                const seatNum = selectedSeatNumber.value;
                const gender = document.querySelector('input[name="gender"]:checked').value;
                const name = passengerName.value;
                
                // Update seat appearance
                selectedSeat.classList.remove('available', 'selected');
                selectedSeat.classList.add('booked', gender);
                
                // Add passenger name to seat
                const passengerNameEl = document.createElement('div');
                passengerNameEl.className = 'passenger-name';
                passengerNameEl.textContent = name;
                selectedSeat.appendChild(passengerNameEl);
                
                // Show success message
                alert(`Seat ${seatNum} booked successfully for ${name}!`);
                
                // Reset form
                bookingForm.style.display = 'none';
                selectedSeat = null;
                
                // Reset preview
                seatPreview.style.backgroundColor = 'var(--seat-available)';
                document.querySelector('input[name="gender"]').checked = false;
            }
        });
    </script>
</body>
</html>