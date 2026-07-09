# Dashboard Editing System Refactor Plan

## Overview
This document outlines a comprehensive plan to refactor the dashboard editing system to address code structure issues, eliminate duplication, and improve maintainability.

## Current Issues Identified

### 1. Code Organization Issues in dashboard.js
- Lack of modular structure
- Inconsistent parameter handling in `openEditBox` function
- UI elements not properly structured for brand selection
- Mixed concerns (UI manipulation, event handling, data processing)

### 2. Inconsistencies Between dashboard.js and inventory.js
- Different parameter signatures for `openEditBox` function
- Slight variations in brand selection UI implementation
- Duplication of nearly identical code with minor differences

### 3. HTML Structure Issues in index.php
- Editing form embedded directly in PHP file
- Inconsistent handling of brand quantities
- Mixed PHP and HTML making maintenance difficult

### 4. Code Duplication
- Nearly identical brand selection functionality in both dashboard.js and inventory.js
- Duplicate event listeners and helper functions

## Proposed Solution

### 1. Create a Modular JavaScript Architecture

#### a. Create a Shared Module for Brand Selection
Create a new file `script/brandSelector.js` that contains all brand selection functionality:

```javascript
// brandSelector.js
class BrandSelector {
    constructor(containerElement) {
        this.container = containerElement;
        this.selectedBrandsContainer = containerElement.querySelector('.selected-brands');
        this.allBrandsPicker = containerElement.querySelector('.all-brands');
        this.addBrandsButton = containerElement.querySelector('#brand-plus');
        this.closePickBrandsButton = containerElement.querySelector('.close-pick-brand');
        this.finishPickingBrandsButton = containerElement.querySelector('#submit-brands');
        this.hiddenInput = containerElement.querySelector('#productbrands');
        
        // Cache all available brand data
        this.allAvailableBrandsData = Array.from(
            this.allBrandsPicker.querySelector("div.brands").querySelectorAll("button")
        ).map(button => ({
            id: button.id,
            name: button.querySelector(".brand-name").textContent
        }));
        
        this.currentlySelectedBrandIDs = [];
        this.initEventListeners();
    }
    
    initEventListeners() {
        this.addBrandsButton.addEventListener("click", () => {
            this.allBrandsPicker.classList.add("active");
            this.renderBrandDisplays();
        });
        
        this.closePickBrandsButton.addEventListener("click", () => {
            this.allBrandsPicker.classList.remove("active");
            this.renderBrandDisplays();
        });
        
        this.finishPickingBrandsButton.addEventListener("click", () => {
            this.currentlySelectedBrandIDs = Array.from(
                this.allBrandsPicker.querySelector("div.brands").querySelectorAll("button.active")
            ).map(button => button.id);
            this.renderBrandDisplays();
            this.allBrandsPicker.classList.remove("active");
        });
    }
    
    createSelectedBrandButton(brandId, brandName) {
        const button = document.createElement("button");
        button.id = brandId;
        button.type = "button";
        button.classList.add("selected-brand-btn");
        button.innerHTML = `
            <span class="brand-name">${brandName}</span>
            <input type='number' min='0' placeholder='Qtt..' required='required' id='quantity-${brandId}' name='quantity-${brandId}'/>
            <input type='number' min='0' placeholder='$..' required='required' id='price-${brandId}' name='price-${brandId}'/>
            <i class='fa fa-x close-selected-brand'></i>
        `;
        
        button.querySelector(".close-selected-brand").addEventListener("click", () => {
            this.currentlySelectedBrandIDs = this.currentlySelectedBrandIDs.filter(id => id !== brandId);
            this.renderBrandDisplays();
        });
        
        return button;
    }
    
    createPickerBrandButton(brandId, brandName, isActive) {
        const button = document.createElement("button");
        button.id = brandId;
        button.type = "button";
        button.innerHTML = `<span class="brand-name">${brandName}</span>`;
        if (isActive) {
            button.classList.add("active");
        }
        
        button.addEventListener('click', function() {
            this.classList.toggle("active");
        });
        
        return button;
    }
    
    renderBrandDisplays() {
        // Clear current displays
        this.selectedBrandsContainer.innerHTML = "";
        this.allBrandsPicker.querySelector("div.brands").innerHTML = "";
        
        // Populate selected brands display
        this.currentlySelectedBrandIDs.forEach(brandId => {
            const brand = this.allAvailableBrandsData.find(b => b.id === brandId);
            if (brand) {
                this.selectedBrandsContainer.appendChild(
                    this.createSelectedBrandButton(brand.id, brand.name)
                );
            }
        });
        
        // Populate all brands picker
        this.allAvailableBrandsData.forEach(brand => {
            const isActiveInPicker = this.currentlySelectedBrandIDs.includes(brand.id);
            this.allBrandsPicker.querySelector("div.brands").appendChild(
                this.createPickerBrandButton(brand.id, brand.name, isActiveInPicker)
            );
        });
        
        // Update the hidden input field for form submission
        this.hiddenInput.value = this.currentlySelectedBrandIDs.join(';') + 
            (this.currentlySelectedBrandIDs.length > 0 ? ';' : '');
    }
    
    setSelectedBrands(brandIds) {
        this.currentlySelectedBrandIDs = brandIds || [];
        this.renderBrandDisplays();
    }
    
    setBrandQuantities(quantities, brandIds) {
        if (quantities && brandIds) {
            for (let i = 0; i < quantities.length; i++) {
                const input = this.container.querySelector(`#quantity-${brandIds[i]}`);
                if (input) {
                    input.value = quantities[i];
                }
            }
        }
    }
    
    getSelectedBrands() {
        return this.currentlySelectedBrandIDs;
    }
}
```

#### b. Create a Shared Module for Product Editing
Create a new file `script/productEditor.js` that contains all product editing functionality:

```javascript
// productEditor.js
class ProductEditor {
    constructor(editBoxElement, brandSelector) {
        this.editBox = editBoxElement;
        this.brandSelector = brandSelector;
        this.closeButton = editBoxElement.querySelector(".close-edit");
        this.initEventListeners();
    }
    
