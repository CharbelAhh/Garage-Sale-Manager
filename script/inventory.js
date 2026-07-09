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

// Initialize brand selector and product editor only if the elements exist
const editBox = document.querySelector(".edited-item");
if (editBox) {
    const brandSelector = new BrandSelector(editBox);
    const productEditor = new ProductEditor(editBox, brandSelector);
    
    // Make productEditor globally accessible for the openEditBox function
    window.productEditor = productEditor;
}

// Global function for opening the edit box (called from HTML)
window.openEditBox = function(prodID, categoryID, brandIDsJson, brandQuantities, brandPrices, description, brandShelfNumbers, brandRowNumbers, brandZoneNumbers, brandWholesalePrices, brandCosts) {
    try {
        const brandIDs = JSON.parse(brandIDsJson || '[]');
        const quantities = JSON.parse(brandQuantities || '[]');
        const prices = JSON.parse(brandPrices || '[]');
        const wholesalePrices = JSON.parse(brandWholesalePrices || '[]');
        const costs = JSON.parse(brandCosts || '[]');
        const shelfNumbers = JSON.parse(brandShelfNumbers || '[]');
        const rowNumbers = JSON.parse(brandRowNumbers || '[]');
        const zoneNumbers = JSON.parse(brandZoneNumbers || '[]');
        
        productEditor.open({
            prodID,
            categoryID,
            brandIDs,
            brandQuantities: quantities,
            brandPrices: prices,
            brandWholesalePrices: wholesalePrices,
            brandCosts: costs,
            brandShelfNumbers: shelfNumbers,
            brandRowNumbers: rowNumbers,
            brandZoneNumbers: zoneNumbers,
            description
        });
    } catch (e) {
        console.error("Error parsing brand data:", e);
        productEditor.open({
            prodID,
            categoryID,
            brandIDs: [],
            description
        });
    }
};

// FILTER SCRIPT SECTION

let filterBox = document.querySelector("#filter-box"); // Being the filter box
let filterBtn = document.querySelector("#filter-btn"); // Being the open filter button
let closeFilter = filterBox.querySelector("#close-filter"); // Being the close filter button

filterBtn.addEventListener("click",()=>{
    filterBox.classList.add("active");
})
closeFilter.addEventListener("click",()=>{
    filterBox.classList.remove("active");
})