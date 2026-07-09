document.addEventListener('DOMContentLoaded', function () {
	function fetchReports(clientID,orderID,table) {
		const xhr = new XMLHttpRequest();
		xhr.open('POST', 'components/fetch_client_info.php', true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4) {
				if (xhr.status === 200) {
					// If one of the sections wasn't found, as a fallback replace the whole report area
                    // const main = document.querySelectorAll(".order-content-box");
					const main = document.querySelector(`#CLIENT-INFO-${clientID} .order-content-box`);
                    if (main)
                    {
                        // main.forEach(e=>{
                        //     e.innerHTML = xhr.responseText;
                        // })
						main.innerHTML = xhr.responseText;
						main.classList.add("active");
                    }
				} else {
					console.error('Failed to load reports:', xhr.statusText);
				}
			}
		};
		xhr.send('ClientID='+ encodeURIComponent(clientID) +'&OrderID=' + encodeURIComponent(orderID)+"&Table="+encodeURIComponent(table));
	}

    // OPEN ORDER INFORMATION BUTTON FOR ALL ORDERS
    const openOrderBtns = document.querySelectorAll(".open-order");
	const main = document.querySelectorAll(".order-content-box");
    openOrderBtns.forEach(e=>{
        e.addEventListener("click",()=>{
			main.forEach(m=>{
				m.classList.remove("active");
			}); // If there are other info boxes already open, close them

			document.querySelectorAll(".all-client-orders").forEach(e=>{
				e.classList.remove("active");
			}); // Close the order history boxes

            fetchReports(e.getAttribute("data-client"),e.getAttribute("data-id"),e.getAttribute("data-table"));
        })
    })
})