<?php
require_once('definitions.php');

/*
 * Register a new user with the database. Return API response.
 */
function apiRegisterNewUser($encoded_request) {
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

	// Register user
	$IS_ADMIN = false;
	if (dbRegisterNewUser($IS_ADMIN, $request->email, $request->phone_number, $request->password)) {
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
function apiUpdateUserInfo($encoded_request) {
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

	// Validate user provided credentials
	if (!$user_info = dbAuthenticateUser($request->email, $request->password)) {
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
function apiGetUserInfo($encoded_request) {
	// TODO(sdsmith):
	
}















?>
