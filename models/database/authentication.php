<?php
require_once("postgres.php");



/*
 * Insert given session key into the active session key list. Return true on
 * success, false otherwise.
 */
function dbInsertSessionKey($session_key, $user_id) {
	$dbconn = dbConnect();
	
	$prepare_ret = pg_prepare($dbconn, 'insert_session_key', 'INSERT INTO active_api_session_keys (session_key, user_id) VALUES ($1, $2)');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'insert_session_key', array($session_key, $user_id));
		if ($resultobj) {
			$result_as_array = pg_fetch_array($resultobj);
			if ($result_as_array && $result_as_array[2] == 1) {	// NOTE(sdsmith): Check if this is the right value to be checking for success
				return true;
			}
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return false;
}



/*
 * Removes given session key from the active key list. Return true on success,
 * false otherwise.
 */
function dbRemoveSessionKey($session_key) {
	$dbconn = dbConnect();
	
	$prepare_ret = pg_prepare($dbconn, 'delete_session_key', 'DELETE FROM active_api_session_keys WHERE session_key = $1');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'delete_session_key', array($session_key));
		if ($resultobj) {
			$result_as_array = pg_fetch_array($resultobj);
			if ($result_as_array && $result_as_array[1] >= 1) {	// NOTE(sdsmith): Check if this is the right value to be checking for success
				return true;
			}
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return false;
}



/*
 * Return true id given session key is active, false otherwise.
 */
function dbIsActiveSessionKey($session_key) {
	$dbconn = dbConnect();
	
	$prepare_ret = pg_prepare($dbconn, 'check_active_session_key', 'SELECT * FROM active_session_keys WHERE session_key = $1');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'check_active_session_key', array($session_key));
		if ($resultobj) {
			$result_as_array = pg_fetch_array($resultobj);
			if ($result_as_array) {
				// There was a result, so we matched

				// Sanity check: confirm there was only one session key match
				if ($result_as_array = pg_fetch_array($resultobj)) {
					// NOTE(sdsmith): should never happen as the session key is the primary key
					die("Two session keys with the same name!!");
				}
			
				return true;
			}
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return false;
}



/*
 * Return array of active session keys associated with the given user id.
 */
function dbGetActiveUserSessionKeys($user_id) {
	$dbconn = dbConnect();
	
	$prepare_ret = pg_prepare($dbconn, 'get_active_user_id_session_keys', 'SELECT * FROM active_session_keys WHERE user_id = $1');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, 'get_active_user_id_session_keys', array($user_id));
		if ($resultobj) {
			$full_result_as_array = pg_fetch_all($resultobj);
			if ($full_result_as_array) {
				return $full_result_as_array;
			}
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return false;
}



/*
* Authenticates the given user/password with the database. Return array of
* user data from 'appuser' corresponding to the authenticated user, or false
* on fail.
*/
function dbAuthenticateUser($username, $password) {
	$dbconn = dbConnect();
	
	$prepare_ret = pg_prepare($dbconn, "credential_check", 'SELECT * FROM appuser, appuser_passwords WHERE appuser.name = $1 and appuser_passwords.password = $2');
	if ($prepare_ret) {
		$resultobj = pg_execute($dbconn, "credential_check", array($username, $password));
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
				return $autheduserdata;
			}
		} else {
			die("Query failed: " . pg_last_error());
		}
	} else {
		die("Prepared statement failed: " . pg_last_error());
	}

	dbClose($dbconn);
	return false;
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
