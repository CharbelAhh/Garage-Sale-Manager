<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include the database connection
include 'components/db_connect.php';

try {
    // Check if the request is a POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the POST data
        $productId = $_POST['transferProdID'] ?? '';
        $brandId = $_POST['transferBrand'] ?? '';
        $destinationLocationId = $_POST['transferDestination'] ?? '';
        $quantityToTransfer = $_POST['transferQuantity'] ?? 0;
        $shelfNb = $_POST['transferShelfNb'] ?? '';
        $rowNb = $_POST['transferRowNb'] ?? '';
        $zoneNb = $_POST['transferZoneNb'] ?? '';
        
        // Validate the data
        if (empty($productId) || empty($brandId) || empty($destinationLocationId) || empty($quantityToTransfer)) {
            echo json_encode(['success' => false, 'message' => 'Missing required data']);
            exit;
        }
        
        // Get the source location from the cookie
        $sourceLocationId = $_COOKIE['current_location'] ?? '';
        if (empty($sourceLocationId)) {
            echo json_encode(['success' => false, 'message' => 'No source location selected']);
            exit;
        }
        
        // Start a transaction
        mysqli_autocommit($conn, FALSE);
        
        try {
            // Get the RelationID for the product and brand
            $relationQuery = "SELECT RelationID FROM productbrands WHERE ProductID = ? AND BrandID = ?";
            $relationStmt = mysqli_prepare($conn, $relationQuery);
            if ($relationStmt) {
                mysqli_stmt_bind_param($relationStmt, "si", $productId, $brandId);
                if (mysqli_stmt_execute($relationStmt)) {
                    $relationResult = mysqli_stmt_get_result($relationStmt);
                    if ($relationRow = mysqli_fetch_assoc($relationResult)) {
                        $relationId = $relationRow['RelationID'];
                    } else {
                        throw new Exception("Could not find product-brand combination");
                    }
                } else {
                    throw new Exception("Error executing relation query: " . mysqli_error($conn));
                }
                mysqli_stmt_close($relationStmt);
            } else {
                throw new Exception("Error preparing relation query: " . mysqli_error($conn));
            }
            
            // Check if there's enough quantity in the source location
            $checkQuery = "SELECT Quantity FROM locationstock WHERE RelationID = ? AND LocationID = ?";
            $checkStmt = mysqli_prepare($conn, $checkQuery);
            if ($checkStmt) {
                mysqli_stmt_bind_param($checkStmt, "ii", $relationId, $sourceLocationId);
                if (mysqli_stmt_execute($checkStmt)) {
                    $checkResult = mysqli_stmt_get_result($checkStmt);
                    if ($checkRow = mysqli_fetch_assoc($checkResult)) {
                        $currentQuantity = $checkRow['Quantity'];
                        if ($currentQuantity < $quantityToTransfer) {
                            throw new Exception("Not enough quantity in source location. Available: $currentQuantity");
                        }
                    } else {
                        throw new Exception("No stock found in source location");
                    }
                } else {
                    throw new Exception("Error executing check query: " . mysqli_error($conn));
                }
                mysqli_stmt_close($checkStmt);
            } else {
                throw new Exception("Error preparing check query: " . mysqli_error($conn));
            }
            
            // Reduce quantity in source location
            $reduceQuery = "UPDATE locationstock SET Quantity = Quantity - ? WHERE RelationID = ? AND LocationID = ?";
            $reduceStmt = mysqli_prepare($conn, $reduceQuery);
            if ($reduceStmt) {
                mysqli_stmt_bind_param($reduceStmt, "iii", $quantityToTransfer, $relationId, $sourceLocationId);
                if (!mysqli_stmt_execute($reduceStmt)) {
                    throw new Exception("Error reducing quantity in source location: " . mysqli_error($conn));
                }
                mysqli_stmt_close($reduceStmt);
            } else {
                throw new Exception("Error preparing reduce query: " . mysqli_error($conn));
            }
            
            // Check if the quantity in the source location is now 0
            $checkZeroQuery = "SELECT Quantity FROM locationstock WHERE RelationID = ? AND LocationID = ?";
            $checkZeroStmt = mysqli_prepare($conn, $checkZeroQuery);
            if ($checkZeroStmt) {
                mysqli_stmt_bind_param($checkZeroStmt, "ii", $relationId, $sourceLocationId);
                if (mysqli_stmt_execute($checkZeroStmt)) {
                    $checkZeroResult = mysqli_stmt_get_result($checkZeroStmt);
                    if ($checkZeroRow = mysqli_fetch_assoc($checkZeroResult)) {
                        $newQuantity = $checkZeroRow['Quantity'];
                        if ($newQuantity == 0) {
                            // Check if this is the only location with this product
                            $countLocationsQuery = "SELECT COUNT(*) as locationCount FROM locationstock WHERE RelationID = ? AND Quantity > 0";
                            $countLocationsStmt = mysqli_prepare($conn, $countLocationsQuery);
                            if ($countLocationsStmt) {
                                mysqli_stmt_bind_param($countLocationsStmt, "i", $relationId);
                                if (mysqli_stmt_execute($countLocationsStmt)) {
                                    $countLocationsResult = mysqli_stmt_get_result($countLocationsStmt);
                                    $countLocationsRow = mysqli_fetch_assoc($countLocationsResult);
                                    $locationCount = $countLocationsRow['locationCount'];
                                    
                                    // If there are other locations with this product, delete this entry
                                    if ($locationCount > 0) {
                                        $deleteQuery = "DELETE FROM locationstock WHERE RelationID = ? AND LocationID = ?";
                                        $deleteStmt = mysqli_prepare($conn, $deleteQuery);
                                        if ($deleteStmt) {
                                            mysqli_stmt_bind_param($deleteStmt, "ii", $relationId, $sourceLocationId);
                                            if (!mysqli_stmt_execute($deleteStmt)) {
                                                throw new Exception("Error deleting entry from locationstock: " . mysqli_error($conn));
                                            }
                                            mysqli_stmt_close($deleteStmt);
                                        } else {
                                            throw new Exception("Error preparing delete query: " . mysqli_error($conn));
                                        }
                                    }
                                } else {
                                    throw new Exception("Error executing count locations query: " . mysqli_error($conn));
                                }
                                mysqli_stmt_close($countLocationsStmt);
                            } else {
                                throw new Exception("Error preparing count locations query: " . mysqli_error($conn));
                            }
                        }
                    }
                } else {
                    throw new Exception("Error executing check zero query: " . mysqli_error($conn));
                }
                mysqli_stmt_close($checkZeroStmt);
            } else {
                throw new Exception("Error preparing check zero query: " . mysqli_error($conn));
            }
            
            // Check if there's already an entry for this relation and destination location
            $checkDestQuery = "SELECT LocationStockID FROM locationstock WHERE RelationID = ? AND LocationID = ?";
            $checkDestStmt = mysqli_prepare($conn, $checkDestQuery);
            if ($checkDestStmt) {
                mysqli_stmt_bind_param($checkDestStmt, "ii", $relationId, $destinationLocationId);
                if (mysqli_stmt_execute($checkDestStmt)) {
                    $checkDestResult = mysqli_stmt_get_result($checkDestStmt);
                    if (mysqli_num_rows($checkDestResult) > 0) {
                        // Update existing entry for destination location
                        mysqli_stmt_close($checkDestStmt);
                        $updateQuery = "UPDATE locationstock SET Quantity = Quantity + ?, ShelfNB = ?, RowNB = ?, ZoneNB = ? WHERE RelationID = ? AND LocationID = ?";
                        $updateStmt = mysqli_prepare($conn, $updateQuery);
                        if ($updateStmt) {
                            mysqli_stmt_bind_param($updateStmt, "isssii", $quantityToTransfer, $shelfNb, $rowNb, $zoneNb, $relationId, $destinationLocationId);
                            if (!mysqli_stmt_execute($updateStmt)) {
                                throw new Exception("Error updating quantity in destination location: " . mysqli_error($conn));
                            }
                            mysqli_stmt_close($updateStmt);
                        } else {
                            throw new Exception("Error preparing update query: " . mysqli_error($conn));
                        }
                    } else {
                        // Insert new entry for destination location
                        mysqli_stmt_close($checkDestStmt);
                        $insertQuery = "INSERT INTO locationstock (RelationID, LocationID, ShelfNB, RowNB, ZoneNB, Quantity) VALUES (?, ?, ?, ?, ?, ?)";
                        $insertStmt = mysqli_prepare($conn, $insertQuery);
                        if ($insertStmt) {
                            mysqli_stmt_bind_param($insertStmt, "iisssi", $relationId, $destinationLocationId, $shelfNb, $rowNb, $zoneNb, $quantityToTransfer);
                            if (!mysqli_stmt_execute($insertStmt)) {
                                throw new Exception("Error inserting new entry in destination location: " . mysqli_error($conn));
                            }
                            mysqli_stmt_close($insertStmt);
                        } else {
                            throw new Exception("Error preparing insert query: " . mysqli_error($conn));
                        }
                    }
                } else {
                    throw new Exception("Error executing destination check query: " . mysqli_error($conn));
                }
            } else {
                throw new Exception("Error preparing destination check query: " . mysqli_error($conn));
            }
            
            // Commit the transaction
            mysqli_commit($conn);
            mysqli_autocommit($conn, TRUE);
            
            // Add to audit log
            $logquery = "INSERT INTO audit_logs (ActionName, ActionDesc) VALUES('Item transferred','Transferred $quantityToTransfer items of $productId (Brand ID: $brandId) from location $sourceLocationId to $destinationLocationId')";
            mysqli_query($conn, $logquery);
            
            echo json_encode(['success' => true, 'message' => 'Transfer successful']);
        } catch (Exception $e) {
            // Rollback the transaction
            mysqli_rollback($conn);
            mysqli_autocommit($conn, TRUE);
            
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
} catch (Exception $e) {
    // Ensure we always return a valid JSON response
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}
?>