<?php
/*
 * Notes API.
 */
require_once('definitions.php');
define('DEFAULT_NUM_NOTES', 10);



/*
 * Gets notes from the database based on the given request parameters.
 */
function apiGetNotes(&$request, &$response) {
	$max_notes = DEFAULT_NUM_NOTES;
	$get_fwd_in_time = false;		// check for notes before time
	$timestamp = null;
	$notes = null;

	// Make sure user is authenticated	
	if (!isAuthenticated($response)) {
		return;
	}

	// Check if time present
	if ($request->time) {
		// Convert time to UTC
		if (!$timestamp = convertUTCTimestamp($request->time->timestamp, $response)) {
			return;
		}

		// Adjust search direction
		switch ($request->time->direction) {
			case 'after':
				$get_fwd_in_time = true;
				break;

			case 'before':
				$get_fwd_in_time = false;
				break;

			default:
				// Bad parameter value
				$response['errors'][] = 'Invalid time direction value';
				$response['status'] = STATUS_BAD_REQUEST;
				return;
		}
	}

	// Check if max_notes present
	if ($request->max_notes) {
		$max_notes = $request->max_notes;
	}

	// Check if location present
	if (!$request->location) {
		$notes = dbGetWorldwideNotes($max_notes, $timestamp, $get_fwd_in_time);
	} else {
		$notes = dbGetLocalNotes($max_notes, $request->location->latitude, $request->location->longitude, $timestamp, $get_fwd_in_tim);
	}

	if ($notes) {
		// Query succeeded
		$response['notes'] = $notes;
		$response['status'] = STATUS_OK;
	} else {
		// Query failed
		$response['errors'] = 'Could not retreive notes';
		$response['status'] = STATUS_INTERNAL_SERVER_ERROR;
	}
}


/*
 * Submit a note to the database.
 */
function apiSubmitNote(&$request, &$response) {
	// Make sure user is authenticated	
	if (!$user_id = isAuthenticated($response)) {
		return;
	}

	// TODO(sdsmith): input validation
	
	if (dbInsertNote($user_id, $request->latitude, $request->longitude, $request->message)) {
		$response['status'] = STATUS_OK;
	} else {
		// Insert failed
		$response['errors'][] = 'Failed to submit note';
		$response['status'] = STATUS_INTERNAL_SERVER_ERROR;
	}
}


/*
 * Report note as being inappropriate.
 */
function apiReportNote(&$request, &$response) {
	if (!$user_id = isAuthenticated($response)) {
		return;
	}
}



/*
 * Apply vote to a post (either positive or negative).
 */
function apiVoteNote(&$request, &$response) {
	// TODO(sdsmith):
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

switch($_SERVER['REQUEST_METHOD']) {
	case 'POST':
		// Decode request
		$REQUEST_VARS = &$_POST;
		if (!$request = requestDecodeJSON($RESQUEST_VARS['request'], $response)) {
			break;
		}

		// Determine which API call is being requested
		if ($request->location && $request->message) {
			// POST Submit note
			apiSubmitNote(&$request, &$response);
			
		} else if ($request->note_id && $request->reason
			// POST Report note
			apiReportNote(&$request, &$response);
			
		} else {
			$response['errors'][] = 'Invalid request parameters';
			$response['status'] = STATUS_BAD_REQUEST;
		}
		break;

	case 'PUT':
		// Decode request
		parse_str(file_get_contents("php://input"), $REQUEST_VARS);
		if ($request = requestDecodeJSON($RESQUEST_VARS['request'], $response)) {
			apiVote($request, $response);
		}
		break;

	case 'GET':
		// Decode request
		$REQUEST_VARS = &$_POST;
		if ($request = requestDecodeJSON($RESQUEST_VARS['request'], $response)) {
			apiGetNotes($request, $response);	
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
