//Display Controller

/*******************************Constructors******************************/

//Constructor for Registraction api request object
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

//Constructor for Login api request object
function packageLoginAPIRequest(username, password) {
	var request_payload = {
		email : username,
		password : password
	}
	return request_payload;
}

//Add more constructors.



/*****************************Logic Functions******************************/

//Initializes the page to the login screen
function initialize() {
	$('body > :not(#login)').hide();
	$('.error').hide();
	$('#login').appendTo('body');	
}


//Changes display to specified page
function changeDisplay(page_to_display) {
	$('#'+page_to_display).show();
	$('body > :not(#'+page_to_display+')').hide();
	$('#'+page_to_display).appendTo('body');
}



/***************************Validation Functions****************************/

//Login validation, packaging, and sending as request to api
//TODO(SLatychev): Add more rigorous validation checking
function loginValidation() {
	$('.error').hide();

	//Validation and field variables
	var validated = true;
	var email = $("input#login_email").val();
	var password = $("input#login_password").val();

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
	
	//Package and send request
	var payloadString = JSON.stringify(packageLoginAPIRequest(email, password));
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
};



/***************************Control Functions****************************/

function registrationValidated(response_object) {
	alert("Account has been registered.\nThank You!");
	changeDisplay('login');
	return false;
}


function loginValidated(response_object) {
	alert("Logging you in");
	changeDisplay('main');
	return false;
}


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
				*/
				
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
						//Display info
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

