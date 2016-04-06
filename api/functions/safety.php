<?php
function safeProfileData($row){

    if($row){
      unset($row['password']);

      if($row['twitter_user_id']){
      	$row['twitter_user_id'] = 1;
      }

      if($row['facebook_user_id']){
      	$row['facebook_user_id'] = 1;
      }

      return $row;
    }
}

function newAuth($db, $auth){

	$newauth = md5("LOL%I=ISUP".microtime());
	mysqli_query($db, "UPDATE user SET auth = '".$newauth."' WHERE auth = '$auth'");
	
	return $newauth;
}

function input( $name, $data_type, $required_length, $max_length = "" ){

	if(isset($_REQUEST[$name])){

	  $input = $_REQUEST[$name];

	  //Required minimum input length check
	  if( strlen($input) >= $required_length ){
	    $conditions['required_length'] = 1;
	  } else {
	    $conditions['required_length'] = 0;
	  }

	  //Maximum input length check
	  if( $max_length != "" ){

	    if( strlen($input) <= $max_length ){
	      $conditions['max_length'] = 1;
	    } else {
	      $conditions['max_length'] = 0;
	    }

	  } else {
	    $conditions['max_length'] = 1;
	  }

	  //Validate input data types - regex usually 
	  switch($data_type){
	  	case 'number':
	  		$conditions['data_type'] = is_numeric($input);
	  		break;

	  	case 'integer':
	  		$conditions['data_type'] = is_integer($input);
	  		break;

	  	case 'float':
	  		$conditions['data_type'] = is_float($input);
	  		break;

	  	case 'string':
	  		//well...
	  		$conditions['data_type'] = true;
	  		break;
	  }

	  //Clean data
	  $input = strip_tags($input);
	  $input = addslashes($input);
	  $input = mysqli_real_escape_string( $GLOBALS['db'], $input );
	  //http://stackoverflow.com/questions/60174/best-way-to-stop-sql-injection-in-php

	  //Manipulate request
	  $_REQUEST[$name] = $input;

	  //Everything OK?
	  if( $conditions['required_length'] && $conditions['max_length'] )
	  {
	    return $input;
	  }
	  else
	  {
	    return false;
	  }

	} else {
	  return false;
	}
}

//To enable loggable function inputs more easily...
function renderInput($args){
	$args = ksort(array_values($args));
	return array_values($args);
}

function backupDB($db){

	$tables = '*';

	//get all of the tables
	if($tables == '*')
	{
		$tables = array();

		$result = mysqli_query('SHOW TABLES');
		while($row = mysqli_fetch_row($result))
		{
			$tables[] = $row[0];
		}
	}
	else
	{
		$tables = is_array($tables) ? $tables : explode(',',$tables);

	}

	//cycle through
	foreach($tables as $table)
	{

		$result = mysqli_query('SELECT * FROM '.$table);
		$num_fields = mysqli_num_fields($result);

		$return.= 'DROP TABLE '.$table.';';
		$row2 = mysqli_fetch_row(mysqli_query('SHOW CREATE TABLE '.$table));
		$return.= "\n\n".$row2[1].";\n\n";

		for ($i = 0; $i < $num_fields; $i++) 
		{

			while($row = mysqli_fetch_row($result))
			{

				$return.= 'INSERT INTO '.$table.' VALUES(';
				for($j=0; $j < $num_fields; $j++) 
				{
					$row[$j] = addslashes($row[$j]);
					$row[$j] = ereg_replace("\n","\\n",$row[$j]);

					if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }

					if ($j < ($num_fields-1)) { $return.= ','; }
				}

				$return.= ");\n";
			}
		}

		$return.="\n\n\n";
	}

	//save file

	$handle = fopen('../backup/db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql','w+');
	fwrite($handle,$return);
	fclose($handle);
}
?>