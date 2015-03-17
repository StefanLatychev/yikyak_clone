<?php
require_once("postgres.php");

// For geo coordinate help:
// http://www.movable-type.co.uk/scripts/latlong.html
define('GEO_LOCAL_RADIUS', 0.01); // ~1.11km latitude and longitude



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
		$getForwardInTime = false;
	}

	// Determine timestamp comparator
	if ($getForwardInTime) {
		$comparator = ">=";
		$sort_direction = "ASC";
	} else {
		$comparator = "<=";
		$sort_direction = "DESC";
	}

	// Perform query
	$dbconn = dbConnect();
	$result = null;

	$sql_query = 'SELECT * FROM (SELECT id, time, location_latitude, location_longitude, votes, message FROM notes ORDER BY time ' . $sort_direction . ') AS ordered_notes WHERE (ordered_notes.location_latitude BETWEEN $1::real - $3::real AND $1::real + $3::real) AND (ordered_notes.location_longitude BETWEEN $2::real - $3::real AND $2::real + $3::real) AND ordered_notes.time ' . $comparator . ' $4 ORDER BY ordered_notes.time ASC LIMIT $5';
	var_dump($sql_query);

	$prepare_ret = pg_prepare($dbconn, 'get_local_notes_' . $comparator . $sort_direction, $sql_query);
	if ($prepare_ret) {
		var_dump(array($latitude, $longitude, GEO_LOCAL_RADIUS, $timestamp, $maxnotes));
		$resultobj = pg_execute($dbconn, 'get_local_notes_' . $comparator . $sort_direction, array($latitude, $longitude, GEO_LOCAL_RADIUS, $timestamp, $maxnotes));
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
		$getForwardInTime = false;
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

	$prepare_ret = pg_prepare($dbconn, 'get_worldwide_notes', 'SELECT * FROM (SELECT id, time, location_latitude, location_longitude, votes, message FROM notes ORDER BY time DESC) AS ordered_notes WHERE ordered_notes.time' . $comparator . '$1 ORDER BY ordered_notes.time ASC LIMIT $2');
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
			$success = true;
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
 * Modifies the vote count of the given note by the given vote delta. Return
 * true on success, false on failure.
 */
function dbUpdateNoteVoteCount($note_id, $vote_delta) {
	$dbconn = dbConnect();
	$success = false;

	$prepare_ret = pg_prepare($dbconn, 'update_note_vote_count', 'UPDATE notes SET votes = votes + $1 WHERE id = $2');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'update_note_vote_count', array($vote_delta, $note_id));
		if ($resultobj) {
			$success = true;
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
 * @param isUpvote	't' if the vote is an upvote, 'f' otherwise
 */
// NOTE(sdsmith): $isUpvote should be a string interpretation of a boolean 
// accepted by postgres.
function dbInsertVote($note_id, $user_id, $isUpvote) {
	$dbconn = dbConnect();
	$success = false;

	$prepare_ret = pg_prepare($dbconn, 'insert_vote', 'INSERT INTO notes_votes (note_id, user_id, upvote) VALUES ($1, $2, $3)');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'insert_vote', array($note_id, $user_id, $isUpvote));
		if ($resultobj) {			
			// Update vote count for corresponding note
			if ($isUpvote == 't') {
				$vote_delta = 1;
			} else {
				$vote_delta = -1;
			}
			
			$success = true;
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);

	// Update vote count if we succeeded
	if ($success) {
		$success = dbUpdateNoteVoteCount($note_id, $vote_delta);
	}

	return $success;
}



/*
 * Updates an existing vote in the database to the new value.
 */
// TODO(sdsmith): Update note vote count
// NOTE(sdsmith): $isUpvote and $oldVote should be a string interpretation of a 
// boolean accepted by postgres. In this case we expect 't' or 'f'.
function dbUpdateVote($note_id, $user_id, $isUpvote, $oldVote) {
	$dbconn = dbConnect();
	$success = false;
	$update_vote = false;

	$prepare_ret = pg_prepare($dbconn, 'update_vote', 'UPDATE notes_votes SET upvote = $3 WHERE note_id = $1 and user_id = $2');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'update_vote', array($note_id, $user_id, $isUpvote));
		if ($resultobj) {			
			if ($isUpvote != $oldVote) {
				// Update vote count for corresponding note
				if ($isUpvote == 't') {
					$vote_delta = 1;
				} else {
					$vote_delta = -1;
				}

				// Modify delta based on old vote
				if ($oldVote == 't') {
					$vote_delta -= 1;
				} else {
					$vote_delta += 1;
				}
				
				$update_vote = true;
			}

			$success = true;
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);

	// Update vote count if we succeeded
	if ($update_vote) {
		$success = dbUpdateNoteVoteCount($note_id, $vote_delta);
	}

	return $success;
}



/*
 * Return true if the given user has voted on the given note, false otherwise.
 */
function dbGetVoteOnNote($note_id, $user_id) {
	$dbconn = dbConnect();
	$result = null;

	$prepare_ret = pg_prepare($dbconn, 'check_vote_existance', 'SELECT * FROM notes_votes WHERE note_id = $1 AND user_id = $2');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'check_vote_existance', array($note_id, $user_id));
		if ($resultobj) {
			$result_as_array = pg_fetch_array($resultobj, null, PGSQL_ASSOC);
			if ($result_as_array) {
				$result = $result_as_array;
			}
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
 * Insert a user's report for the given note.
 */
// TODO(sdsmith): update the note entry that it has been reported. Will have
// to modify schema to accomodate this.
function dbInsertReport($note_id, $reporter_id, $reason) {
	$dbconn = dbConnect();
	$success = false;
	$timestamp = date('Y-m-d H:i:s');

	$prepare_ret = pg_prepare($dbconn, 'insert_report', 'INSERT INTO notes_reported (note_id, reporter_id, time, reason) VALUES ($1, $2, $3, $4)');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'insert_report', array($note_id, $reporter_id, $timestamp, $reason));
		if ($resultobj) {
			$success = true;
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return $success;
}








?>
