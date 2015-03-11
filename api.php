<?php
/*
 * Uses http status codes to relay information to the API user.
 *
 *	// REQUEST FORMAT
 *	class APIRequest {
 *		public $operation;
 *		public $latitude;
 *		public $longitude;
 *		public $note;
 *	};
 *
 */
define("APP_ROOT_PATH", dirname(__FILE__));
session_save_path(APP_ROOT_PATH . "/sess");
session_start();

// Load constants
require_once("models/definitions.php")

// Load available API operations
require_once(APP_ROOT_PATH . "/models/api/GET.php");
require_once(APP_ROOT_PATH . "/models/api/PUT.php");
require_once(APP_ROOT_PATH . "/models/api/POST.php");
require_once(APP_ROOT_PATH . "/models/api/DELETE.php");

$errormessages = [];
$response = new APIResponse();	// to be filled and sent to requester

$jsonrequest = &$_REQUEST['request'];



/*
 * Performs the requested operation and fills out the $response object with
 * appropriate values.
 */
function fulfillJSONRequest($jsonrequest) {
	// decode JSON request
	if ($request = json_decode($jsonrequest, true)) {
		$errormessages[] = "JSON decode failed";
		$response->status = STATUS_BAD_REQUEST;
		return;
	}
	
	// determine operation request
	switch($request['operation'][1]) {
		case "GET":
			GETRequest($request);
			break;
		case "PUT":
			PUTRequest($request);
			break;
		case "POST":
			POSTRequest($request);
			break;
		case "DELETE":
			DELETERequest($request);
			break;
		default:
			$errormessages[] = "Invalid operation"
			$response->status = STATUS_BAD_REQUEST;
	}
}



/*
 * Returns a random value to be used as a token.
 */
function getToken() {
	return rand();
}



/***** MAIN *****/
// Do initial session setup
if (!isset($_SESSION['authenticated'])) {
	$_SESSION['authenticated'] = false;
}
// TODO(sdsmith): fulfill request. If user is unauthenticated, then make sure 
// they get sent STATUS_UNAUTHENTICATED so they know to send a login request.

// API Result
$response->errors = $errormessages;
print json_encode($response);	// TODO(sdsmith): Check return ?
?>






