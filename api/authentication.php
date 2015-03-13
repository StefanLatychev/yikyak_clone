<?php
require_once("definitions.php");
require_once("../models/database/authentication.php");

// TODO(sdsmith): Set active session key expire time so that it can be removed 
// from the db after a certain amount of time, invalidating the API key.

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
 * Sets an api_session_key cookie on user's system.
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
		http_status_code(STATUS_UNAUTHORIZED);
		return $response;
	}

	// TODO(sdsmith): whitelist

	// Authenticate user
	if (dbAuthenticateUser($username, $password)) {
		// Generate session key
		$session_key = generateSessionKey();

		// Register session key
		dbInsertSessionKey($session_key);
		
		// Set the API key as a cookie on the user's machine
		// @param secure	indicates only send cookie over https
		setcookie('api_session_key', $session_key, $secure=true);
		
		// Finish the response
		http_status_code(STATUS_OK);
		
	} else {
		// Invalid credentials
		http_status_code(STATUS_UNAUTHORIZED);
	}

	return $response;
}



/*
 * Logs user out of API by invalidating their session key. Return true on
 * success, false otherwise.
 * Invalidates user's api_session_kay cookie.
 */
function apiLogout($request) {
	$responce = array();
	$session_key = &$_COOKIE['api_session_key'];

	// Confirm there is no more to the request
	if (sizeof($request) != 0) {
		// TODO(sdsmith): Bad request
	}

	// Confirm session key is provided
	if (!isset($session_key)) {
		// TODO(sdsmith): no session key
	}

	// Invalidate cookie from user
	setcookie("api_session_key", "", time()-3600);

	// Unregister active session key
	if (dbRemoveSessionKey($session_key)) {
		http_status_code(STATUS_OK);
		return true;
	} else {
		// Database failed to remove session_key.
		http_status_code(STATUS_INTERNAL_SERVER_ERROR);
		return false;
	}
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
			http_status_code(STAUTS_NOT_IMPLEMENTED);
	}

	return $response;
}

?>
