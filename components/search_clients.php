<?php
include __DIR__ . '/db_connection.php';
header('Content-Type: text/html; charset=utf-8');

$q = '';
if (isset($_POST['q'])) {
    $q = trim($_POST['q']);
}

// use one query and always bind a LIKE parameter
$like = '%' . $q . '%';
$sql = "SELECT ClientID, ClientName, PhoneNumber
        FROM clients
        WHERE (ClientName COLLATE utf8mb4_general_ci LIKE ? OR PhoneNumber LIKE ?)
        ORDER BY ClientID ASC
        LIMIT 200";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    // for debugging: uncomment temporarily
    // echo "ERROR PREPARE: " . $conn->error;
    exit;
}

$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $clientID = (int)$row['ClientID'];
    $clientName = htmlspecialchars($row['ClientName'], ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($row['PhoneNumber'], ENT_QUOTES, 'UTF-8');
    $style = ($clientID === -1) ? "style='background-color:rgba(200,200,200);'" : "";
    echo "<div id='{$clientID}' {$style} data-name=\"{$clientName}\" class='client-option'>{$clientName} ({$phone})</div>";
}

$stmt->close();
$conn->close();
?>