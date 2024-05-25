<?php
// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "living_cozy";

// Using the mysqli object to create a connection and store it in the $db variable
$db = new mysqli($host, $user, $pass, $db_name);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
?>
