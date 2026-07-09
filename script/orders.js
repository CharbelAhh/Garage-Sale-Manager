const addOrderBtn = document.querySelector("section#add-order-btn button#add-order");
addOrderBtn.addEventListener("click",()=>{
    
})

var orders = document.querySelectorAll("section#orders .order");
var options = document.querySelectorAll("section#orders .order .order-options");
var cancelBox = document.querySelector("section#orders #box-cancel-order");
var completeBox = document.querySelector("section#orders #box-complete-order");

orders.forEach(e=>{
    e.querySelector(".order-infos .showOptions").addEventListener("click",()=>{
        orders.forEach(e1=>{
            e1.querySelector(".order-options").classList.remove("visible");
        })
        e.querySelector(".order-options").classList.add("visible");
        if (cancelBox.classList.contains("visible"))
            cancelBox.classList.remove("visible");
    })
})

options.forEach(e=>{
    e.querySelector(".options-top button.close-options").addEventListener("click",()=>{
        e.classList.remove("visible");
    })
})

// Mostly related to the 'Affirm order cancellation box'
function cancelOrder(orderNum){
    options.forEach(e=>{
        e.classList.remove("visible");
    })
    cancelBox.querySelector("p span").textContent = `#${orderNum}`;
    cancelBox.querySelector("form input").value = orderNum;
    cancelBox.classList.add("visible");
}
cancelBox.querySelector(".options-top button.close-options").addEventListener("click",()=>{
    cancelBox.classList.remove("visible");
})

function completeOrder(orderNum){
    options.forEach(e=>{
        e.classList.remove("visible");
    })
    completeBox.querySelector("p span").textContent = `#${orderNum}`;
    completeBox.querySelector("form input").value = orderNum;
    completeBox.classList.add("visible");
}
completeBox.querySelector(".options-top button.close-options").addEventListener("click",()=>{
    completeBox.classList.remove("visible");
})
// Until here

var orderForm = document.querySelector("section#add-order-btn form.orderForm");
var orderBtn = document.querySelector("section#add-order-btn button#add-order");
var closeOrder = document.querySelector("section#add-order-btn form.orderForm .top-form button#close-order");
var pages = document.querySelector("section#add-order-btn form.orderForm .main-form");

orderBtn.addEventListener("click",()=>{
    orderForm.classList.add("visible");
})

var addPageBtn = document.querySelector("section#add-order-btn form.orderForm .bottom-order #add-page");
var remPageBtn = document.querySelector("section#add-order-btn form.orderForm .bottom-order #rem-page");
var confirmDel = document.querySelector("section#add-order-btn form.orderForm .bottom-order .confirm-del");

let currentPage = orderForm.querySelector("#currentPage");
let pageAmt = orderForm.querySelector("#page");
let pageCopy = document.querySelector("section#add-order-btn form.orderForm .main-form .page").innerHTML;

let pageAmtDiv = orderForm.querySelector(".page-nb");
let currentPageHTML = orderForm.querySelector(".page-now");
let pageAmtHTML = orderForm.querySelector(".page-amt");

addPageBtn.addEventListener("click", () => {
    pageAmt.value = parseInt(pageAmt.value) + 1;

    let tempDiv = document.createElement("div");
    tempDiv.innerHTML = pageCopy;

    // let clientName = tempDiv.querySelector(".client-name");
    // if (clientName) {
    //     clientName.remove();
    // }

    pages.insertAdjacentHTML("beforeend", `<div class="page page${pageAmt.value} hidden">${tempDiv.innerHTML}</div>`);
    pageAmtHTML.innerHTML = pageAmt.value;

    if (remPageBtn.classList.contains("hidden"))
        remPageBtn.classList.remove("hidden");
    if (pageAmtDiv.classList.contains("hidden"))
        pageAmtDiv.classList.remove("hidden");

});


let rightArrowPage = pageAmtDiv.querySelector(".arrow-right");
let leftArrowPage = pageAmtDiv.querySelector(".arrow-left");

rightArrowPage.addEventListener("click",rightArrowClick);

function rightArrowClick(){
    if (parseInt(currentPage.value) < parseInt(pageAmt.value))
    {
        currentPage.value = parseInt(currentPage.value)+1
        currentPageHTML.innerHTML = currentPage.value;

        for (let i=0; i<pages.children.length; i++)
            {
                if (!pages.children[i].classList.contains("hidden"))
                    pages.children[i].classList.add("hidden");
            }

        pages.children[parseInt(currentPage.value)-1].classList.remove("hidden");

        if (pages.children[parseInt(currentPage.value)-1].classList.contains("left"))
            pages.children[parseInt(currentPage.value)-1].classList.remove("left")
        pages.children[parseInt(currentPage.value)-1].classList.add("right");
    }
}
leftArrowPage.addEventListener("click",()=>{
    if (parseInt(currentPage.value) > 1)
    {
        currentPage.value = parseInt(currentPage.value)-1
        currentPageHTML.innerHTML = currentPage.value;

        for (let i=0; i<pages.children.length; i++)
            {
                if (!pages.children[i].classList.contains("hidden"))
                    pages.children[i].classList.add("hidden");
            }

        pages.children[parseInt(currentPage.value)-1].classList.remove("hidden");

        if (pages.children[parseInt(currentPage.value)-1].classList.contains("right"))
            pages.children[parseInt(currentPage.value)-1].classList.remove("right")
        pages.children[parseInt(currentPage.value)-1].classList.add("left");
    }
})

