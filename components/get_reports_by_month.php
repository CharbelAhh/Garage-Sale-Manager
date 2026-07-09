<?php
header('Content-Type: text/html; charset=utf-8');
include __DIR__ . '/db_connection.php';

$year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
$month = isset($_POST['month']) ? (int)$_POST['month'] : date('n');

if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2000 || $year > 3000) $year = date('Y');

// Build the same section HTML as in reports.php but for the given month/year
ob_start();
?>
<section id="earning-data" class="report-section">
    <div class="data-box">
        <div class="data-item">
            <span>Total gain</span>
            <?php
                $qe = $conn->prepare("SELECT SUM(oh.Quantity * oh.UnitPrice)-SUM(oh.Quantity * pb.Cost) AS `total_gain` FROM order_history oh JOIN productbrands pb ON oh.RelationID=pb.RelationID WHERE MONTH(oh.date_added) = ? AND YEAR(oh.date_added) = ?");
                $qe->bind_param('ii', $month, $year);
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
                $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
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

<section id="data-count" class="report-section">
    <div class="data-box">
        <div class="data-item">
            <span>Total Orders This Month</span>
            <p>
                <?php
                $query = $conn->prepare("SELECT COUNT(*) AS total_orders FROM orders WHERE MONTH(`DateAdded`) = ? AND YEAR(`DateAdded`) = ?");
                $query->bind_param('ii', $month, $year);
                $query->execute();
                $queryRes = $query->get_result();
                $data = $queryRes->fetch_assoc();

                $query2 = $conn->prepare("SELECT COUNT(DISTINCT OrderID) AS total_orders FROM order_history WHERE MONTH(`date_added`) = ? AND YEAR(`date_added`) = ?");
                $query2->bind_param('ii', $month, $year);
                $query2->execute();
                $queryRes2 = $query2->get_result();
                $data_completed = $queryRes2->fetch_assoc();

                echo (int)$data['total_orders'] + (int)$data_completed['total_orders'];
                ?> (<?php
                $q3 = $conn->prepare("SELECT COUNT(*) AS pending_orders FROM orders WHERE MONTH(`DateAdded`) = ? AND YEAR(`DateAdded`) = ?");
                $q3->bind_param('ii', $month, $year);
                $q3->execute();
                $qr3 = $q3->get_result();
                $d3 = $qr3->fetch_assoc();
                echo (int)$d3['pending_orders'];
                ?> pending)
            </p>
        </div>
        <div class="data-item">
            <span>Products Sold This Month</span>
            <p>
                <?php
                $q4 = $conn->prepare("SELECT COUNT(DISTINCT productbrands.ProductID) AS products_sold FROM order_history JOIN productbrands ON order_history.RelationID=productbrands.RelationID WHERE MONTH(`date_added`) = ? AND YEAR(`date_added`) = ?");
                $q4->bind_param('ii', $month, $year);
                $q4->execute();
                $r4 = $q4->get_result();
                $d4 = $r4->fetch_assoc();
                echo (int)$d4['products_sold'];
                ?>
            </p>
        </div>
    </div>
    <div class="data-box">
        <div class="data-item">
            <span>Items Available in Stock</span>
            <p>
                <?php
                $q5 = $conn->prepare("SELECT SUM(Quantity) AS products_in_stock FROM locationstock");
                $q5->execute();
                $r5 = $q5->get_result();
                $d5 = $r5->fetch_assoc();
                echo (int)$d5['products_in_stock'];
                ?>
            </p>
        </div>
        <div class="data-item">
            <span>Items sold This Month</span>
            <p>
                <?php
                $q6 = $conn->prepare("SELECT SUM(`Quantity`) AS nb_items FROM order_history WHERE MONTH(`date_added`) = ? AND YEAR(`date_added`) = ?");
                $q6->bind_param('ii', $month, $year);
                $q6->execute();
                $r6 = $q6->get_result();
                $d6 = $r6->fetch_assoc();
                echo (int)$d6['nb_items'];
                ?>
            </p>
        </div>
    </div>
    <div class="data-box">
        <div class="data-item">
            <span>New Customers</span>
            <p>
                <?php
                $q7 = $conn->prepare("SELECT COUNT(*) AS new_customers FROM clients WHERE MONTH(`TimeAdded`) = ? AND YEAR(`TimeAdded`) = ? AND ClientID!=-1");
                $q7->bind_param('ii', $month, $year);
                $q7->execute();
                $r7 = $q7->get_result();
                $d7 = $r7->fetch_assoc();
                echo (int)$d7['new_customers'];
                ?>
            </p>
        </div>
        <div class="data-item">
            <span>Total Customers</span>
            <p>
                <?php
                $q8 = $conn->prepare("SELECT COUNT(*) AS total_customers FROM clients WHERE clientID!=-1");
                $q8->execute();
                $r8 = $q8->get_result();
                $d8 = $r8->fetch_assoc();
                echo (int)$d8['total_customers'];
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
                $qq = $conn->prepare("SELECT pb.ProductID,b.BrandName, SUM(oh.Quantity) AS sold
                    FROM order_history oh
                    JOIN productbrands pb ON oh.RelationID = pb.RelationID
                    JOIN products p ON pb.ProductID = p.ProductID
                    JOIN Brand b ON pb.BrandID=b.BrandID
                    WHERE MONTH(oh.date_added) = ? AND YEAR(oh.date_added) = ?
                    GROUP BY pb.ProductID
                    ORDER BY sold DESC
                    LIMIT 5");
                $qq->bind_param('ii', $month, $year);
                $qq->execute();
                $rr = $qq->get_result();
                while ($row = $rr->fetch_assoc()) {
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
                $qq2 = $conn->prepare("SELECT c.ClientID, c.ClientName, o.Type, COUNT(DISTINCT o.OrderID) AS orders_count
                    FROM order_history o
                    JOIN clients c ON o.ClientID = c.ClientID
                    WHERE MONTH(o.date_added) = ? AND YEAR(o.date_added) = ?
                    GROUP BY o.ClientID
                    ORDER BY orders_count DESC
                    LIMIT 5");
                $qq2->bind_param('ii', $month, $year);
                $qq2->execute();
                $rr2 = $qq2->get_result();
                while ($row = $rr2->fetch_assoc()) {
                    if ($row['ClientID']==-1)
                    {
                        $cname = "<span style='color:var(--color1)'>[Quick Order]</span>";
                        echo "<tr><td>" . $cname . "</td><td>" . (int)$row['orders_count'] . "</td></tr>";

                    }
                    else
                    {
                        $cname = $row['ClientName'] ?: ('Client #' . $row['ClientID']);
                        echo "<tr><td>" . htmlspecialchars($cname) . "</td><td>" . (int)$row['orders_count'] . "</td></tr>";
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
                $q=$conn->prepare("SELECT pb.ProductID,oh.Quantity,oh.OrderID,c.ClientName,COUNT(oh.OrderItemID) AS ItemCount,SUM(oh.Quantity * oh.UnitPrice)-SUM(oh.Quantity * pb.Cost) AS `total_gain` FROM order_history oh JOIN productbrands pb ON oh.RelationID=pb.RelationID JOIN brand b ON pb.BrandID=b.BrandID JOIN clients c ON c.ClientID=oh.ClientID WHERE MONTH(oh.date_added) = ? AND YEAR(oh.date_added) = ? GROUP BY oh.OrderID ORDER BY oh.date_added DESC LIMIT 5");
                $q->bind_param('ii', $month, $year);
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
<?php
$html = ob_get_clean();
echo $html;
$conn->close();
?>