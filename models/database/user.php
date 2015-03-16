<?php



/*
 * Insert user into the database. Return true on successful insert, false 
 * otherwise.
 */
function dbRegisterNewUser($isAdmin, $email, $phoneNumber, $password) {
	$insert_status = false;

	// Make sure the empty string is not passed to the database.
	if ($phoneNumber == '') {
		$phoneNumber = null;
	}

	$dbconn = dbConnect();

	pg_prepare($dbconn, "insert_user_info", 'INSERT INTO appuser (admin, email, phone_number, join_date, validated, last_login) VALUES ($1, $2, $3, $4, false, $4)');
	//pg_prepare($dbconn, "insert_user_info", 'INSERT INTO appuser (admin, email, phone_number, join_date, validated, last_login) VALUES (false, $1, $2, $3, false, $3)');
	pg_prepare($dbconn, "insert_user_password", 'INSERT INTO appuser_passwords (user_id, password) VALUES ((SELECT id FROM appuser WHERE email = $1), $2)');
	
	// TODO(sdsmith): check input validity

	// Registration information is valid
	$timestamp = date('Y-m-d H:i:s');

	// Must convert boolean value to pg acceptable value (due to php qwirk outlined below)
	// https://bugs.php.net/bug.php?id=44791
	if ($isAdmin) {
		$pgconv_isAdmin = 't';
	} else {
		$pgconv_isAdmin = 'f';
	}

	$result = pg_execute($dbconn, "insert_user_info", array($pgconv_isAdmin, $email, $phoneNumber, $timestamp));
	//$result = pg_execute($dbconn, "insert_user_info", array($email, $phoneNumber, $timestamp));

	if ($result) {
		$result = pg_execute($dbconn, "insert_user_password", array($email, $password));
		if ($result) {
			$insert_status = true;
		} else {
			die("Could not insert password into appuser_password: " . pg_last_error());
			// TODO(sdsmith): unroll the initial successful insert.
		}
	} else {
		die("Could not insert user into appuser: " . pg_last_error());
	}

	dbClose($dbconn);
	return $insert_status;
}



/*
 * Return true if given email is in the database.
 */
function dbExistsEmail($email) {
	$exists = false;
	$dbconn = dbConnect();

	if (pg_prepare($dbconn, 'exists_email', 'SELECT * FROM appuser WHERE email = $1')) {
		if ($resultobj = pg_execute($dbconn, 'exists_email', array($email))) {
			$exists = pg_fetch_array($resultobj);
		}
	} 

	dbClose($dbconn);
	return $exists;
}



/*
 * Return true if the given phone number exists in the database.
 */
function dbExistsPhoneNumber($phoneNumber) {
	$exists = false;
	$dbconn = dbConnect();

	if (pg_prepare($dbconn, 'exists_phone_number', 'SELECT * FROM appuser WHERE phone_number = $1')) {
		if ($resultobj = pg_execute($dbconn, 'exists_phone_number', array($phoneNumber))) {
			$exists = pg_fetch_array($resultobj);
		}
	} 

	dbClose($dbconn);
	return $exists;
}



/*
 * Updates given user id's information to the given values (if given).
 */
function dbUpdateUserInfo($user_id, $email=null, $phoneNumber=null, $password=null) {
	$insert_status = true;
	$dbconn = dbConnect();

	// Prepare queries
	$prep_ret1 = pg_prepare($dbconn, 'update_user_email', 'UPDATE appuser SET email = $1 WHERE id = $2');
	$prep_ret2 = pg_prepare($dbconn, 'update_user_phone_number', 'UPDATE appuser SET phone_number = $1 WHERE id = $2');
	$prep_ret3 = pg_prepare($dbconn, 'update_user_password', 'UPDATE appuser_passwords SET password = $1 WHERE user_id = $2');

	// Confirm preperations were successful
	if (!$prep_ret1 || !$prep_ret2 || !$prep_ret2) {
		$insert_status = false;
	}

	// Check which values to change
	if ($email && $insert_status) {
		$insert_status = pg_execute($dbconn, 'update_user_email', array($email, $user_id));
	}

	if ($phoneNumber && $insert_status) {
		$insert_status = pg_execute($dbconn, 'update_user_phone_number', array($phoneNumber, $user_id));
	}


	if ($password && $insert_status) {
		$insert_status = pg_execute($dbconn, 'update_user_password', array($password, $user_id));
	}

	dbClose($dbconn);
	return $insert_status;
}
?>
