<?php
header('Content-Type: text/html; charset=utf-8');
include __DIR__ . '/db_connection.php';

// Build the same section HTML as in reports.php but for the given month/year
ob_start();
$clientID = $_POST['ClientID'];
$table = $_POST['Table'];
$orderID = $_POST['OrderID'];
?>
<div class="order-info-top">
    <span class="order-title"><?php echo "Order #$orderID"; ?></span>
</div>
<div class="order-items-list">
    <?php
        $query = $conn->prepare("SELECT * FROM `$table` WHERE `OrderID`=?");
        $query->bind_param("i",$orderID);
        $query->execute();
        $queryRes = $query->get_result();

        while ($row = $queryRes->fetch_array())
        {
            $orderItemID = $row['OrderItemID'];
            $quantity = $row['Quantity'];
            $cost = $row['UnitPrice'];
            $relation = $row['RelationID'];

            $queryProduct = $conn->prepare("SELECT `ProductID`,`BrandID` FROM `productbrands` WHERE `RelationID`=? LIMIT 1");
            $queryProduct->bind_param("i",$relation);
            $queryProduct->execute();
            $queryProductRes = $queryProduct->get_result()->fetch_assoc();
            $product = $queryProductRes["ProductID"];

            $brandid = $queryProductRes["BrandID"];
            $queryBrand = $conn->prepare("SELECT `BrandName` FROM `brand` WHERE `BrandID`=? LIMIT 1");
            $queryBrand->bind_param("i",$brandid);
            $queryBrand->execute();
            $queryBrandRes = $queryBrand->get_result()->fetch_assoc();
            $brand = $queryBrandRes["BrandName"];

            $finalprice = $cost*$quantity;

            echo
            "<div class='order-item'>
                <div class='order-item-top'>
                    <span>Order Item #$orderItemID</span>
                </div>
                <div class='order-item-content'>
                    <p><b class='label'>Product:</b><span class='value'>$product</span></p>
                    <p><b class='label'>Brand:</b><span class='value'>$brand</span></p>
                    <p><b class='label'>Quantity:</b><span class='value'>$quantity</span></p>
                    <p><b class='label'>Cost:</b><span class='value'>$finalprice$</span></p>";
                    echo "<button class='del-item-btn' onclick='openDelItem($orderItemID)'><i class='fa fa-angle-down'></i> Delete item</button>";
                    echo "<form action='manage.php' method='POST' class='item-del-form' id='item-del-form-$orderItemID'>
                    <input type='text' hidden value='$orderItemID' name='del-order-item'/>
                    <input type='text' hidden value='$orderID' name='del-order-id'/>
                    <input type='text' hidden value='$table' name='del-order-table'/>
                    <span>Are you sure you want to delete this order's item?</span>
                    <button type='submit'>Confirm deletion</button>
                    </form>";
            echo "</div>";
        }
    ?>
</div>
<script>
</script>
<?php
$html = ob_get_clean();
echo $html;
$conn->close();
?>