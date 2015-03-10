<?php
/*
 * Uses http state messages to relay information to user.
// REQUEST FORMAT
class APIRequest {
	public $operation;
	public $latitude;
	public $longitude;
	public $note;
};
 */
define("APP_ROOT_PATH", dirname(__FILE__));
session_save_path(APP_ROOT_PATH . "/sess");
session_start();

define("STATUS_BAD_REQUEST", "400 Bad Request");
define("STATUS_SUCCESS", "200 OK");
define("STATUS_UNAUTHORIZED", "401 Unauthorized");

class APIResponce {
	public $errors;
	public $status;
	public $notes;
};


$errormessages = [];
$responce = new APIResult();

$jsonrequest = &$_REQUEST['request'];




function performJSONRequest($jsonrequest) {
	// decode JSON request
	if ($request = json_decode($jsonrequest, true)) {
		$errormessages[] = "JSON decode failed";
		$responce->status = STATUS_BAD_REQUEST;
		return;
	}
	
	// determine operation request
	switch($request['operation'][1]) {
		case "GET":
			GETRequest($request);
			break;
		case "POST":
			POSTRequest($request);
			break;
		case "UPDATE":
			UPDATERequest($request);
			break;
		case "DELETE":
			break;
		default:
			$errormessages[] = "Invalid operation"
			$responce->status = STATUS_BAD_REQUEST;
	}
}

/*
 * Returns a random value to be used as a token.
 */
function getToken() {
	return rand();
}


/*
 * Handles GET requests from client.
 * Can get:
 *	notes
 * 		// NOTE(sdsmith): optional args not implemented
operation	GET NOTES [MAX number] [TIME timestamp]
latitude	
longitude
 */
function GETRequest($request) {
	$op_args = $request['operation'].split();
	
	if (strtoupper($op_args[1]) == "NOTES") {

	}
}


function POSTRequest($request) {

}

/*
 * Handles authentication and account status (as well as changed to notes if we
 * allow it).
 */
function UPDATERequest($request) {
}

function DELETERequest($request) {
}



/***** MAIN *****/
// Do initial session setup
if (!isset($_SESSION['authenticated'])) {
	$_SESSION['authenticated'] = false;
	$errormessages[] = "Unauthenticated user";
	$responce->status = STATUS_UNAUTHORIZED;
}


// API Result
print json_encode($responce);	// TODO(sdsmith): Check return ?
?>






