<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toyota Coaster 34-Seater Bus Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            border-radius: 15px 15px 0 0 !important;
            background: linear-gradient(to right, #2c3e50, #4a6491);
            color: white;
        }
        
        .bus-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        #busCanvas {
            width: 100%;
            height: 500px;
            border: 2px solid #2c3e50;
            border-radius: 10px;
            background: #f8f9fa;
            cursor: pointer;
        }
        
        .legend {
            display: flex;
            justify-content: center;
            margin-top: 20px;
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
        
        .seat-info {
            background: #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .booking-form {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .student-info {
            background: #e7f3ff;
            border-radius: 10px;
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
            color: white;
        }
        
        .badge-submitted {
            background-color: #28a745;
        }
        
        .badge-pending {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4 text-white"><i class="fas fa-bus"></i> Toyota Coaster 34-Seater Bus Booking</h1>
        
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
        
        <!-- Bus Canvas -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-bus"></i> Bus Layout - Top-Down View</h5>
            </div>
            <div class="card-body">
                <div class="bus-container">
                    <canvas id="busCanvas"></canvas>
                    
                    <div class="seat-info" id="seat-info" style="display: none;">
                        <h6>Selected Seat: <span id="selected-seat-number">-</span></h6>
                        <p><strong>Status:</strong> <span id="seat-status">Available</span></p>
                        <p><strong>Passenger:</strong> <span id="seat-passenger">-</span></p>
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #95a5a6;"></div>
                        <span>Available Seat</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #3498db;"></div>
                        <span>Male Booked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #e84393;"></div>
                        <span>Female Booked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #2ecc71;"></div>
                        <span>Selected</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #7f8c8d;"></div>
                        <span>Booked</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Booking Form -->
        <div id="booking-form" class="booking-form" style="display: none;">
            <h5 class="text-center mb-4"><i class="fas fa-ticket-alt"></i> Book Your Seat</h5>
            
            <form id="booking-details">
                <input type="hidden" id="selected-seat-number" name="selected_seat_number">
                
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
            const canvas = document.getElementById('busCanvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas dimensions
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            
            const studentInfo = document.getElementById('student-info');
            const bookingForm = document.getElementById('booking-form');
            const seatInfo = document.getElementById('seat-info');
            const selectedSeatNumber = document.getElementById('selected-seat-number');
            const bookingUniversityId = document.getElementById('booking-university-id');
            const passengerName = document.getElementById('passenger-name');
            const cancelBtn = document.getElementById('cancel-booking');
            const verifyForm = document.getElementById('verify-form');
            const bookingDetails = document.getElementById('booking-details');
            
            let selectedSeat = null;
            let currentStudent = null;
            let seats = {};
            
            // Initialize seats data
            function initializeSeats() {
                // Define all 34 seats with their positions
                const seatData = [
                    // Row 1: Driver + 2 seats
                    { id: 'driver', row: 1, col: 1, x: 50, y: 100, width: 60, height: 80, type: 'driver' },
                    { id: '1A', row: 1, col: 2, x: 150, y: 100, width: 40, height: 60 },
                    { id: '1B', row: 1, col: 3, x: 200, y: 100, width: 40, height: 60 },
                    
                    // Row 2: 4 seats
                    { id: '2A', row: 2, col: 1, x: 150, y: 180, width: 40, height: 60 },
                    { id: '2B', row: 2, col: 2, x: 200, y: 180, width: 40, height: 60 },
                    { id: '2C', row: 2, col: 3, x: 300, y: 180, width: 40, height: 60 },
                    { id: '2D', row: 2, col: 4, x: 350, y: 180, width: 40, height: 60 },
                    
                    // Row 3: 2 seats + door
                    { id: '3A', row: 3, col: 1, x: 150, y: 260, width: 40, height: 60 },
                    { id: '3B', row: 3, col: 2, x: 200, y: 260, width: 40, height: 60 },
                    
                    // Row 4: 4 seats
                    { id: '4A', row: 4, col: 1, x: 150, y: 340, width: 40, height: 60 },
                    { id: '4B', row: 4, col: 2, x: 200, y: 340, width: 40, height: 60 },
                    { id: '4C', row: 4, col: 3, x: 300, y: 340, width: 40, height: 60 },
                    { id: '4D', row: 4, col: 4, x: 350, y: 340, width: 40, height: 60 },
                    
                    // Row 5: 4 seats
                    { id: '5A', row: 5, col: 1, x: 150, y: 420, width: 40, height: 60 },
                    { id: '5B', row: 5, col: 2, x: 200, y: 420, width: 40, height: 60 },
                    { id: '5C', row: 5, col: 3, x: 300, y: 420, width: 40, height: 60 },
                    { id: '5D', row: 5, col: 4, x: 350, y: 420, width: 40, height: 60 },
                    
                    // Row 6: 4 seats
                    { id: '6A', row: 6, col: 1, x: 150, y: 500, width: 40, height: 60 },
                    { id: '6B', row: 6, col: 2, x: 200, y: 500, width: 40, height: 60 },
                    { id: '6C', row: 6, col: 3, x: 300, y: 500, width: 40, height: 60 },
                    { id: '6D', row: 6, col: 4, x: 350, y: 500, width: 40, height: 60 },
                    
                    // Row 7: 4 seats
                    { id: '7A', row: 7, col: 1, x: 150, y: 580, width: 40, height: 60 },
                    { id: '7B', row: 7, col: 2, x: 200, y: 580, width: 40, height: 60 },
                    { id: '7C', row: 7, col: 3, x: 300, y: 580, width: 40, height: 60 },
                    { id: '7D', row: 7, col: 4, x: 350, y: 580, width: 40, height: 60 },
                    
                    // Row 8: 4 seats
                    { id: '8A', row: 8, col: 1, x: 150, y: 660, width: 40, height: 60 },
                    { id: '8B', row: 8, col: 2, x: 200, y: 660, width: 40, height: 60 },
                    { id: '8C', row: 8, col: 3, x: 300, y: 660, width: 40, height: 60 },
                    { id: '8D', row: 8, col: 4, x: 350, y: 660, width: 40, height: 60 },
                    
                    // Row 9: 5 seats
                    { id: '9A', row: 9, col: 1, x: 150, y: 740, width: 40, height: 60 },
                    { id: '9B', row: 9, col: 2, x: 200, y: 740, width: 40, height: 60 },
                    { id: '9C', row: 9, col: 3, x: 300, y: 740, width: 40, height: 60 },
                    { id: '9D', row: 9, col: 4, x: 350, y: 740, width: 40, height: 60 },
                    { id: '9E', row: 9, col: 5, x: 400, y: 740, width: 40, height: 60 }
                ];
                
                // Initialize all seats as available
                seatData.forEach(seat => {
                    seats[seat.id] = {
                        ...seat,
                        booked: false,
                        passengerName: '',
                        gender: '',
                        selected: false
                    };
                });
                
                // Mark some seats as booked for demonstration
                seats['1A'].booked = true;
                seats['1A'].passengerName = 'John Smith';
                seats['1A'].gender = 'male';
                
                seats['2C'].booked = true;
                seats['2C'].passengerName = 'Emma Johnson';
                seats['2C'].gender = 'female';
                
                seats['4B'].booked = true;
                seats['4B'].passengerName = 'Michael Brown';
                seats['4B'].gender = 'male';
                
                seats['6D'].booked = true;
                seats['6D'].passengerName = 'Sarah Davis';
                seats['6D'].gender = 'female';
            }
            
            // Draw the bus
            function drawBus() {
                // Clear canvas
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Draw bus outline
                ctx.fillStyle = '#2c3e50';
                ctx.fillRect(40, 50, 420, 780);
                
                // Draw bus windows
                ctx.fillStyle = '#3498db';
                ctx.fillRect(45, 55, 410, 40); // Front window
                
                // Side windows
                for (let i = 0; i < 8; i++) {
                    ctx.fillRect(45, 120 + i * 80, 410, 30);
                }
                
                // Draw aisle
                ctx.fillStyle = '#ecf0f1';
                ctx.fillRect(250, 100, 20, 700);
                
                // Draw door
                ctx.fillStyle = '#8B4513';
                ctx.fillRect(45, 260, 40, 100);
                ctx.fillStyle = '#cd853f';
                ctx.fillRect(50, 265, 30, 90);
                
                // Draw seats
                Object.values(seats).forEach(seat => {
                    if (seat.type === 'driver') {
                        // Draw driver seat
                        ctx.fillStyle = '#e74c3c';
                        ctx.fillRect(seat.x, seat.y, seat.width, seat.height);
                        ctx.fillStyle = 'white';
                        ctx.font = '12px Arial';
                        ctx.fillText('DRIVER', seat.x + 5, seat.y + 45);
                    } else {
                        // Determine seat color based on status
                        if (seat.selected) {
                            ctx.fillStyle = '#2ecc71'; // Selected
                        } else if (seat.booked) {
                            if (seat.gender === 'male') {
                                ctx.fillStyle = '#3498db'; // Male
                            } else if (seat.gender === 'female') {
                                ctx.fillStyle = '#e84393'; // Female
                            } else {
                                ctx.fillStyle = '#7f8c8d'; // Booked (unknown gender)
                            }
                        } else {
                            ctx.fillStyle = '#95a5a6'; // Available
                        }
                        
                        // Draw seat
                        ctx.fillRect(seat.x, seat.y, seat.width, seat.height);
                        
                        // Draw seat number
                        ctx.fillStyle = 'white';
                        ctx.font = '12px Arial';
                        ctx.fillText(seat.id, seat.x + 10, seat.y + 35);
                        
                        // Draw passenger name if booked
                        if (seat.booked && seat.passengerName) {
                            ctx.fillStyle = 'white';
                            ctx.font = '10px Arial';
                            const shortName = seat.passengerName.length > 8 ? 
                                seat.passengerName.substring(0, 8) + '...' : seat.passengerName;
                            ctx.fillText(shortName, seat.x + 5, seat.y + 50);
                        }
                    }
                });
                
                // Draw labels
                ctx.fillStyle = '#2c3e50';
                ctx.font = '14px Arial';
                ctx.fillText('FRONT', 200, 40);
                ctx.fillText('DOOR', 30, 310);
            }
            
            // Handle canvas click
            canvas.addEventListener('click', function(event) {
                if (!currentStudent) {
                    alert('Please verify your University ID first!');
                    return;
                }
                
                const rect = canvas.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                
                // Check if any seat was clicked
                Object.values(seats).forEach(seat => {
                    if (seat.type === 'driver') return; // Skip driver seat
                    
                    if (x >= seat.x && x <= seat.x + seat.width &&
                        y >= seat.y && y <= seat.y + seat.height) {
                        
                        if (seat.booked) {
                            // Show booked seat info
                            document.getElementById('selected-seat-number').textContent = seat.id;
                            document.getElementById('seat-status').textContent = 'Booked';
                            document.getElementById('seat-passenger').textContent = seat.passengerName;
                            seatInfo.style.display = 'block';
                            bookingForm.style.display = 'none';
                        } else {
                            // Select available seat
                            if (selectedSeat) {
                                selectedSeat.selected = false;
                            }
                            
                            seat.selected = true;
                            selectedSeat = seat;
                            
                            // Update seat info
                            document.getElementById('selected-seat-number').textContent = seat.id;
                            document.getElementById('seat-status').textContent = 'Available';
                            document.getElementById('seat-passenger').textContent = '-';
                            seatInfo.style.display = 'block';
                            
                            // Show booking form
                            bookingForm.style.display = 'block';
                            selectedSeatNumber.value = seat.id;
                            bookingForm.scrollIntoView({ behavior: 'smooth' });
                        }
                        
                        // Redraw bus
                        drawBus();
                    }
                });
            });
            
            // University ID verification
            verifyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const universityId = document.getElementById('university_id').value.trim();
                
                if (universityId) {
                    // Simulate API call - replace with actual backend call
                    simulateVerification(universityId);
                }
            });
            
            // Cancel booking
            cancelBtn.addEventListener('click', function() {
                if (selectedSeat) {
                    selectedSeat.selected = false;
                    selectedSeat = null;
                }
                bookingForm.style.display = 'none';
                seatInfo.style.display = 'none';
                drawBus();
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
                
                // Update seat data
                selectedSeat.booked = true;
                selectedSeat.passengerName = name;
                selectedSeat.gender = gender;
                selectedSeat.selected = false;
                
                // Update seat info
                document.getElementById('seat-status').textContent = 'Booked';
                document.getElementById('seat-passenger').textContent = name;
                
                // Show success message
                alert(`Seat ${seatNum} booked successfully for ${name}!`);
                
                // Reset form
                bookingForm.style.display = 'none';
                selectedSeat = null;
                
                // Redraw bus
                drawBus();
            }
            
            // Initialize and draw
            initializeSeats();
            drawBus();
            
            // Handle window resize
            window.addEventListener('resize', function() {
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;
                drawBus();
            });
        });
    </script>
</body>
</html>