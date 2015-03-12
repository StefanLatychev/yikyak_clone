<?php
/*
 * Uses http status codes to relay information to the API user.
 */
define("APP_ROOT_PATH", dirname(__FILE__));
session_save_path(APP_ROOT_PATH . "/sess");
session_start();

// TODO(sdsmith): Do I make the responce global?


// Load constants
require_once("../definitions.php")

// Load available API operations


/*
 * Performs the requested operation and fills out the $response object with
 * appropriate values.
 */
function fulfillAPIRequest($api_url, $response_format) {
	$request = explode('/', $api_url);	//
	$subrequest = array_slice($request, 1);
	$response = null;


	// Check which API call is being made
	switch($request[0]) {	// NOTE(sdsmith): Check if url will start with api or with the category
		case "authentication":
			$responce = apiAuthentication($subrequest);
		default:
			// Call to unknown api
			// TODO(sdsmith):
	}

	// Format output to requested type
	// NOTE(sdsmith): will always be response to request, although it may
	// be a failure notice.
	switch($response_format) {
		case "json":
			$response = json_encode($response);			

		default:
			die("PANIC: webserver is allowing unexpected formats");
	}

	return $response;
}



/***** MAIN *****/
print fulfillAPIRequest(&$_GET['url'], &$_GET['response_format']);
?>
