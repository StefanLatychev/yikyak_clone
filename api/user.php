<?php
require_once('definitions.php');
require_once('../models/database/user.php');
require_once('verification.php');



/*
 * Register a new user with the database. Return API response.
 */
// TODO(sdsmith): impose password, email and phone length limits
function apiRegisterNewUser(&$encoded_request) {
	$response = getAPIResponseTemplate();
	$valid_input = true;

	// Decode request
	// NOTE(sdsmith): makes assumption it's a json request
	if (!$request = requestDecodeJSON($encoded_request, $response)) {
		return $response;
	}
	
	// Verifiy input
	// Check email is present
	if (!property_exists($request, 'email1') 
				|| !property_exists($request, 'email2')) 
	{
		$valid_input = false;
		$response['errors'][] = 'Email and confirmation email not present';
	}

	// Check password is present
	if (!property_exists($request, 'password1') 
				|| !property_exists($request, 'password2')) 
	{
		$valid_input = false;
		$response['errors'][] = 'Password and confirmation password not present';
	}

	// Confirm required information is present
	if (!$valid_input) {
		$response['status'] = STATUS_BAD_REQUEST;
		return $response;
	}

	// email1
	if (!whitelistString($request->email1, WHITELIST_REGEX_EMAIL)) {
		$valid_input = false;
		$response['errors'][] = 'Invalid email1 parameter';
	}

	// email2
	if (!whitelistString($request->email2, WHITELIST_REGEX_EMAIL)) {
		$valid_input = false;
		$response['errors'][] = 'Invalid email2 parameter';
	}

	// phone_number
	if (!property_exists($request, 'phone_number') 
		|| !whitelistString($request->phone_number, 
					WHITELIST_REGEX_EMAIL)) 
	{
		$valid_input = false;
		$response['errors'][] = 'Invalid phone_number parameter';
	}

	// password1
	if (!whitelistString($request->password1, WHITELIST_REGEX_EMAIL)) {
		$valid_input = false;
		$response['errors'][] = 'Invalid password1 parameter';
	}

	// password2
	if (!whitelistString($request->password2, WHITELIST_REGEX_EMAIL)) {
		$valid_input = false;
		$response['errors'][] = 'Invalid password2 parameter';
	}

	// Confirm valid input
	if (!$valid_input) {
		$response['status'] = STATUS_BAD_REQUEST;
		return $response;
	}

	// Confirm there will be no duplicate email or phone number in database
	if (dbExistsEmail($request->email1)) {
		$valid_input = false;
		$response['errors'][] = 'Email already registered';
	}
	if (property_exists($request, 'phone_number') 
			&& dbExistsPhoneNumber($request->phone_number)) {
		$valid_input = false;
		$response['errors'][] = 'Phone number already registered';
	}

	// Confirm no duplicate entries in database
	if (!$valid_input) {
		$response['status'] = STATUS_BAD_REQUEST;
		return $response;
	}

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
	var_dump($encoded_request);
	$response = getAPIResponseTemplate();
	
	// Make sure user is authenticated
	if (!$requester_id = isAuthenticated($response)) {
		return $response;
	}

	var_dump($encoded_request);

	// Decode request
	// NOTE(sdsmith): makes assumption it's a json request
	if (!$request = requestDecodeJSON($encoded_request, $response)) {
		echo("decode failed");
		return $response;
	}
	var_dump($encoded_request);
	var_dump($request);

	// TODO(sdsmith): verifiy input
	//dbExistsEmail
	//dbExistsPhoneNumber


	// Validate user provided credentials
	$user_info = dbAuthenticateUser($request->current_email, 
						$request->current_password);
	// Confirm credentials provided match the session key owner
	if (!$user_info || $requester_id != $user_info['id']) {
		// Either credentials were bad, or user entered another user's 
		// credentials. Bad user.
		$response['errors'][] = 'Invalid credentials';
		$response['status'] = STATUS_UNAUTHORIZED;
		return $response;
	}

	// Update user information
	if (dbUpdateUserInfo($user_info['id'], $request->new_email1, $request->new_password1)) {
		$response['status'] = STATUS_OK;
	} else {
		// Insert failed
		$response['errors'][] = 'Could not update information';
		$response['status'] = STATUS_INTERNAL_SERVER_ERROR;
	}

	return $response;
}



/*
 * Get dump of the current user's information.
 */
function apiGetUserInfo(&$encoded_request) {
	$response = getAPIResponseTemplate();
	$valid_input = true;

	// Make sure user is authenticated
	if (!$requester_id = isAuthenticated($response)) {
		return $response;
	}

	// Decode request
	// NOTE(sdsmith): makes assumption it's a json request
	if (!$request = requestDecodeJSON($encoded_request, $response)) {
		return $response;
	}	

	// Verify input
	// Email	
	if (!property_exists($request, 'email') 
		|| !whitelistString($request->email, WHITELIST_REGEX_EMAIL)) 
	{
		$valid_input = false;
		$response['errors'][] = 'Invalid email parameter';
	}

	// Password
	if (!property_exists($request, 'password') 
		|| !whitelistString($request->password, 
					WHITELIST_REGEX_PASSWORD)) 
	{
		$valid_input = false;
		$response['errors'][] = 'Invalid password parameter';
	}

	// Confirm input is valid
	if (!$valid_input) {
		$response['status'] = STATUS_BAD_REQUEST;
		return $response;
	}

	// Validate user provided credentials to get user info
	$user_info = dbAuthenticateUser($request->email, $request->password);

	// Confirm credentials provided match the session key owner
	if (!$user_info || $requester_id != $user_info['id']) {
		// Either credentials were bad, or user entered another user's 
		// credentials. Bad user.
		$response['errors'][] = 'Invalid credentials';
		$response['status'] = STATUS_UNAUTHORIZED;
		return $response;
	}

	// Set information
	unset($user_info['id']);	// do not give the user their own id
	$response['user_info'] = $user_info;
	$response['status'] = STATUS_OK;

	return $response;
}




/***** MAIN *****/
// Check if the connection is HTTPS
// TODO(sdsmith): Remove all blocking of https verification
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
		$REQUEST_VARS = &$_GET;
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