    initEventListeners() {
        this.closeButton.addEventListener("click", () => {
            this.close();
        });
    }
    
    open(productData) {
        // Reset the entire edit box state
        this.editBox.classList.remove("active");
        
        // Populate product details
        this.editBox.querySelector("#editProdID").value = productData.prodID;
        this.editBox.querySelector("#editProdID2").value = productData.prodID;
        this.editBox.querySelector("#editCatg").value = productData.categoryID;
        this.editBox.querySelector("#editPrice").value = productData.price;
        this.editBox.querySelector("#editDescription").value = productData.description;
        this.editBox.querySelector("#editCurrency").value = productData.currency;
        
        // Initialize brand selection
        this.brandSelector.setSelectedBrands(productData.brandIDs);
        
        // Set brand quantities if provided
        if (productData.brandQuantities && productData.brandIDs) {
            this.brandSelector.setBrandQuantities(productData.brandQuantities, productData.brandIDs);
        }
        
        // Show the edit box
        this.editBox.classList.add("active");
    }
    
    close() {
        this.editBox.classList.remove("active");
        this.brandSelector.setSelectedBrands([]);
    }
}
```

#### c. Simplified dashboard.js
Refactor dashboard.js to use the new modular components:

```javascript
// dashboard.js
document.querySelector("#view-all").addEventListener("click", () => {
    document.location = "inventory.php";
});

// Delete items box
const delconfirmbox = document.querySelector(".prod-del-box");
const delProdIDInput = delconfirmbox.querySelector("#delProdID");
const delclosebtn = delconfirmbox.querySelector(".close-box");

function openDelBox(prodID) {
    delProdIDInput.value = prodID;
    delconfirmbox.classList.add("active");
    delconfirmbox.querySelector("b").textContent = prodID;
}

delclosebtn.addEventListener("click", () => {
    delconfirmbox.classList.remove("active");
});

// Initialize brand selector and product editor
const editBox = document.querySelector(".edited-item");
const brandSelector = new BrandSelector(editBox);
const productEditor = new ProductEditor(editBox, brandSelector);

// Make productEditor globally accessible for the openEditBox function
window.productEditor = productEditor;

// Open manage data page
let openManager = document.querySelector("#open-database");
openManager.addEventListener("click", () => {
    document.location = "manage.php";
});

// Global function for opening the edit box (called from HTML)
window.openEditBox = function(prodID, categoryID, brandIDsJson, brandQuantities, price, currency, description) {
    try {
        const brandIDs = JSON.parse(brandIDsJson || '[]');
        const quantities = JSON.parse(brandQuantities || '[]');
        
        productEditor.open({
            prodID,
            categoryID,
            brandIDs,
            brandQuantities: quantities,
            price,
            currency,
            description
        });
    } catch (e) {
        console.error("Error parsing brand data:", e);
        productEditor.open({
            prodID,
            categoryID,
            brandIDs: [],
            price,
            currency,
            description
        });
    }
};
```

#### d. Simplified inventory.js
Refactor inventory.js to use the new modular components:

```javascript
// inventory.js
// Delete items box
var delconfirmbox = document.querySelector(".prod-del-box");
let delclosebtn = delconfirmbox.querySelector(".close-box");

