<?php
require_once('definitions.php');
require_once('../models/database/user.php');




// VERIFIED
/*
 * Register a new user with the database. Return API response.
 */
function apiRegisterNewUser(&$encoded_request) {
	$response = getAPIResponseTemplate();

	// Decode request
	// NOTE(sdsmith): makes assumption it's a json request
	if (!$request = requestDecodeJSON($encoded_request, $response)) {
		return $response;
	}

	// TODO(sdsmith): input validation

	// Confirm email/password information matches
	if (!($request->email1 == $request->email2 && 
			$request->password1 == $request->password2)) {
		$response['errors'][] = 'Email and/or password information does not match';
		$response['status'] = STATUS_BAD_REQUEST;
		return $response;
	}

	// Register user
	$IS_ADMIN = false;
	if (dbRegisterNewUser($IS_ADMIN, $request->email1, $request->phone_number, $request->password1)) {
		$response['status'] = STATUS_OK;
	} else {
		// Registration failed
		$response['errors'][] = 'Failed to register user';
		$response['status'] = STATUS_INTERNAL_SERVER_ERROR;
	}

	return $response;
}



/*
 * Update the current user's information.
 */
function apiUpdateUserInfo(&$encoded_request) {
	$response = getAPIResponseTemplate();
	
	// Make sure user is authenticated
	if (!isAuthenticated($response)) {
		return $response;
	}

	// Decode request
	// NOTE(sdsmith): makes assumption it's a json request
	if (!$request = requestDecodeJSON($encoded_request, $response)) {
		return $response;
	}

	// TODO(sdsmith): verifiy input

	// Validate user provided credentials
	if (!$user_info = dbAuthenticateUser($request->current_email, $request->current_password)) {
		$response['errors'][] = 'Invalid credentials';
		$response['status'] = STATUS_UNAUTHORIZED;
		return $response;
	}

	// Update user information
	if (dbUpdateUserInfo($user_info['id'], $request->new_email, $request->new_password)) {
		$response['status'] = STATUS_OK;
	} else {
		// Insert failed
		$response['errors'][] = 'Could not updated information';
		$response['status'] = STATUS_INTERNAL_SERVER_ERROR;
	}

	return $response;
}



/*
 * Get dump of the current user's information.
 */
function apiGetUserInfo(&$encoded_request) {
	$response = getAPIResponseTemplate();

	// Make sure user is authenticated
	if (!isAuthenticated($response)) {
		return $response;
	}

	// Decode request
	// NOTE(sdsmith): makes assumption it's a json request
	if (!$request = requestDecodeJSON($encoded_request, $response)) {
		return $response;
	}	

	// TODO(sdsmith): verify input

	// Validate user provided credentials to get user info
	if (!$user_info = dbAuthenticateUser($request->current_email, $request->current_password)) {
		$response['errors'][] = 'Invalid credentials';
		$response['status'] = STATUS_UNAUTHORIZED;
		return $response;
	}	

	// Set information
	$response['user_info'] = $user_info;
	$response['status'] = STATUS_OK;

	return $response;
}




/***** MAIN *****/
// Check if the connection is HTTPS
/*if (!$_SERVER['HTTPS']) {
	die("Connection must be over HTTPS");
}
*/

// Decode HTTP request type and get request parameters
$REQUEST_VARS = null;
$response = null;
switch($_SERVER['REQUEST_METHOD']) {
	case 'POST':
		$REQUEST_VARS = &$_POST;
		$response = apiRegisterNewUser($REQUEST_VARS['request']);
		break;

	case 'PUT':
		parse_str(file_get_contents("php://input"), $REQUEST_VARS);
		$response = apiUpdateUserInfo($REQUEST_VARS['request']);

	case 'GET':
		parse_str(file_get_contents("php://input"), $REQUEST_VARS);
		$response = apiGetUserInfo($REQUEST_VARS['request']);
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
