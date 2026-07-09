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
        $queryresult = mysqli_query($conn, "SELECT COUNT(*) FROM audit_logs");
        $row = mysqli_fetch_row($queryresult);
        $logcount = $row[0];
    
        if ($logcount>10)
        {
            $querydel = "DELETE FROM audit_logs
                        WHERE ActionID NOT IN (
                            SELECT ActionID
                            FROM (
                                SELECT ActionID
                                FROM audit_logs
                                ORDER BY ActionID DESC
                                LIMIT 10
                            ) AS latest
                        )";
            mysqli_query($conn,$querydel);
        }
        
        $search = '';
        if (isset($_GET['client-search']))
            $search = $_GET['client-search'];

        // HANDLE PAYMENT REQUEST
        if (isset($_POST['payment-amt']))
        {
            $amt = trim($_POST['payment-amt']);
            $cost = trim($_POST['cost']);
            $order = trim($_POST['orderid']);
            $client = trim($_POST['client']);
            $amtPaid = 0;

            $queryGetPayments = $conn->prepare("SELECT payment_amt FROM payment_history WHERE OrderID=?");
            $queryGetPayments->bind_param("i",$order);
            $queryGetPayments->execute();
            $paymentResult = $queryGetPayments->get_result();

            while ($row=$paymentResult->fetch_array())
            {
                $amtPaid+=$row['payment_amt'];
            }

            if (empty($amt)) {
                $result = "fail";
                $msg = "Place a payment amount.";
            }

            // Just reminder: $amtPaid = amt already paid; $cost = full cost; $amt = amt paying; $cost-$amtPaid = rest amt to pay
            else {
                if ($amt>$cost-$amtPaid)
                {
                    $amt=$cost-$amtPaid;
                    $result = "success";
                    $msg = "Amount placed is more than needed for this item, paid $amt$.";
                }
                else if ($amt + $amtPaid < 0)
                {
                    $amt=-$amtPaid;
                    $result = "success";
                    $msg = "Amount placed is more than needed for this item, deducted $amt$.";
                }
                else {
                    $queryClient = $conn->prepare("SELECT ClientName FROM clients WHERE ClientID=? LIMIT 1");
                    $queryClient->bind_param('i',$client);
                    $queryClient->execute();
                    $clientRes = $queryClient->get_result();
                    $clientName = $clientRes->fetch_assoc()['ClientName'];
                    $result = "success";
                    if ($amt<=0)
                        $word = "deducted";
                    else $word = "paid";
                    $msg = "{$clientName} $word $amt$ for order #$order";
                }
                $queryInsert = $conn->prepare("INSERT INTO payment_history (payment_amt,ClientID,OrderID) VALUES(?,?,?)");
                $queryInsert->bind_param("dii",$amt,$client,$order);
                $queryInsert->execute();

                $logquery = $conn->prepare("INSERT INTO audit_logs (ActionName,ActionDesc) VALUES(?,?)");
                $audit_title = "Payment Added";
                $audit_desc = $msg;
                $logquery->bind_param('ss',$audit_title,$audit_desc);
                $logquery->execute();
            }
        }
        if (isset($_POST['del-order-item'])) // DELETE AN ORDER'S ITEM
        {
            $itemid = $_POST['del-order-item'];
            $orderid = $_POST['del-order-id'];
            $deltable = $_POST['del-order-table'];

            $querydel = $conn->prepare("DELETE FROM $deltable WHERE OrderItemID = ?");
            $querydel->bind_param("i",$itemid);

            $querycheck = $conn->prepare("SELECT * FROM $deltable WHERE OrderID=?");
            $querycheck->bind_param("i",$orderid);
            $querycheck->execute();

            if ($querycheck->get_result()->num_rows<=1)
            {
                $querydelorder = $conn->prepare("DELETE FROM Orders WHERE OrderID = ?");
                $querydelorder->bind_param("i",$orderid);
                $querydelorder->execute();
            }
            $querydel->execute();
            $result = "success";
            $msg = "Deleted an item #$itemid from the order #$orderid";

            $logquery = $conn->prepare("INSERT INTO audit_logs (ActionName,ActionDesc) VALUES(?,?)");
            $audit_title = "Deleted order item";
            $audit_desc = $msg;
            $logquery->bind_param('ss',$audit_title,$audit_desc);
            $logquery->execute();
        }
        if (isset($_POST['cancel-payment']))
        {
            $payid=$_POST['payment-id'];
            $payamt=$_POST['amt-cancelled'];

            $queryOrder = $conn->prepare("SELECT OrderID FROM payment_history WHERE paymentID=? LIMIT 1");
            $queryOrder->bind_param("i",$payid);
            $queryOrder->execute();
            $queryOrderRes = $queryOrder->get_result();
            $payOrderID = $queryOrderRes->fetch_assoc()['OrderID'];

            $queryDel = $conn->prepare("DELETE FROM payment_history WHERE paymentID=? LIMIT 1");
            $queryDel->bind_param("i",$payid);
            $queryDel->execute();

            $result = "success";
            $msg = "Cancelled <b>$payamt$</b> from order #$payOrderID";

            $logquery = $conn->prepare("INSERT INTO audit_logs (ActionName,ActionDesc) VALUES(?,?)");
            $audit_title = "Payment Cancelled";
            $audit_desc = $msg;
            $logquery->bind_param('ss',$audit_title,$audit_desc);
            $logquery->execute();
        }

        // HANDLE DELETE CATEG
        if (isset($_POST['delete-catg']))
        {
            $nbdel = 0; // nb of items deleted
            $err = '';
            foreach ($_POST as $key => $value) {
                if ($key!='delete-catg')
                {
                    $querycheck = "SELECT * FROM products WHERE CategoryID=$key";
                    $queryresult = mysqli_query($conn, $querycheck);
                    if (mysqli_num_rows($queryresult) > 0)
                    {
                        $query2 = "SELECT CatgName FROM categories WHERE CategoryID=$key";
                        $queryresult2 = mysqli_query($conn,$query2);
                        $err = mysqli_fetch_array($queryresult2)[0];
                        break;
                    }
                    else {
                        $querydel = "DELETE FROM categories WHERE `CategoryID`=$key";
                        mysqli_query($conn,$querydel);
                        $nbdel++;
                    }
                }
            }
            if (!empty($err))
            {
                $result = "fail";
                $msg = "Could not delete category <b>$err</b> because it is already in use.<br>Deleted $nbdel item(s).";
            }
            else if ($nbdel==0)
            {
                $result = "fail";
                $msg = "Pick at least one item to delete!";
            }
            else if (!empty(mysqli_error($conn)))
            {
                $result = "fail";
                $msg = mysqli_error($conn);
            }
            else {
                $result = "success";
                $msg = "Successfully deleted $nbdel item(s).";

                $logquery = "INSERT INTO audit_logs (ActionName,ActionDesc) VALUES('Item deleted','Deleted $nbdel item(s) from categories')";
                mysqli_query($conn,$logquery);
                echo mysqli_error($conn);
            }
        }

        if (isset($_POST['delete-brand']))
        {
            $nbdel = 0; // nb of items deleted
            $err = '';
            foreach ($_POST as $key => $value) {
                if ($key!='delete-brand')
                {
                    $querycheck = "SELECT * FROM productbrands WHERE BrandID=$key";
                    $queryresult = mysqli_query($conn, $querycheck);
                    if (mysqli_num_rows($queryresult) > 0)
                    {
                        $query2 = "SELECT BrandName FROM `brand` WHERE BrandID=$key";
                        $queryresult2 = mysqli_query($conn,$query2);
                        $err = mysqli_fetch_array($queryresult2)[0];
                        break;
                    }
                    else {
                        $querydel = "DELETE FROM brand WHERE `BrandID`=$key";
                        mysqli_query($conn,$querydel);
                        $nbdel++;
                    }
                }
            }
            if (!empty($err))
            {
                $result = "fail";
                $msg = "Could not delete brand <b>$err</b> because it is already in use.<br>Deleted $nbdel item(s).";
            }
            else if ($nbdel==0)
            {
                $result = "fail";
                $msg = "Pick at least one item to delete!";
            }
            else if (!empty(mysqli_error($conn)))
            {
                $result = "fail";
                $msg = mysqli_error($conn);
            }
            else {
                $result = "success";
                $msg = "Successfully deleted $nbdel item(s).";

                $logquery = "INSERT INTO audit_logs (ActionName,ActionDesc) VALUES('Item deleted','Deleted $nbdel item(s) from brands')";
                mysqli_query($conn,$logquery);
                echo mysqli_error($conn);
            }
        }

        if (isset($_POST['delete-location']))
        {
            $nbdel = 0; // nb of items deleted
            $err = '';
            $inUseErr = '';
            if (isset($_COOKIE['current_location']))
            {
                $current_location = $_COOKIE['current_location'];
            }
            foreach ($_POST as $key => $value) {
                if ($key!='delete-location')
                {
                    // Check if the location is in use by any product
                    $querycheck = "SELECT COUNT(*) as count FROM locationstock WHERE LocationID=$key";
                    $queryresult = mysqli_query($conn, $querycheck);
                    $row = mysqli_fetch_assoc($queryresult);
                    $locationInUse = $row['count'] > 0;
                    
                    if ($locationInUse) {
                        // Get the location name for the error message
                        $query2 = "SELECT LocationName FROM `locations` WHERE LocationID=$key";
                        $queryresult2 = mysqli_query($conn,$query2);
                        $locationName = mysqli_fetch_array($queryresult2)[0];
                        $inUseErr = $locationName;
                        break;
                    } else if (isset($current_location) && $key==$current_location) {
                        $query2 = "SELECT LocationName FROM `locations` WHERE LocationID=$key";
                        $queryresult2 = mysqli_query($conn,$query2);
                        $err = mysqli_fetch_array($queryresult2)[0];
                    } else {
                        $querydel = "DELETE FROM locations WHERE `LocationID`=$key";
                        mysqli_query($conn,$querydel);
                        $nbdel++;
                    }
                }
            }
            if (!empty($inUseErr))
            {
                $result = "fail";
                $msg = "Could not delete location <b>$inUseErr</b> because it is already in use by products.<br>Please move the products to another location before deleting this location.";
            }
            else if (!empty($err))
            {
                $result = "fail";
                $msg = "Could not delete location <b>$err</b> because it is already in use.<br>Deleted $nbdel item(s).";
            }
            else if ($nbdel==0)
            {
                $result = "fail";
                $msg = "Pick at least one item to delete!";
            }
            else if (!empty(mysqli_error($conn)))
            {
                $result = "fail";
                $msg = mysqli_error($conn);
            }
            else {
                $result = "success";
                $msg = "Successfully deleted $nbdel item(s).";

                $logquery = "INSERT INTO audit_logs (ActionName,ActionDesc) VALUES('Item deleted','Deleted $nbdel item(s) from locations')";
                mysqli_query($conn,$logquery);
                echo mysqli_error($conn);
            }
        }

        if (isset($_GET['status'])) {
            $status = $_GET['status'];
            if ($status=='added') {
                echo "<script>alert('Client successfully added!');
                window.location = '".$_SERVER['PHP_SELF']."';
                </script>";
            } else if ($status=='success') {
                // pass
            } else if ($status=='deleted') {
                echo "<script>alert('Client successfully deleted!');
                window.location = '".$_SERVER['PHP_SELF']."';
                </script>";
            } else if ($status=='error') {
                echo "<script>alert('An error occurred while processing the request. Please try again.');
                window.location = '".$_SERVER['PHP_SELF']."';
                </script>";
            }
        }
    ?>
    <main class="data-manager">
        <!-- 
            Manage (delete): categories, brands...
            Audit logs to note everything that happened;
        -->
            <section id="remove-options">
                <div class="remove-items">
                    <div class="top">
                        <h1>Categories</h1>
                    </div>
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <div class="remove-options">
                            <?php
                                $query = "SELECT * FROM `categories` ORDER BY CatgName";
                                $queryresult = mysqli_query($conn,$query);

                                while ($row = mysqli_fetch_array($queryresult))
                                {
                                    echo
                                    "<div class='del-option'>".
                                    $row['CatgName']
                                    ." <input name='".$row['CategoryID']."' type='checkbox'/></div>";
                                }
                            ?>
                        </div>
                        <button type="submit" name="delete-catg">Delete</button>
                    </form>
                </div>
                <div class="remove-items">
                    <div class="top">
                        <h1>Brands</h1>
                    </div>
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <div class="remove-options">
                            <?php
                                $query = "SELECT * FROM `brand` ORDER BY BrandName";
                                $queryresult = mysqli_query($conn,$query);

                                while ($row = mysqli_fetch_array($queryresult))
                                {
                                    echo
                                    "<div class='del-option'>".
                                    $row['BrandName']
                                    ." <input name='".$row['BrandID']."' type='checkbox'/></div>";
                                }
                            ?>
                        </div>
                        <button type="submit" name="delete-brand">Delete</button>
                    </form>
                </div>
                <div class="remove-items">
                    <div class="top">
                        <h1>Locations</h1>
                    </div>
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <div class="remove-options">
                            <?php
                                $query = "SELECT * FROM `locations` ORDER BY LocationName";
                                $queryresult = mysqli_query($conn,$query);

                                while ($row = mysqli_fetch_array($queryresult))
                                {
                                    echo
                                    "<div class='del-option'>".
                                    $row['LocationName']
                                    ." <input name='".$row['LocationID']."' type='checkbox'/></div>";
                                }
                            ?>
                        </div>
                        <button type="submit" name="delete-location">Delete</button>
                    </form>
                </div>
            </section>
            <section id="manage-clients">
                <div class="top">
                    <h1>Manage Clients</h1>
                    <p>Manage, search and check recent purshases of clients.</p>
                </div>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" class="client-searcher">
                    <input type="search" name="client-search" id="client-search" value="<?php echo $search; ?>" placeholder="Search for a client... (Name / Number)"/>
                    <button id="search-client-btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="add-client-section">
                    <button id="add-client-btn"><i class="fa fa-plus"></i> Add New Client</button>
                    <form id="add-client-form" method="POST" action="client_management.php">
                        <h2>Add New Client</h2>
                        <p><b>Client Name:</b> <input name="new-client-name" required type="text" placeholder="Client Name"></p>
                        <p><b>Client Phone:</b> <input name="new-client-phone" required type="text" placeholder="Client Phone"></p>
                        <p><b>Description:</b> <textarea name="new-client-desc" rows="4" placeholder="Write description..."></textarea></p>
                        <input type="submit" value="Add Client" name="add-new-client">
                        <button type="button" id="cancel-add-client-btn">Cancel</button>
                    </form>
                </div>
                <?php
                    if (!empty($search)) {
                        echo "<p class='search-results-info'>Showing results for '<b>$search</b>'</p>";
                    }
                ?>
                <div class="clients-container">
                    <?php
                if (!empty($search))
                    {
                        $query = $conn->prepare(
                            "SELECT * FROM clients 
                            WHERE (LOWER(ClientName) LIKE LOWER(?) OR PhoneNumber LIKE ?) 
                            AND ClientID!=-1 
                            ORDER BY TimeAdded DESC
                            LIMIT 5"
                        );                                    $likeSearch = "%".$search."%";
                        $query->bind_param("ss", $likeSearch, $likeSearch);
                        $query->execute();
                    }
                    else
                    {
                        $query = $conn->prepare("SELECT * FROM clients WHERE ClientID!=-1 ORDER BY TimeAdded DESC LIMIT 5");
                        $query->execute();
                    }
                    $resultquery = $query->get_result();

                    while ($row = mysqli_fetch_array($resultquery))
                    {
                        // Clients info list

                        $clientID = $row['ClientID'];
                        $clientName = $row['ClientName'];
                        $clientPhone = $row['PhoneNumber'];
                        $clientDesc = $row['Description'];
                        echo "
                            <div id='CLIENT-INFO-$clientID' class='client-info-modal'>
                                <div class='client-info-content'>
                                    <div class='client-info-top'>
                                        <h2>Client Information</h2>
                                        <button type='button' class='close-client-info' data-clientid='$clientID'><i class='fa fa-x'></i></button>
                                    </div>
                                    <form class='client-edit-form' method='POST' action='client_management.php'>
                                        <p><input name='client-edit-id' hidden required type='text' value='$clientID'></p>
                                        <p><b>Client Name:</b> <input name='client-edit-name' required type='text' value='$clientName'></p>
                                        <p><b>Client Phone:</b> <input name='client-edit-phone' required type='text' value='$clientPhone'></p>
                                        <p><b>Description:</b> <textarea name='client-edit-desc' rows='4' placeholder='Write description...'>$clientDesc</textarea></p>
                                        <input type='submit' value='Save Changes'>
                                    </form>
                                    <div class='client-orders-info'>
                                        <button type='button' class='orders-client-btn'>Show Orders</button>
                                        <div class='all-client-orders' id='all-client-orders-$clientID'>
                                            <div class='client-purchases-section'>
                                                <h3><i class='fa fa-stopwatch'></i> Pending orders:</h3>
                                                <div class='pending-purchases purshases-list'>";
                        
                        $pendingOrdersQuery = $conn->prepare(
                            "SELECT OrderID, DateAdded 
                            FROM orders 
                            WHERE ClientID=? 
                            ORDER BY DateAdded DESC"
                        );
                        $pendingOrdersQuery->bind_param("i", $clientID);
                        $pendingOrdersQuery->execute();
                        $pendingOrdersResult = $pendingOrdersQuery->get_result();

                        if ($pendingOrdersResult->num_rows > 0) {
                            while ($orderRow = mysqli_fetch_array($pendingOrdersResult)) {
                                $orderID = $orderRow['OrderID'];
                                $orderDate = explode(' ',$orderRow['DateAdded'])[0];

                                echo "
                                    <div class='purchase-item'>
                                        <button type='button' class='open-order' id='open-order-$orderID' data-id='$orderID' data-client='$clientID' data-table='ordereditems'>Order #$orderID</button>
                                        ";
                                    $queryP = $conn->prepare("SELECT * FROM `ordereditems` WHERE `OrderID`=?");
                                    $queryP->bind_param("i",$orderID);
                                    $queryP->execute();
                                    $queryPRes = $queryP->get_result();

                                    $ordersum = 0;
                                    $paid = 0;

                                    // SEE HOW MUCH IS PAID FROM THE ORDER
                                    $queryGetPayments = $conn->prepare("SELECT payment_amt FROM payment_history WHERE OrderID=?");
                                    $queryGetPayments->bind_param("i",$orderID);
                                    $queryGetPayments->execute();
                                    $paymentResult = $queryGetPayments->get_result();

                                    while ($row=$paymentResult->fetch_array())
                                    {
                                        $paid+=$row['payment_amt'];
                                    }

                                    while ($rowP = $queryPRes->fetch_array())
                                    {
                                        $orderItemID = $rowP['OrderItemID'];
                                        $quantity = $rowP['Quantity'];
                                        $cost = $rowP['UnitPrice'];
                                        $relation = $rowP['RelationID'];

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

                                        // Sum of each individual item price

                                        $finalprice = $cost*$quantity;
                                        $ordersum+=$finalprice;
                                    }
                                    // Calculate the amount paid (Change the column OrderItemID to OrderID in the DB)

                                    if ($ordersum<=$paid)
                                        echo "<p><b class='label'>Status:</b><span class='value paid'>Paid</span></p>";
                                    else
                                    {
                                        echo "<div class='payment-bar' data-paid='$paid' data-cost='$ordersum'>
                                            <span class='progress-bar' style='background-size:".($paid/$ordersum*100)."% 100%'>Paid $paid$</span>
                                            <button type='button' class='add-payment-btn' data-order='$orderID'><i class='fa fa-plus'></i></button>
                                        </div>
                                        <form action='".$_SERVER['PHP_SELF']."' method='POST' class='payment-form' id='payment-form-$orderID'>
                                            <input type='text' hidden name='cost' value='$ordersum'/>
                                            <input type='text' hidden name='orderid' value='$orderID'/>
                                            <input type='text' hidden name='client' value='$clientID'/>
                                            <input type='number' placeholder='".($ordersum-$paid)."$ to pay...' step='0.01' name='payment-amt'/>
                                            <button type='submit' class='submit-payment'><i class='fa-solid fa-paper-plane'></i></button>
                                        </form>";
                                    }
                                    echo "
                                    <div class='payment-history-section'>
                                            <button class='payment-history-btn' id='payment-history-".$clientID."'><i class='fa fa-angle-down'></i>Payment History</button>
                                            <div class='payment-history-content'>";
                                        $queryPayHistory = $conn->prepare("SELECT * FROM payment_history WHERE OrderID=? ORDER BY paymentID DESC");
                                        $queryPayHistory->bind_param('i',$orderID);
                                        $queryPayHistory->execute();
                                        $PayHistoryRes = $queryPayHistory->get_result();
                                        if ($PayHistoryRes->num_rows==0)
                                            echo "<div class='payment-history-i'><div>No payment history.</div></div>";

                                        while ($rowH = $PayHistoryRes->fetch_array())
                                        {
                                            $payID = $rowH['paymentID'];
                                            $pay_amt = $rowH['payment_amt'];
                                            $pay_date = $rowH['payment_date'];
                                            $dateObj = new DateTime($pay_date);

                                            $date   = $dateObj->format('Y-m-d');
                                            $hour   = $dateObj->format('H');
                                            $minute = $dateObj->format('i');

                                            echo "<div class='payment-history-i'>"; // For every payment history instance
                                            echo "<div>&bull; <b>Payment ID: </b><span>$payID</span></div>";
                                            $negpay = "pay_amt";
                                            if ($pay_amt<0)
                                                $negpay="neg";
                                            echo "<div><b>Amount: </b><span class='$negpay'>$pay_amt$</span></div>";
                                            echo "<div><b>Date: </b><span>$date $hour:$minute</span></div>";
                                            echo "<button class='cancel-payment-btn'>Cancel payment</button>";
                                            echo "<form class='cancel-payment-form' action='".$_SERVER['PHP_SELF']."' method='POST'>
                                                    <input hidden value='$payID' name='payment-id'>
                                                    <input hidden value='$pay_amt' name='amt-cancelled'>
                                                    <div class='form-top'>Do you want to cancel a <b>$pay_amt$</b> payment?</div>
                                                    <div class='form-options'>
                                                        <button type='button' class='return-btn'>Return</button>
                                                        <button type='submit' name='cancel-payment' class='confirm-btn'>Confirm</button>
                                                    </div>
                                            </form>";
                                            echo "</div>";
                                        }
                                            echo "</div>
                                        </div>
                                        <p class='order-date'>$orderDate</p>
                                    </div>
                                ";
                        }
                    }
                        else echo "No pending orders.";
                        
                        echo "</div>
                                </div>";

                        $orderHistoryQuery = $conn->prepare(
                            "SELECT OrderID, Type, Quantity, UnitPrice, date_added 
                            FROM order_history 
                            WHERE ClientID=? 
                            GROUP BY OrderID
                            ORDER BY date_added DESC
                            LIMIT 5"
                        );
                        $orderHistoryQuery->bind_param("i", $clientID);
                        $orderHistoryQuery->execute();
                        $orderHistoryResult = $orderHistoryQuery->get_result();
                        echo "<div class='client-purchases-section'>
                        <h3><i class='fa fa-clock'></i> Order History:</h3>";
                        echo "<div class='recent-purchases purshases-list'>";
                        
                        if ($orderHistoryResult->num_rows > 0) {
                            while ($historyRow = mysqli_fetch_array($orderHistoryResult)) {
                                $orderID = $historyRow['OrderID'];
                                $type = $historyRow['Type'];
                                $quantity = $historyRow['Quantity'];
                                $unitPrice = $historyRow['UnitPrice'];
                                $dateAdded = explode(' ',$historyRow['date_added'])[0];

                                echo "
                                    <div class='purchase-item'>
                                        <button type='button' class='open-order' id='open-order-$orderID' data-id='$orderID' data-client='$clientID' data-table='order_history'>Order #$orderID</button>
                                        ";
                                    $queryP = $conn->prepare("SELECT * FROM `order_history` WHERE `OrderID`=?");
                                    $queryP->bind_param("i",$orderID);
                                    $queryP->execute();
                                    $queryPRes = $queryP->get_result();

                                    $ordersum = 0;
                                    $paid = 0;

                                    // SEE HOW MUCH IS PAID FROM THE ORDER
                                    $queryGetPayments = $conn->prepare("SELECT payment_amt FROM payment_history WHERE OrderID=?");
                                    $queryGetPayments->bind_param("i",$orderID);
                                    $queryGetPayments->execute();
                                    $paymentResult = $queryGetPayments->get_result();

                                    while ($row=$paymentResult->fetch_array())
                                    {
                                        $paid+=$row['payment_amt'];
                                    }
                                    while ($rowP = $queryPRes->fetch_array())
                                    {
                                        $orderItemID = $rowP['OrderItemID'];
                                        $quantity = $rowP['Quantity'];
                                        $cost = $rowP['UnitPrice'];
                                        $relation = $rowP['RelationID'];

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

                                        // Sum of each individual item price

                                        $finalprice = $cost*$quantity;
                                        $ordersum+=$finalprice;
                                    }
                                    if ($ordersum<=$paid)
                                        echo "<p><b class='label'>Status:</b><span class='value paid'>Paid</span></p>";
                                    else
                                    {
                                        echo "<div class='payment-bar' data-paid='$paid' data-cost='$ordersum'>
                                            <span class='progress-bar' style='background-size:".($paid/$ordersum*100)."% 100%'>Paid $paid$</span>
                                            <button type='button' class='add-payment-btn' data-order='$orderID'><i class='fa fa-plus'></i></button>
                                        </div>
                                        <form action='".$_SERVER['PHP_SELF']."' method='POST' class='payment-form' id='payment-form-$orderID'>
                                            <input type='text' hidden name='cost' value='$ordersum'/>
                                            <input type='text' hidden name='orderid' value='$orderID'/>
                                            <input type='text' hidden name='client' value='$clientID'/>
                                            <input type='number' placeholder='".($ordersum-$paid)."$ to pay...' step='0.01' name='payment-amt'/>
                                            <button type='submit' class='submit-payment'><i class='fa-solid fa-paper-plane'></i></button>
                                        </form>";
                                    }
                                echo "
                                        <div class='payment-history-section'>
                                            <button class='payment-history-btn' id='payment-history-".$clientID."'><i class='fa fa-angle-down'></i>Payment History</button>
                                            <div class='payment-history-content'>";
                                        $queryPayHistory = $conn->prepare("SELECT * FROM payment_history WHERE OrderID=? ORDER BY paymentID DESC");
                                        $queryPayHistory->bind_param('i',$orderID);
                                        $queryPayHistory->execute();
                                        $PayHistoryRes = $queryPayHistory->get_result();
                                        if ($PayHistoryRes->num_rows==0)
                                            echo "<div class='payment-history-i'><div>No payment history.</div></div>";

                                        while ($rowH = $PayHistoryRes->fetch_array())
                                        {
                                            $payID = $rowH['paymentID'];
                                            $pay_amt = $rowH['payment_amt'];
                                            $pay_date = $rowH['payment_date'];
                                            $dateObj = new DateTime($pay_date);

                                            $date   = $dateObj->format('Y-m-d');
                                            $hour   = $dateObj->format('H');
                                            $minute = $dateObj->format('i');

                                            echo "<div class='payment-history-i'>"; // For every payment history instance
                                            echo "<div>&bull; <b>Payment ID: </b><span>$payID</span></div>";
                                            $negpay = "pay_amt";
                                            if ($pay_amt<0)
                                                $negpay="neg";
                                            echo "<div><b>Amount: </b><span class='$negpay'>$pay_amt$</span></div>";
                                            echo "<div><b>Date: </b><span>$date $hour:$minute</span></div>";
                                            echo "<button class='cancel-payment-btn'>Cancel payment</button>";
                                            echo "<form class='cancel-payment-form' action='".$_SERVER['PHP_SELF']."' method='POST'>
                                                    <input hidden value='$payID' name='payment-id'>
                                                    <input hidden value='$pay_amt' name='amt-cancelled'>
                                                    <div class='form-top'>Do you want to cancel a <b>$pay_amt$</b> payment?</div>
                                                    <div class='form-options'>
                                                        <button type='button' class='return-btn'>Return</button>
                                                        <button type='submit' name='cancel-payment' class='confirm-btn'>Confirm</button>
                                                    </div>
                                            </form>";
                                            echo "</div>";
                                        }
                                    echo "</div>
                                        </div>
                                        <p class='order-date'>$dateAdded</p>
                                    </div>
                                ";
                            }
                        }
                        else echo "No recent purshases.";
                        echo "
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <form class='client-delete-form' method='POST' action='client_management.php' id='delete-client-form-$clientID'>
                                        <h3>Delete Client:</h3>
                                        <p>Warning: This action cannot be undone. Deleting a client will remove all of it's history from the system.</p>
                                        <p><input name='client-edit-id' hidden required type='text' value='$clientID'></p>
                                        <input type='button' value='Delete Client' class='delete-client-btn' data-clientid='$clientID'>
                                        <div class='confirm-delete-client-modal' id='CONFIRM-DELETE-CLIENT-$clientID'>
                                            <p>Are you sure you want to delete this client?</p>
                                            <div class='box-options'>
                                                <button type='button' class='cancel-delete-client-btn' data-clientid='$clientID'>Cancel</button>
                                                <button type='submit' name='confirm-delete-client' class='confirm-delete-client-btn' data-clientid='$clientID'>Delete</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div class='order-content-box'>
                                    </div>
                                </div>
                            </div>";
                    }
                    ?>
                    <table id="clients-data">
                        <thead>
                            <tr>
                                <td>Client ID</td>
                                <td>Client Name</td>
                                <td>Client Phone</td>
                                <td>Manage</td>
                            </tr>
                        </thead>
                        <tbody id="clients-tbody">
                            <?php
                            if (!empty($search))
                                {
                                    $query = $conn->prepare(
                                        "SELECT * FROM clients 
                                        WHERE (LOWER(ClientName) LIKE LOWER(?) OR PhoneNumber LIKE ?) 
                                        AND ClientID!=-1 
                                        ORDER BY TimeAdded DESC 
                                        LIMIT 5"
                                    );                                    $likeSearch = "%".$search."%";
                                    $query->bind_param("ss", $likeSearch, $likeSearch);
                                    $query->execute();
                                }
                                else
                                {
                                    $query = $conn->prepare("SELECT * FROM clients WHERE ClientID!=-1 ORDER BY TimeAdded DESC LIMIT 5");
                                    $query->execute();
                                }
                                $resultquery = $query->get_result();

                                while ($row = mysqli_fetch_array($resultquery))
                                {
                                    $clientID = $row['ClientID'];
                                    $clientName = $row['ClientName'];
                                    $clientPhone = $row['PhoneNumber'];
                                    echo "
                                        <tr>
                                            <td>
                                                $clientID
                                            </td>
                                            <td>
                                                $clientName
                                            </td>
                                            <td>
                                                $clientPhone
                                            </td>
                                            <td>
                                                <button id='manage-client-$clientID' class='manage-client-btn' data-clientid='".$row['ClientID']."'><i class='fa fa-circle-user'></i></button>
                                            </td>
                                        </tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section id="audit-logs">
                <div class="top">
                    <h1>Audit Logs</h1>
                    <p>The audit logs record recently done actions or events in the website for safety purposes, such as: Adding, deleting, or editing an item.</p>
                </div>
                <?php
                    $query = "SELECT * FROM audit_logs ORDER BY ActionID DESC LIMIT 10";
                    $resultquery = mysqli_query($conn,$query);
                ?>
                <?php
                if (mysqli_num_rows($resultquery)==0)
                    echo "No information yet to display.";
                ?>
                <table id="logs-content" <?php if (mysqli_num_rows($resultquery)==0) echo "hidden";?>>
                    <thead>
                        <tr>
                            <td>Event ID</td>
                            <td>Action</td>
                            <td>Description</td>
                            <td>Time</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            while ($row = mysqli_fetch_array($resultquery))
                            {
                                echo "
                                    <tr>
                                        <td>
                                            ".$row['ActionID']."
                                        </td>
                                        <td>
                                            ".$row['ActionName']."
                                        </td>
                                        <td>
                                            ".$row['ActionDesc']."
                                        </td>
                                        <td>
                                            ".$row['TimeAdded']."
                                        </td>
                                    </tr>
                                ";
                            }
                        ?>
                    </tbody>
                </table>
            </section>
    </main>

    <?php include 'components/msgbox.php'; ?>
    <script src="script/index.js?v=<?=time()?>"></script>
    <script src="script/managePage.js?v=<?=time()?>"></script>
    <script src="script/orderInfoFetch.js?v=<?=time()?>"></script>
    <script>
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>