<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Garage</title>

    <link rel="stylesheet" href="styles/style.css?v=<?=time()?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/charts.css/dist/charts.min.css">
    <script src="https://kit.fontawesome.com/353d5e1444.js" crossorigin="anonymous"></script>
    <script
    src="https://code.jquery.com/jquery-3.7.1.js"
    integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
    crossorigin="anonymous"></script>
</head>
<body>
    <?php include 'components/headerp.php'; ?>
    <?php
    // Check if the guest client exists, if not, create it
    $queryGuest = $conn->prepare("SELECT * FROM clients WHERE ClientID=?");
    $guestID = -1;
    $queryGuest->bind_param("i",$guestID);
    $queryGuest->execute();
    $resultGuest = $queryGuest->get_result();
    if ($resultGuest->num_rows==0)
    {
        $queryAddGuest = $conn->prepare("INSERT INTO clients(`ClientID`,`ClientName`,`PhoneNumber`) VALUES(?,?,?)");
        $guestName = "Guest";
        $guestPhone = "Quick Order";
        $queryAddGuest->bind_param("iss",$guestID,$guestName,$guestPhone);
        $queryAddGuest->execute();
    }

    if (isset($_POST['cancel-order']))
    {
        $orderID = $_POST['order-id'];
        $queryCancel = "DELETE FROM orders WHERE OrderID='$orderID'";
        $queryCancel2 = "DELETE FROM ordereditems WHERE OrderID='$orderID'";

        mysqli_query($conn,$queryCancel);
        mysqli_query($conn,$queryCancel2);
        
        echo "<script>alert('Success! Cancelled order #$orderID');</script>";
        $logquery = $conn->prepare("INSERT INTO audit_logs (ActionName,ActionDesc) VALUES(?,?)");
        $actionName = "Cancel Order";
        $actionDesc = "Cancelled order #$orderID";
        $logquery->bind_param("ss",$actionName,$actionDesc);
        $logquery->execute();
    }
    if (isset($_POST['complete-order']))
    {
        $orderID = $_POST['order-id'];

        // search for order info with order id
        $queryID = "SELECT * FROM Orders O JOIN `ordereditems` OI ON O.OrderID=OI.OrderID WHERE O.OrderID=$orderID";
        $resultID = mysqli_query($conn,$queryID);

        $errors = 0;

        while ($row = mysqli_fetch_array($resultID))
        {
            $orderItemID = $row["OrderItemID"];
            $relationID = $row["RelationID"];
            $type = $row["Type"];
            $quantity = $row["Quantity"];
            $unitPrice = $row["UnitPrice"];
            $clientID = $row["ClientID"];
            $dateAdded = $row['DateAdded'];

            // check if product is still in stock
            $totalQtt = 0;
            $queryCheck = $conn->prepare("SELECT * FROM locationstock WHERE `RelationID`=?");
            $queryCheck->bind_param("i",$relationID);
            $queryCheck->execute();
            $queryRes = $queryCheck->get_result();

            while ($row2 = $queryRes->fetch_array()){
                $totalQtt+=$row2['Quantity'];
            }
            if ($totalQtt<$quantity){
                $errors += 1;
            }
        }

        $resultID = mysqli_query($conn,$queryID);
        if ($errors==0)
        {
            while ($row = mysqli_fetch_array($resultID))
            {
                $orderItemID = $row["OrderItemID"];
                $relationID = $row["RelationID"];
                $type = $row["Type"];
                $quantity = $row["Quantity"];
                $unitPrice = $row["UnitPrice"];
                $clientID = $row["ClientID"];
                $dateAdded = $row["DateAdded"];

                // for adding items to the order_history, which will appear in the reports
                $queryAdd = "INSERT INTO order_history(`OrderItemID`,`OrderID`,`RelationID`,`Type`,`Quantity`,`UnitPrice`,`ClientID`,`date_ordered`)
                VALUES($orderItemID,$orderID,$relationID,'$type',$quantity,$unitPrice,$clientID,'$dateAdded')";
                mysqli_query($conn,$queryAdd);

                // algorithm to substract quantities from locationstock according to the quantity of product ordered
                $querySelect = "SELECT * FROM locationstock WHERE `RelationID`=$relationID";
                $querySlcRes = mysqli_query($conn,$querySelect);

                while ($row2 = mysqli_fetch_array($querySlcRes))
                {
                    if ($row2['Quantity']>$quantity)
                    {
                        $queryUpd = "UPDATE `locationstock` SET `quantity`=`quantity`-$quantity WHERE `LocationStockID`='".$row2['LocationStockID']."'";
                        mysqli_query($conn,$queryUpd);
                        $quantity=0;
                    }
                    else
                    {
                        $quantity-=$row2['Quantity'];
                        $queryUpd = "UPDATE `locationstock` SET `quantity`=0 WHERE `LocationStockID`=".$row2['LocationStockID'];
                        mysqli_query($conn,$queryUpd);
                    }
                }
            }

            $query = $conn->prepare("DELETE FROM ordereditems WHERE `OrderID`=$orderID");
            $query->execute();
            $query2 = $conn->prepare("DELETE FROM orders WHERE `OrderID`=$orderID");
            $query2->execute();
            
            // Delete the order from the orders
            echo "<script>alert('Success! Completed order #$orderID');</script>";
            $logquery = $conn->prepare("INSERT INTO audit_logs (ActionName,ActionDesc) VALUES(?,?)");
            $actionName = "Complete Order";
            $actionDesc = "Completed order #$orderID";
            $logquery->bind_param("ss",$actionName,$actionDesc);
            $logquery->execute();
        }
        else {
            echo "<script>alert('Fail! You don\'t have enough products in stock to complete this order.');</script>";
        }

        echo mysqli_error($conn);
    }
    if (isset($_POST['submit-order'])) {
        // Get form data
        $clientID = $_POST['name'];
        $products = $_POST['prod'];
        $brands = $_POST['brand'];
        $quantities = $_POST['quant'];
        $priceTypes = $_POST['price-type']; // Get price types for each item
        $descriptions = $_POST['desc'];
        
        // Start transaction
        mysqli_autocommit($conn, false);
        
        try {
            // Insert a new order record
            $orderQuery = "INSERT INTO orders (ClientID) VALUES ('$clientID')";
            if (!mysqli_query($conn, $orderQuery)) {
                throw new Exception("Error creating order: " . mysqli_error($conn));
            }
            
            // Get the ID of the newly created order
            $orderID = mysqli_insert_id($conn);
            
            // Insert each ordered item
            for ($i = 0; $i < count($products); $i++) {
                // Skip if product is not selected
                if (empty($products[$i]) || empty($brands[$i]) || empty($quantities[$i])) {
                    continue;
                }
                
                // Get the RelationID from productbrands table
                $prodID = $products[$i];
                $brandID = $brands[$i];
                $quantity = $quantities[$i];
                $priceType = $priceTypes[$i]; // Get price type for this item
                $desc = mysqli_real_escape_string($conn,$descriptions[$i]);
                
                // Get RelationID and prices from productbrands table
                $relationQuery = "SELECT RelationID, Price, WholesalePrice FROM productbrands WHERE ProductID = '$prodID' AND BrandID = '$brandID'";
                $relationResult = mysqli_query($conn, $relationQuery);
                
                if ($relationRow = mysqli_fetch_assoc($relationResult)) {
                    $relationID = $relationRow['RelationID'];
                    
                    // Get unit price based on user selection
                    if ($priceType == 'wholesale') {
                        $unitPrice = $relationRow['WholesalePrice'];
                    } else {
                        $unitPrice = $relationRow['Price'];
                    }
                    
                    // Insert ordered item
                    $itemQuery = "INSERT INTO ordereditems (OrderID, RelationID, Type, Quantity, UnitPrice, Info) VALUES ('$orderID', '$relationID', 'customer', '$quantity', '$unitPrice', '$desc')";
                    
                    if (!mysqli_query($conn, $itemQuery)) {
                        throw new Exception("Error adding item to order: " . mysqli_error($conn));
                    }
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            $logquery = $conn->prepare("INSERT INTO audit_logs (ActionName,ActionDesc) VALUES(?,?)");
            $actionName = "Create Order";
            $actionDesc = "Created order #$orderID";
            $logquery->bind_param("ss",$actionName,$actionDesc);
            $logquery->execute();

            echo "<script>alert('Order submitted successfully!');
            window.location = '".$_SERVER['PHP_SELF']."'</script>";
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            echo "<script>alert(\"Error submitting order: " . $e->getMessage() . "\");
            window.location = '".$_SERVER['PHP_SELF']."'</script>";
}
        
        // Re-enable autocommit
        mysqli_autocommit($conn, true);
    }
    ?>
    <?php include 'components/msgbox.php'; ?>
    <main>
        <section id="add-order-btn">
            <!-- ADD CLIENT! -->
            <form action="" method="POST" id="client-form">
                <div class="top-form">
                    <h1><i class="fa fa-user"></i> Add a client</h1>
                    <button type="button" id="close-clientform">
                        <i class="fa fa-x"></i>
                    </button>
                </div>
                <div class="fields">
                    <div class="field new-client">
                        <label for="new-client">Client Name</label>
                        <input type="text" name="new-client" placeholder="Write here..." id="new-client">
                    </div>
                    <div class="field client-number">
                        <label for="client-number">Phone Number</label>
                        <input type="text" name="client-number"  id="client-number" placeholder="Write here...">
                    </div>
                </div>
                <button type="submit" id="submit-client">Add</button>
            </form>
            <!-- ADD ORDER! -->
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="orderForm">
                <input type="text" name="pages" id="page" value="1" hidden>
                <input type="text" name="currentPage" id="currentPage" value="1" hidden>
                <div class="top-form">
                    <h1>Add order</h1>
                    <button type="button" id="close-order"><i class="fa fa-x"></i></button>
                </div>
                <div class="field client-name">
                    <label for="name">Client</label>
                    <input required type="text" id="name" name="name" hidden value="-1" placeholder="Type here...">
                    <button id="select-client" type="button"><div class="fa fa-angle-down"></div> Select</button>
                        <div class="manage-clients">
                            <button id="add-client" type="button">Add new</button>

                            <!-- single clients list + search input -->
                            <div class="clients-search">
                                <input type="search" id="client-search" placeholder="Search clients..." autocomplete="off">
                                <input type="hidden" id="order-client-id" name="client_id" value="">
                                <div class="clients-list">
                                    <?php
                                    $query = "SELECT * FROM clients ORDER BY ClientID ASC";
                                    $resultquery = mysqli_query($conn,$query);

                                    while ($row = mysqli_fetch_array($resultquery))
                                    {
                                        $style = "";
                                        if ($row['ClientID']==-1)
                                            $style = "style='background-color:var(--color1);color:white;'";
                                        echo "<div id='".$row['ClientID']."' $style data-name=\"".htmlspecialchars($row['ClientName'])."\" class='client-option'>" . htmlspecialchars($row['ClientName']) . " (" . htmlspecialchars($row['PhoneNumber']) . ")</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                </div>
                <div class="main-form">
                    <div class="page page1">
                        <div class="field">
                            <label for="prod">Product ID</label>
                            <select required class="prod" name="prod[]">
                                <option value="">Select</option>
                                <?php
                                    if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                                        // If a location is selected, only show products that have stock in that location
                                        $locationID = $_COOKIE['current_location'];
                                        $query = "SELECT DISTINCT p.* FROM products p
                                                JOIN productbrands pb ON p.ProductID = pb.ProductID
                                                JOIN locationstock ls ON pb.RelationID = ls.RelationID
                                                WHERE ls.LocationID = '$locationID'
                                                ORDER BY p.DateAdded DESC";
                                    } else {
                                        // If no location is selected, show all products
                                        $query = "SELECT * FROM products ORDER BY DateAdded DESC";
                                    }
                                    $queryresult = mysqli_query($conn,$query);
                                    
                                    while ($row = mysqli_fetch_array($queryresult))
                                    {
                                        echo "<option value='".$row['ProductID']."'>".$row['ProductID']."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="brand">Brand</label>
                            <select required class="brand" name="brand[]">
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="price-type">Price Type</label>
                            <select required class="price-type" name="price-type[]">
                                <option value="normal" selected>Normal price</option>
                                <option value="wholesale">Wholesale price</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="quant">Quantity</label>
                            <select required class="quant" name="quant[]">
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="field">
                            <textarea class="desc" name="desc[]" placeholder="Write extra information..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="bottom-order">
                    <div class="confirm-del hidden">
                        <div class="content">
                            <h1>Delete this page?</h1>
                            <div class="confirm-btns">
                                <button class="yes confirm-btn" type="button">Yes</button>
                                <button class="no confirm-btn" type="button">No</button>
                            </div>
                        </div>
                    </div>
                    <button id="add-page" type="button">Add</button>                    
                    <button id="rem-page" class="hidden" type="button">X</button>                    
                    <div class="page-nb hidden">page <button type="button" class="page-arrow arrow-left"><i class="fa fa-arrow-left"></i></button>
                        <span class="page-now">1</span>/<span class="page-amt">1</span>
                        <button type="button" class="page-arrow arrow-right"><i class="fa fa-arrow-right"></i></button>
                    </div>
                </div>
                <button type="submit" id="submit-order" name="submit-order">Submit <i class="fa fa-pen"></i></button>
            </form>
            <button id="add-order">
                <i class="fa fa-plus"></i>
                Add Order
            </button>
        </section>
        <section id="no-orders">
            No orders currently available.
        </section>
        <section id="orders">
            <div id="box-cancel-order">
                <div class="options-top">
                    <h1><i class="fa fa-angle-right"></i> Cancel order</h1>
                    <button type="button" class="close-options"><i class="fa fa-x"></i></button>
                </div>
                <p>Are you sure you want to cancel the order <span>{ITEM}</span>?</p>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <input type="text" name="order-id" hidden>
                    <button type="submit" name="cancel-order">I'm sure</button>
                </form>
            </div>
            <div id="box-complete-order">
                <div class="options-top">
                    <h1><i class="fa fa-angle-right"></i> Complete order</h1>
                    <button type="button" class="close-options"><i class="fa fa-x"></i></button>
                </div>
                <p>Are you sure you want to complete this order <span>{ITEM}</span>?</p>
                <span>This will add the ordered items' information to the reports data.</span>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <input type="text" name="order-id" hidden>
                    <button type="submit" name="complete-order">I'm sure</button>
                </form>
            </div>
            <?php
            // Fetch orders with client and item information
            $ordersQuery = "SELECT o.OrderID, o.DateAdded, o.ClientID
                             FROM orders o
                             ORDER BY o.DateAdded DESC";
            $ordersResult = mysqli_query($conn, $ordersQuery);
            
            if (mysqli_num_rows($ordersResult) > 0) {
                while ($order = mysqli_fetch_assoc($ordersResult)) {
                    $clientName = "Guest";
                    $extra = "";
                    if ($order['ClientID']!=-1)
                    {
                        $queryClient = $conn->prepare("SELECT ClientName FROM clients WHERE ClientID=?");
                        $queryClient->bind_param("i",$order['ClientID']);
                        $queryClient->execute();
                        $resultClient = $queryClient->get_result();
                        if ($rowClient = $resultClient->fetch_assoc()) {
                            $clientName = $rowClient['ClientName'];
                        }
                    }
                    else {
                        $extra = " <span>[Quick Order]<span> ";
                    }
                    $orderID = $order['OrderID'];
                    $orderDate = date('d/m/Y', strtotime($order['DateAdded']));
                    
                    echo '<div class="order">';
                    echo '    <div class="order-options">';
                    echo '        <div class="options-top">';
                    echo '            <h1>Order options</h1>';
                    echo '            <button type="button" class="close-options"><i class="fa fa-x"></i></button>';
                    echo '        </div>';
                    echo '        <div class="options">';
                    echo '            <button type="button" onclick="cancelOrder('.$orderID.')" class="cancel-order">Cancel order</button>';
                    echo '            <button type="button" onclick="completeOrder('.$orderID.')" class="complete-order">Complete order</button>';
                    echo '        </div>';
                    echo '    </div>';
                    echo '    <div class="top-order">';
                    echo '        <span>Order #' . $orderID . ': <h3>'. htmlspecialchars($clientName) . '</h3>'.$extra.'</span>';
                    echo '        <span class="orderDate">' . $orderDate . '</span>';
                    echo '    </div>';
                    
                    // Fetch items for this order
                    $itemsQuery = "SELECT oi.OrderItemID, oi.Quantity, oi.UnitPrice, oi.Info, p.ProductID, b.BrandName
                                   FROM ordereditems oi
                                   JOIN productbrands pb ON oi.RelationID = pb.RelationID
                                   JOIN products p ON pb.ProductID = p.ProductID
                                   JOIN brand b ON pb.BrandID = b.BrandID
                                   WHERE oi.OrderID = $orderID";
                    $itemsResult = mysqli_query($conn, $itemsQuery);
                    
                    $totalPrice = 0;
                    $totalProfit = 0;
                    echo '    <div class="order-infos">';
                    echo '        <div class="order-items-container">';
                    echo '            <div class="order-items-labels">';
                    echo '                <div class="order-item-label">Product ID</div>';
                    echo '                <div class="order-item-label">Brand</div>';
                    echo '                <div class="order-item-label">Quantity</div>';
                    echo '                <div class="order-item-label">Unit Price</div>';
                    echo '                <div class="order-item-label">Total</div>';
                    echo '            </div>';
                    echo '            <div class="order-items-grid">';
                    
                    while ($item = mysqli_fetch_assoc($itemsResult)) {
                        $orderItemID = $item['OrderItemID'];
                        $quantity = $item['Quantity'];
                        $unitPrice = $item['UnitPrice'];
                        $productID = $item['ProductID'];
                        $brandName = $item['BrandName'];
                        $info = htmlspecialchars($item['Info']);
                        if (empty($info))
                            $info = "No description.";
                        $itemTotal = $quantity * $unitPrice;

                        $itemsQuery = "SELECT BrandID FROM brand WHERE `BrandName`='".$brandName."'";
                        $itemsResult2 = mysqli_query($conn,$itemsQuery);
                        $queryfetch = "SELECT Cost FROM productbrands WHERE ProductID = '$productID' AND `BrandID`='".mysqli_fetch_row($itemsResult2)[0]."'";
                        $queryResult = mysqli_query($conn, $queryfetch);
                        
                        $totalPrice += $itemTotal;
                        $cost = mysqli_fetch_row($queryResult)[0];
                        $totalProfit += ($unitPrice - $cost) * $quantity;
                        echo '<div class="descboxcontainer"><div class=\'prod-desc-box\' id=\'desc-'.$orderItemID.'\'>'.$info.'</div>';
                        echo '<div class="order-item-cell order_product_name" onclick=\'OpenItemDesc('.$orderItemID.')\'>' . htmlspecialchars($productID) . '</div>';
                        echo '</div>';
                        echo '                <div class="order-item-cell">' . htmlspecialchars($brandName) . '</div>';
                        echo '                <div class="order-item-cell">' . $quantity . '</div>';
                        echo '                <div class="order-item-cell">$' . number_format($unitPrice, 2) . '</div>';
                        echo '                <div class="order-item-cell">$' . number_format($itemTotal, 2) . '</div>';
                    }
                    
                    echo '            </div>';
                    echo '            <div class="order-total">';
                    echo '                <div class="order-total-label">Total:</div>';
                    echo '                <div class="order-total-value">$' . number_format($totalPrice, 2) . '</div>';
                    echo '            </div>';
                    echo '<hr>';
                    echo '            <div class="order-total">';
                    echo '                <div class="order-total-label">Profit:</div>';
                    echo '                <div class="order-gain-value">$' . number_format($totalProfit, 2) . '</div>';
                    echo '            </div>';
                    echo '        </div>';
                    echo '        <button type="button" class="showOptions"><i class="fa fa-sliders"></i></button>';
                    echo '    </div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No orders found.</p>';
            }
            ?>
        </section>
    </main>

    <?php include 'components/msgbox.php'; ?>
    <script src="script/index.js?v=<?=time()?>"></script>
    <script src="script/orders.js?v=<?=time()?>"></script>
    <script>
    $('#client-form').on('submit', function (e) {
        e.preventDefault();

        let clientName = $('#new-client').val().trim();
        let clientNumber = $('#client-number').val().trim();

        if (clientName === '') return;

        $.ajax({
            url: 'components/add_client.php',
            method: 'POST',
            data: {
                'new-client': clientName,
                'client-number': clientNumber
            },
            success: function (response) {
                // Replace the entire client list with the updated one from DB
                $('.clients-list').html(response);

                // Clear the form
                $('#new-client').val('');
                $('#client-number').val('');
            },
            error: function () {
                alert("Failed to add client.");
            }
        });
    });

    $(document).on('change', 'select.prod', function () {
        const $page = $(this).closest('.page');
        const productId = $(this).val();

        const $brandSelect = $page.find('select.brand');
        const $quantSelect = $page.find('select.quant');

        if (!productId) {
            $brandSelect.html('<option value="">Select</option>');
            $quantSelect.html('<option value="">Select</option>');
            return;
        }

        $.ajax({
            url: 'components/get_brands_by_product.php',
            method: 'POST',
            data: { productID: productId },
            success: function (response) {
                $brandSelect.html(response);
                $quantSelect.html('<option value="">Select</option>'); // reset quantity
            },
            error: function () {
                alert('Could not fetch brands.');
            }
        });
    });

    $(document).on('change', 'select.brand', function () {
        const $page = $(this).closest('.page');
        const brandId = $(this).val();
        const productId = $page.find('select.prod').val();

        const $quantSelect = $page.find('select.quant');

        if (!brandId || !productId) {
            $quantSelect.html('<option value="">Select</option>');
            return;
        }

        $.ajax({
            url: 'components/get_quantity_by_brand.php',
            method: 'POST',
            data: {
                productID: productId,
                brandID: brandId
            },
            success: function (response) {
                $quantSelect.html(response);
            },
            error: function () {
                alert('Could not fetch quantity.');
            }
        });
    // Handle complete order button click
    document.querySelectorAll(".complete-order").forEach(button => {
        button.addEventListener("click", function() {
            const orderElement = this.closest(".order");
            const orderID = orderElement.querySelector(".top-order span").textContent.split("#")[1].split(":")[0];
            
            // Create a form data object to send the complete order request
            const formData = new FormData();
            formData.append('complete-order', '1');
            formData.append('order-id', orderID);
            
            // Send AJAX request to complete the order
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Remove the order from the DOM
                orderElement.remove();
                alert("Order completed successfully!");
            })
            .catch(error => {
                alert("Failed to complete order.");
            });
    });
        });
    });

// Client search functionality

(function(){
    let clientSearchTimer = null;
    const $search = $('#client-search');
    const $list = $('.clients-list');
    const $panel = $('.manage-clients');

    function fetchClients(query) {
        $.ajax({
            url: 'components/search_clients.php',
            method: 'POST',
            data: { q: query },
            success: function (response) {
                $list.html(response);
            }
        });
    }

    $search.on('input', function () {
        clearTimeout(clientSearchTimer);
        const q = $(this).val();
        clientSearchTimer = setTimeout(function () {
            fetchClients(q);
        }, 200);
    });

    $search.on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(clientSearchTimer);
            fetchClients($search.val());
        }
    });

    $('#select-client').on('click', function () {
        $panel.toggleClass('open');
        if ($panel.hasClass('open')) {
            // Remove any inline display:none left by older code so CSS can control visibility
            $panel.removeAttr('style');
            if ($search.val().trim() === '') fetchClients('');
            $search.focus();
        } else {
            $search.blur();
        }
    });

    // single delegated click handler for selection (DO NOT call .hide())
    $(document).on('click', '.clients-list .client-option', function () {
        const clientID = $(this).attr('id');
        const clientName = $(this).data('name') || $(this).text().trim();

        $('#name').val(clientID);
        $('#order-client-id').val(clientID);

        const safeName = $('<div>').text(clientName).html();
        $('#select-client').html('<div class="fa fa-angle-down"></div> ' + safeName);

        $('.clients-list .client-option').removeClass('selected');
        $(this).addClass('selected');

        // Close by class only — do NOT set inline styles
        $panel.removeClass('open active');
        $search.val('').blur();
    });

})(); 

// Handle client selection

$(document).on('click', '.clients-list .client-option', function () {
    const clientID = $(this).attr('id');
    const clientName = $(this).data('name') || $(this).text().trim();

    // put the selected client ID into the input the server expects
    $('#name').val(clientID);
    $('#order-client-id').val(clientID);  // keep hidden in sync if used

    // update the Select button to show the chosen name (escape text)
    const safeName = $('<div>').text(clientName).html();
    $('#select-client').html('<div class="fa fa-angle-down"></div> ' + safeName);

    // mark selection visually
    $('.clients-list .client-option').removeClass('selected');
    $(this).addClass('selected');

    // close/hide the manage-clients panel (do NOT clear or hide .clients-list)
    $('.manage-clients').removeClass('open active');
    $('.manage-clients').hide(); // optional if panel is shown via show()/hide()
    $('#client-search').val('').blur();
});
// ...existing code...

    </script>
</body>
</html>

<?php mysqli_close($conn); ?>