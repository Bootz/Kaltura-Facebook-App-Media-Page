<?php
//This is the front page for the admin console which is used to set up the Page Tab's gallery

//Includes the config file that contains all the Facebook App info
require_once('config.php');
$page = @$_REQUEST['fb_page_id'];
//Prevents unauthorized users from accessing the admin console for the Page Tab
//Details on appropriately accessing the admin console for the Page Tab can be found in the README
if($page == '') {
	echo 'You may only visit this admin console from your Facebook Page'.'</br>';
	die();
}
else if($_SERVER['HTTP_REFERER'] != 'https://www.facebook.com/' && $_SERVER['HTTP_REFERER'] != 'http://www.facebook.com/') {
	echo 'You may only visit this admin console from your Facebook Page'.'</br>';
	die();
}
$pageURL = json_decode(file_get_contents('https://graph.facebook.com/'.$page))->link.'?v=app_'.APP_ID;
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Media Page</title>
	<!-- Style Includes -->
	<link href="appStyle.css" media="screen" rel="stylesheet"/>
	<link href="lib/chosen/chosen.css" rel="stylesheet"/>
	<link href="lib/select2/select2.css" rel="stylesheet"/>
	<!-- Script Includes -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
	<script src="lib/chosen/chosen.jquery.js"></script>
	<script src="lib/select2/select2.js"></script>
	<!-- Page Scripts -->
	<script>
		//Keeps track of your Kaltura session when you log in
		var kalturaSession = "";
		//Keeps track of the Partner ID for your session
		var partnerId = 0;
		//The id of your Facebook Page
		var page = <?php echo $page; ?>;
		//The URL for the Page Tab on your Facebook Page
		var pageURL = '<?php echo $pageURL; ?>';

		//Validates your email when logging in
		function validEmail(input) {
			var filter = /^[a-zA-Z0-9]+[a-zA-Z0-9_.-]+[a-zA-Z0-9_-]+@[a-zA-Z0-9]+[a-zA-Z0-9.-]+[a-zA-Z0-9]+.[a-z]{2,4}$/;
			if(!filter.test(input.value)) {
				input.setCustomValidity("Invalid email");
				return false;
			}
			else {
				input.setCustomValidity('');
				return true;
			}
		}

		//Validates your password when logging in
		function validPassword(input) {
			if(input.value == '') {
				input.setCustomValidity("Please enter a password");
				return false;
			}
			else {
				input.setCustomValidity('');
				return true;
			}
		}

		//Generates a Kaltura Session for logging into the admin console
		function loginSubmit() {
			$('#loginButton').hide();
			$('#loginLoader').show();
			$.ajax({
				type: "POST",
				url: "getSession.php",
				data: {email: $('#email').val(), partnerId: 0, password: $('#password').val()}
			}).done(function(msg) {
				$('#loginLoader').hide();
				if(msg == "loginfail") {
					$('#loginButton').show();
					alert("Invalid username/password");
				}
				else {
					$('body').blur();
					response = $.parseJSON(msg);
					if(response[0] == 1) {
						kalturaSession = response[1];
						partnerId = response[2];
						showSetup();
					}
					else {
						partnerLogin(response);
					}
				}
			});
		}

		//This lets the user select a Partner ID to log into
		//This is only displayed if there is more than one partner on an account
		function partnerLogin(response) {
			$('#email').attr("readonly", "readonly");
			$('#password').attr("readonly", "readonly");
			$.ajax({
				type: "POST",
				url: "partnerSelect.php",
				data: {response: response}
			}).done(function(msg) {
				$('#partnerDiv').html(msg);
				$('#email').keyup(function(event) {
					if(event.which == 13)
						partnerSubmit();
				});
				$('#password').keyup(function(event) {
					if(event.which == 13)
						partnerSubmit();
				});
				jQuery('.czntags').chosen({search_contains: true});
			});
		}

		//Generates a Kaltura session for the specific Partner ID
		function partnerSubmit() {
			$('#sumbitPartner').hide();
			$('#loginLoader').show();
			$.ajax({
				type: "POST",
				url: "getSession.php",
				data: {email: $('#email').val(), password: $('#password').val(), partnerId: $('#partnerChoice').val()}
			}).done(function(msg) {
				$('#loginLoader').show();
				if(msg == "loginfail") {
					alert("Invalid username/password");
					$('#submitPartner').show();
				}
				else if(msg == 'idfail') {
					alert("Invalid Partner ID");
					$('#submitPartner').show();
				}
				else {
					response = $.parseJSON(msg);
					kalturaSession = response[1];
					partnerId = $('#partnerChoice').val();
					showSetup();
				}
			});
		}

		//Once the user is logged in, let them set their gallery up
		function showSetup() {
			$.ajax({
				type: 'POST',
				url: 'showSetup.php',
				data: {session: kalturaSession, partnerId: partnerId}
			}).done(function(msg) {
				$('#setupDiv').html(msg);
				$('#loginDiv').slideUp();
				jQuery('.czntags').chosen({search_contains: true});
			});
		}
	</script>
</head>
<body style="height: 500px;">
	<div id="parent">
		<div id='setup'>
			<div id='title'><h1>Kaltura Media Page for Pages</h1></div>
			<div id='loginDiv'>
				<div id='signup' class='section'>
					Kaltura Account Credentials (sign up at: <a href='http://corp.kaltura.com/free-trial' target='_blank'>http://corp.kaltura.com/free-trial</a>)
				</div>
				<div id='login'>
					<form method='post' id='loginForm' action='javascript:loginSubmit();' class='box'>
						<div id='loginFields'>
							<div id='emailDiv' class='loginField'>
								<span>KMC Email: </span><input type='text' id='email' oninput='validEmail(this)' autofocus='autofocus' required>
							</div>
							<div id='passwordDiv' class='loginField'>
								<span style='margin-right: 13px;'>Password: </span><input type='password' id='password' oninput='validPassword(this)' required>
							</div>
							<div id='partnerDiv'></div>
							<div id='buttonDiv' class='loginField'>
								<input type='submit' class='btnLogin' value='Login' id='loginButton'>
								<img src='lib/loginLoader.gif' id='loginLoader' style='display: none;'>
							</div>
						</div>
					</form>
				</div>
			</div>
			<div id='setupDiv'></div>
		</div>
	</div>
</body>
</html>