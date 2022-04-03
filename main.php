<?php
/*
Plugin Name: Ingram Marketplace Integration
Description: An Ingram Marketplace Integration
Author: Hugo Lindsay
Version: 0.1
*/

// The admin page identifier in the url
define("SLUG", 'ingram-marketplace');
define("DB_DATA", 'ingram_marketplace_data');

// Adds a menu page in the Admin Dashboard
add_action('admin_menu', 'imgram_marketplace_plugin_menu');
function imgram_marketplace_plugin_menu(){
    add_menu_page( 'Ingram Marketplace Plugin', 'Ingram Marketplace', 'manage_options', SLUG, 'ingram_marketplace_init' );
}

// Called when the menu page loads
function ingram_marketplace_init(){
	// Check for task to perform and execute it
	if ($_POST["task"] == "update_credentials") {
		task_update_credentials();
	} else if ($_POST["task"] == "test_connection") {
		task_test_connection();
	} else if ($_POST["task"] == "get_total_product_count") {
		task_get_total_product_count();
	} else if ($_POST["task"] == "download_json") {
		task_download_json();
	} else if ($_POST["task"] == "download_csv") {
		task_download_csv();
	} else if ($_POST["task"] == "import_products") {
		task_import_products();
	}

	display_page_content();
}

// Displays the admin page content
function display_page_content() {
	$data = get_option(DB_DATA);

	// Display Page title
    echo '<h1>Ingram Marketplace</h1><hr><br>';

	// Display form and button to update credentials
    echo '
	<form style="display:table" method="post" action="' . admin_url( "admin.php?page=" . SLUG ) . '">   
		<input type="hidden" name="task" value="update_credentials">   
		<div style="display:table-row"> <label style="display:table-cell" for="base_url">Base Url:</label>   <input type="text" name="base_url" id="base_url" value="' . $data->base_url . '"> </div>
		<div style="display:table-row"> <label style="display:table-cell" for="username">Username:</label>   <input type="text" name="username" id="username" value="' . $data->username . '"> </div>
		<div style="display:table-row"> <label style="display:table-cell" for="password">Password:</label>   <input type="text" name="password" id="password" value="' . $data->password . '"> </div>
		<div style="display:table-row"> <label style="display:table-cell" for="subscription_key">Subscription Key:</label>   <input type="text" name="subscription_key" id="subscription_key" value="' . $data->subscription_key . '"></div>
		<div style="display:table-row"> <label style="display:table-cell" for="marketplace">Marketplace:</label>   <input type="text" name="marketplace" id="marketplace" value="' . $data->marketplace . '"> </div>
		<br>
	<input type="submit" name="submit" value="Save"> </form> <br><hr><br>';

	// Display Test Connection button
    echo '
	<form method="post" action="' . admin_url( "admin.php?page=" . SLUG ) . '">   
		<input type="hidden" name="task" value="test_connection">
	<input type="submit" name="submit" value="Test Connection"> </form> <br><hr><br>';

	// Display button to query total products from Ingram
    echo '
	<form method="post" action="' . admin_url( "admin.php?page=" . SLUG ) . '">   
		<input type="hidden" name="task" value="get_total_product_count">
	<input type="submit" name="submit" value="Get Total Product Count"> </form> <br><hr><br>';

	// Display from and buttons to query products from Ingram and use that to either: download json/download csv/import them into wordpress
    echo '
	<h2>Import Products</h2>
	<p>We get a json from Ingram\'s API. We then convert that to a CSV compatible for bulk import into wordpress. 
	You can dowload the JSON or CSV to analyze them without affecting wordpress. When you\'re ready, click \'Import Products\' to import products from Ingram directly into wordpress.
	<b>Note: These commands can take a few minutes when dealing with a lot of data.</b></p>
	<script>
		function set_task_download_json() { document.getElementById("import_product_task").value = "download_json" }
		function set_task_download_csv() { document.getElementById("import_product_task").value = "download_csv" }
		function set_task_import_products() { document.getElementById("import_product_task").value = "import_products" }
	</script>
	<form method="post" action="' . admin_url( "admin.php?page=" . SLUG ) . '">   
		<input id="import_product_task" type="hidden" name="task" value="">
		<div style="display:table-row"> <label style="display:table-cell" for="limit">Limit:</label>   <input type="text" name="limit" id="limit" value="10000"> </div>
		<div style="display:table-row"> <label style="display:table-cell" for="offset">Offset:</label>   <input type="text" name="offset" id="offset" value="0"> </div>
		<br>
		<button type="submit" name="submit" onclick="set_task_download_json()">Download JSON</button> 
		<button type="submit" name="submit" onclick="set_task_download_csv()">Download CSV</button>
		<button type="submit" name="submit" onclick="set_task_import_products()">Import Products</button>
	</form> <br><hr><br>';
}

// Updates the ingram marketplace credentials 
function task_update_credentials() {
	$data = get_option(DB_DATA);
	$data->base_url = $_POST["base_url"];
	$data->username = $_POST["username"];
	$data->password = $_POST["password"];
	$data->subscription_key = $_POST["subscription_key"];
	$data->marketplace = $_POST["marketplace"];
	update_option(DB_DATA, $data);
	echo "<script>alert('Credentials Updated!')</script>";
}

