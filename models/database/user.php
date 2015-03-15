<?php



/*
 * Insert user into the database. Return true on successful insert, false 
 * otherwise.
 */
function dbRegisterNewUser($isAdmin, $email, $phoneNumber, $password) {
	$insert_status = false;
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
	var_dump($result);

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
 * TODO(sdsmith):
 */
function dbUpdateUserInfo($user_id, $email=null, $phoneNumber=null, $password=null) {
	$insert_status = false;
	$num_args = 0;
	$args = array();

	// Check which values to change
	if ($email) {
		$num_args += 1;
		$set_clause .= "";// TODO(sdsmith):
	}

	$dbconn = dbConnect();
	pg_prepare($dbconn, "update_user_info", 'UPDATE appuser SET ');
	
	// TODO(sdsmith): check validity
	// Registration information is valid
	$timestamp = date('Y-m-d H:i:s');
	$result = pg_execute($dbconn, "update_user_info", array());
	if ($result) {
		
	} else {
		die("Could not update user information: " . pg_last_error());
	}

	dbClose($dbconn);
	return $insert_status;
}
?>
