<?php
include 'components/db_connection.php';
$result = "";
$msg = "";


if (isset($_COOKIE['current_location']))
{
    $query = "SELECT LocationName FROM locations WHERE `LocationID` = '".$_COOKIE['current_location']."'";
    $queryresult = mysqli_query($conn,$query);
    $current_location = mysqli_fetch_row($queryresult)[0];
}

if (isset($_POST['add-category']))
{
    $catg = trim($_POST['catgname']);

    $exists = 0;
    $querycheck = "SELECT CategoryID FROM categories WHERE `CatgName`='$catg'";
    if (mysqli_num_rows(mysqli_query($conn,$querycheck))>0)
        $exists = 1;

    if (!empty(trim($catg)) && !$exists)
    {
        $query = "INSERT INTO categories (`CatgName`) VALUE('$catg');";
        mysqli_query($conn,$query);
        $result = "success";
        $msg = "Added category $catg";

        $logquery = "INSERT INTO audit_logs (ActionName,ActionDesc) VALUES('Item added','Added category $catg')";
        mysqli_query($conn,$logquery);
        echo mysqli_error($conn);
    }
    else {
        $result = "fail";
        if ($exists)
            $msg = "This category already exists.";
        else
            $msg = "Something went wrong, please check that no fields are empty.";
    }
}

if (isset($_POST['add-brand']))
{
    $brand = trim($_POST['brandname']);

    $exists = 0;
    $querycheck = "SELECT BrandID FROM brand WHERE `BrandName`='$brand'";
    if (mysqli_num_rows(mysqli_query($conn,$querycheck))>0)
        $exists = 1;

    if (!empty(trim($brand)))
    {
        $query = "INSERT INTO brand (`BrandName`) VALUE('$brand');";
        mysqli_query($conn,$query);
        $result = "success";
        $msg = "Added brand $brand";

        $logquery = "INSERT INTO audit_logs (ActionName,ActionDesc) VALUES('Item added','Added brand $brand')";
        mysqli_query($conn,$logquery);
        echo mysqli_error($conn);
    }
    else {
        $result = "fail";
        if ($exists)
            $msg = "This brand already exists.";
        else
            $msg = "Something went wrong, please check that no fields are empty.";
    }
}

if (isset($_POST['add-location']))
{
    $location = trim($_POST['locationname']);

    $exists = 0;
    $querycheck = "SELECT LocationID FROM locations WHERE `LocationName`='$location'";
    if (mysqli_num_rows(mysqli_query($conn,$querycheck))>0)
        $exists = 1;

    if (!empty(trim($location)))
    {
        $query = "INSERT INTO locations (`LocationName`) VALUE('$location');";
        mysqli_query($conn,$query);
        $result = "success";
        $msg = "Added location $location";

        $logquery = "INSERT INTO audit_logs (ActionName,ActionDesc) VALUES('Item added','Added location $location')";
        mysqli_query($conn,$logquery);
        echo mysqli_error($conn);
    }
    else {
        $result = "fail";
        if ($exists)
            $msg = "This location already exists.";
        else
            $msg = "Something went wrong, please check that no fields are empty.";
    }
}

