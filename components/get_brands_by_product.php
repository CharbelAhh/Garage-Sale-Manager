<?php
$conn = mysqli_connect("localhost", "root", "", "garagedb");

if (isset($_POST['productID'])) {
    $productID = mysqli_real_escape_string($conn, $_POST['productID']);

    if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
        // If a location is selected, only show brands that have stock in that location
        $locationID = $_COOKIE['current_location'];
        $query = "
            SELECT b.BrandID, b.BrandName
            FROM productbrands pb
            JOIN brand b ON pb.BrandID = b.BrandID
            JOIN locationstock ls ON pb.RelationID = ls.RelationID
            WHERE pb.ProductID = '$productID' AND ls.LocationID = '$locationID' AND ls.Quantity > 0
        ";
    } else {
        // If no location is selected, show all brands for this product
        $query = "
            SELECT b.BrandID, b.BrandName
            FROM productbrands pb
            JOIN brand b ON pb.BrandID = b.BrandID
            WHERE pb.ProductID = '$productID'
        ";
    }

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        echo '<option value="">Select</option>';
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='{$row['BrandID']}'>{$row['BrandName']}</option>";
        }
    } else {
        echo '<option value="">No brands available for this product</option>';
    }
}
?>