<?php
// Set Timezone to East Africa Time (Tanzania)
date_default_timezone_set('Africa/Dar_es_Salaam');
$timezone_offset = $env['TIMEZONE_OFFSET'];
$servername = $env['DB_HOST']; 
$username   = $env['DB_USER'];           
$password   = $env['DB_PASS'];     
$dbname     = $env['DB_NAME'];  

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query("SET time_zone='$timezone_offset'");
?>