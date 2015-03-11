<?php
/***** Definitions *****/

// Request status codes
define("STATUS_BAD_REQUEST", "400 Bad Request");
define("STATUS_SUCCESS", "200 OK");
define("STATUS_UNAUTHORIZED", "401 Unauthorized");

/*
 * APIResponse struct for sending responses to API requests.
 */
class APIResponse {
	public $errors;
	public $status;
	public $notes;
};
?>
