<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "garagedb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Clean up duplicate entries in productbrands table
// Keep only the entry with the highest RelationID for each ProductID/BrandID combination
$sql = "SELECT ProductID, BrandID, MAX(RelationID) as MaxID
        FROM productbrands 
        GROUP BY ProductID, BrandID 
        HAVING COUNT(*) > 1";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $productID = $conn->real_escape_string($row['ProductID']);
        $brandID = $conn->real_escape_string($row['BrandID']);
        $maxID = $conn->real_escape_string($row['MaxID']);
        
        // Delete all duplicate entries except the one with the highest RelationID
        $deleteSql = "DELETE FROM productbrands 
                      WHERE ProductID='$productID' 
                      AND BrandID='$brandID' 
                      AND RelationID != $maxID";
        
        if ($conn->query($deleteSql) === TRUE) {
            echo "Deleted duplicate entries for ProductID: $productID, BrandID: $brandID<br>";
        } else {
            echo "Error deleting duplicates: " . $conn->error . "<br>";
        }
    }
} else {
    echo "No duplicate entries found.<br>";
}

$conn->close();
?>