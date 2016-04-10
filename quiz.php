<?php
//GENERALLY SPEAKING, THIS SYSTEM WOULD BE MUCH MUCH BETTER FOR MATHS WHERE MULTIPLE CHOICE QUESTIONS CAN BE GOOD, WHEREAS IN ENGLISH YOU TYPICALLY WANT TO JUDGE QUALITY OF ANSWER RATHER THAN TRUE OR FALSE.
session_start();
$user = array("LOGGED_IN" => false);
$auth = "";
$added = "";
$error = "";
$action = 'action = "quiz.php"';

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
	die('<html>
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
								<li><a href ="index.php">Log In</a></li>
							</ul>
						</div>
					</div>
				</nav>
				<div class ="container">
					<div class ="jumbotron">
					<!--it\'s still in columns at medium screen size and it uses 6 of the 12 grid boxes-->
						<h1>Sorry!</h1>
						<p>You do not currently have adequate permissions to view this page.
					</div>
					<div class="alert alert-danger" role="alert">
						<strong>Error</strong>: This page is restricted to <strong>logged in</strong> users.
					</div>
					If you have already made an account please login.
					<br>Otherwise you can make an account now on the login page.
				</div>
			</body>
		</html>');
}
//get book data for dropdown menu
$bookdata = mysqli_query($connectsql,"SELECT * FROM book");
while($bookinfo = mysqli_fetch_array($bookdata)){
//if needed uncomment
	$bookid[] = $bookinfo['id'];
	$booktitle[] = $bookinfo['title'];
//	$bookauthor[] = $bookinfo['author'];
}
//alphabetical sorting
//prepairing names for html
//assigning the correct id's to the books
//do $bookid['To Kill a Mockingbird'] to return it's id in the db in this case '14'
for($i = 0; $i<count($booktitle);$i++){	
	$bookid[$booktitle[$i]] = $bookid[$i];
//look up the other way id => name
//that didn't work but it's not needed IIRC
//	$booktitle[$bookid[$i]] = $booktitle[$i];
}

sort($booktitle);
for($i = 0; $i<count($booktitle);$i++){
	$printablebooktitle[] = "<option>".$booktitle[$i]."</option>";
}
//finalising names for html
$booktitles =  implode("",$printablebooktitle);
//else there are no books in the db
$togglebook = '<div class="form-group">
					<label class="col-sm-2 control-label">Book</label>
					<div class="col-sm-10">
						<select name="BOOK" '.$booknameinput.' class="form-control">
						'.$booktitles.'
						</select>
					</div>
				</div>';
				
//no of questions for each book
//SELECT bookid, COUNT(1) AS count FROM questions GROUP BY bookid
//$test = mysqli_query($connectsql,"SELECT * FROM question WHERE bookid = '$bookid'");
//so bookid is returned as ['id'] = '14' and the count for that book is ['count'] = 3
$data = mysqli_query($connectsql,"SELECT bookid AS id, COUNT(1) AS count FROM question GROUP BY bookid");
while($info = mysqli_fetch_array($data)){
	$questioncount[$info['id']] = $info['count'];
}
//finalising number of questions for each book into html
//LOOK HERE $questionnos = implode("",$printablequestionno);

