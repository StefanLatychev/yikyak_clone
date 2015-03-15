<?php
require_once("postgres.php");

// For geo coordinate help:
// http://www.movable-type.co.uk/scripts/latlong.html
define('GEO_LOCAL_RADIUS', 0.01); // ~1.11km latitude and longitude


// TODO(sdsmith): DONT SEND BACK USER_ID ON NOTES IN LOCAL AND WORLDWIDE


/*
 * Return array of all notes in your the given area, with a maximum array length
 * of maxnotes. If timestamp is provided, getForwardInTime must also be
 * provided.
 * Note that it searches location areas in a square.
 */
function dbGetLocalNotes(	$maxnotes,
				$latitude, 
				$longitude,
				$timestamp = null,
				$getForwardInTime = null) {

	// Set default time stamp to current if not provided
	if (!$timestamp) {
		$timestamp = date('Y-m-d H:i:s');
		$getForwardInTime = true;
	}

	// Determine timestamp comparator
	if ($getForwardInTime) {
		$comparator = ">=";
	} else {
		$comparator = "<=";
	}

	// Perform query
	$dbconn = dbConnect();
	$result = null;

	$prepare_ret = pg_prepare($dbconn, 'get_local_notes', 'SELECT * FROM (SELECT id, time, locaion_latitude, location_longitude, votes, message FROM notes ORDER BY time DESC) WHERE location_latitude BETWEEN $1 - $3 AND $1 + $3 AND location_longitude BETWEEN $2 - $3 AND $2 + $3 AND time' . $comparator . '$4 LIMIT $5');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'get_local_notes', array($latitude, $longitude, GEO_LOCAL_RADIUS, $timestamp, $maxnotes));
		if ($resultobj) {
			$result = pg_fetch_all($resultobj);
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return $result;
}

/*
 * Same as dbGetLocalNotes, but only considers the time a note was posted, not
 * the location.
 */
function dbGetWorldwideNotes(	$maxnotes,
				$timestamp = null,
				$getForwardInTime = null) {

	// Set default time stamp to current if not provided
	if (!$timestamp) {
		$timestamp = date('Y-m-d H:i:s');
		$getForwardInTime = true;
	}

	// Determine timestamp comparator
	if ($getForwardInTime) {
		$comparator = ">=";
	} else {
		$comparator = "<=";
	}

	// Perform query
	$dbconn = dbConnect();
	$result = null;

	$prepare_ret = pg_prepare($dbconn, 'get_worldwide_notes', 'SELECT * FROM (SELECT id, time, locaion_latitude, location_longitude, votes, message FROM notes ORDER BY time DESC) WHERE time' . $comparator . '$1 LIMIT $2');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'get_worldwide_notes', array($timestamp, $maxnotes));
		if ($resultobj) {
			$result = pg_fetch_all($resultobj);
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return $result;
}



/*
 * Insert given note into the database.
 */
function dbInsertNote($user_id, $latitude, $longitude, $message) {
	$dbconn = dbConnect();
	$success = false;
	$timestamp = date('Y-m-d H:i:s');

	$prepare_ret = pg_prepare($dbconn, 'insert_note', 'INSERT INTO notes (user_id, time, location_latitude, location_longitude, votes, message) VALUES ($1, $2, $3, $4, 0, $5)');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'insert_note', array($user_id, $timestamp, $latitude, $longitude, $message));
		if ($resultobj) {
			$result_as_array = pg_fetch_array($resultobj);
			if ($result_as_array && $result_as_array[2] == 1) {	// NOTE(sdsmith): Check if this is the right value to be checking for success
				$success = true;
			}
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return $success;
}



/*
 * Insert a user's vote on a particular note into the database.
 */
function dbInsertVote($note_id, $user_id, $isUpvote) {
	$dbconn = dbConnect();
	$success = false;

	$prepare_ret = pg_prepare($dbconn, 'insert_vote', 'INSERT INTO notes_votes (note_id, user_id, upvote) VALUES ($1, $2, $3)');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'insert_vote', array($note_id, $user_id, $isUpvote));
		if ($resultobj) {
			$result_as_array = pg_fetch_array($resultobj);
			if ($result_as_array && $result_as_array[2] == 1) {	// NOTE(sdsmith): Check if this is the right value to be checking for success
				$success = true;
				// TODO(sdsmith): update vote count for corresponding note
			}
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return $success;
}



/*
 * Return true if the given user has voted on the given note, false otherwise.
 */
function dbHasVotedOnNote($note_id, $user_id) {
	// TODO(sdsmith):
}



/*
 * Insert a user's report for the given note.
 */
// TODO(sdsmith): update the note entry that it has been reported.
function dbInsertReport($note_id, $reporter_id, $reason) {
	$dbconn = dbConnect();
	$success = false;
	$timestamp = date('Y-m-d H:i:s');

	$prepare_ret = pg_prepare($dbconn, 'insert_report', 'INSERT INTO notes_reported (note_id, reporter_id, time, reason) VALUES ($1, $2, $3, $4)');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'insert_report', array($note_id, $reporter_id, $timestamp, $reason));
		if ($resultobj) {
			$result_as_array = pg_fetch_array($resultobj);
			if ($result_as_array && $result_as_array[2] == 1) {	// NOTE(sdsmith): Check if this is the right value to be checking for success
				$success = true;
			}
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return $success;
}



/*
 * Removes given note from the database. User id must be provided so there is a
 * record of who deleted it.
 */
function dbRemoveNote($note_id, $user_id) {
	// TODO(sdsmith):
}









?>
