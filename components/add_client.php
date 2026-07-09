<?php
$pdo = new PDO("mysql:host=localhost;dbname=garagedb", "root", ""); // Adjust credentials

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["new-client"])) {
    $name = trim($_POST["new-client"]);
    $number = trim($_POST["client-number"]);

    if ($name !== "") {
        // Insert new client
        $stmt = $pdo->prepare("INSERT INTO clients (ClientName, PhoneNumber) VALUES (?, ?)");
        $stmt->execute([$name, $number]);
    }
}

// Now fetch updated list
$stmt = $pdo->query("SELECT * FROM clients ORDER BY ClientID DESC"); // Optional: ORDER BY id DESC

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<div id='".$row['ClientID']."' data-name=\"".$row['ClientName']."\" class='client-option'>" . htmlspecialchars($row['ClientName']) . " (" . htmlspecialchars($row['PhoneNumber']) . ")</div>";
}
?>

<script>
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

</script>