//Display Controller

var USERNAME;
var LATITUDE;
var LONGITUDE;
var NOTEPULL;

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
	
	var request_payload = {
		location,
		time, 
		max_notes :  maxNotes
	}
	return request_payload;
}



//Constructor for sending notes api request object
function packageSendNotesAPIRequest(latitude, longitude, msg){
	var location = {
		latitude : latitude,
		longitude : longitude
	}
	
	var request_payload = {
		location,
		message : msg
	}
	return request_payload;
}



/*
 * Return note vote API request object with the given attributes.
 * @param note_id	id of the note to apply the vote to
 * @param upvote	't' if the vote is up, 'f' if the vote is down
 */
function packageNoteVoteAPIRequest(note_id, upvote) {
	var request_payload = {
		note_id: note_id,
		upvote: upvote
	}

	return request_payload;
}



/*
 * Return note report API request object with the given attributes.
 * @param note_id	id of the note to apply the vote to
 * @param reason	reason for reporting note
 */
function packageNoteReportAPIRequest(note_id, reason) {
	var request_payload = {
		note_id: note_id,
		reason: reason
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
	//TODO(SLatychev): Prevent first note fetch to avoid worldwide fetch
	return false;
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
	return false;
}



//Changes display to specified page(reinitialize map if changing to main page)
function changeDisplay(page_to_display) {
	$('#'+page_to_display).show();
	$('body > :not(#'+page_to_display+')').hide();
	
	
	//Reinitialize map when we change to main page, this prevents
	//the map from displaying incorrectly when we change from and back to it.
	if(page_to_display === "main") {
		initializeMap();
		NOTEPULL = setInterval(
			function notes() {
				noteRequest(); 
			}, 5000);
	} else {
		clearInterval(NOTEPULL);
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
	USERNAME = email;
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
	var num = new RegExp("[0-9]+");
	if(phone != "" && !num.test(phone)) {
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
//TODO(SLatychev): Add more rigorous validation checking
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
	if(email != USERNAME){
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
	//create new date object
	var now = new Date();
	
	//Get the current date
	var date = [now.getFullYear(), now.getMonth() + 1, now.getDate()];
	
	//Get the current time
	var time = [now.getHours(), now.getMinutes(), now.getSeconds()];
	
	//Will put time in double digits
	for(var i = 0; i < 3; i++) {
		if(time[i] < 10) {
			time[i] = "0"+time[i];
		}
	}
	
	for(var j = 1; j < 3; j++) {
		if(date[j] < 10) {
			date[j] = "0"+date[j];
		}
	}
	
	var current_timestamp = date.join("-")+ " " + time.join(":");
	
	var direction = "before";
	
	var payloadString = JSON.stringify(packageGetNotesAPIRequest(LATITUDE, LONGITUDE, current_timestamp, direction, ""));
	sendAPIRequest("api/notes.php", "GET", payloadString, displayNotes);
	return false;
}



function sendMessage() {
	var msg = $("input#message").val();
	
	if(msg == ""){
		return false;
	}
	
	var payloadString = JSON.stringify(packageSendNotesAPIRequest(LATITUDE, LONGITUDE, msg));
	sendAPIRequest("api/notes.php", "POST", payloadString, noteRequest);
	return false;
}


//Request server to logout
function logoutRequest() {
	var payloadString = JSON.stringify({});
	sendAPIRequest("api/authentication.php", "DELETE", payloadString, logout);
	return false;
}



/*
 * Send note vote to server and update the note on screen.
 * @param upvote	't' if upvote, 'f' is downvote
 */
function noteVoteRequest(note_id, upvote) {
	var payloadString = JSON.stringify(packageNoteVoteAPIRequest(note_id, upvote));
	sendAPIRequest("api/notes.php", "PUT", payloadString, 
		function(request_object) {
			// TODO():
			// get note of given id 
			var note = document.getElementById("note_"+note_id.toString());
			// update its appears to 'applied vote' appearance
			if(note) {
				if(upvote == "t") {
					note.votes ++;  		
				} else if(upvote == "f") {
					note.votes --;
				}
			}
		});
	return false;
}


/*
 * Send report for a given note.
 */
function noteReportRequest(note_is, reason) {
	var payloadString = JSON.stringify(packageNoteReportAPIRequest(note_id, reason));
	sendAPIRequest("api/report.php", "POST", payloadString, 
			function(request_object) {
				alert("Report has been sent. Thank you for keeping our timelines safe!");	
			});
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
	var account_info_fieldset = document.createElement("fieldset");
	account_info_fieldset.id = "account_info_fieldset";
	account_info_div.appendChild(account_info_fieldset);
	
	//Get the user info object
	var user_info = response_object.user_info;
	for(var key in user_info){
		//If user_info object has a key property
		if(user_info.hasOwnProperty(key)){
			
			//Create a div for the the key value pair
			var div_element = document.createElement("div");
			div_element.id = "info_element";
			account_info_fieldset.appendChild(div_element);
			
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


//Construct notes from response_object note list
function displayNotes(response_object) {
	var notes = response_object.notes;
	for(var note in notes) {
		//Indented to reflect html indentation
		
		//If note exists then do not duplicate it
		if(document.getElementById('note_'+notes[note].id)) {
			continue;
		}
		
		//Note div
		var note_div = document.createElement("div");
		note_div.className = "note"; 
		note_div.id = "note_" + notes[note].id;
			
			//Note message div
			var note_message_div = document.createElement("div");
			note_message_div.classMessage = "note_message_div";
				
				//Note message span
				var note_message_span = document.createElement("span");
				note_message_span.className = "note_message_span";
				note_message_span.innerHTML = notes[note].message;
				note_message_div.appendChild(note_message_span);
			
			note_div.appendChild(note_message_div);
			
			//Vote wrapper div
			var vote_wrapper_div = document.createElement("div");
			vote_wrapper_div.className = "wrapper_vote";
				
				//Vote options wrapper div
				var vote_wrapper_options_div = document.createElement("div");
				vote_wrapper_options_div.className = "wrapper_vote_options";
					
					//Upvote button
					var upvote_button = document.createElement("button");
					upvote_button.className = "upvote";
					upvote_button.id = "upvote_btn_"+notes[note].id;
					upvote_button.value = "Upvote";
					upvote_button.type = "button";
					upvote_button.innerHTML = "Upvote";
					vote_wrapper_options_div.appendChild(upvote_button);
					
					//Downvote button
					var downvote_button = document.createElement("button");
					downvote_button.className = "downvote";
					downvote_button.id = "downvote_btn_"+notes[note].id;
					downvote_button.value = "Downvote";
					downvote_button.type = "button";
					downvote_button.innerHTML = "Downvote";
					vote_wrapper_options_div.appendChild(downvote_button);
					
				vote_wrapper_div.appendChild(vote_wrapper_options_div);
			
			note_div.appendChild(vote_wrapper_div);
			
			//Note metadata div
			var note_metadata_wrapper_div = document.createElement("div");
			note_metadata_wrapper_div.className = "wrapper_note_metadata_div";
			
				//Note post time wrapper div
				var note_posttime_wrapper_div = document.createElement("div");
				note_posttime_wrapper_div.className = "wrapper_note_posttime_div";
					
					//Note post time wrapper span
					var note_posttime_wrapper_span = document.createElement("span");
					note_posttime_wrapper_span.className = "note_posttime_span";
					note_posttime_wrapper_span.innerHTML = notes[note].time;
					note_posttime_wrapper_div.appendChild(note_posttime_wrapper_span);
				
				note_metadata_wrapper_div.appendChild(note_posttime_wrapper_div);
				
				//Note vote count wrapper div
				var note_votecount_wrapper_div = document.createElement("div");
				note_votecount_wrapper_div.className = "wrapper_note_votecount_div";
					
					//Note vote count wrapper span
					var note_votecount_wrapper_span = document.createElement("span");
					note_votecount_wrapper_span.className = "note_votecount_span";
					note_votecount_wrapper_span.innerHTML = notes[note].votes;
					note_votecount_wrapper_div.appendChild(note_votecount_wrapper_span);
				
				note_metadata_wrapper_div.appendChild(note_votecount_wrapper_div);
			
			note_div.appendChild(note_metadata_wrapper_div);
		
		document.getElementById('timeline').appendChild(note_div);
		
		
		//Functions that are called on mouse up for upvote/downvote buttons
		$('#note_'+notes[note].id).on('mouseup', '.upvote', function(event) {

			noteVoteRequest(event.target.id.toString().split("_")[2], 't');
		});
	
		$('#note_'+notes[note].id).on('mouseup', '.downvote', function(event) {
			noteVoteRequest(event.target.id.toString().split("_")[2], 'f');
		});	
		
	}
	return false;
}


/***************************Geolocation Functions****************************/


function getLocation() {
	if(navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(getPosition);
	} else {
		alert("Geolocation not supported");
	}
	return false;
}
	
function getPosition(position) {
	LATITUDE = position.coords.latitude;
	LONGITUDE = position.coords.longitude;
	
	return false;
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
		var request = $.ajax({
			url: api_request_url,
			method: http_request_method,
			data: {request : encoded_request_payload},
			success: function(result) {		
				// decode result (it is in json format)
				result_object = JSON.parse(result);
				
				switch(Number(result_object.status)){
					case STATUS_OK:
						//Callback function goes here
						callback_func(result_object);
						break;
					case STATUS_BAD_REQUEST:
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
	return false;
}

