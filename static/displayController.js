//Display Controller

var username;
var latitude;
var longitude;
var notePull;

/*******************************Constructors******************************/

//Constructor for registraction api request object
function packageRegistrationAPIRequest(email1, email2, phone, password, confirm) {
	var request_payload = {
		email1: email1,
		email2: email2,
		phone_number: phone,
		password1: password,
		password2: confirm
	}
	return request_payload;
}


//Constructor for basic authentication api request object
function packageBasicAPIRequest(email, password) {
	var request_payload = {
		email : email,
		password : password
	}
	return request_payload;
}


//Constructor for get notes api request object 
function packageGetNotesAPIRequest(latitude, longitude, timestamp, direction, maxNotes) {
	var location = {
		latitude : latitude,
		longitude : longitude
	}
	
	var time = {
		timestamp : timestamp,
		direction : direction
	}
	
	var notes = {
		max_notes : maxNotes
	}
	
	var request_payload = {
		location,
		time, 
		notes
	}
	return request_payload;
}

//Add more constructors as needed







/************Initialization & Display Change Functions******************/

//Initializes the page to the login screen
function initialize() {
	$('body > :not(#login)').hide();
	$('.error').hide();
	$('#login').appendTo('body');
}



//Google Map Initializer
function initializeMap() {
	var mapCanvas = document.getElementById("googleMap");
	var mapProp = {
		center:new google.maps.LatLng(51.508742,-0.120850),
		zoom:5,
		mapTypeId:google.maps.MapTypeId.ROADMAP
	};
	map=new google.maps.Map(mapCanvas, mapProp);
}



//Changes display to specified page(reinitialize map if changing to main page)
function changeDisplay(page_to_display) {
	$('#'+page_to_display).show();
	$('body > :not(#'+page_to_display+')').hide();
	
	
	//Reinitialize map when we change to main page, this prevents
	//the map from displaying incorrectly when we change from and back to it.
	if(page_to_display === "main") {
		initializeMap();
		notePull = setInterval(
			function notes() {
				noteRequest(); 
			}, 5000);
	} else {
		clearInterval(notePull);
	}
	$('#'+page_to_display).appendTo('body');
	return false;
}








/**********************Validation & Request Functions**************************/

//Login validation, packaging, and sending as request to api
//TODO(SLatychev): Add more rigorous validation checking
function loginValidation() {
	$('.error').hide();

	//Validation and field variables
	var validated = true;
	var email = $("#login_email").val();
	var password = $("#login_password").val();

	//Email Validation
	if(email == ""){
		$("label#login_email_error").show();
		$("input#login_email").focus();
		validated = false;
	}
	
	//Password Validation
	if(password == ""){
		$("label#login_password_error").show();
		$("input#login_password").focus();
		validated = false;
	}
	
	
	//Validation check
	if(!validated){
		return false;
	}
	username = email;
	//Package and send request
	var payloadString = JSON.stringify(packageBasicAPIRequest(email, password));
	sendAPIRequest("api/authentication.php", "POST", payloadString, loginValidated);
	return false;
}



//Registration validation, packaging, and sending as request to api.
//TODO(SLatychev): Add more rigorous validation checking
function registerValidation(){
	//Hide all error labels
	$('.error').hide();
	
	//Validation and Field variables
	var validated = true;
	var email1 = $("input#email1").val();
	var email2 = $("input#email2").val();
	var phone = $("input#phone").val();
	var password = $("input#password").val();
	var confirm = $("input#confirm").val();

	//Email Validation
	if(email1 == ""){
		$("label#email1_error").show();
		$("input#email1").focus();
		validated = false;
	}	
	if(email2 == ""){
		$("label#email2_error").show();
		$("input#email2").focus();
		validated = false;
	} else if(email2 != email1) {
		$("label#email2_error2").show();
		$("input#email2").focus();
		validated = false;
	}	
	
	//Phone Validation
	var num = new RegExp("[0-9]*");
	if(phone != "" && num.test(phone)) {
		validated = false;
	}
	
	//Password Validation
	if(password == ""){
	$("label#password_error").show();
		$("input#password").focus();
		validated = false;
	}
	if(confirm == ""){
		$("label#confirm_error").show();
		$("input#confirm").focus();
		validated = false;
	} else if(confirm != password) {
		$("label#confirm_error2").show();
		$("input#confirm").focus();
		validated = false;
	}
	
	//Validation Check
	if(!validated) {
		return false;
	}

	//Package and send request
	var payloadString = JSON.stringify(packageRegistrationAPIRequest(email1, email2, phone, password, confirm));
	sendAPIRequest("api/user.php", "POST", payloadString, registrationValidated);
	return false;
}


