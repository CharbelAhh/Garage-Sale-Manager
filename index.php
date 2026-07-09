<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garage</title>

    <link rel="stylesheet" href="styles/style.css?v=<?=time()?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/charts.css/dist/charts.min.css">
    <script src="https://kit.fontawesome.com/353d5e1444.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include 'components/headerp.php'; ?>
    <?php
    // Add logs for all data updates
    // Secure "Manage Data"

    if (isset($_POST['delProd']))
    {
        // Start transaction for consistency
        mysqli_autocommit($conn, FALSE);
        
        try {
            // Delete from locationstock table first (foreign key constraint)
            $query = "DELETE ls FROM locationstock ls
                     JOIN productbrands pb ON ls.RelationID = pb.RelationID
                     WHERE pb.ProductID = '".mysqli_real_escape_string($conn, $_POST['delProdID'])."'";
            mysqli_query($conn,$query);
            
            // Delete from productbrands table
            $query2 = "DELETE FROM productbrands WHERE ProductID = '".mysqli_real_escape_string($conn, $_POST['delProdID'])."'";
            mysqli_query($conn,$query2);
            
            // Delete from Products table
            $query3 = "DELETE FROM Products WHERE ProductID = '".mysqli_real_escape_string($conn, $_POST['delProdID'])."'";
            mysqli_query($conn,$query3);
            
            // Commit transaction
            mysqli_commit($conn);
            mysqli_autocommit($conn, TRUE);
            
            $result = "success";
            $msg = "Deleted item ".htmlspecialchars($_POST['delProdID']);
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            mysqli_autocommit($conn, TRUE);
            
            $result = "fail";
            $msg = "Error deleting item: " . $e->getMessage();
        }
        
        if (!empty(mysqli_error($conn)))
            {
                $result = "fail";
                $msg = mysqli_error($conn);
            };

        $logquery = "INSERT INTO audit_logs (ActionName,ActionDesc) VALUES('Item deleted','Deleted item ".htmlspecialchars($_POST['delProdID'])."')";
        mysqli_query($conn,$logquery);
        echo mysqli_error($conn);
    }

    ?>
    <?php include 'components/msgbox.php'; ?>
    <main>
        <section id="location-pick">
            <h3>Location</h3>
            <select name="location-pick" id="location-select">
                            <option value="">Select</option>
                            <?php
                                $current_location = "";
                                if (isset($_COOKIE['current_location']))
                                {
                                    $current_location = $_COOKIE['current_location'];
                                }
                                
                                $query = "SELECT * FROM locations";
                                $queryresult=mysqli_query($conn,$query);
                                
                                while ($row = mysqli_fetch_array($queryresult))
                                {
                                    $selected = ($row['LocationID'] == $current_location) ? ' selected' : '';
                                    echo '<option value="'.$row['LocationID'].'"'.$selected.'>'.$row['LocationName'].'</option>';
                                }
                            ?>
                        </select>
        </section>
        <section id="stock-rate">
            <div class="item" id="total-items-nb">
                <span class="name">
                    Total Items
                </span>
                <span class="amt-nb"><?php
                if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                    // If a location is selected, count products that have stock in that location
                    $locationID = $_COOKIE['current_location'];
                    $query = "SELECT DISTINCT p.* FROM Products p
                            JOIN productbrands pb ON p.ProductID = pb.ProductID
                            JOIN locationstock ls ON pb.RelationID = ls.RelationID
                            WHERE ls.LocationID = '$locationID'";
                } else {
                    // If no location is selected, count all products
                    $query = "SELECT * FROM Products";
                }
                $sqlresult = mysqli_query($conn,$query);
                echo mysqli_num_rows($sqlresult);
                ?></span>
            </div>
            <div class="item" id="low-stock-nb">
                <span class="name">
                    Low Stock
                </span>
                <span class="amt-nb"><?php
                if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                    // If a location is selected, get stock for that specific location
                    $locationID = $_COOKIE['current_location'];
                    $query = "SELECT pb.ProductID, COALESCE(SUM(ls.Quantity), 0) AS TotalQuantity
                            FROM productbrands pb
                            JOIN locationstock ls ON pb.RelationID = ls.RelationID
                            WHERE ls.LocationID = '$locationID'
                            GROUP BY pb.ProductID
                            HAVING TotalQuantity < 5";
                } else {
                    // If no location is selected, get stock for all locations
                    $query = "SELECT pb.ProductID, COALESCE(SUM(ls.Quantity), 0) AS TotalQuantity
                            FROM productbrands pb
                            JOIN locationstock ls ON pb.RelationID = ls.RelationID
                            GROUP BY pb.ProductID
                            HAVING TotalQuantity < 5";
                }
                $sqlresult = mysqli_query($conn,$query);
                echo mysqli_num_rows($sqlresult);
                ?></span>
            </div>
            <div class="item" id="out-stock-nb">
                <span class="name">
                    Out of Stock
                </span>
                <span class="amt-nb"><?php
                if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                    // If a location is selected, get stock for that specific location
                    $locationID = $_COOKIE['current_location'];
                    $query = "SELECT pb.ProductID, COALESCE(SUM(ls.Quantity), 0) AS TotalQuantity
                            FROM productbrands pb
                            JOIN locationstock ls ON pb.RelationID = ls.RelationID
                            WHERE ls.LocationID = '$locationID'
                            GROUP BY pb.ProductID
                            HAVING TotalQuantity = 0";
                } else {
                    // If no location is selected, get stock for all locations
                    $query = "SELECT pb.ProductID, COALESCE(SUM(ls.Quantity), 0) AS TotalQuantity
                            FROM productbrands pb
                            JOIN locationstock ls ON pb.RelationID = ls.RelationID
                            GROUP BY pb.ProductID
                            HAVING TotalQuantity = 0";
                }
                $sqlresult = mysqli_query($conn,$query);
                echo mysqli_num_rows($sqlresult);
                ?></span>
            </div>
        </section>
        <section id="stock-update">
            <div class="options">
                <h1>Recent Items</h1>
                <button id="view-all">View All</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Part Name</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <!-- <th>Price</th> -->
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <div class="prod-del-box">
                        <span class="box-title">Are you sure you want to delete <b>x</b>?</span>
                        <span class="box-subtitle" style="width:75%;text-align:center;"><i class="fa fa-warning" style="color:rgba(250,200,0);font-size:1.1rem"></i> It is only recommended to delete when the product was added by accident.</span>
                        <div class="box-options">
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                <input type="text" name="delProdID" hidden value="" id="delProdID">
                                <button type="submit" name="delProd" class="yes">Yes</button>
                                <button type="button" class="close-box">Close</button>
                            </form>
                        </div>
                    </div>
                    <?php
                    if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                        // If a location is selected, only show products that have stock in that location
                        $locationID = $_COOKIE['current_location'];
                        $query = "SELECT DISTINCT p.* FROM Products p
                                JOIN productbrands pb ON p.ProductID = pb.ProductID
                                JOIN locationstock ls ON pb.RelationID = ls.RelationID
                                WHERE ls.LocationID = '$locationID'
                                ORDER BY p.DateAdded DESC LIMIT 3";
                    } else {
                        // If no location is selected, show all products
                        $query = "SELECT * FROM Products ORDER BY DateAdded DESC LIMIT 3";
                    }
                    $sqlresult = mysqli_query($conn,$query);
                    
                    $categories = [];
                    $catgQuery = "SELECT CategoryID, CatgName FROM categories";
                    $catgResult = mysqli_query($conn, $catgQuery);
                    while ($catgRow = mysqli_fetch_assoc($catgResult)) {
                        $categories[$catgRow['CategoryID']] = $catgRow['CatgName'];
                    }

                    while ($row=mysqli_fetch_array($sqlresult))
                    {
                        $categoryName = $categories[$row['CategoryID']] ?? 'N/A';
                        
                        // $currencySymbol = ($row['Currency'] == "USD") ? "$" : "£";

                        if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                            // If a location is selected, get stock for that specific location
                            $locationID = $_COOKIE['current_location'];
                            $query2 = "SELECT COALESCE(SUM(ls.Quantity), 0) AS TotalQuantity
                                    FROM productbrands pb
                                    JOIN locationstock ls ON pb.RelationID = ls.RelationID
                                    WHERE pb.ProductID='".$row['ProductID']."' AND ls.LocationID = '$locationID'";
                        } else {
                            // If no location is selected, get stock for all locations
                            $query2 = "SELECT SUM(ls.Quantity) AS TotalQuantity
                                    FROM productbrands pb
                                    JOIN locationstock ls ON pb.RelationID = ls.RelationID
                                    WHERE pb.ProductID='".$row['ProductID']."'";
                        }
                        $result2 = mysqli_query($conn, $query2);
                        if ($result2 && mysqli_num_rows($result2) > 0) {
                            $data = mysqli_fetch_assoc($result2);
                            $totalQuantity = $data['TotalQuantity'];
                        } else {
                            $totalQuantity = 0; // No entries for this product
                        }

                        $prodstatus = "<span>In&nbsp;Stock</span>";
                        if ($totalQuantity<=0)
                            $prodstatus = "<span class='out-stock'>Out of Stock</span>";
                        
                        $productBrandIDs = [];
                        $productQuantities = [];
                        $productPrices = [];
                        $productShelfNumbers = [];
                        $productRowNumbers = [];
                        $querybrands = "SELECT pb.BrandID, pb.Price, COALESCE(SUM(ls.Quantity), 0) AS Quantity, ls.ShelfNB, ls.RowNB
                                FROM productbrands pb
                                LEFT JOIN locationstock ls ON pb.RelationID = ls.RelationID";
                        
                        if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                            // If a location is selected, get stock for that specific location
                            $locationID = $_COOKIE['current_location'];
                            $querybrands .= " AND ls.LocationID = '$locationID'";
                        }
                        
                        $querybrands .= " WHERE pb.ProductID = '".mysqli_real_escape_string($conn, $row['ProductID'])."'";
                        
                        // Only include brands with quantity > 0 in the current location
                        if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                            $querybrands .= " AND ls.Quantity > 0";
                        }
                        
                        $querybrands .= " GROUP BY pb.BrandID, pb.Price, ls.ShelfNB, ls.RowNB";
                        $queryresult = mysqli_query($conn, $querybrands);
                        while ($brandrow=mysqli_fetch_array($queryresult))
                        {
                            $productBrandIDs[] = $brandrow['BrandID'];
                            $productQuantities[] = $brandrow['Quantity'];
                            $productPrices[] = $brandrow['Price'];
                            $productShelfNumbers[] = $brandrow['ShelfNB'] ?? '';
                            $productRowNumbers[] = $brandrow['RowNB'] ?? '';
                        }
                        $jsonProductBrandIDs = htmlspecialchars(json_encode($productBrandIDs), ENT_QUOTES, 'UTF-8');
                        $jsonProductQuantities = htmlspecialchars(json_encode($productQuantities), ENT_QUOTES, 'UTF-8');
                        $jsonProductPrices = htmlspecialchars(json_encode($productPrices), ENT_QUOTES, 'UTF-8');
                        $jsonProductShelfNumbers = htmlspecialchars(json_encode($productShelfNumbers), ENT_QUOTES, 'UTF-8');
                        $jsonProductRowNumbers = htmlspecialchars(json_encode($productRowNumbers), ENT_QUOTES, 'UTF-8');
                        $jsonDescription = htmlspecialchars(json_encode($row['Description'] ?? ''), ENT_QUOTES, 'UTF-8');

                        echo "
                            <tr>
                                <td class=\"part-name\">".htmlspecialchars($row["ProductID"])."</td>
                                <td class=\"category\">".htmlspecialchars($categoryName)."</td>
                                <td class=\"stock\">".htmlspecialchars($totalQuantity)."</td>
                                <td class=\"status\">$prodstatus</td>
                                <td class=\"actions\">
                                    <button class=\"delete-item\" style=\"color:red;\" onclick=\"openDelBox('".htmlspecialchars($row['ProductID'])."');\">
                                        <i class=\"fa fa-trash\"></i>
                                    </button>
                                </td>
                            </tr>
                        ";
                    }
                    ?>
                </tbody>
            </table>
            <?php // Edit functionality removed from dashboard, only available in inventory.php ?>
        </section>
        <button id="open-database">
            <i class="fa fa-screwdriver-wrench"></i>
            Manage Data
        </button>
    </main>

    <script src="script/brandSelector.js?v=<?=time()?>"></script>
    <script src="script/index.js?v=<?=time()?>"></script>
    <script src="script/dashboard.js?v=<?=time()?>"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>