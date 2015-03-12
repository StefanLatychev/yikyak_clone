<?php
/*
 * Uses http status codes to relay information to the API user.
 */
define("APP_ROOT_PATH", dirname(__FILE__));
session_save_path(APP_ROOT_PATH . "/sess");
session_start();

// TODO(sdsmith): check if running on https, and error if not.


// Load constants
require_once("definitions.php")

// Load available API operations
require_once("authentication.php");



http_response_code(STATUS_FORBIDDEN);	// http status is forbidden by default



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
		case 'authentication':
			$responce = apiAuthentication($subrequest);
		case 'user':
			$responce = ;
		case 'notes':
			$responce = ;
		default:
			// Call to unknown api
			http_status_code(STAUTS_NOT_IMPLEMENTED);
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
