// ADD CLIENT MODAL
const addClientBtn = document.getElementById("add-client-btn");
const addClientForm = document.getElementById("add-client-form");
const cancelAddClientBtn = document.getElementById("cancel-add-client-btn");

addClientBtn.addEventListener("click", () => {
    addClientForm.classList.add("active");
});
cancelAddClientBtn.addEventListener("click", () => {
    addClientForm.classList.remove("active");
});

document.body.addEventListener("click",()=>{
    document.querySelectorAll(`.all-client-orders`).forEach(e=>{
        e.classList.remove("active");
    })
	document.querySelectorAll(".order-content-box").forEach(e=>{
        e.classList.remove("active");
    });

})
document.querySelectorAll(`.all-client-orders`).forEach(e=>{
    e.addEventListener("click",(ev)=>{
        ev.stopPropagation();
    })
})
document.querySelectorAll(".order-content-box").forEach(e=>{
    e.addEventListener("click",(ev)=>{
        ev.stopPropagation();
    })
})


// MANAGE.PHP - CLIENT INFO MODAL
const clientManageBtns = document.querySelectorAll(".manage-client-btn");

clientManageBtns.forEach(btn => {
    const clientID = btn.getAttribute("data-clientid");
    const clientInfoBox = document.querySelector(`#CLIENT-INFO-${clientID}`);
    const confirmDelBox = document.querySelector(`#CONFIRM-DELETE-CLIENT-${clientID}`);
    const clientOrdersBox =  document.querySelector(`#all-client-orders-${clientID}`);

    // When the manage client button is clicked
    btn.addEventListener("click", () => {
        document.querySelectorAll(".client-info-modal").forEach(modal => {
            modal.classList.remove("active");
        });

        if (clientInfoBox.classList.contains("active"))
            clientInfoBox.classList.remove("active");
        else
            clientInfoBox.classList.add("active");

        document.querySelectorAll(`.all-client-orders`).forEach(e=>{
            e.classList.remove("active");
        })
    });

    clientInfoBox.querySelector("button.orders-client-btn").addEventListener("click",(ev)=>{
        if (clientOrdersBox.classList.contains("active"))
            clientOrdersBox.classList.remove("active");
        else
            clientOrdersBox.classList.add("active");

        document.querySelectorAll(".order-content-box").forEach(e=>{
            e.classList.remove("active");
        });

        ev.stopPropagation();
    })
    
    document.querySelector(`#CLIENT-INFO-${clientID} .close-client-info`).addEventListener("click", () => {
        clientInfoBox.classList.remove("active");
        confirmDelBox.classList.remove("active");
        clientOrdersBox.classList.remove("active");
    });
    document.querySelector(`#delete-client-form-${clientID} .delete-client-btn`).addEventListener("click", () => {
        confirmDelBox.classList.add("active");
    });
    document.querySelector(`#delete-client-form-${clientID} .cancel-delete-client-btn`).addEventListener("click", () => {
        confirmDelBox.classList.remove("active");
    });
});

// Client info content management
// Payment progress bar
const orderItems = document.querySelectorAll(".purchase-item");

if (orderItems)
{
    orderItems.forEach(e=>{
        const paymentBar = e.querySelector(".payment-bar");
        // let price = paymentBar.getAttribute("data-cost");
        // let paid = paymentBar.getAttribute("data-paid");
        var progressBar = e.querySelector(".progress-bar");
        // let prc_paid = (paid/price)*100 // percentage paid
        // progressBar.style.backgroundSize = `${prc_paid}% 100%`;
        if (paymentBar)
        {
            progressBar.addEventListener("click",()=>{
                document.querySelectorAll(".payment-form").forEach(b=>{
                    b.classList.remove("active");
                })
            })

            paymentBar.querySelector("button").addEventListener("click",()=>{
                document.querySelectorAll(".payment-form").forEach(b=>{
                    b.classList.remove("active");
                })
                document.querySelector(`#payment-form-${paymentBar.querySelector("button").getAttribute("data-order")}`).classList.add("active");
            })
        }

        let pay_history_section = e.querySelector(".payment-history-section");
        let open_history = pay_history_section.querySelector(".payment-history-btn");
        let history_content = pay_history_section.querySelector(".payment-history-content");

        open_history.addEventListener("click",()=>{
            document.querySelectorAll(".payment-history-content").forEach(i=>{
                if (i!=history_content)
                    i.classList.remove("active");
            })
            
            if (history_content.classList.contains("active"))
                history_content.classList.remove("active");
            else
                history_content.classList.add("active");
        })

        history_content.querySelectorAll(".payment-history-i").forEach(i=>{
            let cancelPayBtn = i.querySelector(".cancel-payment-btn");
            let cancelPayForm = i.querySelector(".cancel-payment-form");
            
            if (cancelPayBtn)
            {
                cancelPayForm.querySelector(".return-btn").addEventListener("click",()=>{
                    document.querySelectorAll(".cancel-payment-form").forEach(ii=>{
                        ii.classList.remove("active");
                    })
                })

                cancelPayBtn.addEventListener("click",()=>{

                    document.querySelectorAll(".cancel-payment-form").forEach(ii=>{
                        ii.classList.remove("active");
                    })

                    cancelPayForm.classList.add("active");
                })
            }  
        })
    })
}

function openDelItem(e){
    let selectedForm=document.querySelector(`#item-del-form-${e}`);

    let delItemForms = document.querySelectorAll(".item-del-form").forEach(f=>{
        if (f!=selectedForm)
            f.classList.remove("active");
    })
    if (selectedForm.classList.contains("active"))
        selectedForm.classList.remove("active");
    else
        selectedForm.classList.add("active");
}