//Registration Controller



//Constructor for Registraction api request object
function RegistrationAPIRequest(email, phone, password, confirm) {
	this.email = email;
	this.phone = phone;
	this.password1 = password;
	this.password2 = confirm;
}

//Constructor for Login api request object
function LoginAPIRequest(username, password) {
	this.email = username;
	this.password = password;
}

//Add more constructors.


//Registration validation, packaging, and sending as request to api.
//Add more rigorous validation for inputs.
$(function(){
	$('.error').hide();
	$(".button").click(function(){
		//validate and process from here
		$('.error').hide();
		
		//Validate if email was given
		var email = $("input#email").val();
		if(email == ""){
			$("label#email_error").show();
			$("input#email").focus();
			return false;
		}
		
		//Validate if phone was given then does it only contain allowed characters.
		var phone = $("input#phone").val();
		//Phone is not a required field

		//Validate if password was given
		var password = $("input#password").val();
		if(password == ""){
			$("label#password_error").show();
			$("input#password").focus();
			return false;
		}

		//Validate if password confirmation was given and matches
		var confirm = $("input#confirm").val();
		if(confirm != password || confirm == ""){
			$("label#confirm_error").show();
			$("input#confirm").focus();
			return false;
		}

		var dataString = JSON.stringify(RegistrationAPIRequest(email, phone, password, confirm));
		//alert (dataString); return false;
		$.ajax({
			type: "POST",
			url: "api/user.php", 
			data: dataString,
			success: function() {
				//return user to login page
			});
		}
	});
	return false;
});
