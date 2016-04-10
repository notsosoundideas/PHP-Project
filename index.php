<?php
//EMAILS CAN BE REUSED, FIX THAT
//FIX LOGIN/LOGOUT BOXES etc
session_start();
$user = array("LOGGED_IN" => false);
$added = "";
$error = "";
//soo, The point of this php doc is
//If there is only a username/pass in $_POST try to login, else add details to dba_close

//specify info about the database for the connection to use
$db_name = 'test';
$db_user = 'root';
//currently pointless add pass later
$db_pass = '';
$db_host = 'localhost';
//connection to mysql
$connectsql = mysqli_connect($db_host, $db_user, $db_pass) or die("Database connection error"); 
//connect to the correct db
$connectdb = mysqli_select_db($connectsql, $db_name) or die("Database selection failure");
//prints that a connection has been established
mysqli_query($connectsql,'SET character_set_results="utf8"') or die("Database result type setting failure");
mysqli_set_charset ($connectsql,"utf8");

// debugging connection 
//echo "Connected to ".$db_name." as ".$db_user;

$ip = $_SERVER['REMOTE_ADDR'];
$user_agent =  mysqli_real_escape_string($connectsql, $_SERVER['HTTP_USER_AGENT']);

function genssid(){
	$chars = str_split("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
	$ssid = array();
	for($i = 0; $i < 20; $i++){
		$num = mt_rand(0, count($chars) - 1);
		$ssid[] = $chars[$num];
	}
	$ssid = implode("",$ssid);
	return $ssid;
}

function sha256($ssid){
	for($i = 0; $i < 10000; $i++){
		$ssid = hash("sha256",$ssid);
	}
	return $ssid;
}
/*checks that something was actually posted to the form, works based on the logic that if
'FIRST' was submitted it came from the main.php page and so the other values would be posted too*/
if(isset($_POST['action'])){
	switch($_POST["action"]){
		case "Login":
			if(isset($_POST["USER"])&& isset($_POST["PASS"])){
				//echo "login attempt<br>";
				if($_POST["USER"] && $_POST["PASS"]){
					$_SESSION["USER"] = $_POST["USER"];
					//still storing unhashed pass in session here might wanna change that in a minute... maybe
					$_SESSION["PASS"] = $_POST["PASS"];
				}
				else{
					//one or more parameters are blank
					$error = '<div class="alert alert-danger" role="alert">
					<strong>Error</strong>: Missing one or more parameters.
					</div>';
				}
			}
			else{
				//one or more parameters were not included
				$error = '<div class="alert alert-danger" role="alert">
				<strong>Error</strong>: Missing one or more parameters.
				</div>';
			}
		break;
		case "Register":
			if(isset($_POST["USER"])&& isset($_POST["PASS"]) && isset($_POST["FIRST"])&& isset($_POST["LAST"]) && isset($_POST["EMAIL"])){
				//escaping for SQL prevents injection
				$first = mysqli_real_escape_string($connectsql, $_POST['FIRST']);
				$last =  mysqli_real_escape_string($connectsql, $_POST['LAST']);
				$un =  mysqli_real_escape_string($connectsql, $_POST['USER']);
				$pass =  mysqli_real_escape_string($connectsql, $_POST['PASS']);
				$email =  mysqli_real_escape_string($connectsql, $_POST['EMAIL']);
				//validation first removes spaces
				$first = trim($first);
				$last = trim($last);
				$un = trim($un);
				$email = trim($email);
				if(strlen($first) > 0 && strlen($last) > 0 && strlen($un) > 0 && strlen($pass) > 0 && strlen($email) > 0){
					//might wanna check if email can have <
					if(substr($email, strlen($email)-9) == "@dhsb.org" && filter_var($email, FILTER_VALIDATE_EMAIL)){
						if(is_numeric(substr($email,0,2))){
//							$user['ACCESS'] = 0;
							$access = NULL;
						}
						else{
							$access = 1;
						}
						$first = str_replace("<", "&lt;", $first);
						$first = str_replace(">", "&gt;", $first);
						$last = str_replace("<", "&lt;", $last);
						$last = str_replace(">", "&gt;", $last);
						$un = str_replace("<", "&lt;", $un);
						$un = str_replace(">", "&gt;", $un);
						$email = str_replace("<", "&lt;", $email);
						$email = str_replace(">", "&gt;", $email);
						//password hash generated + random salt
						$pass = password_hash($pass, PASSWORD_BCRYPT);
						//might shove this elsewhere
						//basicly check the email ends with @dhsb.org
						//to test check is name@gmail.com@dhsb.org is accepted
						$data = mysqli_query($connectsql,"SELECT * FROM user WHERE username = '$un'");
						$data2 = mysqli_query($connectsql,"SELECT * FROM user WHERE email = '$email'");
						if($info = mysqli_fetch_array($data)){
							$error = '<div class="alert alert-danger" role="alert">
							<strong>Error</strong>: This <strong>username</strong> is already in use.
							</div>';
						}
						elseif($info = mysqli_fetch_array($data2)){
							$error = '<div class="alert alert-danger" role="alert">
							<strong>Error</strong>: This <strong>email address </strong> is already in use.
							</div>';
						}
						else{
							$sql = "
							INSERT INTO user
							(datetime, ip, user_agent, username, password, email, firstname, lastname, teacher)
							VALUES
							(NOW(), '$ip', '$user_agent', '$un', '$pass', '$email', '$first', '$last', '$access');
							";
							mysqli_query($connectsql,$sql);
							$added = '<div class="alert alert-success" role="alert">
							Thank you for registering</div>';
						}
					}
					else{
						$error = '<div class="alert alert-danger" role="alert">
						<strong>Error</strong>: Invalid email addresses, only <strong>@dhsb.org</strong> adresses may register.
						</div>';
					}
				}
				else{
					//one or more parameters are blank 
					$error = '<div class="alert alert-danger" role="alert">
					<strong>Error</strong>: Missing one or more parameters.
					</div>';
				}
				
			}	
			else{
				//one or more parameters not sent
				$error = '<div class="alert alert-danger" role="alert">
				<strong>Error</strong>: Missing one or more parameters.
				</div>';
			}
		break;
		case "Logout":
			unset($_SESSION["USER"]);
			unset($_SESSION["PASS"]);
		break;
	}
}
//authentication section
if(isset($_SESSION["USER"]) && isset($_SESSION["PASS"])){
	if(($_SESSION["USER"]) && ($_SESSION["PASS"])){
		//prevents SQL injection
		$un = mysqli_real_escape_string($connectsql, $_SESSION['USER']);
		$pw = mysqli_real_escape_string($connectsql, $_SESSION['PASS']);
		$data = mysqli_query($connectsql,"SELECT * FROM user WHERE username = '$un'");
		//I dunno if this is problematic, You type the wrong pass and the correct pass is brought out of the db for comparision, seems okay though?
		//If anything is returned $un exists in db it's equal to $info
		if($info = mysqli_fetch_array($data)){
			if(password_verify($pw, $info['password'])){
				$user['USERNAME'] = $info['username'];
				$user['FIRST'] = $info['firstname'];
				$user['LAST'] = $info['lastname'];
				$user['AUTH'] = $info['teacher'];
				$user['LOGGED_IN'] = true;
			}
			else{
				$error = '<div class="alert alert-danger" role="alert">
				<strong>Error</strong>: Invalid username and password combination.
				</div>';
			}
		}
		else{
// this might have fixed the persistant problem now
			$error = '<div class="alert alert-danger" role="alert">
			<strong>Error</strong>: Invalid username and password combination.
			</div>';
		}
	}
	else{
		$error = '<div class="alert alert-danger" role="alert">
		<strong>Error</strong>: Blank username and password combination.
		</div>';
	}
}
if(!$user['LOGGED_IN']){
//now incorrect usernames and passwords aren't stored in $_SESSION for the next attempt
//maybe fixing the persistant 'bad username/password error?
//	if(isset($ apparently this isn't needed... for some reason. honestly expected this to cause errors if they aren't set to begin with.
	unset($_SESSION['USER']);
	unset($_SESSION['PASS']);
	//deals with not-logged-in-people
	print '	<html>
				<head>
					<meta name="viewport" content="width=device-width, initial-scale=1">
					<title>Main page</title>
					<link href ="css/bootstrap.min.css" rel ="stylesheet">
				</head>
				<body>
				<!--JQUERY MUST LOAD BEFORE BOOTSTRAP AT ALL TIMES-->
					<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
					<script src ="js/bootstrap.min.js"></script>
					<nav class="navbar navbar-inverse navbar-static-top">
						<div class ="container-fluid">
							<div class="navbar-header">
								<a href ="index.php" class = "navbar-brand">ReadingHelper</a>
								<!--button that only appears on small screens to show the navHeaderCollapse div-->
								<button type ="button" class ="navbar-toggle" data-toggle="collapse" data-target=".navHeaderCollapse">
								<!-- the button is 3 bars on top of each other-->	
									<span class ="icon-bar"></span>
									<span class ="icon-bar"></span>
									<span class ="icon-bar"></span>
								</button>
							</div>
							<div class ="collapse navbar-collapse navHeaderCollapse">
								<ul class ="nav navbar-nav">
								<!--home goes to index.php to ensure you have to logged in--> 
									<li><a href ="index.php">Home</a></li>
									<li><a href ="input.php">Input</a></li>
									<li><a href ="quiz.php">Quiz</a></li>
									<li><a href ="help.html">Help</a></li>
								</ul>
								<ul class = "nav navbar-nav navbar-right">
									<li class ="active"><a href ="">Log In</a></li>
								</ul>
							</div>
						</div>
					</nav>
					<div class ="container">
					'.$added.$error.'
						<h1>Please Login or Register below.</h1>
						<div class ="row">
						<!--it\'s still in columns at medium screen size and it uses 6 of the 12 grid boxes-->
							<div class ="col-md-6">
								<h3>Login</h3>
								<p>If you have already made an account, Sign in here.
								<!--form-horizontal ensures that each of the form-group divs is counted as a row of a grid-->
								<form class ="form-horizontal" action="index.php" method="post">
									<div class="form-group">
									<!--the label takes up 2 of the 12 columns only seperating label from text box on small screens-->
										<label class="col-sm-2 control-label">Username</label>
										<!--the text box takes up the remaining 10 of the 12 columns-->
										<div class="col-sm-10">
											<input type="text" name="USER" class="form-control" placeholder="Username">
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Password</label>
										<div class="col-sm-10">
											<input type="password" name="PASS" class="form-control" placeholder="Password">
										</div>
									</div>
								<input class ="btn btn-default" name="action" type="submit" value="Login"/>
								</form>
							</div>
							<div class ="col-md-6">
								<h3>Register</h3>
								<p>If you haven\'t already, Make an account now.
								<form class ="form-horizontal" action="index.php" method="post">
								<div class="form-group">
									<label class="col-sm-2 control-label">Username</label>
									<div class="col-sm-10">
										<input type="text" name="USER" class="form-control" placeholder="Username">
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">Email</label>
									<div class="col-sm-10">
										<input type="text" name="EMAIL" class="form-control" placeholder="example@dhsb.org">
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">Firstname</label>
									<div class="col-sm-10">
										<input type="text" name="FIRST" class="form-control" placeholder="e.g John">
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">Lastname</label>
									<div class="col-sm-10">
										<input type="text" name="LAST" class="form-control" placeholder="e.g Smith">
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">Password</label>
									<div class="col-sm-10">
										<input type="password" name="PASS" class="form-control" placeholder="Password">
									</div>
								</div>
								<input class ="btn btn-default" name="action" type="submit" value="Register"/>
								</form>
							</div>
						</div>
						<a href="">click here</a> to reset your password.
					</div>
				</body>
			</html>';
}
else{
	//deals with logged in people
	if($user['AUTH']){
		$added = '<div class="alert alert-success" role="alert"> This account has <strong>teacher</strong> permissions.</div>';
	}
	$userfirstletter = strtoupper($user['FIRST'][0]);
	$upperlastname = ucfirst($user['LAST']);
	print '	<html>
				<head>
					<meta name="viewport" content="width=device-width, initial-scale=1">
					<title>Main page</title>
					<link href ="css/bootstrap.min.css" rel ="stylesheet">
				</head>
				<body>
				<!--JQUERY MUST LOAD BEFORE BOOTSTRAP AT ALL TIMES-->
					<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
					<script src ="js/bootstrap.min.js"></script>
					<nav class="navbar navbar-inverse navbar-static-top">
						<div class ="container-fluid">
							<div class="navbar-header">
								<a href ="index.php" class = "navbar-brand">ReadingHelper</a>
								<!--button that only appears on small screens to show the navHeaderCollapse div-->
								<button type ="button" class ="navbar-toggle" data-toggle="collapse" data-target=".navHeaderCollapse">
								<!-- the button is 3 bars on top of each other-->	
									<span class ="icon-bar"></span>
									<span class ="icon-bar"></span>
									<span class ="icon-bar"></span>
								</button>
							</div>
							<div class ="collapse navbar-collapse navHeaderCollapse">
								<ul class ="nav navbar-nav">
									<li class ="active"><a href ="index.php">Home</a></li>
									<li><a href ="input.php">Input</a></li>
									<li><a href ="quiz.php">Quiz</a></li>
									<li><a href ="help.html">Help</a></li>
								</ul>
								<ul class = "nav navbar-nav navbar-right">
			<!-- page only acessable when logged in-->
			<!--<form method=post><input name="action" type="submit" value="Logout"/></form>-->
			<!--because it\'s not a button but a link to a script it styles as a link, which is great!-->
									<li><a href="logout.php">Log out</a></li>
								</ul>
							</div>
						</div>
					</nav>
					<div class ="container">
					'.$added.$error.'
						<h1>Welcome, '.$userfirstletter.'.'.$upperlastname.'</h1>
						<div class ="row">
						<!--it\'s still in columns at medium screen size and it uses 6 of the 12 grid boxes-->
							<div class ="col-md-6">
								<h3>Work</h3>
								<p>Current Work is set below.
							</div>
							<div class ="col-md-6">
								<h3>Todo</h3>
								<p>Words go here
							</div>
						</div>
					</div>
				</body>
			</html>';
}
/* TODO
worth nothing "Bad username/password combo" is persistant untill next login for some reason **might be fixed**
0. case sensitivity
1. SALT&HASH thing - might be done, AFAICT
2. **CurrentFocus** store sessionID in cookie not username pass 
2.1 store a token in a cookie. so there is no session to be changed by a user
2.2 the token would expire after a set amount of time.
2.3 definately don't have a sessionID in the url
3. Make users have decent passwords i.e strlen(pass)>8
4. various permission levels i.e 1= student 2= teacher
5. email collection/excluding non-dhsb.org email addresses 
new db table, assign random string to user, check if they have confirmed it, send that to user, 
they click on a link that's like it was generated by get, so "localhost?confirmation='$randomstring'"
then you do $_GET['confirmation']
*/