//Prompt for reauthentication
function authValidation() {	
	//Hide all error labels
	$('.error').hide();
	
	var email = $("input#authenticate_email").val();
	var password = $("input#authenticate_password").val();
	var validated = true;
	
	//Email Validation
	if(email == ""){
		$("label#authenticate_email_error").show();
		$("input#authenticate_email").focus();
		validated = false;
	}
	if(email != username){
		$("label#authenticate_error").show();
		return false;
	}
	
	//Password Validation
	if(password == ""){
		$("label#authenticate_password_error").show();
		$("input#authenticate_password").focus();
		validated = false;
	}
	
	if(!validated) {
		return false;
	}
	
	var payloadString = JSON.stringify(packageBasicAPIRequest(email, password));
	sendAPIRequest("api/user.php", "GET", payloadString, userInfo);
	return false;
}


//Request server to get notes
function noteRequest() {
	var time = Date.now() / 1000 | 0;
	var direction = "before";
	
	var payloadString = JSON.stringify(packageGetNotesAPIRequest(latitude, longitude, time, direction, ""));
	sendAPIRequest("api/notes.php", "GET", payloadString, displayNotes);
	return false;
}


//Request server to logout
function logoutRequest() {
	var payloadString = JSON.stringify({});
	sendAPIRequest("api/authentication.php", "DELETE", payloadString, logout);
	return false;
}










/***************************Control Functions****************************/

//User has registered account, send to login page
function registrationValidated(response_object) {
	changeDisplay('login');
	return false;
}


//User has logged in, send to main page
function loginValidated(response_object) {
	changeDisplay('main');
	getLocation();
	return false;
}


//User has logged out, send to login page
function logout() {
	changeDisplay('login');
	return false;
}


//User has authenticated, send to account page and construct user info profile
function userInfo(response_object) {
	changeDisplay('account');
	
	//If we've already asked for account info do not generate again
	if(document.getElementById('account_info')) {
		
		return false;
	}
	
	//Create main div
	var account_info_div = document.createElement("div");
	account_info_div.id = "account_info";
	
	//Add field set to main div
	var account_info_form = document.createElement("fieldset");
	account_info_div.appendChild(account_info_form);
	
	//Get the user info object
	var user_info = response_object.user_info;
	for(var key in user_info){
		//If user_info object has a key property
		if(user_info.hasOwnProperty(key)){
			
			//Create a div for the the key value pair
			var div_element = document.createElement("div");
			div_element.id = "info_element";
			account_info_form.appendChild(div_element);
			
			//Load key value data into div
			var user_info_content = document.createTextNode(key+": "+user_info[key]);
			div_element.appendChild(user_info_content);
		}
	}
	
	//Append the created account_info div to the document's user_info div
	document.getElementById('user_info').appendChild(account_info_div);
	return false;
}

//Deconstruct Dom objects so that they do not carry over
function deconstructDOM(parent, display_change) {
	if(parent != null) {
		var element = document.getElementById(parent);
		element.parentNode.removeChild(element);
	}
	if(display_change !== "" || display_change != null) {
		changeDisplay(display_change);
	}
	return false;
}


function displayNotes(response_object) {
	alert("success");
	notes = response_object.notes;
	
}


/***************************Geolocation Functions****************************/


function getLocation() {
	if(navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(getPosition);
	} else {
		alert("Geolocation not supported");
	}
}
	
function getPosition(position) {
	//alert("Latitude: "+position.coords.latitude+"\nLongitude: "+position.coords.longitude);
	latitude = position.coords.latitude;
	longitude = position.coords.longitude;
}


/***************************API Request Functions****************************/


/*
 * Sends AJAX request to back-end and actions appropriately based on responce 
 * status code.
 */
function sendAPIRequest(
	api_request_url, 
	http_request_method, 
	encoded_request_payload, 
	callback_func) {
		//alert("Stepping into API Request");
		var request = $.ajax({
			url: api_request_url,
			method: http_request_method,
			data: {request : encoded_request_payload},
			success: function(result) {		
				// decode result (it is in json format)
				result_object = JSON.parse(result);
				
				/*
				alert("Parsed JSON object");
				alert(result_object.status);
				alert(STATUS_OK);
				alert(Number(result_object.status) === STATUS_OK);
				//*/
				
				switch(Number(result_object.status)){
					case STATUS_OK:
						//Callback function goes here
						callback_func(result_object);
						//alert("Good Request");
						break;
					case STATUS_BAD_REQUEST:
						//alert("Bad Request");
						break;
					case STATUS_UNAUTHORIZED:
						changeDisplay('error');
						break;
					case STATUS_FORBIDDEN:
						//Display info
						break;
					case STATUS_REQUEST_URI_TOO_LONG:
						//Display info
						break;
					case STATUS_INTERNAL_SERVER_ERROR:
						//Display info
						break;
					case STATUS_NOT_IMPLEMENTED:
						//Display info
						break;
					default:
						//Do default action
			}
		}
	});
}