//getting no of questions still going to have to confirm number is valid serverside, don't trust this
/*$questiondata = mysqli_query($connectsql,"SELECT * FROM question");
while($questioninfo = mysqli_fetch_array($questiondata)){
	$questionid[] = $questioninfo['id'];
	// a list of all book ids with repeats
	$questionbookid[] = $questioninfo['bookid'];
	$questiontext[] = $questioninfo['text'];
	$questioncorrect[] = $questioninfo['correct'];
	$questionother1[] = $questioninfo['other1'];
	$questionother2[] = $questioninfo['other2'];
	$questionother3[] = $questioninfo['other3'];
}

//for($i = 0; $i<count($questionid);$i++){
	//$questionbookid['1'] = '15' this shows which book a quesiton is tied to. '1' being the quesiton and '15' the book
//	$questionbookidlink[$questionid[$i]] = $questionbookid[$i];
//	print "Ignore<br>";
//	print $questionbookidlink[$questionid[$i]];
//	print "Ignore<br>";
	//we can already convert bookid to book title with $bookid['name'] = id but can't do the reverse
	//added that so now is $booktitle['15'] = 'Romeo and Juilet'
	//all in all $questionbookid['1'] //the selected question // => 15 $booktitle['15'] => 'Romeo and juilet'
	// $booktitle[$questionbookid[$id]] => the name of the book for the question
//	$questionid[$questionbookid[$i]] = $questionid[$i];
//print "IGNORE OTHER SHIT<br>";
//	print var_dump($questionid);
	//YOU WRE HERE MIGHTY NOT WORK
}
//list of book ids
for($i = 0; $i<count($questionbookidlink);$i++){
	if($question$bookid[$i])
}
if(isset($_POST['BOOK'])){
	if($booktitle[$_POST['BOOK']]){
		$selectedbookid = $booktitle[$_POST['BOOK']];
		//basicly we now want an array with the questionids that match the bookid
		//$selectedbookquestions = $book;
	}
	else{
		$error = "Invalid book title";
	}
// confirms logged in as student
}*/
//if book is sent
//print var_dump($_POST);
if(isset($_POST['BOOK'])){
//	if($_POST['AMOUNT'] = '--'){
		$booknameinput = 'id="disabledTextInput"';
		$questionnumberinput = '';
		$action = 'action = "question.php"';
		$backbutton = '<a href="" class="btn btn-default" role="button">Back</a>';
		$nextbutton = 'value="Begin"';
		//no longer vulnerable to xss
		$chosenbook = str_replace("<", "&lt;", $_POST['BOOK']);
		$chosenbook = str_replace(">", "&gt;", $_POST['BOOK']);
		$togglebook = '	<fieldset disabled>
							<div class="form-group">
								<label class="col-sm-2 control-label">Book</label>
								<div class="col-sm-10">
									<select name="BOOK" '.$booknameinput.' class="form-control">
										<option>'.$chosenbook.'</option>
									</select>
								</div>
							</div>
						</fieldset>';
		//GET BOOK ID 
		$bookidtoload = $bookid[$_POST['BOOK']];
		$_SESSION['BOOK'] = $bookidtoload;
//		$chosenquestionno = $questioncount[$bookidtoload];
		/*<div class="form-group">
			<label class="col-sm-2 control-label">Book</label>
			<div class="col-sm-10">
				<select name="BOOK" '.$booknameinput.' class="form-control">
				'.$booktitles.'
				</select>
			</div>
		</div>*/
		
		//isset will notice if there are any questions or not for that book
		if(isset($questioncount[$bookidtoload])){
			//potentially not most optimal solution, easy way to remember max question no for book
			$_SESSION['MAXAMOUNT'] = $questioncount[$bookidtoload];
			for($i=0;$i<$questioncount[$bookidtoload];$i++){
				//has to be plus 1 because 0- questions isn't valid
				$printablequestionno[] = "<option>".($i+1)."</option>";
			}
			$questionnos = implode("",$printablequestionno);
		}
		else{
			$questionnos = "<option>0</option>";
		}
		$togglequestion = '	<div class="form-group">
						<label class="col-sm-2 control-label">Amount</label>
						<div class="col-sm-10">
							<select name="AMOUNT" '.$questionnumberinput.' class="form-control">
								'.$questionnos.'
							</select>
						</div>
					</div>';
/*	}
	print "<p>";
	print var_dump($_POST);
	print "<br>YOU HAVE PRESSED BEGIN";
	print "<p>";
*/
}
//to get here you have to have gone through the thing above to put into $session and have submitted an amount
/*elseif(isset($_SESSION['BOOK']) && isset($_POST['AMOUNT'])){
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
	print var_dump($rnq);
	$error = '<div class="alert alert-danger" role="alert">
		Book has been set, questions beign now. ID = '.$bookidforsql.' QUESTION NUM = '.$_SESSION["AMOUNT"].'</div>';
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
	$added[] = '<div class="alert alert-success" role="alert">';
	for($i=0;$i<count($rnq);$i++){
		$added[] = 'ID'.$i.' = '.$rnq[$i].', ';
		print $printablequestion[$rnq[$i]]['text'];
		print $printablequestion[$rnq[$i]]['correct'];
		print $printablequestion[$rnq[$i]]['other1'];
		if($printablequestion[$rnq[$i]]['other2'] != ""){
			print $printablequestion[$rnq[$i]]['other2'];
		}
		else{
			print "test_blank_answer_ignore";
		}
		if($printablequestion[$rnq[$i]]['other3'] != ""){
			print $printablequestion[$rnq[$i]]['other3'];
		}
		else{
			print "test_blank_answer_ignore";
		}
	}
	$added[] = '</div>';
	$added = implode($added);
	unset($_SESSION['AMOUNT']);
	unset($_SESSION['BOOK']);
	unset($_SESSION['MAXAMOUNT']);
}*/
	
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
						<h1>Quiz</h1>
						<div class ="row">
						<!--it\'s still in columns at medium screen size and it uses 6 of the 12 grid boxes-->
							<div class ="col-md-6">
								<h3>Book Selection</h3>
								<p>Please select which book you would like to answer questions from followed by the amount of Questions you wish to complete.
								<form class ="form-horizontal" '.$action.' method="post">
									'.$togglebook.'
									'.$togglequestion.'
									'.$backbutton.'
									<input class ="btn btn-default navbar-right" name="selected" type="submit" '.$nextbutton.'/>
								</form>
							</div>
							<div class ="col-md-6">
							</div>
						</div>
					</div>
				</body>
			</html>';
}

?>