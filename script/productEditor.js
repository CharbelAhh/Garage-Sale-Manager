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
        
        // Add form submission validation
        const saveButton = this.editBox.querySelector(".save-edit");
        if (saveButton) {
            saveButton.addEventListener("click", (e) => {
                const validation = this.brandSelector.validateInputs();
                if (!validation.isValid) {
                    e.preventDefault();
                    alert("Please fix the following errors:\n" + validation.errors.join("\n"));
                }
            });
        }
    }
    
    open(productData) {
        // Reset the entire edit box state
        this.editBox.classList.remove("active");
        
        // Populate product details
        this.editBox.querySelector("#editProdID").value = productData.prodID;
        this.editBox.querySelector("#editProdID2").value = productData.prodID;
        this.editBox.querySelector("#editCatg").value = productData.categoryID;
        // this.editBox.querySelector("#editPrice").value = productData.price;
        this.editBox.querySelector("#editDescription").value = productData.description;
        // this.editBox.querySelector("#editCurrency").value = productData.currency;
        
        // Initialize brand selection
        this.brandSelector.setSelectedBrands(productData.brandIDs);
        
        // Set brand quantities if provided
        if (productData.brandQuantities && productData.brandIDs) {
            this.brandSelector.setBrandQuantities(productData.brandQuantities, productData.brandIDs);
        }
        
        // Set brand prices if provided
        if (productData.brandPrices && productData.brandIDs) {
            this.brandSelector.setBrandPrices(productData.brandPrices, productData.brandIDs);
        }
        
        // Set brand wholesale prices if provided
        if (productData.brandWholesalePrices && productData.brandIDs) {
            this.brandSelector.setBrandWholesalePrices(productData.brandWholesalePrices, productData.brandIDs);
        }
        
        // Set brand costs if provided
        if (productData.brandCosts && productData.brandIDs) {
            this.brandSelector.setBrandCosts(productData.brandCosts, productData.brandIDs);
        }
        
        // Set shelf numbers if provided
        if (productData.brandShelfNumbers && productData.brandIDs) {
            this.brandSelector.setBrandShelfNumbers(productData.brandShelfNumbers, productData.brandIDs);
        }
        
        // Set row numbers if provided
        if (productData.brandRowNumbers && productData.brandIDs) {
            this.brandSelector.setBrandRowNumbers(productData.brandRowNumbers, productData.brandIDs);
        }
        
        // Set zone numbers if provided
        if (productData.brandZoneNumbers && productData.brandIDs) {
            this.brandSelector.setBrandZoneNumbers(productData.brandZoneNumbers, productData.brandIDs);
        }
        
        // Show the edit box
        this.editBox.classList.add("active");
    }
    
    close() {
        this.editBox.classList.remove("active");
        this.brandSelector.setSelectedBrands([]);
    }
}