<!DOCTYPE html>
<html>

<head>
	<title>YikYak Clone</title>
</head>

<body>
<script type="text/javascript">
var STATUS_BAD_REQUEST_CODE = "400";
var STATUS_SUCCESS_CODE = "200";
var STATUS_UNAUTHORIZED = "401";

/*
 * Updates page information with the api responce.
 * @param	api_responce	validated responce from back-end
 */
function updatePage(api_responce) {
	// TODO(sdsmith):
}

/*
 * Constructor for APIRequest object.
 */
function APIRequest(operation, latitude, longitude) {
	this.operation = operation;
	this.latitude = latitude;
	this.longitude = longitude;
	this.note = null;
}

/*
 * Sends AJAX request to back-end and actions appropriately based on responce 
 * status code.
 * @param	api_request	APIRequest object to send
 */
function sendAPIRequest(api_request) {
	$.post("api.php",
		JSON.stringify(request), 
		function(responce) {
			// Check responce status code
			switch(responce.status.split()[0]) {
				case STATUS_SUCCESS_CODE:
					// Request success
					updatePage(responce);
				case STATUS_UNAUTHORIZED:
					// User needs to be authenticated
					// TODO(sdsmith):
				case STATUS_BAD_REQUEST_CODE:
					// Bad request
					// TODO(sdsmith):
				default:
					// Unknown status code
					// TODO(sdsmith):
			}
		},
		"json");

}
</script>


	<div class="page" id="login">
		<!-- User login form -->
		<form id="login_form" method="post">
			<h1>Login</h1>
			<input type="text" name="login_username" value="" />
			<input type="password" name="login_password" />
			<input type="submit" name="Login" />
		</form>

		<button onclick="setPage('registration')">Register</button>
	</div>

	<div class="page" id="registration">
	</div>

	<div class="page" id="main">
	</div>

</body>
</html>