function openDelBox(prodID) {
    let delProdID = delconfirmbox.querySelector("#delProdID");
    delProdID.value = prodID;
    delconfirmbox.classList.add("active");
    delconfirmbox.querySelector("b").textContent = delProdID.value;
}

delclosebtn.addEventListener("click", () => {
    delconfirmbox.classList.remove("active");
});

// Initialize brand selector and product editor
const editBox = document.querySelector(".edited-item");
const brandSelector = new BrandSelector(editBox);
const productEditor = new ProductEditor(editBox, brandSelector);

// Make productEditor globally accessible for the openEditBox function
window.productEditor = productEditor;

// Global function for opening the edit box (called from HTML)
window.openEditBox = function(prodID, categoryID, brandIDsJson, quantity, price, currency, description) {
    try {
        const brandIDs = JSON.parse(brandIDsJson || '[]');
        
        productEditor.open({
            prodID,
            categoryID,
            brandIDs,
            quantity,
            price,
            currency,
            description
        });
    } catch (e) {
        console.error("Error parsing brand data:", e);
        productEditor.open({
            prodID,
            categoryID,
            brandIDs: [],
            quantity,
            price,
            currency,
            description
        });
    }
};
```

### 2. HTML Structure Improvements

#### a. Create a Reusable Editing Component
Instead of embedding the editing form directly in index.php and inventory.php, create a reusable PHP component:

```php
<!-- components/edit-form.php -->
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
                <td class="price-currency">
                    <input id="editPrice" name="editPrice" type="number" step="0.01" placeholder="Eg. 99">
                    <select name="editCurrency" id="editCurrency">
                        <option value="USD">($) USD</option>
                        <option value="LBP">(£) LBP</option>
                    </select>
                </td>
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
                <td class="desc"><textarea name="editDescription" id="editDescription" placeholder="Description..."></textarea></td>
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
```

Then include this component in both index.php and inventory.php:

```php
<!-- In index.php and inventory.php -->
<?php include 'components/edit-form.php'; ?>
```

### 3. Benefits of This Approach

#### a. Elimination of Code Duplication
- Brand selection functionality is now in a single, reusable module
- Product editing functionality is centralized
- Event listeners and helper functions are no longer duplicated

#### b. Improved Maintainability
- Changes to brand selection only need to be made in one place
- Clear separation of concerns (UI, business logic, data handling)
- Easier to understand and modify individual components

#### c. Enhanced Modularity
- Each module has a single responsibility
- Modules can be tested independently
- New features can be added without affecting existing functionality

#### d. Consistent API
- Both dashboard.js and inventory.js now use the same approach
- Parameter handling is consistent across both files
- Global functions have clear, documented interfaces

## Implementation Steps

### Phase 1: Create New Modules
1. Create `script/brandSelector.js` with the BrandSelector class
2. Create `script/productEditor.js` with the ProductEditor class
3. Update HTML to include these new scripts

### Phase 2: Refactor JavaScript Files
1. Refactor `dashboard.js` to use the new modules
2. Refactor `inventory.js` to use the new modules
3. Update global `openEditBox` functions to use consistent parameter handling

### Phase 3: Refactor HTML Structure
1. Create `components/edit-form.php` with the reusable editing form
2. Update `index.php` to include the component
3. Update `inventory.php` to include the component

### Phase 4: Testing and Validation
1. Test all functionality in both dashboard and inventory pages
2. Verify brand selection works correctly
3. Verify product editing works correctly
4. Verify delete functionality still works
5. Test edge cases and error handling

## Expected Outcomes

After implementing this refactor:

1. **Reduced Code Duplication**: The brand selection functionality will exist in only one place
2. **Improved Code Organization**: Clear separation of concerns with dedicated modules
3. **Enhanced Maintainability**: Changes can be made in one place and affect all uses
4. **Better Consistency**: Both dashboard and inventory pages will use the same approach
5. **Easier Testing**: Individual modules can be tested independently
6. **Scalability**: New features can be added more easily without affecting existing code

## Risk Mitigation

1. **Backup Current Implementation**: Keep copies of current files before making changes
2. **Incremental Implementation**: Implement changes in phases to allow for testing
3. **Thorough Testing**: Test all functionality after each phase
4. **Documentation**: Maintain clear documentation of the new architecture
5. **Rollback Plan**: Have a plan to revert changes if critical issues arise

This refactor will transform the messy, duplicated code into a clean, modular, and maintainable system that follows modern JavaScript best practices.
