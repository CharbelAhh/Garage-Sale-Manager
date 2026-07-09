<?php
include 'components/db_connection.php';

// Handle add client form
if (isset($_POST['add-new-client']) && isset($_POST['new-client-name']) && isset($_POST['new-client-phone']) && isset($_POST['new-client-desc']))
{
    $clientName = mysqli_real_escape_string($conn, $_POST['new-client-name']);
    $clientPhone = mysqli_real_escape_string($conn, $_POST['new-client-phone']);
    $clientDesc = mysqli_real_escape_string($conn, $_POST['new-client-desc']);
    $errors = 0;

    if (!empty(trim($clientName)) && !empty(trim($clientPhone)))
        $errors=1;

    $insertQuery = $conn->prepare("INSERT INTO Clients (ClientName, PhoneNumber, Description) VALUES (?, ?, ?)");

    if ($errors>0)
    {
    $insertQuery->bind_param("sss", $clientName, $clientPhone, $clientDesc);

    
    $logquery = $conn->prepare("INSERT INTO audit_logs (ActionName,ActionDesc) VALUES(?,?)");
    $audit_title = "Client Added";
    $audit_desc = "New client <b>[</b>$clientName<b>]</b>";
    $logquery->bind_param('ss',$audit_title,$audit_desc);
    $logquery->execute();
    }

    if ($insertQuery->execute())
    {
        // Successfully added
        header("Location: manage.php?status=added");
        exit();
    }
    else
    {
        // Error occurred
        header("Location: manage.php?status=error");
        exit();
    }
}
// Handle edit client form
else if (isset($_POST['client-edit-id']) && isset($_POST['client-edit-name']) && isset($_POST['client-edit-phone']) && isset($_POST['client-edit-desc']))
{

    $clientID = mysqli_real_escape_string($conn, $_POST['client-edit-id']);
    $clientName = mysqli_real_escape_string($conn, $_POST['client-edit-name']);
    $clientPhone = mysqli_real_escape_string($conn, $_POST['client-edit-phone']);
    $clientDesc = mysqli_real_escape_string($conn, $_POST['client-edit-desc']);

    $updateQuery = $conn->prepare("UPDATE Clients SET ClientName=?, PhoneNumber=?, Description=? WHERE ClientID=?");
    $updateQuery->bind_param("sssi", $clientName, $clientPhone, $clientDesc, $clientID);

    $logquery = $conn->prepare("INSERT INTO audit_logs (ActionName,ActionDesc) VALUES(?,?)");
    $audit_title = "Client Edited";
    $audit_desc = "Edited client <b>[</b>$clientName<b>]</b>";
    $logquery->bind_param('ss',$audit_title,$audit_desc);
    $logquery->execute();

    if ($updateQuery->execute())
    {
        // Successfully updated
        header("Location: manage.php?status=success");
        exit();
    }
    else
    {
        // Error occurred
        header("Location: manage.php?status=error");
        exit();
    }
}
else if (isset($_POST['confirm-delete-client']) && isset($_POST['client-edit-id']))
{
    $clientID = mysqli_real_escape_string($conn, $_POST['client-edit-id']);

    // Delete associated records in other tables (Orders, order_history)
    $deleteClientOrderHistory = $conn->prepare("DELETE FROM order_history WHERE ClientID=?");
    $deleteClientOrderHistory->bind_param("i", $clientID);
    $deleteClientOrderHistory->execute();
    
    $deleteClientOrders = $conn->prepare("DELETE FROM Orders WHERE ClientID=?");
    $deleteClientOrders->bind_param("i", $clientID);
    $deleteClientOrders->execute();

    // Delete client from database
    $deleteQuery = $conn->prepare("DELETE FROM Clients WHERE ClientID=?");
    $deleteQuery->bind_param("i", $clientID);

    $logquery = $conn->prepare("INSERT INTO audit_logs (ActionName,ActionDesc) VALUES(?,?)");
    $audit_title = "Client Deleted";
    $audit_desc = "Deleted client ID#<b>[</b>$clientID<b>]</b>";
    $logquery->bind_param('ss',$audit_title,$audit_desc);
    $logquery->execute();

    if ($deleteQuery->execute())
    {
        // Successfully deleted
        header("Location: manage.php?status=deleted");
        exit();
    }
    else
    {
        // Error occurred
        header("Location: manage.php?status=error");
        exit();
    }
}
else {
    // Invalid access
    header("Location: manage.php");
    exit();
}
?>