// Navigation bar / page selection

var navBar = document.querySelector(".nav-bar");
navBar.querySelectorAll("button").forEach(b=>{
    if (document.location.href.includes(b.id))
        b.classList.add("active");

    b.addEventListener("click",()=>{
        if (b.textContent == "Dashboard")
            document.location = "index.php";
        else
            document.location = b.textContent.toLowerCase()+".php";
    })
})
let activeButtons=0; // Check if any of the nav buttons were selected
navBar.querySelectorAll("button").forEach(b=>{
    if (b.classList.contains("active"))
        activeButtons++;
})
if (!activeButtons)
    navBar.querySelector("#dashboard").classList.add("active");

var btnList = document.querySelector(".add-buttons");

document.querySelector("#add-items").addEventListener("click",()=>{
    if (btnList.classList.contains("hidden"))
        btnList.classList.remove("hidden");
    else btnList.classList.add("hidden");
});

var addItemsBtns = document.querySelector(".add-buttons");
var addForms = document.querySelector(".add-forms");
var forms = addForms.querySelectorAll("form");


addItemsBtns.querySelectorAll("button").forEach(b=>{
    b.addEventListener("click",()=>{
        if (addForms.classList.contains("hide"))
            addForms.classList.remove("hide");
        addForms.classList.add("show");

        forms.forEach(e => {
            if (e.classList.contains("active"))
                e.classList.remove("active")
        });

        btnList.classList.add("hidden");
        forms[Object.values(addItemsBtns.querySelectorAll("button")).indexOf(b)].classList.add("active");
    })
})

// Add Product //

let pageHeader = document.querySelector("header");

let allBrandsDiv = document.querySelector("div.all-brands");
let addBrandsBtn = document.querySelector("#brand-plus");
let closePickBrands = document.querySelector(".close-pick-brand");

addBrandsBtn.addEventListener("click",()=>{
    if (!allBrandsDiv.classList.contains("active"))
        allBrandsDiv.classList.add("active");
})

let pickedBrands = [];
let brandButtons = allBrandsDiv.querySelector("div.brands").querySelectorAll("button");
brandButtons.forEach(b=>{
    b.addEventListener("click",()=>{
        if (!b.classList.contains("active"))
        {
            b.classList.add("active");
            pickedBrands.push(b.id);
        }
        else
        {
            b.classList.remove("active");
            let removedBrandIndex;
            for (let i=0; i<pickedBrands.length; i++)
            {
                if (pickedBrands[i]==b.id)
                    removedBrandIndex=i;
            }
            pickedBrands.splice(removedBrandIndex,1);
        }
    });
})

let brandsInput = document.querySelector("#productbrands");

closePickBrands.addEventListener("click",()=>{
brandButtons.forEach(b=>{
        if (b.classList.contains("active"))
        {
            b.classList.remove("active");
            let removedBrandIndex;
            for (let i=0; i<pickedBrands.length; i++)
            {
                if (pickedBrands[i]==b.id)
                    removedBrandIndex=i;
            }
            pickedBrands.splice(removedBrandIndex,1);
        }
    })
    
    allBrandsDiv.classList.remove("active");
})

// I was here

let selectedBrandsDiv = document.querySelector(".selected-brands");
let finishBrandsBtn = document.querySelector("#submit-brands");
finishBrandsBtn.addEventListener("click",()=>{
    brandButtons.forEach(b=>{
        if (pickedBrands.includes(b.id))
        {
            b.classList.remove("active");
            
            // To see where the index of the child we want to remove is
            let removedBrandIndex;
            for (let i=0; i<pickedBrands.length; i++)
            {
                if (pickedBrands[i]==b.id)
                    removedBrandIndex=i;
            }

            pickedBrands.splice(removedBrandIndex,1);
            let parentNode = b.parentNode;
            parentNode.removeChild(b);

            let removeBrandBtn = document.createElement("button");
            removeBrandBtn.type = "button";
            removeBrandBtn.innerHTML = "<i class='fa fa-x'></i>";
            removeBrandBtn.classList.add("remove-brand-btn");

            let QuantityBrandInput = document.createElement("Input");
            QuantityBrandInput.type = "number";
            QuantityBrandInput.min = "0";
            QuantityBrandInput.required = "required";
            QuantityBrandInput.name = `quantity-${b.id}`;
            QuantityBrandInput.placeholder = "Qtt..";
            QuantityBrandInput.classList.add("brand-quantity");

            let PriceBrandInput = document.createElement("Input");
            PriceBrandInput.type = "number";
            PriceBrandInput.min = "0";
            PriceBrandInput.required = "required";
            PriceBrandInput.name = `price-${b.id}`;
            PriceBrandInput.placeholder = "$..";
            PriceBrandInput.classList.add("brand-price");

            brandsInput.value+=b.id+";";

            removeBrandBtn.addEventListener("click",()=>{
                selectedBrandsDiv.removeChild(b);
                b.removeChild(removeBrandBtn);
                b.removeChild(QuantityBrandInput)
                b.removeChild(PriceBrandInput)
                b.classList.remove("selected-brand-btn");
                parentNode.appendChild(b);

                // we split the values in the string separated by a semicolon
                // and we remove the item from it, then put it back together as a string
                let chopList = brandsInput.value.split(";");
                let chopItemIndex;
                for (let i=0; i<chopList.length; i++)
                {
                    if (chopList[i]==b.id)
                        chopItemIndex=i;
                }
                chopList.splice(chopItemIndex,1)

                let newValue = "";
                chopList.forEach(e=>{
                    if (e!="")
                        newValue+=e+";";
                })
                brandsInput.value = newValue;
            })

            b.appendChild(QuantityBrandInput);
            b.appendChild(PriceBrandInput);
            b.appendChild(removeBrandBtn);
            b.classList.add("selected-brand-btn");
            selectedBrandsDiv.appendChild(b);
        }
    });
    allBrandsDiv.classList.remove("active");
})

// close forms

var formCloseBtns = document.querySelectorAll(".close-form");

formCloseBtns.forEach(e=>{
    e.addEventListener("click",()=>{
        addForms.classList.remove("show");
        addForms.classList.add("hide");

        if (allBrandsDiv.classList.contains("active"))
            allBrandsDiv.classList.remove("active");
    })
})

// close message box

var msgbox = document.querySelector(".msg-box");
let msgclose = msgbox.querySelector(".close-box");

msgclose.addEventListener("click",()=>{
    msgbox.classList.remove("active");
    document.location = location.href;
})

// Location selection handling
document.addEventListener("DOMContentLoaded", function() {
    const locationSelect = document.getElementById("location-select");
    
    if (locationSelect) {
        // Handle location selection change
        locationSelect.addEventListener("change", function() {
            const selectedLocationId = this.value;
            
            // Send AJAX request to set/delete the cookie
            fetch("set_location_cookie.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "locationId=" + encodeURIComponent(selectedLocationId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("Location cookie set/deleted successfully");
                    // Refresh the page after setting/deleting the cookie
                    location.reload();
                } else {
                    console.error("Error setting/deleting location cookie:", data.message);
                }
            })
            .catch(error => {
                console.error("Error setting/deleting location cookie:", error);
                // Even if there's an error, we still want to refresh the page
                // to maintain consistent behavior
                location.reload();
            });
        });
    }
});