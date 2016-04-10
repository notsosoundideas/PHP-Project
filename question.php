<?php
session_start();
$user = array("LOGGED_IN" => false);
$auth = "";
$added = "";
$error = "";

$backbutton = "";
$nextbutton = 'value="Next"';
$booknameinput = '';
$questionnumberinput = 'id="disabledTextInput"';
$questionnos = "";
$togglequestion = '<fieldset disabled>
					<div class="form-group">
						<label class="col-sm-2 control-label">Amount</label>
						<div class="col-sm-10">
							<select name="AMOUNT" id="disabledSelect" class="form-control">
								<option>--</option>
							</select>
						</div>
					</div>
				</fieldset>';
$togglebook = "";
$db_name = 'test';
//currently pointless add pass later
$db_user = 'root';
$db_pass = '';
$db_host = 'localhost';
//connection to mysql
$connectsql = mysqli_connect($db_host, $db_user, $db_pass) or die("Database connection error"); 
//connect to the correct db
$connectdb = mysqli_select_db($connectsql, $db_name) or die("Database selection failure");
//prints that a connection has been established
mysqli_query($connectsql,'SET character_set_results="utf8"') or die("Database result type setting failure");
mysqli_set_charset ($connectsql,"utf8");

//auth section, cofirm user is a student
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
				if($user['AUTH']){
					$auth = "teacher";
				}
				else{
					$auth = "student";
				}
			}
		}
	}
}
if(!$user['LOGGED_IN']){
	die(header("Location: quiz.php"));
}

if(isset($_SESSION['BOOK']) && isset($_POST['AMOUNT'])){
	if(!$_POST['AMOUNT']){
		unset($_SESSION['BOOK']);
		//doesn't throw up an error but it does send you back so it should be fairly clear
		die(header("Location: quiz.php"));
	}
	$bookidforsql = $_SESSION['BOOK'];
	$_SESSION['AMOUNT'] = $_POST['AMOUNT'];
	//pick chosen number of random question numbers 
//THIS IS GOING TO SERVE AS PROGRESSION, REMOVE 1 FROM ARRAY EVERYTIME QUESTION COMPLETE	
//PREVENT REPEAT QUESTIONS
	$rnq = [];
	while(count($rnq) < $_SESSION['AMOUNT']){
		//rand can return the max number entered so it's -1 
		$temp = rand(0,$_SESSION['MAXAMOUNT']-1);
		if(!in_array($temp, $rnq)){
			$rnq[] = $temp;
		}
		
		
	}
	/*$error = '<div class="alert alert-danger" role="alert">
		Book has been set, questions beign now. ID = '.$bookidforsql.' QUESTION NUM = '.$_SESSION["AMOUNT"].'</div>';*/
	//mainthing
	$questiondata = mysqli_query($connectsql,"SELECT * FROM question WHERE bookid = '$bookidforsql'");
	$count = 0;
	while($question = mysqli_fetch_array($questiondata)){
		$printablequestion[$count]['text'] = $question['text'];
		$printablequestion[$count]['correct'] = $question['correct'];
		$printablequestion[$count]['other1'] = $question['other1'];
		if(isset($question['other2'])){
			$printablequestion[$count]['other2'] = $question['other2'];
		}
		else{
			$printablequestion[$count]['other2'] = "";
		}
		if(isset($question['other3'])){
			$printablequestion[$count]['other3'] = $question['other3'];
		}
		else{
			$printablequestion[$count]['other3'] = "";
		}
		$count += 1;
	}
	for($i=0;$i<count($rnq);$i++){
		$text[$i] = $printablequestion[$rnq[$i]]['text'];
		//addind this part to the  html
		if(isset($questionpart)){
			unset($questionpart);
		}
		$questionpart[] = '	<h3>'.$text[$i].'</h3>
							<form class ="form-horizontal" action="question.php" method="post">
								<div class="btn-group" data-toggle="buttons">
									<p>
									';
		
		$correct[$i] = $printablequestion[$rnq[$i]]['correct'];
		//adding $correct to html
		$questionpart[] = '	<label class="btn btn-default">
								<input type="radio" name="options" id="option1" autocomplete="off"> '.$correct[$i].'
							</label>
							<p>
							';
		$other1[$i] = $printablequestion[$rnq[$i]]['other1'];
		//adding other to $correct html
		$questionpart[] = '	<label class="btn btn-default">
								<input type="radio" name="options" id="option2" autocomplete="off"> '.$other1[$i].'
							</label>
							<p>
							';								
		if($printablequestion[$rnq[$i]]['other2'] != ""){
			$other2[$i] = $printablequestion[$rnq[$i]]['other2'];
			//only adds to html if it exists
			$questionpart[] = '	<label class="btn btn-default">
								<input type="radio" name="options" id="option3" autocomplete="off"> '.$other2[$i].'
							</label>
							<p>
							';
		}
		else{
			$other2[$i] = "test_blank_answer_ignore";
		}
		if($printablequestion[$rnq[$i]]['other3'] != ""){
			$other3[$i] = $printablequestion[$rnq[$i]]['other3'];
			//addingin it to html only if exisitsing
			$questionpart[] = '	<label class="btn btn-default">
								<input type="radio" name="options" id="option4" autocomplete="off"> '.$other3[$i].'
							</label>
							<p>
							';
		}
		else{
			$other3[$i] = "test_blank_answer_ignore";
		}
		$questionpart[] = '</div></form>';
		$structuredquestionpart[$i] = implode($questionpart);
	}
	//okay, so the problem is structuredquestionpart[$i] still contains everything from the first loop iteration when doing the second
	//PRINT AMOUNT NUMBER OF QUESTIONPARTS
//	$structuredquestionpart[] = '<input class ="btn btn-default navbar-left" name="selected" type="submit" value="Next"/>';
	$finalquestion = implode($structuredquestionpart);
	unset($_SESSION['AMOUNT']);
	unset($_SESSION['BOOK']);
	unset($_SESSION['MAXAMOUNT']);
}
else{
	die(header("Location: quiz.php"));
}	
if($auth = "student"){
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
									<li><a href ="index.php">Home</a></li>
									<li><a href ="input.php">Input</a></li>
									<li class ="active"><a href ="">Quiz</a></li>
									<li><a href ="help.html">Help</a></li>
								</ul>
								<ul class = "nav navbar-nav navbar-right">
									<li><a href ="login.html">Log In</a></li>
								</ul>
							</div>
						</div>
					</nav>
					<div class ="container">
					'.$added.$error.'
						<h1>Questions</h1>
						<p>Please complete all the questions then click on the "next" button at the bottom of the page.
						<div class ="row">
						<!--it\'s still in columns at medium screen size and it uses 6 of the 12 grid boxes-->
							<div class ="col-md-6">
							'.$finalquestion.'
							</div>
							<div class ="col-md-6">
							<input class ="btn btn-default navbar-left" name="selected" type="submit" value="Next"/>
							</div>
						</div>
					</div>
				</body>
			</html>';
}

?>