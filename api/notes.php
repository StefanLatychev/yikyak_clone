<?php
/*
 * Notes API.
 */
require_once('definitions.php');
require_once('verification.php');
require_once('../models/database/notes.php');
define('DEFAULT_NUM_NOTES', 10);



/*
 * Gets notes from the database based on the given request parameters.
 */
function apiGetNotes(&$request, &$response) {
	$max_notes = DEFAULT_NUM_NOTES;
	$timestamp = null;
	$notes = null;
	$valid_input = true;

	// Make sure user is authenticated	
	if (!isAuthenticated($response)) {
		return;
	}


	// Validate input
	// Check if time present
	if (parameterExists($request, 'time')) {
		if (parameterExists($request->time, "timestamp") 
			&& whitelistString($request->time->timestamp, 
					WHITELIST_REGEX_UTC_TIMESTAMP)) 
		{
			$timestamp = $request->time->timestamp;
		} else {
			// Bad input
			$valid_input = false;
			$response['errors'][] = 'Invalid timestamp parameter';
		}

		// Adjust search direction
		if (parameterExists($request->time, "direction")) {
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
		} else {	
			// Bad input
			$valid_input = false;
			$response['errors'][] = 'Invalid direction parameter';
		}
	}

	// Check if max_notes present
	if (parameterExists($request, 'max_notes')) {
		if (whitelistString($request->max_notes, WHITELIST_NUMBERIC)) {
			$max_notes = $request->max_notes;
		} else {
			// Bad input
			$valid_input = false;
			$response['errors'][] = 'Invalid max_notes parameter';
		}
	}

	// Check if location present
	if (parameterExists($request, 'location')) {
		if (!parameterExists($request->location, 'latitude') 
			|| !whitelistString($request->location->latitude, 
						WHITELIST_REGEX_LOCATION)) 
		{
			$valid_input = false;
			$response['errors'][] = 'Invalid latitude parameter';
		}

		if (!parameterExists($request->location, 'longitude') 
			|| !whitelistString($request->location->longitude, 
						WHITELIST_REGEX_LOCATION)) 
		{
			$valid_input = false;
			$response['errors'][] = 'Invalid longitude parameter';
		}
	}

	// Confirm that all input was valid
	if (!$valid_input) {
		$response['status'] = STATUS_BAD_REQUEST;
		return;
	}
	

	// Perform query for notes
	if (parameterExists($request, 'location')) {
		$notes = dbGetLocalNotes($max_notes, 
					$request->location->latitude, 
					$request->location->longitude, 
					$timestamp, 
					$get_fwd_in_time);
	} else {
		$notes = dbGetWorldwideNotes($max_notes, 
						$timestamp, 
						$get_fwd_in_time);
	}

	// Check query success
	if ($notes) {
		// Query succeeded
		$response['notes'] = $notes;
		$response['status'] = STATUS_OK;
	} else {
		// Query failed
		$response['errors'][] = 'No notes meeting criteria';
		$response['status'] = STATUS_OK;
		$response['notes'] = array();
	}
}



/*
 * Submit a note to the database. Escapes all message character to html 
 * encoding equivalents if available before inserting into database.
 */
