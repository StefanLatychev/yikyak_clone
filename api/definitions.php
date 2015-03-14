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


?>
