<?php
// bounce user if not logged in

	if(!defined('WW_SESS')) {
		exit();
	}
	
// updates go here

	$updates = array();	

	/* 2011-11-22 added fields to enable Google-friendly redirects */

	$updates[] = array(	'action'	=>	'add',
						'table'		=>	'articles',
						'column'	=>	'redirect_code',
						'type'		=>	'SMALLINT(3) NULL AFTER seo_keywords'
						);
	$updates[] = array(	'action'	=>	'add',
						'table'		=>	'articles',
						'column'	=>	'redirect_url',
						'type'		=>	'VARCHAR(255) NULL AFTER redirect_code'
						);
						
	/* end of 2011-11-22 updates */


// run updates

	if(isset($_POST['run_updates'])) {

		$update_status = 'running updates...';

		foreach($updates as $to_update) {
	
			switch($to_update['action']) {
				
				case 'add':
					$update_status .= add_column($to_update['table'],$to_update['column'],$to_update['type']);
					break;
	
				case 'drop':
					// commented out for safety - probably won't need this anyway
					// $update_status .= drop_column($to_update['table'],$to_update['column']);
					break;
				
				case 'change':
					$update_status .= change_column($to_update['table'],$to_update['column'],$to_update['type']);
					break;	
				
			}
		}
		
		$update_status .= 'finished updates...';
		
	}

/**
 * check_table
 * 
 * checks the table for the specified column
 * returns true if the column is found
 * false otherwise
 * 
 */	

	function check_table($table, $column) {
		$conn 	= author_connect();
		$query 	= "SHOW columns FROM ".$table." LIKE '".$column."'";
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		// return true we find a matching column
		if(!empty($row)) {
			return true; 
		}
		return false;		
	}

/**
 * add_column
 * 
 * adds the specified column to the table
 * 
 */	
 	
	function add_column($table, $column, $type) {
		$check 	= check_table($table, $column);
		if($check === true) {
			return 'Column '.$column.' already found in table '.$table.'<br />';
		}
		$conn 	= author_connect();
		$query 	= "ALTER TABLE ".$table." ADD ".$column." ".$type."";
		$conn->query($query);
		if($conn->error) {
			return 'error: '.$conn->error.'<br />';
		}
		if(!$conn->error) {
			return 'Added column '.$column.' to table '.$table.'<br />';
		}
	}

/**
 * change_column
 * 
 * changes the specified column in the table
 * 
 */	
 
	function change_column($table, $column, $type) {
		$check 	= check_table($table, $column);
		if($check === false) {
			return 'Column '.$column.' NOT found in table '.$table.'<br />';
		}
		$conn 	= author_connect();
		$query 	= "ALTER TABLE ".$table." CHANGE ".$column." ".$column." ".$type."";
		$conn->query($query);
		if($conn->error) {
			return 'error: '.$conn->error.'<br />';
		}
		if(!$conn->error) {
			return 'Changed column '.$column.' in table '.$table.'<br />';
		}
	}

/**
 * drop_column
 * 
 * drops the specified column from the table
 * 
 */	
 	
	function drop_column($table, $column) {
		$check 	= check_table($table, $column);
		if($check === false) {
			return 'Column '.$column.' NOT found in table '.$table.'<br />';
		}
		$conn 	= author_connect();
		$query 	= "ALTER TABLE ".$table." DROP ".$column;
		$conn->query($query);
		if($conn->error) {
			return 'error: '.$conn->error.'<br />';
		}
		if(!$conn->error) {
			return 'Dropped column '.$column.' from table '.$table.'<br />';;
		}
	}

	
/* output */


// page title - if undefined the site title is displayed by default

	$page_title = 'Updates';
															
// build header

	$left_text = 'Updates';
	$right_text = '';
	
	$page_header = show_page_header($left_text, $right_text);
	
	$main_content = $page_header;

// display update results

	if(isset($update_status)) {
		$main_content .= '<p>'.$update_status.'</p>';
		$main_content .= '<p><a href="'.$action_url.'">Reload page...</a></p>';
	}
	
// run an auto-check to see if any updates are outstanding

	/* 	will need a better function to check if 'change' updates are needed 
		at present only the existence of the column is checked */

	// initialize variables

	$to_add 	= 0;
	$to_drop 	= 0;
	$to_change 	= 0;
	$outstanding_updates = false;

	// run through each item in the updates array

	foreach($updates as $update) {

		$check = check_table($update['table'],$update['column']);
					
		switch($update['action']) {
			
			case 'add':
				if($check === false) {
					$to_add++;
				}
				break;

			case 'drop':
				if($check === true) {
					$to_drop++;
				}
				break;
			
			case 'change':
				if($check === true) {
					$to_change++;
				}
				break;	
			
		}
	}

// display total for columns to be added, changed or dropped
	
	if(!empty($to_add)) {
		$main_content .= '<p>'.$to_add.' column(s) to add</p>';
		$outstanding_updates = true;
	}
	if(!empty($to_drop)) {
		$main_content .= '<p>'.$to_drop.' column(s) to drop</p>';
		$outstanding_updates = true;
	}
		if(!empty($to_change)) {
		$main_content .= '<p>'.$to_change.' column(s) to change</p>';
		$outstanding_updates = true;
	}

// display form only if changes have been detected
	
	if($outstanding_updates === true) {
		$main_content .= '
			<form action="'.$action_url.'" method="post" name="run_updates_form">
				<input name="run_updates" type="submit" value="Run updates?"/>
			</form>
			<hr />
			';
	}


?>