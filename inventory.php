<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | Garage</title>

    <link rel="stylesheet" href="styles/style.css?v=<?=time()?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/charts.css/dist/charts.min.css">
    <script src="https://kit.fontawesome.com/353d5e1444.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include 'components/headerp.php'; ?>
    <?php
    if (isset($_POST['delProd']))
    {
        $query = "DELETE FROM Products WHERE ProductID = '".$_POST['delProdID']."'";
        mysqli_query($conn,$query);

        $result = "success";
        $msg = "Deleted item ".$_POST['delProdID'];

        $logquery = "INSERT INTO audit_logs (ActionName,ActionDesc) VALUES('Item deleted','Deleted item ".htmlspecialchars($_POST['delProdID'])."')";
        mysqli_query($conn,$logquery);
        echo mysqli_error($conn);
    }
    ?>
    <?php
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
    }

    if (isset($_POST['save-edit']))
    {
        // Include the unified product editing handler
        include 'components/edit_product_handler.php';
        
        // Validate required fields
        $errors = [];
        if (empty(trim($_POST['editProdID']))) {
            $errors[] = "Product ID is required";
        }
        if (empty(trim($_POST['editCategory']))) {
            $errors[] = "Category is required";
        }
        if (empty(trim($_POST['editDescription']))) {
            $errors[] = "Description is required";
        }
        
        if (empty($errors)) {
            $productIdToUpdate = $_POST['editProdID'];
            $categoryId = $_POST['editCategory'];
            $description = $_POST['editDescription'];
            $newBrandIDsString = $_POST['productbrands'];
            
            // Call the unified handler function
            $editResult = handleProductEdit($conn, $productIdToUpdate, $categoryId, $description, $newBrandIDsString);
            
            if ($editResult['success']) {
                $result = "success";
                $msg = $editResult['message'];
                
                // Add to audit log
                $logquery = "INSERT INTO audit_logs (ActionName, ActionDesc) VALUES('Item edited','Edited item ".htmlspecialchars($productIdToUpdate)."')";
                mysqli_query($conn,$logquery);
                echo mysqli_error($conn);
            } else {
                $result = "fail";
                $msg = $editResult['message'];
            }
        } else {
            $result = "fail";
            $msg = implode("<br>", $errors);
        }
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
        <section id="stock-update">
            <div class="options">
                <h1 class="inv-title">Search Inventory <?php if (isset($_COOKIE['current_location'])) {
                    $query = "SELECT LocationName FROM locations WHERE LocationID='".$_COOKIE['current_location']."'";
                    $queryresult = mysqli_query($conn,$query);
                    $current_location = mysqli_fetch_row($queryresult)[0];
                    echo "<span>|</span> Showing results in <span>".$current_location."</span>";
                }; ?></h1>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" id="search-form" method="GET">
                    <?php if (isset($_GET['page'])): ?>
                        <input type="hidden" name="page" value="1">
                    <?php endif; ?>
                    <div id="filter-box">
                        <div class="filter-header">
                            <h1 class="title">Filter by</h1>
                            <button id="close-filter" type="button"><i class="fa fa-x"></i></button>
                        </div>
                        <div class="filter-options">
                            <div class="filter-option" id="filter-category">
                                <label for="catg-filter">Category</label>
                                <select name="catg-filter" id="catg-filter">
                                    <option value="">Select</option>
                                    <?php
                                    $sqlquery = "SELECT * FROM categories";
                                    $sqlresult = mysqli_query($conn,$sqlquery);
                                    
                                    while ($row=mysqli_fetch_array($sqlresult))
                                    {
                                        $selected = "";
                                        if (isset($_GET['search']))
                                            if ($_GET['catg-filter']==$row['CategoryID'])
                                            {
                                                $selected = "selected";
                                            }
                                        echo "<option $selected value='".$row['CategoryID']."'>".$row['CatgName']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="filter-option" id="filter-brand">
                                <label for="brand-filter">Brand</label>
                                <select name="brand-filter" id="brand-filter">
                                    <option value="">Select</option>
                                    <?php
                                    $sqlquery = "SELECT * FROM brand";
                                    $sqlresult = mysqli_query($conn,$sqlquery);
                                    
                                    while ($row=mysqli_fetch_array($sqlresult))
                                    {
                                        $selected = "";
                                        if (isset($_GET['search']))
                                            if ($_GET['brand-filter']==$row['BrandID'])
                                            {
                                                $selected = "selected";
                                            }

                                        echo "<option $selected value='".$row['BrandID']."'>".$row['BrandName']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="filter-option" id="filter-desc">
                                <label for="desc-filter">Description</label>
                                    <?php
                                    $desccontent = "";
                                    
                                    if (isset($_GET['search'])) {
                                        $desccontent = $_GET['desc-filter'];
                                        }?>
                                <textarea name="desc-filter" id="desc-filter" placeholder="Write description content..."><?php echo $desccontent ?></textarea>
                            </div>
                        </div>
                        <button type="submit" id="filter-search">Done</button>
                    </div>
                    <button id="filter-btn" type="button" style="<?php $filtered=""; if (isset($_GET['search'])) {
                        foreach ($_GET as $key => $value) {
                            if (!empty($value))
                                $filtered = "background-color:rgba(209,239,255)";}} echo $filtered; ?>">
                        <i class="fa fa-filter"></i>
                    </button>
                    <div id="search-bar">
                        <button id="searchbtn" type="submit"><i class="fa fa-magnifying-glass"></i></button>
                        <input type="text" name="search" placeholder="Search for item..." value = '<?php if (isset($_GET['search'])) echo $_GET['search']; ?>'>
                    </div>
                </form>
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
                    <div class="prod-transfer-box">
                        <span class="box-title">Transfer <b id="transferProdID">x</b></span>
                        <div class="box-options">
                            <form id="transferForm">
                                <input type="hidden" name="transferProdID" id="transferProdIDInput">
                                <div class="form-group">
                                    <label for="transferBrand">Brand:</label>
                                    <select name="transferBrand" id="transferBrand" required>
                                        <!-- Brand options will be populated by JavaScript -->
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="transferDestination">Destination Location:</label>
                                    <select name="transferDestination" id="transferDestination" required>
                                        <?php
                                        // Get all locations except the current one
                                        $currentLocationID = "";
                                        if (isset($_COOKIE['current_location'])) {
                                            $currentLocationID = $_COOKIE['current_location'];
                                        }
                                        $query = "SELECT * FROM locations WHERE LocationID != '$currentLocationID' ORDER BY LocationName";
                                        $queryresult = mysqli_query($conn, $query);
                                        while ($row = mysqli_fetch_array($queryresult)) {
                                            echo '<option value="'.$row['LocationID'].'">'.$row['LocationName'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="transferQuantity">Quantity to Transfer:</label>
                                    <input type="number" name="transferQuantity" id="transferQuantity" min="1" required>
                                    <small>Available: <span id="availableQuantity">0</span></small>
                                </div>
                                <div class="form-group">
                                    <label for="transferShelfNb">Col Number:</label>
                                    <input type="text" name="transferShelfNb" id="transferShelfNb" maxlength="5" placeholder="Eg. 1, A1...">
                                </div>
                                <div class="form-group">
                                    <label for="transferRowNb">Row Number:</label>
                                    <input type="text" name="transferRowNb" id="transferRowNb" maxlength="5" placeholder="Eg. 1, 2...">
                                    <label for="transferZoneNb">Zone Number:</label>
                                    <input type="text" name="transferZoneNb" id="transferZoneNb" maxlength="5" placeholder="Eg. A, B...">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="yes">Transfer</button>
                                    <button type="button" class="close-transfer-box">Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="prod-details-box">
                        <span class="box-title">Product Details: <b id="detailsProdID">x</b></span>
                        <div class="box-options">
                            <div class="product-info">
                                <div class="info-group">
                                    <label>Category:</label>
                                    <span id="detailsCategory"></span>
                                </div>
                                <div class="info-group">
                                    <label>Description:</label>
                                    <span id="detailsDescription"></span>
                                </div>
                                <div class="info-group">
                                    <label>Brands & Quantities:</label>
                                    <div id="detailsBrands"></div>
                                </div>
                                <div class="info-group">
                                    <label>Locations & Quantities:</label>
                                    <div id="detailsLocations"></div>
                                </div>
                            </div>
                            <div class="box-actions">
                                <button type="button" class="close-details-box">Close</button>
                            </div>
                        </div>
                    </div>
                    <?php
                    $limitbypage = 10; // Number of items per page
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $page = max(1, $page); // Ensure page is at least 1
                    $offset = ($page - 1) * $limitbypage;
                    
                    $cond = "";

                    if (isset($_GET['search']))
                    {
                        $search = strtolower("%".$_GET['search']."%");
                        $cond = "WHERE LOWER(p.ProductID) LIKE '$search'";

                        if (!empty($_GET['catg-filter']))
                            $cond.=" AND `CategoryID` = ".$_GET['catg-filter'];

                        if (!empty($_GET['brand-filter']))
                            $cond.=" AND pb.BrandID = ".$_GET['brand-filter'];

                        if (!empty($_GET['desc-filter']))
                        {
                            $desc = trim($_GET['desc-filter']);                  // remove whitespace
                            $desc = mysqli_real_escape_string($conn, $desc);     // escape quotes
                            $descfilter = strtolower("%$desc%");
                            $cond .= " AND LOWER(p.Description) LIKE '$descfilter'";
                        }
                    }

                    if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                        // If a location is selected, only show products that have stock in that location
                        $locationID = $_COOKIE['current_location'];
                        $query = "SELECT DISTINCT p.* FROM Products p
                                JOIN productbrands pb ON p.ProductID = pb.ProductID
                                JOIN locationstock ls ON pb.RelationID = ls.RelationID
                                WHERE ls.LocationID = '$locationID'";
                        if (!empty($cond)) {
                            // Extract the search condition from $cond (removing "WHERE ")
                            $searchCondition = substr($cond, 6);
                            $query .= " AND " . $searchCondition;
                        }
                        $query .= " ORDER BY p.DateAdded DESC LIMIT $limitbypage OFFSET $offset";
                    } else {
                        // If no location is selected, show all products
                        $query = "SELECT DISTINCT p.* FROM Products p JOIN productbrands pb ON p.ProductID=pb.ProductID $cond ORDER BY p.DateAdded DESC LIMIT $limitbypage OFFSET $offset";
                    }
                    $sqlresult = mysqli_query($conn,$query);
                    if (!$sqlresult) {
                        echo "Error in query: " . mysqli_error($conn);
                    }
                    
                    // For pagination, we need the total count of results
                    if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                        // If a location is selected, count products that have stock in that location
                        $locationID = $_COOKIE['current_location'];
                        $countQuery = "SELECT COUNT(DISTINCT p.ProductID) as total FROM Products p
                                JOIN productbrands pb ON p.ProductID = pb.ProductID
                                JOIN locationstock ls ON pb.RelationID = ls.RelationID
                                WHERE ls.LocationID = '$locationID'";
                        if (!empty($cond)) {
                            // Extract the search condition from $cond (removing "WHERE ")
                            $searchCondition = substr($cond, 6);
                            $countQuery .= " AND " . $searchCondition;
                        }
                    } else {
                        // If no location is selected, count all products
                        $countQuery = "SELECT COUNT(*) as total FROM Products p";
                        if (!empty($cond)) {
                            $countQuery .= " " . $cond; // $cond already includes "WHERE"
                        }
                    }
                    $sqlresultNum = mysqli_query($conn, $countQuery);
                    if ($sqlresultNum) {
                        $totalRows = mysqli_fetch_assoc($sqlresultNum)['total'];
                    } else {
                        $totalRows = 0;
                    }
                    
                    $categories = [];
                    $catgQuery = "SELECT CategoryID, CatgName FROM categories";
                    $catgResult = mysqli_query($conn, $catgQuery);
                    while ($catgRow = mysqli_fetch_assoc($catgResult)) {
                        $categories[$catgRow['CategoryID']] = $catgRow['CatgName'];
                    }

                    if ($sqlresult) {
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
                            $totalQuantity = 0; // No entries for this product or query failed
                        }

                        $prodstatus = "<span>In&nbsp;Stock</span>";
                        if ($totalQuantity<=0)
                            $prodstatus = "<span class='out-stock'>Out of Stock</span>";
                        
                        $productBrandIDs = [];
                        $productBrandNames = [];
                        $productQuantities = [];
                        $productPrices = []; // Product's brand prices
                        $productWholesalePrices = []; // Product's brand wholesale prices
                        $productCosts = []; // Product's brand costs
                        $productShelfNumbers = [];
                        $productRowNumbers = [];
                        $productZoneNumbers = [];
                        $querybrands = "SELECT pb.BrandID, b.BrandName, pb.Price, pb.WholesalePrice, pb.Cost, COALESCE(SUM(ls.Quantity), 0) AS Quantity, ls.ShelfNB, ls.RowNB, ls.ZoneNB
                                FROM productbrands pb
                                LEFT JOIN brand b ON pb.BrandID = b.BrandID
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
                        
                        $querybrands .= " GROUP BY pb.BrandID, pb.Price, ls.ShelfNB, ls.RowNB, ls.ZoneNB";
                        $queryresult = mysqli_query($conn, $querybrands);
                        if ($queryresult) {
                            while ($brandrow=mysqli_fetch_array($queryresult))
                            {
                                $productBrandIDs[] = $brandrow['BrandID'];
                                $productBrandNames[] = $brandrow['BrandName'];
                                $productQuantities[] = $brandrow['Quantity'];
                                $productPrices[] = $brandrow['Price'];
                                $productWholesalePrices[] = $brandrow['WholesalePrice'];
                                $productCosts[] = $brandrow['Cost'];
                                $productShelfNumbers[] = $brandrow['ShelfNB'] ?? '';
                                $productRowNumbers[] = $brandrow['RowNB'] ?? '';
                                $productZoneNumbers[] = $brandrow['ZoneNB'] ?? '';
                            }
                        }
                        $jsonProductBrandIDs = htmlspecialchars(json_encode($productBrandIDs), ENT_QUOTES, 'UTF-8');
                        $jsonProductBrandNames = htmlspecialchars(json_encode($productBrandNames), ENT_QUOTES, 'UTF-8');
                        $jsonProductQuantities = htmlspecialchars(json_encode($productQuantities), ENT_QUOTES, 'UTF-8');
                        $jsonProductPrices = htmlspecialchars(json_encode($productPrices), ENT_QUOTES, 'UTF-8');
                        $jsonProductWholesalePrices = htmlspecialchars(json_encode($productWholesalePrices), ENT_QUOTES, 'UTF-8');
                        $jsonProductCosts = htmlspecialchars(json_encode($productCosts), ENT_QUOTES, 'UTF-8');
                        $jsonProductShelfNumbers = htmlspecialchars(json_encode($productShelfNumbers), ENT_QUOTES, 'UTF-8');
                        $jsonProductRowNumbers = htmlspecialchars(json_encode($productRowNumbers), ENT_QUOTES, 'UTF-8');
                        $jsonProductZoneNumbers = htmlspecialchars(json_encode($productZoneNumbers), ENT_QUOTES, 'UTF-8');
                        $jsonDescription = htmlspecialchars(json_encode($row['Description'] ?? ''), ENT_QUOTES, 'UTF-8');

                        // Get location quantities for all locations
                        $locationQuantities = [];
                        $locationNames = [];
                        $querylocations = "SELECT l.LocationName, COALESCE(SUM(ls.Quantity), 0) AS Quantity
                                FROM locations l
                                LEFT JOIN locationstock ls ON l.LocationID = ls.LocationID
                                LEFT JOIN productbrands pb ON ls.RelationID = pb.RelationID
                                WHERE pb.ProductID = '".mysqli_real_escape_string($conn, $row['ProductID'])."'
                                GROUP BY l.LocationID, l.LocationName";
                        $locationresult = mysqli_query($conn, $querylocations);
                        if ($locationresult) {
                            while ($locationrow = mysqli_fetch_array($locationresult)) {
                                $locationNames[] = $locationrow['LocationName'];
                                $locationQuantities[] = $locationrow['Quantity'];
                            }
                        }
                        $jsonLocationQuantities = htmlspecialchars(json_encode($locationQuantities), ENT_QUOTES, 'UTF-8');
                        $jsonLocationNames = htmlspecialchars(json_encode($locationNames), ENT_QUOTES, 'UTF-8');
                        
                        echo "
                            <tr>
                                <td class=\"part-name\" style=\"cursor: pointer; color: var(--color1);\"
                                    data-product-id=\"".htmlspecialchars($row['ProductID'])."\"
                                    data-category-name=\"".htmlspecialchars($categoryName)."\"
                                    data-description='".$jsonDescription."'
                                    data-brand-names='".$jsonProductBrandNames."'
                                    data-brand-quantities='".$jsonProductQuantities."'
                                    data-brand-prices='".$jsonProductPrices."'
                                    data-brand-wholesale-prices='".$jsonProductWholesalePrices."'
                                    data-brand-costs='".$jsonProductCosts."'
                                    data-location-quantities='".$jsonLocationQuantities."'
                                    data-location-names='".$jsonLocationNames."'
                                    onclick=\"openProductDetailsBox(this)\">".htmlspecialchars($row["ProductID"])."</td>
                                <td class=\"category\">".htmlspecialchars($categoryName)."</td>
                                <td class=\"stock\">".htmlspecialchars($totalQuantity)."</td>
                                <td class=\"status\">$prodstatus</td>
                                <td class=\"actions\">
                                    <button class=\"edit-item\" style=\"color:var(--color1)\"
                                        data-product-id=\"".htmlspecialchars($row['ProductID'])."\"
                                        data-category-id=\"".htmlspecialchars($row['CategoryID'])."\"
                                        data-brand-ids='".$jsonProductBrandIDs."'
                                        data-brand-quantities='".$jsonProductQuantities."'
                                        data-brand-prices='".$jsonProductPrices."'
                                        data-brand-wholesale-prices='".$jsonProductWholesalePrices."'
                                        data-brand-costs='".$jsonProductCosts."'
                                        data-brand-shelf-numbers='".$jsonProductShelfNumbers."'
                                        data-brand-row-numbers='".$jsonProductRowNumbers."'
                                        data-brand-zone-numbers='".$jsonProductZoneNumbers."'
                                        data-description='".$jsonDescription."'
                                        onclick=\"openEditBoxFromDataAttributes(this)\">
                                        <i class=\"fa fa-edit\"></i>
                                    </button>
                                    <button class=\"transfer-item\" style=\"color:var(--color3);\"
                                        data-product-id=\"".htmlspecialchars($row['ProductID'])."\"
                                        data-brand-ids='".$jsonProductBrandIDs."'
                                        data-brand-names='".$jsonProductBrandNames."'
                                        data-brand-quantities='".$jsonProductQuantities."'
                                        onclick=\"openTransferBox(this)\">
                                        <i class=\"fa fa-exchange-alt\"></i>
                                    </button>
                                    <button class=\"delete-item\" style=\"color:red;\" onclick=\"openDelBox('".htmlspecialchars($row['ProductID'])."');\">
                                        <i class=\"fa fa-trash\"></i>
                                    </button>
                                </td>
                            </tr>
                        ";
                        }
                    }
                    ?>
                </tbody>
            </table>
            <?php include 'components/edit-form.php'; ?>
            <div class="bottom-options <?php if (!($totalRows<=$limitbypage)) echo "active" ?>">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><button type="button"><i class="fa fa-arrow-left"></i></button></a>
                <?php else: ?>
                    <button type="button" disabled><i class="fa fa-arrow-left"></i></button>
                <?php endif; ?>
                <span>Page <?php echo $page; ?> / <?php echo ceil($totalRows/$limitbypage) ?: 1; ?></span>
                <?php if ($page < ceil($totalRows/$limitbypage)): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"><button type="button"><i class="fa fa-arrow-right"></i></button></a>
                <?php else: ?>
                    <button type="button" disabled><i class="fa fa-arrow-right"></i></button>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="script/brandSelector.js?v=<?=time()?>"></script>
    <script src="script/productEditor.js?v=<?=time()?>"></script>
    <script src="script/index.js?v=<?=time()?>"></script>
    <script src="script/inventory.js?v=<?=time()?>"></script>
    
    <script>
    // Function to open edit box using data attributes
    function openEditBoxFromDataAttributes(button) {
        try {
            const productId = button.getAttribute('data-product-id');
            const categoryId = button.getAttribute('data-category-id');
            const brandIds = JSON.parse(button.getAttribute('data-brand-ids') || '[]');
            const brandQuantities = JSON.parse(button.getAttribute('data-brand-quantities') || '[]');
            const brandPrices = JSON.parse(button.getAttribute('data-brand-prices') || '[]');
            const brandWholesalePrices = JSON.parse(button.getAttribute('data-brand-wholesale-prices') || '[]');
            const brandCosts = JSON.parse(button.getAttribute('data-brand-costs') || '[]');
            const brandShelfNumbers = JSON.parse(button.getAttribute('data-brand-shelf-numbers') || '[]');
            const brandRowNumbers = JSON.parse(button.getAttribute('data-brand-row-numbers') || '[]');
            const brandZoneNumbers = JSON.parse(button.getAttribute('data-brand-zone-numbers') || '[]');
            // const price = button.getAttribute('data-price');`
            // const currency = button.getAttribute('data-currency');
            const description = JSON.parse(button.getAttribute('data-description') || '""');
            
            // Call the existing openEditBox function with the parsed data
            // Check if a location is selected before opening the edit box
            const locationSelect = document.querySelector("#location-select");
            if (!locationSelect || locationSelect.value === "") {
                alert("Please select a location first before editing a product.");
                return;
            }
            
            openEditBox(productId, categoryId, JSON.stringify(brandIds), JSON.stringify(brandQuantities), JSON.stringify(brandPrices), description, JSON.stringify(brandShelfNumbers), JSON.stringify(brandRowNumbers), JSON.stringify(brandZoneNumbers), JSON.stringify(brandWholesalePrices), JSON.stringify(brandCosts));
        } catch (e) {
            console.error("Error parsing data attributes:", e);
        }
    }
    </script>
    
    <script>
    // Location selection handling
    document.addEventListener("DOMContentLoaded", function() {
        const locationSelect = document.getElementById("location-select");
        
        if (locationSelect) {
            // Handle location selection change
            locationSelect.addEventListener("change", function() {
                const selectedLocationId = this.value;
                
                // Send AJAX request to set/delete the cookie
                fetch("set_location_cookie.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "locationId=" + encodeURIComponent(selectedLocationId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Location cookie set/deleted successfully");
                        // Refresh the page after setting/deleting the cookie
                        location.reload();
                    } else {
                        console.error("Error setting/deleting location cookie:", data.message);
                    }
                })
                .catch(error => {
                    console.error("Error setting/deleting location cookie:", error);
                    // Even if there's an error, we still want to refresh the page
                    // to maintain consistent behavior
                    location.reload();
                });
            });
        }
    });
    </script>
    
    <script>
    // Function to open product details box
    function openProductDetailsBox(button) {
        try {
            const productId = button.getAttribute('data-product-id');
            const categoryName = button.getAttribute('data-category-name');
            const description = JSON.parse(button.getAttribute('data-description') || '""');
            const brandNames = JSON.parse(button.getAttribute('data-brand-names') || '[]');
            const brandQuantities = JSON.parse(button.getAttribute('data-brand-quantities') || '[]');
            const brandPrices = JSON.parse(button.getAttribute('data-brand-prices') || '[]');
            const brandWholesalePrices = JSON.parse(button.getAttribute('data-brand-wholesale-prices') || '[]');
            const brandCosts = JSON.parse(button.getAttribute('data-brand-costs') || '[]');
            const locationQuantities = JSON.parse(button.getAttribute('data-location-quantities') || '[]');
            const locationNames = JSON.parse(button.getAttribute('data-location-names') || '[]');
            
            // Set product ID in the modal
            document.getElementById('detailsProdID').textContent = productId;
            
            // Set category
            document.getElementById('detailsCategory').textContent = categoryName;
            
            // Set description
            document.getElementById('detailsDescription').textContent = description;
            
            // Set brands and quantities
            const brandsContainer = document.getElementById('detailsBrands');
            brandsContainer.innerHTML = '';
            if (brandNames.length > 0) {
                const brandsList = document.createElement('ul');
                for (let i = 0; i < brandNames.length; i++) {
                    const listItem = document.createElement('li');
                    listItem.textContent = `${brandNames[i]} - Quantity: ${brandQuantities[i] || 0}, Price: $${brandPrices[i] || 0}, Wholesale Price: $${brandWholesalePrices[i] || 0}, Cost: $${brandCosts[i] || 0}`;
                    brandsList.appendChild(listItem);
                }
                brandsContainer.appendChild(brandsList);
            } else {
                brandsContainer.textContent = 'No brands available';
            }
            
            // Set locations and quantities
            const locationsContainer = document.getElementById('detailsLocations');
            locationsContainer.innerHTML = '';
            if (locationNames.length > 0) {
                const locationsList = document.createElement('ul');
                for (let i = 0; i < locationNames.length; i++) {
                    const listItem = document.createElement('li');
                    listItem.textContent = `${locationNames[i]} - Quantity: ${locationQuantities[i] || 0}`;
                    locationsList.appendChild(listItem);
                }
                locationsContainer.appendChild(locationsList);
            } else {
                locationsContainer.textContent = 'No locations available';
            }
            
            // Show the details box
            document.querySelector('.prod-details-box').classList.add('active');
        } catch (e) {
            console.error("Error opening product details box:", e);
        }
    }
    
    // Initialize event listeners when the page loads
    document.addEventListener("DOMContentLoaded", function() {
        // Ensure product details box is hidden when the page loads
        const productDetailsBox = document.querySelector('.prod-details-box');
        if (productDetailsBox) {
            productDetailsBox.classList.remove('active');
        }
        
        // Add event listener for close details box button
        const closeDetailsBoxButton = document.querySelector('.close-details-box');
        if (closeDetailsBoxButton) {
            closeDetailsBoxButton.addEventListener('click', function() {
                document.querySelector('.prod-details-box').classList.remove('active');
            });
        }
        
        // Add event listener for close transfer box button
        const closeTransferBoxButton = document.querySelector('.close-transfer-box');
        if (closeTransferBoxButton) {
            closeTransferBoxButton.addEventListener('click', function() {
                document.querySelector('.prod-transfer-box').classList.remove('active');
            });
        }
        
        // Add event listener for brand selection change
        const brandSelect = document.getElementById('transferBrand');
        if (brandSelect) {
            brandSelect.addEventListener('change', updateAvailableQuantity);
        }
        
        // Add event listener for transfer form submission
        const transferForm = document.getElementById('transferForm');
        if (transferForm) {
            transferForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(transferForm);
                
                // Send AJAX request to transfer stock
                fetch('transfer_stock.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Check if the response is valid JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.indexOf('application/json') !== -1) {
                        return response.json();
                    } else {
                        // If not JSON, throw an error
                        throw new Error('Server returned invalid response');
                    }
                })
                .then(data => {
                    if (data.success) {
                        alert('Transfer successful!');
                        // Close the transfer box
                        document.querySelector('.prod-transfer-box').classList.remove('active');
                        // Refresh the page to show updated quantities
                        location.reload();
                    } else {
                        alert('Transfer failed: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during transfer: ' + error.message);
                });
            });
        }
    });
    </script>
    
    <script>
    // Function to update available quantity display
    function updateAvailableQuantity() {
        const brandSelect = document.getElementById('transferBrand');
        const selectedOption = brandSelect.options[brandSelect.selectedIndex];
        if (selectedOption) {
            const match = selectedOption.textContent.match(/\(Available: (\d+)\)/);
            if (match) {
                document.getElementById('availableQuantity').textContent = match[1];
                document.getElementById('transferQuantity').max = match[1];
            }
        }
    }
    
    // Function to open transfer box
    function openTransferBox(button) {
        try {
            // Check if a location is selected before opening the transfer box
            const locationSelect = document.querySelector("#location-select");
            if (!locationSelect || locationSelect.value === "") {
                alert("Please select a location first before transferring a product.");
                return;
            }
            
            const productId = button.getAttribute('data-product-id');
            const brandIds = JSON.parse(button.getAttribute('data-brand-ids') || '[]');
            const brandNames = JSON.parse(button.getAttribute('data-brand-names') || '[]');
            const brandQuantities = JSON.parse(button.getAttribute('data-brand-quantities') || '[]');
            
            // Set product ID in the modal
            document.getElementById('transferProdID').textContent = productId;
            document.getElementById('transferProdIDInput').value = productId;
            
            // Populate brand selection dropdown
            const brandSelect = document.getElementById('transferBrand');
            brandSelect.innerHTML = '';
            
            // Use brand names instead of brand IDs
            brandIds.forEach((brandId, index) => {
                const option = document.createElement('option');
                option.value = brandId;
                const brandName = brandNames[index] || 'Brand ' + brandId;
                option.textContent = brandName + ' (Available: ' + (brandQuantities[index] || 0) + ')';
                brandSelect.appendChild(option);
            });
            
            // Set initial available quantity
            updateAvailableQuantity();
            
            // Show the transfer box
            document.querySelector('.prod-transfer-box').classList.add('active');
        } catch (e) {
            console.error("Error opening transfer box:", e);
        }
    }
    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>