<?php
/**
 * 
 */
require_once '../lib/battleship/board.php';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EVA AI HACKATHON</title>
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="css/lib/bootstrap-3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/lib/bootstrap-3.3.7/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script type="text/javascript" src="js/lib/jquery-1.12.4.min.js"></script>
    <script type="text/javascript" src="js/lib/jquery.rest-1.0.2.min.js"></script>
    <script type="text/javascript" src="css/lib/bootstrap-3.3.7/js/bootstrap.min.js"></script>
</head>
<body>
	<div id="body" class="container-fluid">
		<p>
			<button id="btn-new_game" class="btn btn-sm btn-default">New game</button>
            <button id="btn-shoot" class="btn btn-sm btn-danger disabled">Shoot</button>
            <button id="btn-check-avail" class="btn btn-sm btn-primary">Check available?</button>
		</p>
    	<div id="grids" class="clearfix"></div>
	</div>
	<script type="text/javascript" src="js/script.js"></script>
</body>
</html>