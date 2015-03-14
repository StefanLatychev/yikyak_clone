<?php
require_once("definitions.php");
require_once("../models/database/authentication.php");

// TODO(sdsmith): Set active session key expire time so that it can be removed 
// from the db after a certain amount of time, invalidating the API key.

// TODO(sdsmith): Have db send error messages to the response['errors'] var



/*
 * Return response with session key on successful credential validation 
 * representing the authenticated user.
 * Only accepts username and password from POST requests.
 * Sets an api_session_key cookie on user's system.
 */
function apiLogin($encoded_request) {
	$response = getAPIResponseTemplate();

	// Decode request
	// NOTE(sdsmith): makes the assumption it's a json request
	if (!$request = requestDecodeJSON($encoded_request, $response)) {
		return $response;
	}

	// Check if username and password provided
	if (!isset($request->email) || !isset($request->password)) {
		$response['errors'][] = 'Full credentials not provided';
		$response['status'] = STATUS_UNAUTHORIZED;
		return $response;
	}

	// TODO(sdsmith): whitelist

	// Authenticate user
	if ($user_info = dbAuthenticateUser($request->email, $request->password)) {
		// Generate session key
		$session_key = generateSessionKey();

		// Register session key
		dbInsertSessionKey($session_key, $user_info['id']);
		
		// Set the API key as a cookie on the user's machine
		// @param secure	indicates only send cookie over https
		setcookie('api_session_key', $session_key, $secure=true);

		// Update last login time
		dbUpdateLastLoginTime($user_info['i']);
			

		// Completed request
		$response['status'] = STATUS_OK;
		
	} else {
		// Invalid credentials
		$response['errors'][] = "Invalid credentials";
		$response['status'] = STATUS_UNAUTHORIZED;
	}

	return $response;
}



/* 
 * Logs user out of API by invalidating their session key. Return response
 * object.
 * Invalidates user's api_session_key cookie.
 */
function apiLogout() {
	$response = getAPIResponseTemplate();
	$session_key = getRequesterAPISessionKey();

	// Confirm session key is provided
	if (!isset($session_key)) {
		// No session key
		$response['errors'][] = "Not logged in; cannot logout";
		$response['status'] = STATUS_BAD_REQUEST;
	}

	// Invalidate cookie from user
	setcookie("api_session_key", "", time()-3600);

	// Unregister active session key
	if (dbRemoveSessionKey($session_key)) {
		$response['status'] = STATUS_OK;
	} else {
		// Database failed to remove session_key.
		$response['errors'][] = "Failed to logout";
		$response['status'] = STATUS_INTERNAL_SERVER_ERROR;
	}

	return $response;
}




/***** MAIN *****/
// Check if the connection is HTTPS
if (!$_SERVER['HTTPS']) {
	die("Connection must be over HTTPS");
}


// Decode HTTP request type and get request parameters
$REQUEST_VARS = null;
$response = null;
switch($_SERVER['REQUEST_METHOD']) {
	case 'POST':
		$REQUEST_VARS = &$_POST;
		$response = apiLogin(&$REQUEST_VARS['request']);
		break;

	case 'DELETE':
		// http://www.lornajane.net/posts/2008/Accessing-Incoming-PUT-Data-from-PHP
		parse_str(file_get_contents("php://input"), $REQUEST_VARS);
		$response = apiLogout();
		break;

	default:
		// Bad request
		$response['errors'][] = 'HTTP request type not accepted';
		$response['status'] = STATUS_BAD_REQUEST;
}


// Send response to requester
// NOTE(sdsmith): assumes the response format is JSON
print json_encode($response);



























?>
