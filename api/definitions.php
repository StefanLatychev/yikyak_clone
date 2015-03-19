<?php
require_once('../models/database/authentication.php');

/***** Definitions *****/

// Request status codes
define("STATUS_OK", "200");
define("STATUS_BAD_REQUEST", "400");
define("STATUS_UNAUTHORIZED", "401");
define("STATUS_FORBIDDEN", "403");
define("STATUS_REQUEST-URI_TOO_LONG", "414");
define("STATUS_INTERNAL_SERVER_ERROR", "500");
define("STATUS_NOT_IMPLEMENTED", "501");

// Session key
define("SESSION_KEY_LENGTH", 25);



/*
 * Return API response associative array with default parameters initialized.
 */
function getAPIResponseTemplate() {
	$responce_template = array();

	$response_template['errors'] = array();
	$response_template['status'] = null;

	return $response_template;
}


/*
 * Return user id of the user if the requesting user is authenticated, null 
 * otherwise. If $response is provided and the user is not authenticated, 
 * response status and error messages will be filled out appropriately.
 */
function isAuthenticated(&$response=null) {
	$user_id = null;

	// Get user session key
	if (!$session_key = getRequesterAPISessionKey($response)) {
		return $user_id;
	}

	// Check if active key
	$key_row = dbActiveSessionKey($session_key);
		

	if (!$key_row && $response) {
		$response['errors'][] = 'Not authenticated';
		$response['status'] = STATUS_UNAUTHORIZED;
	}

	return $key_row['user_id'];
}


/*
 * Generates session keys for use in authenticating users.
 * 62^SESSION_KEY_LENGTH possibilities.
 */
function generateSessionKey() {
	$valid_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$valid_chars_max_index = strlen($valid_chars) - 1;
	$session_key = '';

	for ($n = 0; $n < SESSION_KEY_LENGTH; $n += 1) {
		$session_key .= $valid_chars[mt_rand(0, $valid_chars_max_index)];
	}

	return $session_key;
}



/*
 * Return current requester's API session key.
 */
function getRequesterAPISessionKey(&$response=null) {
	$session_key = null;

	if (isset($_COOKIE['api_session_key'])) {
		$session_key = $_COOKIE['api_session_key'];
	} else if ($response) {
		// Cookie not provided, reporting
		$response['errors'][] = 'Authentication cookie not provided. Have you logged in?';
		$response['status'] = STATUS_UNAUTHORIZED;
	}
	return $session_key;
}



/*
 * Decodes json object. Return stdClass object representing json object on 
 * success, null otherwise. If there is an error, $responce will be populated 
 * with the appropriate status and error messages if provided.
 * 
 * @param json_encoded_object	object in JSON format to decode
 * @param response		APIResponceTemplate object to be populated on
 *				error if provided.
 */
// TODO(sdsmith): weird issue with apiUpdateUserInfo
function requestDecodeJSON($json_encoded_object, &$response=null) {
	$request = json_decode($json_encoded_object);

	if (json_last_error() != JSON_ERROR_NONE && $response) {
		$response['errors'][] = "Bad JSON format: " . json_last_error();
		$response['status'] = STATUS_BAD_REQUEST;
	}

	return $request;
}



/*
 * Return given timestamp (with timezone) converted to a UTC timestamp (without 
 * timezone), and null otherwise. If there is an error, $response will be 
 * populated with the appropriate status and error messages if provided.
 */
function convertUTCTimestamp($timezone_timestamp, &$response=null) {
}














?>