// Tests the connection to ingram marketplace
function task_test_connection() {
	$data = get_option(DB_DATA);
	$token = get_marketplace_token($data);
	$token = json_decode($token)->{'token'};
	if (isset($token)) {
		echo "<script>alert('Connection Success!')</script>";
	} else {
		echo "<script>alert('Connection Failed!')</script>";
	}
}
function task_get_total_product_count() {
	$data = get_option(DB_DATA);
	$token = get_marketplace_token($data);
	$token = json_decode($token)->{'token'};
	if (!isset($token)) {
		echo "<script>alert('Failed to Authenticate!')</script>";
		return;
	}
	$products = get_marketplace_products($data, $token, 0, 0);
	if (!isset($products)) {
		echo "<script>alert('Failed to retrieve products!')</script>";
		return;
	}
	$products = json_decode($products);
	$count = $products->{'pagination'}->{'total'};
	if (!isset($count)) {
		echo "<script>alert('Failed to read product count!')</script>";
		return;
	}
	echo "<script>alert('Ingram Marketplace total products: " . $count . "')</script>";
}
function task_download_json() {
	$data = get_option(DB_DATA);
	$token = get_marketplace_token($data);
	$token = json_decode($token)->{'token'};
	if (!isset($token)) {
		echo "<script>alert('Failed to Authenticate!')</script>";
		return;
	}
	$products = get_marketplace_products($data, $token, $_POST["limit"], $_POST["offset"]);
	if (!isset($products)) {
		echo "<script>alert('Failed to retrieve products!')</script>";
		return;
	}

	//download the products json
	echo '<a id="downloadAnchorElem" style="display:none"></a>
	<script>
		var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(' . $products . ', null, "\t"));
		var dlAnchorElem = document.getElementById("downloadAnchorElem");
		dlAnchorElem.setAttribute("href", dataStr);
		dlAnchorElem.setAttribute("download", "products.json");
		dlAnchorElem.click();
	</script>';
}
function task_download_csv() {
	$data = get_option(DB_DATA);
	$token = get_marketplace_token($data);
	$token = json_decode($token)->{'token'};
	if (!isset($token)) {
		echo "<script>alert('Failed to Authenticate!')</script>";
		return;
	}
	$products = get_marketplace_products($data, $token, $_POST["limit"], $_POST["offset"]);
	if (!isset($products)) {
		echo "<script>alert('Failed to retrieve products!')</script>";
		return;
	}

	$csv = build_csv($products);

	//download the products csv
	echo '<a id="downloadAnchorElem" style="display:none"></a>
	<script>
		var dataStr = "data:text/csv;charset=utf-8," + encodeURIComponent("' . $csv . '");
		var dlAnchorElem = document.getElementById("downloadAnchorElem");
		dlAnchorElem.setAttribute("href", dataStr);
		dlAnchorElem.setAttribute("download", "products.csv");
		dlAnchorElem.click();
	</script>';
}

function build_csv($products) {
	$products = json_decode($products);
	$data = $products->{'data'};

	// set the column names --------------------
	$columns = array('SKU', 'name');
	$csv = add_csv_row($csv, $columns);

	foreach ($data as &$product) {
		// set the row values ------------------------------
		$row = array($product->{'mpn'}, $product->{'name'});
		$csv = add_csv_row($csv, $row);
	}
	return $csv;
}
function add_csv_row($csv, $row) {
	for ($i = 0; $i < count($row); $i++) { 
		$row[$i] = '\"'.$row[$i].'\"'; 
	}
	$csv .= join(",", $row) . '\\n';
	return $csv;
}
function task_import_products() {
	
	echo "<script>alert('This feature is not yet available! In the meantime, download the csv and use the Products->Import page to import the products. Or use a plugin for additional csv import features. For example: https://wordpress.org/plugins/woocommerce-xml-csv-product-import/')</script>";
	return;

	$data = get_option(DB_DATA);
	$token = get_marketplace_token($data);
	$token = json_decode($token)->{'token'};
	if (!isset($token)) {
		echo "<script>alert('Failed to Authenticate!')</script>";
		return;
	}
	$products = get_marketplace_products($data, $token, $_POST["limit"], $_POST["offset"]);
	if (!isset($products)) {
		echo "<script>alert('Failed to retrieve products!')</script>";
		return;
	}
	
	$csv = build_csv($products);
	import_csv_to_woocommerce($csv);
	
	echo "<script>alert('Imported products successfully!')</script>";
}

function import_csv_to_woocommerce($csv) {
	//TODO: import_csv_to_woocommerce
}

function get_marketplace_products($data, $token, $limit, $offset) {
	// use key 'http' even if you send the request to https://...
	$options = array(
		'http' => array(
			'header'  => array( 
				"Content-Type: application/json",
				"Authorization: Bearer " . $token,
				"X-Subscription-Key: " . $data->subscription_key,
			),
			'method'  => 'GET',
			'content' => ''
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents($data->base_url . '/products?limit=' . $limit . '&offset=' . $offset, false, $context);
	if ($result === FALSE) { 
		return; 
	}
	return $result;
}

// Retrieves an authentication token
function get_marketplace_token($data) {
	// use key 'http' even if you send the request to https://...
	$options = array(
		'http' => array(
			'header'  => array( 
				"Content-Type: application/json",
				"Authorization: Basic " . base64_encode($data->username . ":" . $data->password),
				"X-Subscription-Key: " . $data->subscription_key,
			),
			'method'  => 'POST',
			'content' => '{"marketplace":"' . $data->marketplace . '"}'
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents($data->base_url . '/token', false, $context);

	if ($result === FALSE) { 
		return; 
	}
	return $result;
}

?>
