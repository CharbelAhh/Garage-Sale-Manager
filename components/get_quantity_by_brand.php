<?php
$conn = mysqli_connect("localhost", "root", "", "garagedb");

if (isset($_POST['productID']) && isset($_POST['brandID'])) {
    $productID = mysqli_real_escape_string($conn, $_POST['productID']);
    $brandID = intval($_POST['brandID']);

    if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
        // If a location is selected, get quantity for that specific location
        $locationID = $_COOKIE['current_location'];
        $query = "SELECT ls.Quantity FROM productbrands pb
                  JOIN locationstock ls ON pb.RelationID = ls.RelationID
                  WHERE pb.ProductID = '$productID' AND pb.BrandID = $brandID AND ls.LocationID = '$locationID'";
    } else {
        // If no location is selected, get total quantity across all locations
        $query = "SELECT SUM(ls.Quantity) AS Quantity FROM productbrands pb
                  JOIN locationstock ls ON pb.RelationID = ls.RelationID
                  WHERE pb.ProductID = '$productID' AND pb.BrandID = $brandID
                  GROUP BY pb.RelationID";
    }

    $result = mysqli_query($conn, $query);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $quantity = intval($row['Quantity']);

        if ($quantity > 0) {
            echo '<option value="">Select</option>';
            for ($i = 1; $i <= $quantity; $i++) {
                echo "<option value='$i'>$i</option>";
            }
        } else {
            echo '<option value="">Out of stock</option>';
        }
    } else {
        // When no location is selected and there's no stock, or when a location is selected and there's no stock in that location
        echo '<option value="">Not available</option>';
    }
}
?>