<?php
$servername = "premium281.web-hosting.com";
$username = "prosdfwo_bus8user";
$password = "Bus8PakAustria";
$dbname   = "prosdfwo_bus_8_db";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>