<?php
require_once("definitions.php");

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
 * Return session key on successful credential validation representing the
 * authenticated user.
 */
function apiLogin($request) {

	if (sizeof($request) != 0) {
		// TODO(sdsmith): bad request
	}

	// TODO(sdsmith):
	// authenticate user

	// generate session key
	$response['api_session_key'] = generateSessionKey();

	// register session key
	// NOTE(sdsmith): will be done through database!	

	return $response;
}



/*
 * Logs user out of API by invalidating their session key.
 */
function apiLogout($request) {

	if (sizeof($request) != 0) {
		// TODO(sdsmith): Bad request
	}

	// TODO(sdsmith):
	// unregister session key
	// NOTE(sdsmith): will be done through database!
}



/*
 * Determines what authentication API is being requested and performs it.
 */
function apiAuthentication($request) {
	$subrequest = array_slice($request, 1);
	$responce = null;

	switch ($request[0]) {
		case "login":
			$responce = apiLogin($subrequest);

		case "logout":
			$responce = apiLogout($subrequest);

		default:
			// TODO(sdsmith): unsupported operation
	}

	return $responce;
}

?>
