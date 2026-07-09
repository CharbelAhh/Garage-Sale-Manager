<?php
function handleProductEdit($conn, $productIdToUpdate, $categoryId, $description, $newBrandIDsString) {
    $errors = [];
    
    // Start transaction for consistency
    mysqli_autocommit($conn, FALSE);
    
    try {
        // Update product information
        $query = "UPDATE `products` SET
                  `CategoryID`=?,
                  `Description`=?
                  WHERE `ProductID`=?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iss", $categoryId, $description, $productIdToUpdate);
            if (!mysqli_stmt_execute($stmt)) {
                $errors[] = "Failed to update product information: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Failed to prepare product update statement: " . mysqli_error($conn);
        }
        
        if (empty($errors)) {
            // Get current location
            $currentLocationID = null;
            if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                $currentLocationID = $_COOKIE['current_location'];
            } else {
                // If no location is selected, we should not proceed with editing the product
                // This case should be handled by the client-side validation we added earlier
                // But just in case, we'll return an error
                return ["success" => false, "message" => "Please select a location first before editing a product."];
            }
            
            // Get currently associated brands for this product
            $currentBrandsQuery = "SELECT BrandID FROM productbrands WHERE ProductID = ?";
            $currentBrandsStmt = mysqli_prepare($conn, $currentBrandsQuery);
            $currentBrandIDs = [];
            if ($currentBrandsStmt) {
                mysqli_stmt_bind_param($currentBrandsStmt, "s", $productIdToUpdate);
                if (mysqli_stmt_execute($currentBrandsStmt)) {
                    $currentBrandsResult = mysqli_stmt_get_result($currentBrandsStmt);
                    while ($row = mysqli_fetch_assoc($currentBrandsResult)) {
                        $currentBrandIDs[] = $row['BrandID'];
                    }
                }
                mysqli_stmt_close($currentBrandsStmt);
            }
            
            // Get new brand IDs
            $newBrandIDsArray = [];
            if (!empty($newBrandIDsString)) {
                $newBrandIDsArray = array_filter(explode(';', trim($newBrandIDsString, ';')));
            }
            
            // Handle productbrands update - update prices for existing brands and remove deleted ones
            if (empty($errors)) {
                // Remove brands that are no longer associated with the product
                $brandsToRemove = array_diff($currentBrandIDs, $newBrandIDsArray);
                foreach ($brandsToRemove as $brandIdToRemove) {
                    // First delete from locationstock table (foreign key constraint)
                    $deleteLocationStockQuery = "DELETE ls FROM locationstock ls
                                                 JOIN productbrands pb ON ls.RelationID = pb.RelationID
                                                 WHERE pb.ProductID = ? AND pb.BrandID = ?";
                    $stmt = mysqli_prepare($conn, $deleteLocationStockQuery);
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "ss", $productIdToUpdate, $brandIdToRemove);
                        if (!mysqli_stmt_execute($stmt)) {
                            $errors[] = "Failed to delete location stock for brand: " . mysqli_error($conn);
                            mysqli_stmt_close($stmt);
                            break;
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $errors[] = "Failed to prepare location stock delete statement: " . mysqli_error($conn);
                        break;
                    }
                    
                    // Then delete from productbrands table
                    $deleteBrandQuery = "DELETE FROM productbrands WHERE ProductID = ? AND BrandID = ?";
                    $stmt = mysqli_prepare($conn, $deleteBrandQuery);
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "ss", $productIdToUpdate, $brandIdToRemove);
                        if (!mysqli_stmt_execute($stmt)) {
                            $errors[] = "Failed to delete brand: " . mysqli_error($conn);
                            mysqli_stmt_close($stmt);
                            break;
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $errors[] = "Failed to prepare brand delete statement: " . mysqli_error($conn);
                        break;
                    }
                }
                
                // Update or insert prices for remaining/new brands
                foreach ($newBrandIDsArray as $brandId) {
                    $brandId = trim($brandId);
                    if (!empty($brandId)) {
                        // Get price, wholesale price, and cost for this brand
                        $brandPrice = isset($_POST['price-'.$brandId]) ? $_POST['price-'.$brandId] : 0;
                        $brandWholesalePrice = isset($_POST['wholesale-price-'.$brandId]) ? $_POST['wholesale-price-'.$brandId] : 0;
                        $brandCost = isset($_POST['cost-'.$brandId]) ? $_POST['cost-'.$brandId] : 0;
                        
                        // Check if productbrands entry exists for this product-brand combination
                        $relationQuery = "SELECT RelationID FROM productbrands WHERE ProductID = ? AND BrandID = ?";
                        $stmt = mysqli_prepare($conn, $relationQuery);
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "ss", $productIdToUpdate, $brandId);
                            if (mysqli_stmt_execute($stmt)) {
                                $relationResult = mysqli_stmt_get_result($stmt);
                                if (mysqli_num_rows($relationResult) > 0) {
                                    // Update existing entry
                                    mysqli_stmt_close($stmt);
                                    $updatePriceQuery = "UPDATE productbrands SET Price = ?, WholesalePrice = ?, Cost = ? WHERE ProductID = ? AND BrandID = ?";
                                    $stmt = mysqli_prepare($conn, $updatePriceQuery);
                                    if ($stmt) {
                                        mysqli_stmt_bind_param($stmt, "dddss", $brandPrice, $brandWholesalePrice, $brandCost, $productIdToUpdate, $brandId);
                                        if (!mysqli_stmt_execute($stmt)) {
                                            $errors[] = "Failed to update brand price: " . mysqli_error($conn);
                                            mysqli_stmt_close($stmt);
                                            break;
                                        }
                                        mysqli_stmt_close($stmt);
                                    } else {
                                        $errors[] = "Failed to prepare brand price update statement: " . mysqli_error($conn);
                                        break;
                                    }
                                } else {
                                    // Productbrands entry doesn't exist, create it
                                    mysqli_stmt_close($stmt);
                                    $insertProductBrandQuery = "INSERT INTO productbrands (ProductID, BrandID, Price, WholesalePrice, Cost) VALUES (?, ?, ?, ?, ?)";
                                    $stmt = mysqli_prepare($conn, $insertProductBrandQuery);
                                    if ($stmt) {
                                        mysqli_stmt_bind_param($stmt, "ssddd", $productIdToUpdate, $brandId, $brandPrice, $brandWholesalePrice, $brandCost);
                                        if (!mysqli_stmt_execute($stmt)) {
                                            $errors[] = "Failed to insert productbrands entry: " . mysqli_error($conn);
                                            mysqli_stmt_close($stmt);
                                            break;
                                        }
                                        mysqli_stmt_close($stmt);
                                    } else {
                                        $errors[] = "Failed to prepare productbrands insert statement: " . mysqli_error($conn);
                                        break;
                                    }
                                }
                            } else {
                                $errors[] = "Failed to check productbrands entry: " . mysqli_error($conn);
                                mysqli_stmt_close($stmt);
                                break;
                            }
                        } else {
                            $errors[] = "Failed to prepare productbrands check statement: " . mysqli_error($conn);
                            break;
                        }
                    }
                }
            }
            
            // Handle location stock update - only for current location and only for brands that are still associated
            if (empty($errors)) {
                foreach ($newBrandIDsArray as $brandId) {
                    $brandId = trim($brandId);
                    if (!empty($brandId)) {
                        // Get quantity, shelf, row and zone numbers for this brand
                        $brandQuantity = isset($_POST['quantity-'.$brandId]) ? $_POST['quantity-'.$brandId] : 0;
                        $brandShelfNb = isset($_POST['shelf-'.$brandId]) ? $_POST['shelf-'.$brandId] : '';
                        $brandRowNb = isset($_POST['row-'.$brandId]) ? $_POST['row-'.$brandId] : '';
                        $brandZoneNb = isset($_POST['zone-'.$brandId]) ? $_POST['zone-'.$brandId] : '';
                        
                        // Get the RelationID for this product-brand combination
                        $relationQuery = "SELECT RelationID FROM productbrands WHERE ProductID = ? AND BrandID = ?";
                        $stmt = mysqli_prepare($conn, $relationQuery);
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "ss", $productIdToUpdate, $brandId);
                            if (mysqli_stmt_execute($stmt)) {
                                $relationResult = mysqli_stmt_get_result($stmt);
                                if (mysqli_num_rows($relationResult) > 0) {
                                    $relationRow = mysqli_fetch_assoc($relationResult);
                                    $relationID = $relationRow['RelationID'];
                                    
                                    // Handle location stock update - check if entry exists for this relation and location
                                    mysqli_stmt_close($stmt);
                                    
                                    // Check if there's already an entry for this relation and current location
                                    $checkQuery = "SELECT LocationStockID FROM locationstock WHERE RelationID = ? AND LocationID = ?";
                                    $stmt = mysqli_prepare($conn, $checkQuery);
                                    if ($stmt) {
                                        mysqli_stmt_bind_param($stmt, "ii", $relationID, $currentLocationID);
                                        if (mysqli_stmt_execute($stmt)) {
                                            $checkResult = mysqli_stmt_get_result($stmt);
                                            if (mysqli_num_rows($checkResult) > 0) {
                                                // Update existing entry for current location
                                                mysqli_stmt_close($stmt);
                                                $updateQuery = "UPDATE locationstock SET Quantity = ?, ShelfNB = ?, RowNB = ?, ZoneNB = ? WHERE RelationID = ? AND LocationID = ?";
                                                $stmt = mysqli_prepare($conn, $updateQuery);
                                                if ($stmt) {
                                                    mysqli_stmt_bind_param($stmt, "isssii", $brandQuantity, $brandShelfNb, $brandRowNb, $brandZoneNb, $relationID, $currentLocationID);
                                                    if (!mysqli_stmt_execute($stmt)) {
                                                        $errors[] = "Failed to update location stock: " . mysqli_error($conn);
                                                    }
                                                } else {
                                                    $errors[] = "Failed to prepare location stock update statement: " . mysqli_error($conn);
                                                }
                                            } else {
                                                // Get shelf and row numbers from another location for this product-brand combination
                                                // Only if they weren't provided in the form
                                                if (empty($brandShelfNb) && empty($brandRowNb) && empty($brandZoneNb)) {
                                                    $shelfRowQuery = "SELECT ls.ShelfNB, ls.RowNB, ls.ZoneNB
                                                                     FROM locationstock ls
                                                                     JOIN productbrands pb ON ls.RelationID = pb.RelationID
                                                                     WHERE pb.ProductID = ? AND pb.BrandID = ? AND ls.LocationID != ?
                                                                     LIMIT 1";
                                                    $stmt2 = mysqli_prepare($conn, $shelfRowQuery);
                                                    if ($stmt2) {
                                                        mysqli_stmt_bind_param($stmt2, "sii", $productIdToUpdate, $brandId, $currentLocationID);
                                                        if (mysqli_stmt_execute($stmt2)) {
                                                            $shelfRowResult = mysqli_stmt_get_result($stmt2);
                                                            if (mysqli_num_rows($shelfRowResult) > 0) {
                                                                $shelfRowData = mysqli_fetch_assoc($shelfRowResult);
                                                                $brandShelfNb = $shelfRowData['ShelfNB'];
                                                                $brandRowNb = $shelfRowData['RowNB'];
                                                                $brandZoneNb = $shelfRowData['ZoneNB'];
                                                            }
                                                        }
                                                        mysqli_stmt_close($stmt2);
                                                    }
                                                }
                                                
                                                // Insert new entry for current location
                                                mysqli_stmt_close($stmt);
                                                $insertLocationStockQuery = "INSERT INTO locationstock (RelationID, LocationID, ShelfNB, RowNB, ZoneNB, Quantity) VALUES (?, ?, ?, ?, ?, ?)";
                                                $stmt = mysqli_prepare($conn, $insertLocationStockQuery);
                                                if ($stmt) {
                                                    mysqli_stmt_bind_param($stmt, "iisssi", $relationID, $currentLocationID, $brandShelfNb, $brandRowNb, $brandZoneNb, $brandQuantity);
                                                    if (!mysqli_stmt_execute($stmt)) {
                                                        $errors[] = "Failed to insert location stock: " . mysqli_error($conn);
                                                    }
                                                } else {
                                                    $errors[] = "Failed to prepare location stock insert statement: " . mysqli_error($conn);
                                                }
                                            }
                                        } else {
                                            $errors[] = "Failed to check existing location stock: " . mysqli_error($conn);
                                        }
                                        if ($stmt) {
                                            mysqli_stmt_close($stmt);
                                        }
                                    } else {
                                        $errors[] = "Failed to prepare location stock check statement: " . mysqli_error($conn);
                                    }
                                } else {
                                    $errors[] = "Product-brand combination not found: " . $productIdToUpdate . " - " . $brandId;
                                }
                            } else {
                                $errors[] = "Failed to get relation ID: " . mysqli_error($conn);
                            }
                        } else {
                            $errors[] = "Failed to prepare relation ID query: " . mysqli_error($conn);
                        }
                    }
                }
            }
        }
        
        // Commit transaction if no errors
        if (empty($errors)) {
            mysqli_commit($conn);
            mysqli_autocommit($conn, TRUE);
            return ["success" => true, "message" => "Edited item <b>".htmlspecialchars($productIdToUpdate)."</b>"];
        } else {
            // Rollback transaction on error
            mysqli_rollback($conn);
            mysqli_autocommit($conn, TRUE);
            return ["success" => false, "message" => implode("<br>", $errors)];
        }
    } catch (Exception $e) {
        // Rollback transaction on exception
        mysqli_rollback($conn);
        mysqli_autocommit($conn, TRUE);
        return ["success" => false, "message" => "Exception occurred: " . $e->getMessage()];
    }
}
?>
