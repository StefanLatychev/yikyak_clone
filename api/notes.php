<?php
/*
 * Notes API.
 */
require_once('definitions.php');
define('DEFAULT_NUM_NOTES', 10);



/*
 * Gets notes from the database based on the given request parameters.
 */
function apiGetNotes($encoded_request) {
	$response = getAPIResponceTemplate();
	$max_notes = DEFAULT_NUM_NOTES;
	$get_fwd_in_time = false;		// check for notes before time
	$timestamp = null;
	$notes = null;

	// Make sure user is authenticated	
	if (!isAuthenticated($response)) {
		return $response;
	}

	// Decode request
	// NOTE(sdsmith): makes assumption it's a JSON request
	if (!$request = requestDecodeJSON($encoded_request, $response)) {
		return $response;
	}

	// Check if time present
	if ($request->time) {
		// Convert time to UTC
		if (!$timestamp = convertUTCTimestamp($request->time->timestamp, $response)) {
			return $response;
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
				return $response;
		}
	}

	// Check if max_notes present
	if ($request->max_notes) {
		$max_notes = $request->max_notes;
	}

	// Check if location present
	if ($request->location) {
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
	
	return $response;
}



/*
 * Report note as being inappropriate.
 */
function apiReport($encoded_request) {
	
}



/*
 * Apply vote to a post (either positive or negative).
 */
function apiVote($encoded_request) {
}
























?>
