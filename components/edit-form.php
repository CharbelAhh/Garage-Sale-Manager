<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
    <table>
        <tfoot class="edited-item">
            <tr class="edit-top">
                <td colspan="6">Edit item</td>
            </tr>
            <tr>
                <td class="part-name">
                    <input type="text" id="editProdID" disabled value="PartName">
                    <input type="hidden" id="editProdID2" name="editProdID">
                </td>
                <td class="category">
                    <select name="editCategory" id="editCatg">
                        <?php
                        $query = "SELECT * FROM `categories`";
                        $resultquery = mysqli_query($conn, $query);
                        
                        while ($row = mysqli_fetch_array($resultquery)) {
                            echo "<option value='" . htmlspecialchars($row['CategoryID']) . "'>" . htmlspecialchars($row['CatgName']) . "</option>";
                        }
                        ?>
                    </select>
                </td>
                <!-- <td class="price-currency">
                    <input id="editPrice" name="editPrice" type="number" step="0.01" placeholder="Eg. 99">
                    <select name="editCurrency" id="editCurrency">
                        <option value="USD">($) USD</option>
                        <option value="LBP">(£) LBP</option>
                    </select>
                </td> -->
                <td class="brand">
                    <label for="brand-plus">Brand</label>
                    <div class="brands-edit">
                        <button id="brand-plus" type="button"><i class="fa fa-plus"></i> Add Brands</button>
                        <div class="selected-brands"></div>
                        <input type="hidden" name="productbrands" id="productbrands">
                        <div class="all-brands">
                            <div class="top">
                                <h1>Pick brands</h1>
                                <button type="button" class="close-pick-brand"><i class="fa fa-x"></i></button>
                            </div>
                            <div class="brands">
                                <?php
                                $query = "SELECT * FROM `brand`";
                                $resultquery = mysqli_query($conn, $query);
                                
                                while ($row = mysqli_fetch_array($resultquery)) {
                                    echo "<button id='" . htmlspecialchars($row['BrandID']) . "' type='button'><span class='brand-name'>" . htmlspecialchars($row['BrandName']) . "</span></button>";
                                }
                                ?>
                            </div>
                            <button id="submit-brands" type="button">Finish Picking</button>
                        </div>
                    </div>
                </td>
                <td class="desc"><textarea name="editDescription" id="editDescription" placeholder="Description..." required></textarea></td>
            </tr>
            <tr class="edit-bottom">
                <td colspan="6">
                    <button class="save-edit" name="save-edit" type="submit">Save changes</button>
                    <button class="close-edit" type="button">Close</button>
                </td>
            </tr>
        </tfoot>
    </table>
</form>