remPageBtn.addEventListener("click",()=>{
    if (confirmDel.classList.contains("hidden"));
        confirmDel.classList.remove("hidden");
})
confirmDel.querySelector(".confirm-btns").children[1].addEventListener("click",()=>{
    confirmDel.classList.add("hidden");
})
confirmDel.querySelector(".confirm-btns").children[0].addEventListener("click",()=>{
    pageAmt.value = parseInt(pageAmt.value)-1;
    pageAmtHTML.innerHTML = pageAmt.value;
    pages.removeChild(pages.children[parseInt(currentPage.value)-1])

    confirmDel.classList.add("hidden");
    if (parseInt(pageAmt.value)<=1)
    {
        remPageBtn.classList.add("hidden");
        pageAmtDiv.classList.add("hidden");
    }

    if (parseInt(currentPage.value) > 1)
    {
        currentPage.value = parseInt(currentPage.value)-1
        currentPageHTML.innerHTML = currentPage.value;

        for (let i=0; i<pages.children.length; i++)
            {
                if (!pages.children[i].classList.contains("hidden"))
                    pages.children[i].classList.add("hidden");
            }

        pages.children[parseInt(currentPage.value)-1].classList.remove("hidden");
    }
    else
        pages.children[0].classList.remove("hidden");
})

// clients manager

var selectClientsBtn = orderForm.querySelector("#select-client");
var clientManager = orderForm.querySelector(".manage-clients");
selectClientsBtn.addEventListener("click",()=>{
    if (clientManager.classList.contains("active"))
        clientManager.classList.remove("active");
    else 
        clientManager.classList.add("active");
})

var clientsList = orderForm.querySelector(".clients-list");
var clientNameInput = orderForm.querySelector("#name");
var allClients = clientsList.querySelectorAll(".client-option");

allClients.forEach(e=>{
    e.addEventListener("click",()=>{
        clientNameInput.value = e.id;
        selectClientsBtn.innerHTML = e.getAttribute("data-name");
        clientManager.classList.remove("active");
    })
})

// clients box drag

const clientForm = document.querySelector("section#add-order-btn #client-form");
var openClientForm = clientManager.querySelector("#add-client");

openClientForm.addEventListener("click",()=>{
    if (!clientForm.classList.contains("active"))
        clientForm.classList.add("active");
})

let isDragging = false;
let offsetX = 0;
let offsetY = 0;

clientForm.addEventListener("mousedown", (e) => {
  isDragging = true;
  offsetX = e.clientX - clientForm.offsetLeft;
  offsetY = e.clientY - clientForm.offsetTop;
});

document.addEventListener("mousemove", (e) => {
  if (!isDragging) return;

  // Calculate new position
  let newLeft = e.clientX - offsetX;
  let newTop = e.clientY - offsetY;

  // Get window and box dimensions
  const maxLeft = window.innerWidth - clientForm.offsetWidth/2;
  const maxTop = window.innerHeight - clientForm.offsetHeight/2;

  // Clamp position within screen
  newLeft = Math.max(0, Math.min(newLeft, maxLeft));
  newTop = Math.max(0, Math.min(newTop, maxTop));

  // Apply new position
  clientForm.style.left = newLeft + "px";
  clientForm.style.top = newTop + "px";
});

document.addEventListener("mouseup", () => {
  isDragging = false;
});

var closeClientForm = clientForm.querySelector("#close-clientform");
    closeClientForm.addEventListener("click",()=>{
        clientForm.classList.remove("active");
})

closeOrder.addEventListener("click",()=>{
    orderForm.classList.remove("visible");

    if (clientManager.classList.contains("active"))
        clientManager.classList.remove("active");

    if (clientForm.classList.contains("active"))
        clientForm.classList.remove("active");
})

// ORDER CANCEL, ORDER MANAGEMENT, ORDER OPTIONS

function OpenItemDesc(ItemID)
{
    let ItemDesc = document.querySelector(`#desc-${ItemID}`);

    document.querySelectorAll(".prod-desc-box").forEach(e=>{
        if (e!=ItemDesc && e.classList.contains("visible"))
            e.classList.remove("visible");
    });

    if (ItemDesc.classList.contains("visible"))
        ItemDesc.classList.remove("visible");
    else
        ItemDesc.classList.add("visible");
}