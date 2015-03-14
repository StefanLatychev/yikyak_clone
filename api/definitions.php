<?php
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
function getRequesterAPISessionKey() {
	return $_COOKIE['api_session_key'];
}
?>
