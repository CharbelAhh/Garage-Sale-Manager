<?php
$conn = mysqli_connect("localhost", "root", "", "garagedb");

function disconnect_db() {
    global $conn;
    mysqli_close($conn);
}
?>