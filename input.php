<?php
session_start();
//Fun facts, use the $added or $error variable to show a stylised error for the user it's called in the html
//added being green, to inform success and error being red to show errors
//PROBABLY A GOOD IDEA TO GIVE ABILITY TO REMOVE BOOKS
//IN RETROSPECT THESE MIGHT NOT ACTUALLY BE NEEDED
//MIGHT BE WORTH ADDING AN 'ADDED BY' SECTION
$user = array("LOGGED_IN" => false);
$pageload = false;
$added = "";
$error = "";

//$change = "";
$sql = "";

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
//if($connectsql){
//	echo "Connected to ".$db_name." as ".$db_user;
//}
//print 'Post is '.var_dump($_POST);
//print 'Session is'.var_dump($_SESSION);
/*debugging POST data
if($_POST){
	print var_dump($_POST);
}
else{
	print 'nah, it\'s empty';
}*/

//auth section, cofirm user is permitted to add to db
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
					$pageload = true;
				}
			}
			//no purpose for else statements as it shouldn't be possible to get those errors anyway
		}
	}
}
if($user['LOGGED_IN']){
	$loginorout = '<li><a href="logout.php">Log out</a></li>';
}
else{
	$loginorout = '<li><a href ="login.html">Log In</a></li>';
}
	if(!$pageload){
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
								<li class ="active"><a href ="">Input</a></li>
								<li><a href ="quiz.php">Quiz</a></li>
								<li><a href ="help.html">Help</a></li>
							</ul>
							<ul class = "nav navbar-nav navbar-right">
								'.$loginorout.'
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
						<strong>Error</strong>: This page is restricted to <strong>teacher</strong> accounts.
					</div>
					If you believe this to be in error,
					<br>Please contact the IT department and provide them the infomation that your account has not been flagged as a Teacher.
				</div>
			</body>
		</html>');
}
//print var_dump($connectsql);
//dropdown menu functionality
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
}
sort($booktitle);
for($i = 0; $i<count($booktitle);$i++){
	$printablebooktitle[] = "<option>".$booktitle[$i]."</option>";
}
//finalising names for html
$booktitles =  implode("",$printablebooktitle);
//else there are no books in the db

/*								<option>To Kill a Mocking Bird</option>
								<option>Romeo and Juilet</option>
								<option>Macbeth</option>
								<option>Of Mice and Men</option>
*/

