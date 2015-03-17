<?php
require_once("postgres.php");



/*
 * Insert given session key into the active session key list. Return true on
 * success, false otherwise.
 */
function dbInsertSessionKey($session_key, $user_id) {
	$dbconn = dbConnect();
	$success = false;

	// TODO(sdsmith): check that user does not have 2 api keys!

	$prepare_ret = pg_prepare($dbconn, 'insert_session_key', 'INSERT INTO active_api_session_keys (session_key, user_id) VALUES ($1, $2)');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'insert_session_key', array($session_key, $user_id));
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
 * Removes given session key from the active key list. Return true on success,
 * false otherwise.
 */
function dbRemoveSessionKey($session_key) {
	$dbconn = dbConnect();
	$success = false;
	
	$prepare_ret = pg_prepare($dbconn, 'delete_session_key', 'DELETE FROM active_api_session_keys WHERE session_key = $1');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'delete_session_key', array($session_key));
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
 * Return row associated with the given session key if it is active, null 
 * otherwise.
 */
function dbActiveSessionKey($session_key) {
	$dbconn = dbConnect();
	$result = null;
	
	$prepare_ret = pg_prepare($dbconn, 'check_active_session_key', 'SELECT * FROM active_api_session_keys WHERE session_key = $1');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'check_active_session_key', array($session_key));
		if ($resultobj) {
			$result_as_array = pg_fetch_array($resultobj);
			if ($result_as_array) {
				// There was a result, so we matched
				$result = $result_as_array;

				// Sanity check: confirm there was only one session key match
				if ($result_as_array = pg_fetch_array($resultobj)) {
					// NOTE(sdsmith): should never happen as the session key is the primary key
					die("Two session keys with the same name!!");
				}			
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
 * Return array of active session keys associated with the given user id.
 */
function dbGetActiveUserSessionKeys($user_id) {
	$dbconn = dbConnect();
	$result = null;
	
	$prepare_ret = pg_prepare($dbconn, 'get_active_user_id_session_keys', 'SELECT * FROM active_session_keys WHERE user_id = $1');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'get_active_user_id_session_keys', array($user_id));
		if ($resultobj) {
			$full_result_as_array = pg_fetch_all($resultobj);
			if ($full_result_as_array) {
				$result = $full_result_as_array;
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
 * Authenticates the given user/password with the database. Return array of
 * user data from 'appuser' corresponding to the authenticated user, or null
 * on fail.
 */
// TODO(sdmiths): only return column indexed array from result, not numerical
function dbAuthenticateUser($email, $password) {
	$dbconn = dbConnect();
	$result = null;
	
	$prepare_ret = pg_prepare($dbconn, "credential_check", 'SELECT (email, phone_number, join_date, validated, last_login) FROM appuser, appuser_passwords WHERE appuser.email = $1 and appuser_passwords.password = $2');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, "credential_check", array($email, $password));
		if ($resultobj) {
			$result_as_array = pg_fetch_array($resultobj);
			if ($result_as_array) {
				// There was a result, so we are authenticated.
				$autheduserdata = $result_as_array;

				// Sanity check: confirm there was only one credential match.
				if ($result_as_array = pg_fetch_array($resultobj)) {
					die("Multiple user credential matches");
				}
	
				// Properly authenticated
				$result = $autheduserdata;
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
 * Updates the last login time of a user to the current time.
 */
function dbUpdateLastLoginTime($user_id) {
	$dbconn = dbConnect();
	if (!pg_prepare($dbconn, "update_last_login_time", "UPDATE appuser SET last_login = $1 WHERE id = $2")) {
		die("Error: " . pg_last_error());
	}

	$timestamp = date('Y-m-d H:i:s');

	$result = pg_execute($dbconn, "update_last_login_time", array($timestamp, $user_id));
	if (!$result) {
		die("Error: " . pg_last_error());
	}

	dbClose($dbconn);
}
?>
