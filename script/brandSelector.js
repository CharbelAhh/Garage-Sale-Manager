// Check if BrandSelector is already defined to prevent "Identifier 'BrandSelector' has already been declared" error
if (typeof BrandSelector !== 'undefined') {
    // BrandSelector is already defined, so we don't need to do anything
    console.log("BrandSelector class already defined, skipping redefinition");
} else {
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
                // Get newly selected brand IDs
                const newlySelectedBrandIDs = Array.from(
                    this.allBrandsPicker.querySelector("div.brands").querySelectorAll("button.active")
                ).map(button => button.id);
                
                // Add newly selected brands to the existing list, avoiding duplicates
                newlySelectedBrandIDs.forEach(brandId => {
                    if (!this.currentlySelectedBrandIDs.includes(brandId)) {
                        this.currentlySelectedBrandIDs.push(brandId);
                    }
                });
                
                this.renderBrandDisplays();
                this.allBrandsPicker.classList.remove("active");
            });
        }
        
        createSelectedBrandButton(brandId, brandName, shelfNb = '', rowNb = '', zoneNb = '') {
            const button = document.createElement("button");
            button.id = `selected-brand-${brandId}`;
            button.type = "button";
            button.classList.add("selected-brand-btn");
            button.innerHTML = `
                <span class="brand-name">${brandName}</span>
                <input type='number' min='0' step='1' placeholder='Qtt..' required='required' id='quantity-${brandId}' name='quantity-${brandId}'/>
                <input type='number' min='0' step='0.01' placeholder='$..' required='required' id='price-${brandId}' name='price-${brandId}'/>
                <input type='number' min='0' step='0.01' placeholder='Wholesale $..' id='wholesale-price-${brandId}' name='wholesale-price-${brandId}'/>
                <input type='number' min='0' step='0.01' placeholder='Cost $..' id='cost-${brandId}' name='cost-${brandId}'/>
                <input type='text' maxlength='5' placeholder='Col..' id='shelf-${brandId}' name='shelf-${brandId}' value='${shelfNb}'/>
                <input type='text' maxlength='5' placeholder='Row..' id='row-${brandId}' name='row-${brandId}' value='${rowNb}'/>
                <input type='text' maxlength='5' placeholder='Zone..' id='zone-${brandId}' name='zone-${brandId}' value='${zoneNb}'/>
                <i class='fa fa-x close-selected-brand'></i>
            `;
            
            button.querySelector(".close-selected-brand").addEventListener("click", (e) => {
                e.stopPropagation();
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
            // Store current values of all input fields before clearing them
            const currentValues = {};
            this.currentlySelectedBrandIDs.forEach(brandId => {
                const quantityInput = this.container.querySelector(`#quantity-${brandId}`);
                const priceInput = this.container.querySelector(`#price-${brandId}`);
                const wholesalePriceInput = this.container.querySelector(`#wholesale-price-${brandId}`);
                const costInput = this.container.querySelector(`#cost-${brandId}`);
                const shelfInput = this.container.querySelector(`#shelf-${brandId}`);
                const rowInput = this.container.querySelector(`#row-${brandId}`);
                const zoneInput = this.container.querySelector(`#zone-${brandId}`);
                
                if (quantityInput) currentValues[`${brandId}-quantity`] = quantityInput.value;
                if (priceInput) currentValues[`${brandId}-price`] = priceInput.value;
                if (wholesalePriceInput) currentValues[`${brandId}-wholesalePrice`] = wholesalePriceInput.value;
                if (costInput) currentValues[`${brandId}-cost`] = costInput.value;
                if (shelfInput) currentValues[`${brandId}-shelf`] = shelfInput.value;
                if (rowInput) currentValues[`${brandId}-row`] = rowInput.value;
                if (zoneInput) currentValues[`${brandId}-zone`] = zoneInput.value;
            });
            
            // Clear current displays
            this.selectedBrandsContainer.innerHTML = "";
            this.allBrandsPicker.querySelector("div.brands").innerHTML = "";
            
            // Populate selected brands display
            this.currentlySelectedBrandIDs.forEach(brandId => {
                const brand = this.allAvailableBrandsData.find(b => b.id === brandId);
                if (brand) {
                    // Get shelf, row and zone numbers if available
                    const shelfNb = this.brandShelfNumbers && this.brandShelfNumbers[brandId] ? this.brandShelfNumbers[brandId] : '';
                    const rowNb = this.brandRowNumbers && this.brandRowNumbers[brandId] ? this.brandRowNumbers[brandId] : '';
                    const zoneNb = this.brandZoneNumbers && this.brandZoneNumbers[brandId] ? this.brandZoneNumbers[brandId] : '';
                    const brandButton = this.createSelectedBrandButton(brand.id, brand.name, shelfNb, rowNb, zoneNb);
                    this.selectedBrandsContainer.appendChild(brandButton);
                    
                    // Restore the values for this brand
                    const quantityInput = brandButton.querySelector(`#quantity-${brandId}`);
                    const priceInput = brandButton.querySelector(`#price-${brandId}`);
                    const wholesalePriceInput = brandButton.querySelector(`#wholesale-price-${brandId}`);
                    const costInput = brandButton.querySelector(`#cost-${brandId}`);
                    const shelfNbInput = brandButton.querySelector(`#shelf-${brandId}`);
                    const rowNbInput = brandButton.querySelector(`#row-${brandId}`);
                    const zoneNbInput = brandButton.querySelector(`#zone-${brandId}`);
                    
                    if (quantityInput && currentValues[`${brandId}-quantity`] !== undefined) {
                        quantityInput.value = currentValues[`${brandId}-quantity`];
                    }
                    if (priceInput && currentValues[`${brandId}-price`] !== undefined) {
                        priceInput.value = currentValues[`${brandId}-price`];
                    }
                    if (wholesalePriceInput && currentValues[`${brandId}-wholesalePrice`] !== undefined) {
                        wholesalePriceInput.value = currentValues[`${brandId}-wholesalePrice`];
                    }
                    if (costInput && currentValues[`${brandId}-cost`] !== undefined) {
                        costInput.value = currentValues[`${brandId}-cost`];
                    }
                    if (shelfNbInput && currentValues[`${brandId}-shelf`] !== undefined) {
                        shelfNbInput.value = currentValues[`${brandId}-shelf`];
                    }
                    if (rowNbInput && currentValues[`${brandId}-row`] !== undefined) {
                        rowNbInput.value = currentValues[`${brandId}-row`];
                    }
                    if (zoneNbInput && currentValues[`${brandId}-zone`] !== undefined) {
                        zoneNbInput.value = currentValues[`${brandId}-zone`];
                    }
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
                        // Ensure the value is a valid number
                        const value = parseFloat(quantities[i]);
                        input.value = isNaN(value) ? 0 : Math.max(0, value);
                    }
                }
            }
        }
        
        setBrandShelfNumbers(shelfNumbers, brandIds) {
            if (shelfNumbers && brandIds) {
                // Store shelf numbers for later use in renderBrandDisplays
                this.brandShelfNumbers = {};
                for (let i = 0; i < shelfNumbers.length; i++) {
                    this.brandShelfNumbers[brandIds[i]] = shelfNumbers[i];
                    
                    // Set the input value if the element exists
                    const input = this.container.querySelector(`#shelf-${brandIds[i]}`);
                    if (input) {
                        input.value = shelfNumbers[i];
                    }
                }
            }
        }
        
        setBrandRowNumbers(rowNumbers, brandIds) {
            if (rowNumbers && brandIds) {
                // Store row numbers for later use in renderBrandDisplays
                this.brandRowNumbers = {};
                for (let i = 0; i < rowNumbers.length; i++) {
                    this.brandRowNumbers[brandIds[i]] = rowNumbers[i];
                    
                    // Set the input value if the element exists
                    const input = this.container.querySelector(`#row-${brandIds[i]}`);
                    if (input) {
                        input.value = rowNumbers[i];
                    }
                }
            }
        }
        
        setBrandZoneNumbers(zoneNumbers, brandIds) {
            if (zoneNumbers && brandIds) {
                // Store zone numbers for later use in renderBrandDisplays
                this.brandZoneNumbers = {};
                for (let i = 0; i < zoneNumbers.length; i++) {
                    this.brandZoneNumbers[brandIds[i]] = zoneNumbers[i];
                    
                    // Set the input value if the element exists
                    const input = this.container.querySelector(`#zone-${brandIds[i]}`);
                    if (input) {
                        input.value = zoneNumbers[i];
                    }
                }
            }
        }
        
        setBrandPrices(prices, brandIds) {
            if (prices && brandIds) {
                for (let i = 0; i < prices.length; i++) {
                    const input = this.container.querySelector(`#price-${brandIds[i]}`);
                    if (input) {
                        // Ensure the value is a valid number
                        const value = parseFloat(prices[i]);
                        input.value = isNaN(value) ? 0 : Math.max(0, value.toFixed(2));
                    }
                }
            }
        }
        
        setBrandWholesalePrices(wholesalePrices, brandIds) {
            if (wholesalePrices && brandIds) {
                for (let i = 0; i < wholesalePrices.length; i++) {
                    const input = this.container.querySelector(`#wholesale-price-${brandIds[i]}`);
                    if (input) {
                        // Ensure the value is a valid number
                        const value = parseFloat(wholesalePrices[i]);
                        input.value = isNaN(value) ? 0 : Math.max(0, value.toFixed(2));
                    }
                }
            }
        }
        
        setBrandCosts(costs, brandIds) {
            if (costs && brandIds) {
                for (let i = 0; i < costs.length; i++) {
                    const input = this.container.querySelector(`#cost-${brandIds[i]}`);
                    if (input) {
                        // Ensure the value is a valid number
                        const value = parseFloat(costs[i]);
                        input.value = isNaN(value) ? 0 : Math.max(0, value.toFixed(2));
                    }
                }
            }
        }
        
        getSelectedBrands() {
            return this.currentlySelectedBrandIDs;
        }
        
        // Validate that all brand inputs have valid values
        validateInputs() {
            let isValid = true;
            const errorMessages = [];
            
            this.currentlySelectedBrandIDs.forEach(brandId => {
                // Validate quantity
                const quantityInput = this.container.querySelector(`#quantity-${brandId}`);
                if (quantityInput) {
                    const quantity = parseFloat(quantityInput.value);
                    if (isNaN(quantity) || quantity < 0) {
                        isValid = false;
                        errorMessages.push(`Invalid quantity for brand ID ${brandId}`);
                        quantityInput.classList.add('invalid');
                    } else {
                        quantityInput.classList.remove('invalid');
                    }
                }
                
                // Validate price
                const priceInput = this.container.querySelector(`#price-${brandId}`);
                if (priceInput) {
                    const price = parseFloat(priceInput.value);
                    if (isNaN(price) || price < 0) {
                        isValid = false;
                        errorMessages.push(`Invalid price for brand ID ${brandId}`);
                        priceInput.classList.add('invalid');
                    } else {
                        priceInput.classList.remove('invalid');
                    }
                }
                
                // Validate wholesale price (optional, but if provided should be valid)
                const wholesalePriceInput = this.container.querySelector(`#wholesale-price-${brandId}`);
                if (wholesalePriceInput) {
                    const wholesalePrice = parseFloat(wholesalePriceInput.value);
                    if (isNaN(wholesalePrice) || wholesalePrice < 0) {
                        isValid = false;
                        errorMessages.push(`Invalid wholesale price for brand ID ${brandId}`);
                        wholesalePriceInput.classList.add('invalid');
                    } else {
                        wholesalePriceInput.classList.remove('invalid');
                    }
                }
                
                // Validate cost (optional, but if provided should be valid)
                const costInput = this.container.querySelector(`#cost-${brandId}`);
                if (costInput) {
                    const cost = parseFloat(costInput.value);
                    if (isNaN(cost) || cost < 0) {
                        isValid = false;
                        errorMessages.push(`Invalid cost for brand ID ${brandId}`);
                        costInput.classList.add('invalid');
                    } else {
                        costInput.classList.remove('invalid');
                    }
                }
                
                // Validate shelf number (optional, but if provided should be valid)
                const shelfInput = this.container.querySelector(`#shelf-${brandId}`);
                if (shelfInput) {
                    const shelfValue = shelfInput.value.trim();
                    if (shelfValue && shelfValue.length > 5) {
                        isValid = false;
                        errorMessages.push(`Shelf number for brand ID ${brandId} is too long (max 5 characters)`);
                        shelfInput.classList.add('invalid');
                    } else {
                        shelfInput.classList.remove('invalid');
                    }
                }
                
                // Validate row number (optional, but if provided should be valid)
                const rowInput = this.container.querySelector(`#row-${brandId}`);
                if (rowInput) {
                    const rowValue = rowInput.value.trim();
                    if (rowValue && rowValue.length > 5) {
                        isValid = false;
                        errorMessages.push(`Row number for brand ID ${brandId} is too long (max 5 characters)`);
                        rowInput.classList.add('invalid');
                    } else {
                        rowInput.classList.remove('invalid');
                    }
                }
                
                // Validate zone number (optional, but if provided should be valid)
                const zoneInput = this.container.querySelector(`#zone-${brandId}`);
                if (zoneInput) {
                    const zoneValue = zoneInput.value.trim();
                    if (zoneValue && zoneValue.length > 5) {
                        isValid = false;
                        errorMessages.push(`Zone number for brand ID ${brandId} is too long (max 5 characters)`);
                        zoneInput.classList.add('invalid');
                    } else {
                        zoneInput.classList.remove('invalid');
                    }
                }
            });
            
            return {
                isValid: isValid,
                errors: errorMessages
            };
        }
    }
    
    // Make BrandSelector available globally
    window.BrandSelector = BrandSelector;
}