function apiSubmitNote(&$request, &$response) {
	$safe_note_message = null;
	$valid_input = true;

	// Make sure user is authenticated	
	if (!$user_id = isAuthenticated($response)) {
		return;
	}

	// TODO(sdsmith): input validation
	// Check location
	if (parameterExists($request, 'location')) {
		// latitude
		if (!parameterExists($request->location, 'latitude') 
			|| !whitelistString($request->location->latitude, 
						WHITELIST_REGEX_LOCATION))
		{
			$valid_input = false;
			$response['errors'][] = 'Invalid latitude parameter';
		}

		// longitude
		if (!parameterExists($request->location, 'latitude') 
			|| !whitelistString($request->location->latitude, 
						WHITELIST_REGEX_LOCATION))
		{
			$valid_input = false;
			$response['errors'][] = 'Invalid longitude parameter';
		}
	} else {
		$valid_input = false;
		$response['errors'][] = 'Invalid location parameter';
	}

	// Check message
	if (propery_exists($request, 'message')) {
		// Escape all escapable characters with their html encoding 
		// equivalent so the text is safe to put directly into an html 
		// page.
		$safe_note_message = htmlentities($request->message, 	
							ENT_QUOTES);
	} else {
		$valid_input = false;
		$response['errors'][] = 'Invalid message parameter';
	}

	// Confirm if valid input
	if (!$valid_input) {
		$response['status'] = STATUS_BAD_REQUEST;
		return;
	}

	// Insert note into database	
	if (dbInsertNote($user_id, $request->location->latitude, 
			$request->location->longitude, $safe_note_message)) {
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

	// TODO(sdsmith): Verifiy input
	// Confirm note exists.

	// Submit report
	if (dbInsertReport($request->note_id, $user_id, $request->reason)) {
		$response['status'] = STATUS_OK;
	} else {
		$response['errors'][] = 'Failed to submit report';
		$response['status'] = STATUS_INTERNAL_SERVER_ERROR;
	}
}



/*
 * Apply vote to a post (either positive or negative).
 */
function apiVoteNote(&$request, &$response) {
	$valid_input = true;

	if (!$user_id = isAuthenticated($response)) {
		return;
	}

	/***** Verify input *****/
	// Check required feilds are present and have good values
	// note_id
	if (!parameterExists($request, 'note_id') 
		|| !whitelistString($request->note_id, WHITELIST_NUMERIC)) 
	{
		$valid_input = false;
		$response['errors'][] = 'Invalid note_id parameter';
	}
	// upvote
	if (!parameterExists($request, 'upvote') 
		|| !($request->upvote == 't' || $request->upvote == 'f'))
	{
		$valid_input = false;
		$response['errors'][] = 'Invalid upvote parameter';
	}

	// Confirm valid input
	if (!$valid_input) {
		$responce['status'] = STATUS_BAD_REQUEST;
		return;
	}

	// Confirm note being voted on exists (dbGetNoteById)
	if (!dbGetNoteById($request->note_id)) {
		$response['errors'][] = 'Note with given id does not exist';
		$response['status'] = STATUS_BAD_REQUEST;
		return;
	}
	

	// Check if user has voted the note previously
	if ($vote_info = dbGetVoteOnNote($request->note_id, $user_id)) {
		// Previous vote entry, update it
		$success = dbUpdateVote($request->note_id, $user_id, $request->upvote, $vote_info['upvote']);
	} else {
		// New vote entry
		$success = dbInsertVote($request->note_id, $user_id, $request->upvote);
	}

	// Check vote submit status
	if ($success) {
		$response['status'] = STATUS_OK;
	} else {
		// Insert/update failed
		$response['errors'][] = 'Failed to apply vote';
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
			break;
		}

		// Determine which API call is being requested
		if ($request->location && $request->message) {
			// POST Submit note
			apiSubmitNote($request, $response);
			
		} elseif ($request->note_id && $request->reason) {
			// POST Report note
			apiReportNote($request, $response);
			
		} else {
			$response['errors'][] = 'Invalid request parameters';
			$response['status'] = STATUS_BAD_REQUEST;
		}
		break;

	case 'PUT':
		// Decode request
		parse_str(file_get_contents("php://input"), $REQUEST_VARS);
		if ($request = requestDecodeJSON($REQUEST_VARS['request'], $response)) {
			apiVoteNote($request, $response);
		} 
		break;

	case 'GET':
		// Decode request
		if (isset($_REQUEST['request'])) {
			$REQUEST_VARS = &$_REQUEST;
		} else {
			parse_str(file_get_contents("php://input"), $REQUEST_VARS);
		}
		if ($request = requestDecodeJSON($REQUEST_VARS['request'], $response)) {
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
