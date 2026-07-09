<?php
$conn = mysqli_connect("localhost", "root", "", "garagedb");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>