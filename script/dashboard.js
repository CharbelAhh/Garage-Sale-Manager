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

// Initialize brand selector and product editor only if the elements exist (only on inventory.php)
const editBox = document.querySelector(".edited-item");
if (editBox) {
    const brandSelector = new BrandSelector(editBox);
    const productEditor = new ProductEditor(editBox, brandSelector);
    
    // Make productEditor globally accessible for the openEditBox function
    window.productEditor = productEditor;
}

// Open manage data page
let openManager = document.querySelector("#open-database");
openManager.addEventListener("click", () => {
    document.location = "manage.php";
});

// Global function for opening the edit box (called from HTML)
window.openEditBox = function(prodID, categoryID, brandIDsJson, brandQuantities, brandPrices, description, brandShelfNumbers, brandRowNumbers) {
    try {
        const brandIDs = JSON.parse(brandIDsJson || '[]');
        const quantities = JSON.parse(brandQuantities || '[]');
        const prices = JSON.parse(brandPrices || '[]');
        const shelfNumbers = JSON.parse(brandShelfNumbers || '[]');
        const rowNumbers = JSON.parse(brandRowNumbers || '[]');
        
        productEditor.open({
            prodID,
            categoryID,
            brandIDs,
            brandQuantities: quantities,
            brandPrices: prices,
            brandShelfNumbers: shelfNumbers,
            brandRowNumbers: rowNumbers,
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