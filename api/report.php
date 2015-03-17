<?php
require_once('definitions.php');
require_once('verification.php');
require_once('../models/database/notes.php');




/*
 * Report note as being inappropriate.
 */
function apiReportNote(&$request, &$response) {
	$valid_input = true;


	if (!$user_id = isAuthenticated($response)) {
		return;
	}

	// TODO(sdsmith): Verifiy input
	/***** Verifiy input *****/
	// note_id
	if (!parameterExists($request, 'note_id') 
		|| !whitelistString($request->note_id, WHITELIST_NUMERIC))
	{
		$valid_input = false;
		$response['errors'][] = 'Invalid note_id parameter';
	}
	// reason
	if (!parameterExists($request, 'reason') 
		|| !whitelistString($request->reason, 
					WHITELIST_REGEX_PHONE_NUMBER)) 
	{
		$valid_input = false;
		$response['errors'][] = 'Invalid reason parameter';
	}

	// Confirm valid input
	if (!$valid_input) {
		$response['status'] = STATUS_BAD_REQUEST;
	}

	// Confirm note being reported exists
	if (!dbGetNoteById($request->note_id)) {
		$response['errors'][] = 'Note being reported does not exists';
		$response['status'] = STATUS_BAD_REQUEST;
		return $response;
	}

	// Check that a report does not already exists
	if (!dbGetReport($request->note_id, $request->user_id)) {
		$response['errors'][] = 'Report already submitted for note';
		$response['status'] = STATUS_BAD_REQUEST;
		return $response;
	}

	// Submit report
	if (dbInsertReport($request->note_id, $user_id, $request->reason)) {
		$response['status'] = STATUS_OK;
	} else {
		$response['errors'][] = 'Failed to submit report';
		$response['status'] = STATUS_INTERNAL_SERVER_ERROR;
	}
}




/***** MAIN *****/
// Check if the connection is HTTPS
// TODO(sdsmith): remove the comment block when not testing
/*if (!$_SERVER['HTTPS']) {
	die("Connection must be over HTTPS");
}
*/

// Decode HTTP request type and decode request parameters
$REQUEST_VARS = null;
$resquest = null;
$response = getAPIResponseTemplate();

// Get request parameters
switch($_SERVER['REQUEST_METHOD']) {
	case 'POST':
		// Decode request
		$REQUEST_VARS = &$_POST;
		if (!$request = requestDecodeJSON($REQUEST_VARS['request'], $response)) {
			apiReportNote($request, $response);
		}
		break;

	default:
		// Bad request
		$response['errors'][] = 'HTTP request type not accepted';
		$response['status'] = STATUS_BAD_REQUEST;
		break;
}


// Send response to requester
// NOTE(sdsmith): assumes the response format is JSON
print json_encode($response);






?>
