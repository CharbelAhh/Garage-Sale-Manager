<?php
$conn = mysqli_connect("localhost", "root", "", "garagedb");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$query = "SELECT * FROM locations";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "Locations in database:\n";
    while($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row["LocationID"]. " - Name: " . $row["LocationName"]. "\n";
    }
} else {
    echo "No locations found in database.\n";
}

mysqli_close($conn);
?>