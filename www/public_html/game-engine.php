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
    <title>EVA AIHACKATHON</title>
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="css/lib/bootstrap-3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/lib/bootstrap-3.3.7/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script type="text/javascript" src="js/lib/jquery-1.12.4.min.js"></script>
    <script type="text/javascript" src="css/lib/bootstrap-3.3.7/js/bootstrap.min.js"></script>
</head>
<body>
	<div id="body" class="container-fluid">
		<p>
			<button id="btn-start_new_game" class="btn btn-sm btn-default">Start new game</button>
		</p>
    	<table id="grids" class="table table-striped table-bordered table-condensed">
    	<?php for ($row = 1; $row <= Board::ROWS; $row++): ?>
    		<tr class="row-<?php echo $row; ?>">
        	<?php for ($col = 1; $col <= Board::COLS; $col++): ?>
            	<td class="text-center col-<?php echo $col; ?>">
            		<div>
                		<small><?php echo "{$row} x {$col}"; ?></small>
                		<span>&nbsp;</span>
            		</div>
            	</td>
        	<?php endfor; ?>
        	</tr>
    	<?php endfor; ?>
    	</table>
	</div>
	<script type="text/javascript" src="js/script.js"></script>
</body>
</html>