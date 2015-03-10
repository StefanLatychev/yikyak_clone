<?php
require_once("../definitions.php")

define("USE_DEFAULT_NUM_NOTES", 0);
define("GET_LATEST_NOTES", null);
/*
 * Handles GET requests from client.
 * Can get:
 *	NOTES
 *	operation:"GET NOTES [TIME timestamp] [MAX number]"
 *	latitude:float	
 *	longitude:float
 */
function GETRequest($request) {
	$op_args = $request['operation'].split();
	
	switch (strtoupper($op_args[1])) {
		case "NOTES":
			
		default:
			$errormessages[] = "Unknown GET operation \'" . strtoupper($op_args[1]) . "\'";
			$responce->status = STATUS_BAD_REQUEST;
	}
}


/*
 * Performs NOTES requests according to given arguments.
 */
function opNOTES($request) {
	$maxnotes = USE_DEFAULT_NUM_NOTES;	// mximum notes to return
	$time = GET_LATEST_NOTES;

	for($i = 0; $i < sizeof($op_args); $i += 1) {
		switch ($op_args[$i]) {
			// TODO(sdsmith): check argument format
			case "TIME":
				// TODO(sdsmith): check given time value format
				$time = $op_args[$i+1];
				break;
			case "MAX":
				// TODO(sdsmith): check max value format
				$maxnotes = $op_args[$i+1];
				break;
			default:
				// Unknown argument
				$errormessages[] = "Unknown GET argument \'" . $op_args[$i] . "\'";
				$responce->status = STATUS_BAD_REQUEST;
		}
	}

	// TODO(sdsmith): now query the database
}
?>