//Inform user that input was a success, pretty cool actually
if(isset($_POST["bookadd"])){
	if($_POST["BOOKTITLE"]){
		//escaping to avoid sql inject from user inputs
		$bookname = mysqli_real_escape_string($connectsql, $_POST["BOOKTITLE"]);
		$bookname = str_replace("<", "&lt;", $bookname);
		$bookname = str_replace(">", "&gt;", $bookname);
		$authorname = mysqli_real_escape_string($connectsql, $_POST["BOOKAUTHOR"]);
		$authorname = str_replace("<", "&lt;", $authorname);
		$authorname = str_replace(">", "&gt;", $authorname);
//		$change = "book";
		$added = '<div class="alert alert-success" role="alert">
		Successfully added
		<strong> '.$bookname.'</strong>
		to the database.</div>';
//SQL actually adding to db
//		if($_POST){
//	if($change = "book"){
		$sql = "
		INSERT INTO book
		(title, author)
		VALUES
		('$bookname', '$authorname');
		";
		mysqli_query($connectsql,$sql);
	}
	else{
		$error = '<div class="alert alert-danger" role="alert">
		<strong>Error</strong>: Book title is missing. </div>';
	}
}
elseif(isset($_POST["questionadd"])){
//	$change = "question";
	if($_POST["QUESTION"] && $_POST["BOOK"] && $_POST["ANSWER"] && $_POST["OTHER1"]){
		if($bookid[$_POST["BOOK"]]){
			$question = mysqli_real_escape_string($connectsql, $_POST["QUESTION"]);
			$question = str_replace("<", "&lt;", $question);
			$question = str_replace(">", "&gt;", $question);
			$book = mysqli_real_escape_string($connectsql, $_POST["BOOK"]);
			$book  = str_replace("<", "&lt;", $book);
			$book = str_replace(">", "&gt;", $book);
			$answer = mysqli_real_escape_string($connectsql, $_POST["ANSWER"]);
			$answer = str_replace("<", "&lt;", $answer);
			$answer = str_replace(">", "&gt;", $answer);
			$other1 = mysqli_real_escape_string($connectsql, $_POST["OTHER1"]);
			$other1 = str_replace("<", "&lt;", $other1);
			$other1 = str_replace(">", "&gt;", $other1);
			$other2 = mysqli_real_escape_string($connectsql, $_POST["OTHER2"]);
			$other2 = str_replace("<", "&lt;", $other2);
			$other2 = str_replace(">", "&gt;", $other2);
			$other3 = mysqli_real_escape_string($connectsql, $_POST["OTHER3"]);
			$other3 = str_replace("<", "&lt;", $other3);
			$other3 = str_replace(">", "&gt;", $other3);
			$id = $bookid[$book];
			$added = '<div class="alert alert-success" role="alert">
			Successfully added the question <strong> '.$question.'</strong>
			for the book <strong>'.$book.'</strong> 
			to the database.
			</div>';
			$sql = "
			INSERT INTO question
			(bookid, text, correct, other1, other2, other3)
			VALUES
			('$id', '$question','$answer','$other1','$other2','$other3');
			";
			mysqli_query($connectsql,$sql);
			
		}
		else{
//this error should not be possible to ever recieve, unless db is empty
			$error = '<div class="alert alert-danger" role="alert">
		<strong>Error</strong>: Book infomation is invalid. </div>';
		}
	}
	else{
	$error = '<div class="alert alert-danger" role="alert">
		<strong>Error</strong>: Question infomation is missing. </div>';
	}
}
//prints out the entire webpage that the user will see
print '<html>
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
						'.$loginorout.'
					</ul>
				</div>
			</div>
		</nav>
		<div class ="container">
		'.$added.$error.'
			<h1>Enter new questions here.</h1>
			<div class ="row">
			<!--it\'s still in columns at medium screen size and it uses 6 of the 12 grid boxes-->
				<div class ="col-md-6">
					<h3>Enter new Book</h3>
					<p>If you do not see your newly added book in the dropdown menu please refresh the page.<p>Additionly, infomation on the author does not need to be included.
					<form class ="form-horizontal" action="input.php" method="post">
						<div class="form-group">
							<label class="col-sm-2 control-label">Title</label>
							<div class="col-sm-10">
								<input type="text" name="BOOKTITLE" class="form-control" placeholder="e.g To Kill a Mockingbird">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">Author</label>
							<div class="col-sm-10">
								<input type="text" name="BOOKAUTHOR" class="form-control" placeholder="e.g Harper Lee">
							</div>
						</div>
						<input class ="btn btn-default" name="bookadd" type="submit" value="Enter"/>
					</form>
				</div>
				<div class ="col-md-6">
					<h3>Enter new Questions</h3>
					<p>Please note: you do not need to include as many potential answers as there are forms.
					<form class ="form-horizontal" action="input.php" method="post">
					<div class="form-group">
						<label class="col-sm-2 control-label">Book</label>
						<div class="col-sm-10">
						<!--Scripting to show db books here-->
							<select name="BOOK" class="form-control">
								'.$booktitles.'
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Question</label>
						<div class="col-sm-10">
							<input type="text" name="QUESTION" class="form-control" placeholder="e.g What do you put at the start of a sentance?">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Answer</label>
						<div class="col-sm-10">
							<input type="text" name="ANSWER" class="form-control" placeholder="e.g A captial letter">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Incorrect</label>
						<div class="col-sm-10">
							<input type="text" name="OTHER1" class="form-control" placeholder="Incorrect answer">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Incorrect</label>
						<div class="col-sm-10">
							<input type="text" name="OTHER2" class="form-control" placeholder="Incorrect answer">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Incorrect</label>
						<div class="col-sm-10">
							<input type="text" name="OTHER3" class="form-control" placeholder="Incorrect answer">
						</div>
					</div>
					<input class ="btn btn-default" name="questionadd" type="submit" value="Enter"/>
					</form>
				</div>
			</div>
			<a href="">click here</a> to refresh the page.
		</div>
	</body>
</html>';
?>