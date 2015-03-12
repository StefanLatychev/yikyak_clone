<?php
require_once("definitions.php");
require_once("../database/authentication.php");



/*
 * Generates session keys for use in authenticating users.
 * 62^SESSION_KEY_LENGTH possibilities.
 */
function generateSessionKey() {
	$valid_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
	$valid_chars_max_index = sizeof($valid_chars) -1 ;
	$session_key = '';

	for ($n = 0; $n < SESSION_KEY_LENGTH; $n += 1) {
		$session_key .= $valid_chars[mt_rand(0, $valid_chars_max_index)];
	}

	return $session_key;
}


/*
 * Return response with session key on successful credential validation 
 * representing the authenticated user.
 * Only accepts username and password from POST requests.
 */
function apiLogin($request) {
	$response = array();
	$username = &$_POST['username'];
	$password = &$_POST['password'];

	// Confirm there is no more to the request
	if (sizeof($request) != 0) {
		// TODO(sdsmith): bad request
	}

	// Check if username and password provided
	if (!isset($username) || !isset($password)) {
		// TODO(sdsmith): improper credentials
	}

	// TODO(sdsmith): whitelist
	// authenticate user
	if (dbAuthenticateUser($username, $password)) {
		// generate session key
		$session_key = generateSessionKey();

		// register session key
		dbInsertSessionKey($session_key);
		$response['api_session_key'] = $session_key;
		// TODO(sdsmith): finish the response
		
	} else {
		// TODO(sdsmith): invalid credentials
	}

	return $response;
}



/*
 * Logs user out of API by invalidating their session key. Return true on
 * success, false otherwise.
 */
function apiLogout($request) {
	$session_key = &$_REQUEST['api_session_key'];

	// Confirm there is no more to the request
	if (sizeof($request) != 0) {
		// TODO(sdsmith): Bad request
	}

	// Confirm session key is provided
	if (!isset($session_key)) {
		// TODO(sdsmith): no session key
	}

	// TODO(sdsmith):
	// unregister active session key
	return dbRemoveSessionKey($session_key);
}



/*
 * Determines what authentication API is being requested and performs it.
 */
function apiAuthentication($request) {
	$subrequest = array_slice($request, 1);
	$response = null;

	switch ($request[0]) {
		case "login":
			$response = apiLogin($subrequest);

		case "logout":
			$response = apiLogout($subrequest);

		default:
			// TODO(sdsmith): unsupported operation
	}

	return $response;
}

?>