if (isset($_POST['add-product']))
{
    $catgID = trim($_POST['category']);
    $partID = trim($_POST['partid']);
    $shelfnb = isset($_POST['shelfnb']) ? trim($_POST['shelfnb']) : '';
    $rownb = isset($_POST['rownb']) ? trim($_POST['rownb']) : '';

    // separating the list of brands and remove empty elements
    $brands = array_filter(explode(";",$_POST['productbrands']));
    // $quantity = $_POST['quantity'];

    $desc = trim($_POST['description']);

    $query = "INSERT INTO products (`ProductID`,`CategoryID`,`Description`)
    VALUES(\"$partID\",\"$catgID\",\"$desc\")";

    // Check if a location is selected
    $locationSelected = true;
    if (!isset($_COOKIE['current_location']) || empty($_COOKIE['current_location'])) {
        $locationSelected = false;
    }
    
    if (!empty(trim($catgID)) && !empty(trim($partID)) && count($brands)>0
    && !empty(trim($desc)) && $locationSelected)
    {
        mysqli_query($conn,$query);

        foreach ($brands as $brand) {
            $brand = trim($brand);
            $brandquantity = $_POST['quantity-'.$brand];
            $brandprice = $_POST['price-'.$brand];
            $brandwholesaleprice = $_POST['wholesale-price-'.$brand];
            $brandcost = $_POST['cost-'.$brand];
            $brandshelfnb = isset($_POST['shelf-'.$brand]) ? trim($_POST['shelf-'.$brand]) : '';
            $brandrownb = isset($_POST['row-'.$brand]) ? trim($_POST['row-'.$brand]) : '';
            $brandzonenb = isset($_POST['zone-'.$brand]) ? trim($_POST['zone-'.$brand]) : '';

            $query = "INSERT INTO productbrands(BrandID,ProductID,Price,WholesalePrice,Cost) VALUES('$brand','$partID','$brandprice','$brandwholesaleprice','$brandcost')";
            mysqli_query($conn,$query);
            
            // Get the RelationID of the inserted record
            $relationID = mysqli_insert_id($conn);
            
            // Insert into locationstock table with the current location
            if (isset($_COOKIE['current_location']) && !empty($_COOKIE['current_location'])) {
                $locationID = $_COOKIE['current_location'];
            } else {
                // If no location is selected, we should not proceed with adding the product
                // This case should be handled by the client-side validation we added earlier
                // But just in case, we'll show an error message
                $result = "fail";
                $msg = "Please select a location first before adding a product.";
                continue; // Skip to the next brand
            }
            
            $query = "INSERT INTO locationstock(RelationID, LocationID, ShelfNB, RowNB, ZoneNB, Quantity) VALUES('$relationID', '$locationID', '" . mysqli_real_escape_string($conn, $brandshelfnb) . "', '" . mysqli_real_escape_string($conn, $brandrownb) . "', '" . mysqli_real_escape_string($conn, $brandzonenb) . "', '$brandquantity')";
            mysqli_query($conn,$query);
        }
        echo mysqli_error($conn);

        $result = "success";
        $msg = "Added product $partID";

        $logquery = "INSERT INTO audit_logs (ActionName,ActionDesc) VALUES('Item added','Added product $partID')";
        mysqli_query($conn,$logquery);
        echo mysqli_error($conn);
    }
    else {
        $result = "fail";
        if (!$locationSelected) {
            $msg = "Please select a location first before adding a product.";
        } else {
            $msg = "Something went wrong, please check that no fields are empty.";
        }
    }
}
?>

<header>
    <div class="title">
        <i class="fa-solid fa-screwdriver-wrench"></i> Garage
    </div>
    <button class="hamburger-menu">
        <i class="fa fa-bars"></i>
    </button>
    <div class="nav-bar">
        <button id="dashboard" class="nav-btn">Dashboard</button>
        <button id="inventory" class="nav-btn">Inventory</button>
        <button id="reports" class="nav-btn">Reports</button>
        <button id="orders" class="nav-btn">Orders</button>
    </div>
    <div class="additems">
        <div class="add-forms">
            <form action="" method="POST" class="product-form">
                <div class="form-top">
                    <h1>Add a product <?php if (isset($_COOKIE['current_location'])) echo "| $current_location"; ?></h1>
                    <button class="close-form" type="button"><i class="fa fa-x"></i></button>
                </div>
                <div class="fields">
                    <div class="field">
                        <label for="category">Category</label>
                        <select name="category" id="category" required>
                            <option value="">Select</option>
                            <?php
                            $query = "SELECT * FROM `categories` ORDER BY CatgName";
                            $resultquery = mysqli_query($conn, $query);

                            while ($row=mysqli_fetch_array($resultquery))
                            {
                                $catg=$row['CatgName'];
                                $catgID=$row['CategoryID'];
                                echo "<option value='$catgID'>$catg</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="partid">Part ID</label>
                        <input type="text" required maxlength="16" name="partid" id="partid" placeholder="Write here...">
                    </div>
                    <div class="field">
                        <label for="brand-plus">Brand</label>
                        <div class="brands-edit">
                            <button id="brand-plus" type="button"><i class="fa fa-plus"></i></button>
                            <div class="selected-brands">
                            </div>
                            <input type="text" required name="productbrands" id="productbrands" hidden>
                            <div class="all-brands">
                                <div class="top">
                                    <h1>Pick brands</h1>
                                    <button type="button" class="close-pick-brand"><i class="fa fa-x"></i></button>
                                </div>
                                <div class="brands">
                                    <?php
                                    $query = "SELECT * FROM `brand`";
                                    $resultquery = mysqli_query($conn, $query);

                                    while ($row=mysqli_fetch_array($resultquery))
                                    {
                                        $brand=$row['BrandName'];
                                        $brandID=$row['BrandID'];
                                        echo "<button id='$brandID' type='button'><span class='brand-name'>$brand</span></button>";
                                    }
                                    ?>
                                </div>
                                <button id="submit-brands" type="button">Finish</button>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="field">
                        <label for="quantity">Stock Quantity</label>
                        <input type="number" required name="quantity" id="quantity" placeholder="Write here...">
                    </div> -->
                    <!-- <div class="field">
                        <label for="PartName">Price</label>
                        <div class="pricetag">
                            <input type="number" required name="price" id="price" placeholder="Eg. 99$">
                            <select required name="currency" id="selectCurrency">
                                <option value="USD" selected>($) USD</option>
                                <option value="LBP">(£) LBP</option>
                            </select>
                        </div>
                    </div> -->
                    <div class="field">
                        <label for="description">Description</label>
                        <textarea required name="description" id="description" placeholder="Write here..."></textarea>
                    </div>
                </div>
                <button type="submit" name="add-product">Add</button>
            </form>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="product-form">
                <div class="form-top">
                    <h1>Add a category</h1>
                    <button class="close-form" type="button"><i class="fa fa-x"></i></button>
                </div>
                <div class="fields">
                    <div class="field">
                        <label for="catgname">Category Name</label>
                        <input type="text" required name="catgname" id="catgname" placeholder="Write here...">
                    </div>
                </div>
                <button type="submit" name="add-category">Add</button>
            </form>
            <form action="" method="POST" class="product-form">
                <div class="form-top">
                    <h1>Add a brand</h1>
                    <button class="close-form" type="button"><i class="fa fa-x"></i></button>
                </div>
                <div class="fields">
                    <div class="field">
                        <label for="brandname">Brand Name</label>
                        <input type="text" required name="brandname" id="brandname" placeholder="Write here...">
                    </div>
                </div>
                <button type="submit" name="add-brand">Add</button>
            </form>
            <form action="" method="POST" class="product-form">
                <div class="form-top">
                    <h1>Add a location</h1>
                    <button class="close-form" type="button"><i class="fa fa-x"></i></button>
                </div>
                <div class="fields">
                    <div class="field">
                        <label for="locationname">This is where added products will go.</label>
                        <input type="text" required name="locationname" id="locationname" placeholder="Write here...">
                    </div>
                </div>
                <button type="submit" name="add-location">Add</button>
            </form>
        </div>

        <div class="add-buttons hidden">
            <button id="add-product" class="btn"><i class="fa fa-plus"></i> Add Product</button>
            <button id="add-category" class="btn"><i class="fa fa-plus"></i> Add Category</button>
            <button id="add-brand" class="btn"><i class="fa fa-plus"></i> Add Brand</button>
            <button id="add-location" class="btn"><i class="fa fa-plus"></i> Add Location</button>
        </div>

        <button id="add-items"><i class="fa fa-wrench"></i> Add Items</button>
    </div>
</header>

<script src="script/brandSelector.js?v=<?=time()?>"></script>

<script>
// Function to get a cookie by name
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

// Add client-side validation for product form submission
document.addEventListener("DOMContentLoaded", function() {
    // Initialize brand selector for the product form
    const productForm = document.querySelector(".product-form");
    if (productForm) {
        const brandSelector = new BrandSelector(productForm);
    }
    
    // Get the add product button in the navigation bar
    const addProductButton = document.getElementById("add-product");
    
    if (addProductButton) {
        // Add click event listener with useCapture=true to execute before other listeners
        addProductButton.addEventListener("click", function(e) {
            // Check if a location is selected by checking the current_location cookie
            const currentLocation = getCookie("current_location");
            if (!currentLocation || currentLocation === "") {
                alert("Please select a location first before adding a product.");
                // Prevent the event from propagating to other listeners
                e.stopImmediatePropagation();
                // Prevent the default action of showing the form
                e.preventDefault();
                return false;
            }
        }, true); // useCapture = true
    }
    
    // Get the hamburger menu button and nav bar
    const hamburgerMenu = document.querySelector(".hamburger-menu");
    const navBar = document.querySelector(".nav-bar");
    
    if (hamburgerMenu && navBar) {
        // Add click event listener to toggle the nav bar
        hamburgerMenu.addEventListener("click", function() {
            navBar.classList.toggle("show");
        });
        
        // Close the nav bar when clicking outside of it
        document.addEventListener("click", function(e) {
            if (!navBar.contains(e.target) && !hamburgerMenu.contains(e.target)) {
                navBar.classList.remove("show");
            }
        });
    }
});
</script>