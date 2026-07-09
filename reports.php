<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Garage</title>

    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/RepStyle.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/charts.css/dist/charts.min.css">
    <script src="https://kit.fontawesome.com/353d5e1444.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include 'components/headerp.php'; ?>
    <main id="reports-main">
        <div class="header-section">
            <h1>Reports</h1>
            <p>Estimate overview of sales, customers, and earnings</p>
            <div class="report-date">
                <button class="date-btn" id="prev-month-btn"><i class="fa-solid fa-chevron-left"></i></button>
                <?php $curYear = date('Y'); $curMonth = date('n'); ?>
                <h2 id="report-month" data-year="<?php echo $curYear; ?>" data-month="<?php echo $curMonth; ?>"><?php echo date('F Y', strtotime("+0 month")); ?></h2>
                <button class="date-btn" id="next-month-btn"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>

        <section id="earning-data" class="report-section">
            <div class="data-box">
                <div class="data-item">
                    <span>Total gain</span>
                    <?php
                        // Addition (for more accurate profit calculation)
                        $qe = $conn->prepare("SELECT SUM(oh.Quantity * oh.UnitPrice)-SUM(oh.Quantity * pb.Cost) AS `total_gain` FROM order_history oh JOIN productbrands pb ON oh.RelationID=pb.RelationID WHERE MONTH(oh.date_added) = MONTH(CURRENT_DATE()) AND YEAR(oh.date_added) = YEAR(CURRENT_DATE())");
                        $qe->execute();
                        $re = $qe->get_result();
                        $de = $re->fetch_assoc();
                        $total_gain = (float)($de['total_gain'] ?? 0);
                    ?>
                    <p <?php if ($total_gain>=0) echo "class='gain'"; else echo "class='lose'"; ?>>
                        <?php
                        echo number_format($total_gain, 2)."$";
                        ?>
                    </p>
                </div>
            </div>
            <div class="data-box">
                <div class="data-item">
                    <span>Average earning (per day)</span>
                    <?php
                        $days = cal_days_in_month(CAL_GREGORIAN, date('n'), date('Y'));
                        $avg = $days > 0 ? ($total_gain / $days) : 0;
                    ?>
                    <p <?php if ($avg>=0) echo "class='gain'"; else echo "class='lose'"; ?>>
                        <?php
                        echo number_format($avg, 2)."$";
                        ?>
                    </p>
                </div>
            </div>
        </section>
        <section id="most-popular" class="report-section">
            <div class="popular-box">
                <div class="popular-table">
                    <h3>Top Selling Items</h3>
                    <table>
                        <thead>
                            <tr><th>Product</th><th>Brand</th><th>Quantity Sold</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        $q = $conn->prepare("SELECT pb.ProductID,b.BrandName, SUM(oh.Quantity) AS sold
                            FROM order_history oh
                            JOIN productbrands pb ON oh.RelationID = pb.RelationID
                            JOIN products p ON pb.ProductID = p.ProductID
                            JOIN Brand b ON pb.BrandID=b.BrandID
                            WHERE MONTH(oh.date_added) = MONTH(CURRENT_DATE()) AND YEAR(oh.date_added) = YEAR(CURRENT_DATE())
                            GROUP BY pb.ProductID
                            ORDER BY sold DESC
                            LIMIT 5");
                        $q->execute();
                        $r = $q->get_result();
                        while ($row = $r->fetch_assoc()) {
                            $pname = $row['ProductID'] ?: ('Product #' . $row['ProductID']);
                            echo "<tr><td>" . htmlspecialchars($pname) . "</td><td>".$row['BrandName']."</td><td>" . (int)$row['sold'] . "</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <div class="popular-table">
                    <h3>Top Loyal Customers</h3>
                    <table>
                        <thead>
                            <tr><th>Client</th><th>Type</th><th>Orders</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        $q2 = $conn->prepare("SELECT c.ClientID, c.ClientName, o.Type, COUNT(DISTINCT o.OrderID) AS orders_count
                            FROM order_history o
                            JOIN clients c ON o.ClientID = c.ClientID
                            WHERE MONTH(o.date_added) = MONTH(CURRENT_DATE()) AND YEAR(o.date_added) = YEAR(CURRENT_DATE())
                            GROUP BY o.ClientID
                            ORDER BY orders_count DESC
                            LIMIT 5");
                        $q2->execute();
                        $r2 = $q2->get_result();
                        while ($row = $r2->fetch_assoc()) {
                            if ($row['ClientID']==-1)
                            {
                                $cname = "<span style='color:var(--color1)'>[Quick Order]</span>";
                                echo "<tr><td>" . $cname . "</td><td>quick order</td><td>" . (int)$row['orders_count'] . "</td></tr>";

                            }
                            else
                            {
                                $cname = $row['ClientName'] ?: ('Client #' . $row['ClientID']);
                                echo "<tr><td>" . htmlspecialchars($cname) . "</td><td>".$row['Type']."</td><td>" . (int)$row['orders_count'] . "</td></tr>";
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <div class="popular-table">
                    <h3>Latest Sold Items</h3>
                    <table>
                        <thead>
                            <tr><th>Client</th><th>Order ID</th><th>Quantity Sold</th><th>Total Gain</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        $q=$conn->prepare("SELECT pb.ProductID,oh.Quantity,oh.OrderID,c.ClientName,COUNT(oh.OrderItemID) AS ItemCount,SUM(oh.Quantity * oh.UnitPrice)-SUM(oh.Quantity * pb.Cost) AS `total_gain` FROM order_history oh JOIN productbrands pb ON oh.RelationID=pb.RelationID JOIN brand b ON pb.BrandID=b.BrandID JOIN clients c ON c.ClientID=oh.ClientID WHERE MONTH(oh.date_added) = MONTH(CURRENT_DATE()) AND YEAR(oh.date_added) = YEAR(CURRENT_DATE()) GROUP BY oh.OrderID ORDER BY oh.date_added DESC LIMIT 5");
                        $q->execute();
                        $r = $q->get_result();
                        while ($row = $r->fetch_assoc()) {
                            $pname = $row['ProductID'] ?: ('Product #' . $row['ProductID']);
                            $total_gain = $row['total_gain'];
                            if ($total_gain>=0)
                                $color = "rgb(0,150,0)";
                            else
                                $color = "rgb(150,0,0)";
                            echo "<tr><td>".htmlspecialchars($row['ClientName'])."</td><td>#".$row['OrderID']."</td><td>" . (int)$row['Quantity'] . "</td><td style='color:$color'>$total_gain$</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <section id="data-count" class="report-section">
            <div class="data-box">
                <div class="data-item">
                    <span>Total Orders This Month</span>
                    <p>
                        <?php
                        $query = $conn->prepare("SELECT COUNT(*) AS total_orders FROM orders WHERE MONTH(`DateAdded`) = MONTH(CURRENT_DATE()) AND YEAR(`DateAdded`) = YEAR(CURRENT_DATE())");
                        $query->execute();
                        $queryRes = $query->get_result();
                        $data = $queryRes->fetch_assoc();

                        // Plus the completed orders
                        $query = $conn->prepare("SELECT COUNT(DISTINCT OrderID) AS total_orders FROM order_history WHERE MONTH(`date_added`) = MONTH(CURRENT_DATE()) AND YEAR(`date_added`) = YEAR(CURRENT_DATE())");
                        $query->execute();
                        $queryRes = $query->get_result();
                        $data_completed = $queryRes->fetch_assoc();

                        echo $data['total_orders']+$data_completed['total_orders'];
                        ?> (<?php
                        $query = $conn->prepare("SELECT COUNT(*) AS pending_orders FROM orders WHERE MONTH(`DateAdded`) = MONTH(CURRENT_DATE()) AND YEAR(`DateAdded`) = YEAR(CURRENT_DATE())");
                        $query->execute();
                        $queryRes = $query->get_result();
                        $data = $queryRes->fetch_assoc();
                        echo $data['pending_orders'];
                        ?> pending)
                    </p>
                </div>
                <div class="data-item">
                    <span>Products Sold This Month</span>
                    <p>
                        <?php
                        $query = $conn->prepare("SELECT COUNT(DISTINCT productbrands.ProductID) AS products_sold FROM order_history JOIN productbrands ON order_history.RelationID=productbrands.RelationID WHERE MONTH(`date_added`) = MONTH(CURRENT_DATE()) AND YEAR(`date_added`) = YEAR(CURRENT_DATE())");
                        $query->execute();
                        $queryRes = $query->get_result();
                        $data = $queryRes->fetch_assoc();
                        echo $data['products_sold'];
                        ?>
                    </p>
                </div>
            </div>
            <div class="data-box">
                <div class="data-item">
                    <span>Items Available in Stock</span>
                    <p>
                        <?php
                        $query = $conn->prepare("SELECT SUM(Quantity) AS products_in_stock FROM locationstock");
                        $query->execute();
                        $queryRes = $query->get_result();
                        $data = $queryRes->fetch_assoc();
                        echo (int)$data['products_in_stock'];
                        ?>
                    </p>
                </div>
                <div class="data-item">
                    <span>Items sold This Month</span>
                    <p>
                        <?php
                        $query = $conn->prepare("SELECT SUM(`Quantity`) AS nb_items FROM order_history WHERE MONTH(`date_added`) = MONTH(CURRENT_DATE()) AND YEAR(`date_added`) = YEAR(CURRENT_DATE())");
                        $query->execute();
                        $queryRes = $query->get_result();
                        $data = $queryRes->fetch_assoc();
                        echo (int)$data['nb_items'];
                        ?>
                </div>
            </div>
            <div class="data-box">
                <div class="data-item">
                    <span>New Customers</span>
                    <p>
                        <?php
                        $query = $conn->prepare("SELECT COUNT(*) AS new_customers FROM clients WHERE MONTH(`TimeAdded`) = MONTH(CURRENT_DATE()) AND YEAR(`TimeAdded`) = YEAR(CURRENT_DATE()) AND ClientID!=-1");
                        $query->execute();
                        $queryRes = $query->get_result();
                        $data = $queryRes->fetch_assoc();
                        echo $data['new_customers'];
                        ?>
                    </p>
                </div>
                <div class="data-item">
                    <span>Total Customers</span>
                    <p>
                        <?php
                        $query = $conn->prepare("SELECT COUNT(*) AS total_customers FROM clients WHERE ClientID!=-1");
                        $query->execute();
                        $queryRes = $query->get_result();
                        $data = $queryRes->fetch_assoc();
                        echo $data['total_customers'];
                        ?>
                    </p>
                </div>
            </div>
        </section>
    </main>

    <?php include 'components/msgbox.php'; ?>
    <script src="script/index.js?v=<?php time(); ?>"></script>
    <script src="script/reportsScript.js?v=<?php time() ?>"></script>
</body>
</html>
<?php $conn->close(); ?>