<?php
################################
##	get.php modified 2/4/2012 by Joshua Komusin
##
##	Changes from previous versions include:
##	- Modification of getRootCategories to include location_id's similar to getSubCategories
##	- Modified queries taking 'event_type' to expect simply 'type' instead, to match with 'type_id's returned
##
################################
##
##	Data function should return a JSON array with the format:
##	Array with elements that are dictionaries with two keys: "header" and "occurrence"
##	"header" containts a dictionary of the specific constraints on the events in the dictionary
##	"occurrence" contains an array of dictionaries which are each an event under the header
##		Each event dictionary has the format similar to the following example:
##		day = 11;
##		end = "2004-10-12 18:00:00-04";
##		instance_id = "2004-10-11 03:00:00-04";
##		magnitude = "15.23999023";
##		month = 10;
##		start = "2004-10-11 03:00:00-04";
##		year = 2004;
##
################################

error_reporting(E_ALL);
ini_set('display_errors', '0');
session_start();

if (!$_SESSION['loggedin'] == true) 
{
	##############################################
	##                                          ##
	##        LOGIN: method=login               ##
	##                                          ##
	##############################################
	if ($_GET['method'] == "login") 
	{
		if (isset($_GET['pass']) && isset($_GET['user'])) 
		{
			$_SESSION['pass'] = $_GET['pass'];
			$_SESSION['user'] = $_GET['user'];
			$_SESSION['database']="edb";
			$_SESSION['host']="grad-01.spatial.maine.edu";
			#$_SESSION['host']="localhost";
			$conn_string="host=".$_SESSION['host']." port=5432 user=".$_SESSION['user']." password=".$_SESSION['pass']." dbname=".$_SESSION['database']."";
			try 
			{
				$dbconn = pg_connect($conn_string);
				if (!$dbconn) 
				{
					throw new Exception('Sorry, could not connect. Check user and pass.');
				}
				$_SESSION['loggedin'] = true;
				echo "Welcome!";
			} 
			catch (Exception $e) 
			{
				echo $e;
			}
		}
	} 
	else 
	{
		echo "you are not logged in";
	}
} 
else 
{
	$conn_string="host=".$_SESSION['host']." port=5432 user=".$_SESSION['user']." password=".$_SESSION['pass']." dbname=".$_SESSION['database']."";
	$dbconn = pg_connect($conn_string);
}

if (isset($_GET['method'])) 
{
	##############################################
	##                                          ##
	##        LOGIN: method=logout              ##
	##                                          ##
	##############################################
	if ($_GET['method'] == "logout") 
	{
		$_SESSION['loggedin'] = false;
		echo "Bye!";
		
	##############################################
	##                                          ##
	##        RELATION: method=relation         ##
	##                                          ##
	##############################################
	}
	 elseif ($_GET['method'] == "relation" && $dbconn) 
	 {
		/* return the pertinent field names these are hard coded because they do double duty as the table names in the database
		and there is only a select list of tables that are allowed for query, mostly for simplicity.
		*/
		$relation_names = array ("type"=>"generic name for an event, alias for event theme",
					"measurand"=>"the raw phenomenon under investigation - a.k.a. sensor type",
					"source"=>"the organization that is the origin of the measured data",
					"unit"=>"unit of measure",
					"transform"=>"filter or preprocessing of the variable prior to condition sifting",
					"condition"=>"sifts through the variable's data for event citeria",
					"location"=>"named geographic location - sans height",
					"height"=>"elevation - negative is below sea level",
					"medium"=>"the material in which the measurement is taken");
		header('Content-type: application/json');
		echo json_encode($relation_names);
		
	#######################################################
	##                                                   ##
	##        FIELD META: method=meta                    ##
	##                    &relation=<relation>           ##
	##                    &height=<feet>        	     ##
	##                                                   ##
	#######################################################
	} 
	elseif ($_GET['method'] == "meta" && $dbconn && $_GET['relation'])
	{
		/* return the id numbers and descriptors for the given relation.
		*/
		if ($_GET['relation']) {
			$relation = $_GET['relation'];
			if ($relation == "location") {
				if (isset($_GET['height'])) {
					$q_height = "AND z_value = ".$_GET['height']."";
				} else {
					$q_height = "";
				}
				$q_names = "SELECT DISTINCT ON (location.location_id) location.location_id, location.z_value, nm.name||' '||location.z_value||' m' as name, nm.description
FROM location,

(SELECT location.location_id, location.location, location_source_name.name, location_source_name.description 
FROM location, location_source_name WHERE location.location_id = location_source_name.location_id) AS nm

WHERE st_force_2d(location.location) = st_force_2d(nm.location)
AND z_value IS NOT NULL ".$q_height." ";

				$field_names = pg_fetch_all(pg_query($dbconn, $q_names));

				$q_names = "SELECT DISTINCT ON (location.location_id) location.location_id, location.z_value, nm.name, nm.description
FROM location,

(SELECT location.location_id, location.location, location_source_name.name, location_source_name.description 
FROM location, location_source_name WHERE location.location_id = location_source_name.location_id) AS nm

WHERE st_force_2d(location.location) = st_force_2d(nm.location)
AND z_value IS NULL AND st_dimension(location.location) <> 0";
				
				$field_names = array_merge($field_names, pg_fetch_all(pg_query($dbconn, $q_names)));
				if ($field_names) {
					header('Content-type: application/json');
					echo json_encode($field_names);
				}
			} elseif ($relation == "type") {
				$q_names = "SELECT  DISTINCT ON (name) type_id, medium_id, measurand_id, transform_id, condition_id, domain_id, unit_id, name FROM event_type";
				$field_names = pg_fetch_all(pg_query($dbconn, $q_names));
				if ($field_names) {
					header('Content-type: application/json');
					echo json_encode($field_names);
				}
			} elseif ($relation == "height") {
				$q_names = "SELECT  DISTINCT ON (z_value) location_id, z_value AS name FROM location";
				$field_names = pg_fetch_all(pg_query($dbconn, $q_names));
				if ($field_names) {
					header('Content-type: application/json');
					echo json_encode($field_names);
				}
			
			} else {
				$q_names = "SELECT DISTINCT ON (name) ".$relation."_id, name, description FROM ".$relation."";
				$field_names = pg_fetch_all(pg_query($dbconn, $q_names));
				if ($field_names) {
					header('Content-type: application/json');
					echo json_encode($field_names);
				}
			}
		}
		
	}		
	###############################################################
	##                                	                         ##
	##        TUPLE COUNT: method=count		                     ##
	##                    &measurand=	<field>                  ##
	##                    &source=		<field>                  ##
	##                    &unit=		<field>                  ##
	##                    &transform=	<field>                  ##
	##                    &condition=	<field>                  ##
	##                    &location=	<field>                  ##
	##                    &height=		<field>                  ##
	##                    &medium=		<field>                  ##
	##                    &start=		<field>                  ##
	##                    &end=		    <field>                  ##
	##                                                           ##
	###############################################################
	elseif ($_GET['method'] == "count" && $dbconn) {
		$q_select = "SELECT count(event_occurrence.instance_id) ";
		$q_from = " FROM event_occurrence, generic_event, location, event_type, source, unit ";
		$q_where = " WHERE generic_event.event_id = event_occurrence.event_id AND generic_event.location_id =  location.location_id  AND generic_event.type_id =  event_type.type_id AND event_type.source_id = source.source_id AND event_type.unit_id = unit.unit_id AND group_id IN (SELECT user_group.group_id FROM user_group, user_name WHERE user_group.user_id = user_name.user_id AND user_name.name = (SELECT current_user))";
		
		if (isset($_GET['type'])) {
			$q_where = $q_where." AND event_type.type_id IN (".$_GET['type'].")";
		}
		
		if ($_GET['measurand']) {
			$q_where = $q_where." AND event_type.measurand_id IN (".$_GET['measurand'].")";
		}
		if ($_GET['source']) {
			$q_where = $q_where." AND source.source_id IN (".$_GET['source'].")";
		}
		if ($_GET['unit']) {
			$q_where = $q_where." AND unit.unit_id IN (".$_GET['unit'].")";
		}
		if ($_GET['medium']) {
			$q_where = $q_where." AND event_type.medium_id IN (".$_GET['medium'].")";
		}
		if ($_GET['transform']) {
			$q_where = $q_where." AND event_type.transform_id IN (".$_GET['transform'].")";
		}
		if ($_GET['condition']) {
			$q_where = $q_where." AND event_type.condition_id IN (".$_GET['condition'].")";
		}
		if ($_GET['location']) {
			$q_where = $q_where." AND generic_event.location_id IN (".$_GET['location'].")";
		}
		if ($_GET['height']) {
			$q_where = $q_where." AND location.z_value IN (".$_GET['height'].")";
		}
		if ($_GET['year']) {
			$q_where = $q_where." AND date_part('year', event_occurrence.start) IN (0,".$_GET['year'].")";
		} 
		if ($_GET['month']) {
			$q_where = $q_where." AND date_part('month', event_occurrence.start) IN (0,".$_GET['month'].")";
		} 
		if ($_GET['day']) {
			$q_where = $q_where." AND date_part('day', event_occurrence.start) IN (0,".$_GET['day'].")";
		}
		if ($_GET['start'] || $_GET['end']) {
			$q_starts = explode(',',$_GET['start']);
			$q_ends   = explode(',',$_GET['end']);
			if($_GET['start'] && $_GET['end'] && (count($q_starts) == count($q_ends))) {
				$q_where = $q_where." AND ( FALSE";
				for($i = 0;$i < count($q_starts); $i++) {
					$q_where = $q_where." OR ";	
					$q_where = $q_where."(event_occurrence.start >= '".AS3ToMySql($q_starts[$i]).")'";
					$q_where = $q_where." AND event_occurrence.end <= '".AS3ToMySql($q_ends[$i])."')";
				}
				$q_where = $q_where.")";	
			} else if ($_GET['start'] && (count($q_starts) == 1)) {
				$q_where = $q_where." AND event_occurrence.start >= '".AS3ToMySql($_GET['start'],"FIRST")."'";
			} else if ($_GET['end'] && (count($q_ends) == 1)) {
				$q_where = $q_where." AND event_occurrence.end <= '".AS3ToMySql($_GET['end'],"LAST")."'";
			} else {
				//Error of some kind
			}
		}
		
		$q_count = $q_select.$q_from.$q_where;
		
		if ($_GET['season']) {
		
			$drop = "DROP TABLE temp;";
			pg_query($dbconn, $drop);
			
			$q_select = "
CREATE TEMP TABLE temp AS
SELECT event_occurrence.instance_id,
CASE WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0621' AND '0920' THEN 'summer'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0921' AND '1220' THEN 'fall'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '1221' AND '1231' THEN 'winter'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0101' AND '0320' THEN 'winter'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0321' AND '0620' THEN 'spring'
	END AS season";

			$temp = $q_select.$q_from.$q_where;
			pg_query($dbconn, $temp);
			$v = array(split(',',$_GET['season']));
			
			$q_count = "SELECT count(temp.instance_id) FROM temp WHERE season IN ('".multi_implode("','",$v)."');";
			//echo $q_option;
		}
		
		
		//print $q_count;
		
		$_count = pg_fetch_all(pg_query($dbconn, $q_count));
		if ($_count) {
			header('Content-type: application/json');
			echo json_encode($_count);
		} //if the location names query was successful
	}
	###############################################################
	##                                	                     ##
	##        TUPLE DATA: method=data		             ##
	##                    &measurand=	<field>              ##
	##                    &source=		<field>              ##
	##                    &unit=		<field>              ##
	##                    &transform=	<field>              ##
	##                    &condition=	<field>              ##
	##                    &location=	<field>              ##
	##                    &height=		<field>              ##
	##                    &medium=		<field>              ##
	##                    &start=		<field>              ##
	##                    &end=		<field>              ##
	##                                                           ##
	###############################################################
	elseif ($_GET['method'] == "data" && $dbconn) 
	{
		$q_select = "SELECT event_type.type_id, event_type.measurand_id, source.source_id, unit.unit_id, event_type.medium_id, event_type.transform_id, event_type.condition_id, location.location_id, location.z_value, event_occurrence.instance_id, event_occurrence.start, event_occurrence.end, event_occurrence.magnitude";
		$q_from = " FROM event_occurrence, generic_event, location, event_type, source, unit";
		$q_where = " WHERE generic_event.event_id = event_occurrence.event_id AND generic_event.location_id =  location.location_id  AND generic_event.type_id =  event_type.type_id AND event_type.source_id = source.source_id AND event_type.unit_id = unit.unit_id AND group_id IN (SELECT user_group.group_id FROM user_group, user_name WHERE user_group.user_id = user_name.user_id AND user_name.name = (SELECT current_user)) ";
		
		if ($_GET['type']) 
		{
			$q_where = $q_where." AND event_type.type_id IN (".$_GET['type'].")";
		}

		if ($_GET['measurand']) 
		{
			$q_where = $q_where." AND event_type.measurand_id IN (".$_GET['measurand'].")";
		}

		if ($_GET['source']) 
		{
			$q_where = $q_where." AND source.source_id IN (".$_GET['source'].")";
		}

		if ($_GET['unit']) 
		{
			$q_where = $q_where." AND unit.unit_id IN (".$_GET['unit'].")";
		}

		if ($_GET['medium']) 
		{
			$q_where = $q_where." AND event_type.medium_id IN (".$_GET['medium'].")";
		}

		if ($_GET['transform']) 
		{
			$q_where = $q_where." AND event_type.transform_id IN (".$_GET['transform'].")";
		}

		if ($_GET['condition']) 
		{
			$q_where = $q_where." AND event_type.condition_id IN (".$_GET['condition'].")";
		}

		if ($_GET['location']) 
		{
			$q_where = $q_where." AND generic_event.location_id IN (".$_GET['location'].")";
		}

		if ($_GET['height']) 
		{
			$q_where = $q_where." AND location.z_value IN (".$_GET['height'].")";
		}

		if ($_GET['year']) 
		{
			$q_where = $q_where." AND date_part('year', event_occurrence.start) IN (0,".$_GET['year'].")";
		}

		if ($_GET['month']) 
		{
			$q_where = $q_where." AND date_part('month', event_occurrence.start) IN (0,".$_GET['month'].")";
		}

		if ($_GET['day']) 
		{
			$q_where = $q_where." AND date_part('day', event_occurrence.start) IN (0,".$_GET['day'].")";
		}

		if ($_GET['start'] || $_GET['end']) 
		{
			$q_starts = explode(',',$_GET['start']);
			$q_ends   = explode(',',$_GET['end']);
		
			if($_GET['start'] && $_GET['end'] && (count($q_starts) == count($q_ends))) 
			{
				$q_where = $q_where." AND ( FALSE";
				
				for($i = 0;$i < count($q_starts); $i++) 
				{
					$q_where = $q_where." OR ";	
					$q_where = $q_where."(event_occurrence.start >= '".AS3ToMySql($q_starts[$i]).")'";
					$q_where = $q_where." AND event_occurrence.end <= '".AS3ToMySql($q_ends[$i])."')";
				}
				
				$q_where = $q_where.")";	
			}
			else if ($_GET['start'] && (count($q_starts) == 1)) 
			{
				$q_where = $q_where." AND event_occurrence.start >= '".AS3ToMySql($_GET['start'],"FIRST")."'";
			}
			else if ($_GET['end'] && (count($q_ends) == 1)) 
			{
				$q_where = $q_where." AND event_occurrence.end <= '".AS3ToMySql($_GET['end'],"LAST")."'";
			} 
			else 
			{
				//Error of some kind
				//TODO maybe do something about this (some sort of feedback)
			}
		}
		
		$q_count = $q_select.$q_from.$q_where;
		
		if ($_GET['season']) 
		{
		
			$drop = "DROP TABLE temp;";
			pg_query($dbconn, $drop);
			
			$q_select = "
CREATE TEMP TABLE temp AS
SELECT event_type.type_id, event_type.measurand_id, source.source_id, unit.unit_id, event_type.medium_id, event_type.transform_id, event_type.condition_id, location.location_id, location.z_value, event_occurrence.instance_id, event_occurrence.start, event_occurrence.end, event_occurrence.magnitude,
CASE WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0621' AND '0920' THEN 'summer'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0921' AND '1220' THEN 'fall'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '1221' AND '1231' THEN 'winter'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0101' AND '0320' THEN 'winter'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0321' AND '0620' THEN 'spring'
	END AS season";

			$temp = $q_select.$q_from.$q_where;
			pg_query($dbconn, $temp);
			$v = array(split(',',$_GET['season']));
			
			$q_count = "SELECT temp.type_id, temp.measurand_id, temp.source_id, temp.unit_id, temp.medium_id, temp.transform_id, temp.condition_id, temp.location_id, temp.z_value, temp.instance_id, temp.start, temp.end, temp.magnitude FROM temp WHERE season IN ('".multi_implode("','",$v)."');";
			//echo $q_option;
		}
		
		$tuples = pg_fetch_all(pg_query($dbconn, $q_count));
		
		if ($tuples) 
		{
			$i = 0;
			$heads = array ('type_id', 'measurand_id', 'source_id', 'unit_id', 'medium_id', 'transform_id', 'condition_id', 'location_id', 'z_value');
			$tails = array ('instance_id', 'start', 'end', 'magnitude');
			foreach ($tuples as $tuple => $keys) 
			{
				if ($i == 0) 
				{
					$header = array ();
					foreach ($heads as $head) 
					{
						foreach ($keys as $key => $value) 
						{
							if ($head == $key) 
							{
								$header[$head] = $value;
							}	
						}
					}
					
					$tailer = array();
					foreach ($tails as $tail) 
					{
						foreach ($keys as $key => $value) 
						{
							if ($key == 'start') 
							{
								$tailer[$tail] = $value;
								$keywords = preg_split("/ /", $value);
								$date = preg_split("/-/", $keywords[0]);
								$tailer['year'] = $date[0];
								$tailer['month'] = $date[1];
								$tailer['day'] = $date[2];
							}
							elseif ($tail == $key) 
							{
								$tailer[$tail] = $value;
							}
							
						}
					}
					
					$data[$i]['header'] = $header;
					$data[$i]['occurrence'][] = $tailer;
					$i = $i + 1;
					
				}
				else 
				{
					$header = array ();
					foreach ($heads as $head) 
					{
						foreach ($keys as $key => $value) 
						{
							if ($head == $key) 
							{
								$header[$head] = $value;
							}	
						}
					}
					
					$tailer = array();
					foreach ($tails as $tail) 
					{
						foreach ($keys as $key => $value) 
						{
							if ($key == 'start') 
							{
								$tailer[$tail] = $value;
								$keywords = preg_split("/ /", $value);
								$date = preg_split("/-/", $keywords[0]);
								$tailer['year'] = $date[0];
								$tailer['month'] = $date[1];
								$tailer['day'] = $date[2];
							}
							elseif ($tail == $key) 
							{
								$tailer[$tail] = $value;
							}
						}
					}
					
					$found = 0;
					
					for ($j=0; $j < $i; $j++) 
					{
						if ( $data[$j]['header'] == $header) 
						{
							$data[$j]['occurrence'][] = $tailer;
							$found = 1;
							break;
						} 
						
					}
					if ($found == 0) 
					{
						$data[$i]['header'] = $header;
						$data[$i]['occurrence'][] = $tailer;
						$i = $i + 1; 
					}
				}  
			}
			echo json_encode($data);
		} //if the location names query was successful
	}	
	#############################################################
	##                                                         ##
	##        RELATION: method=options                         ##
	##                    &relation=	<relation>     	   ##
	##                    &measurand=	<ids>        	   ##
	##                    &source=		<ids>              ##
	##                    &unit=		<ids>              ##
	##                    &medium=		<ids>              ##
	##                    &transform=	<ids>              ##
	##                    &condition=	<ids>              ##
	##                    &location=	<ids>              ##
	##                    &height=		<ids>              ##
	##                                                         ##
	#############################################################
	elseif ($_GET['method'] == "option" || $_GET['method'] == "options" && $dbconn) 
	{
		#########################################################################
		## this section returns the full list of options as a list of id numbers
	
		$q_from = " FROM event_occurrence, generic_event, location, event_type, source, unit";
		$q_where = " WHERE generic_event.event_id = event_occurrence.event_id AND generic_event.location_id =  location.location_id  AND generic_event.type_id =  event_type.type_id AND event_type.source_id = source.source_id AND event_type.unit_id = unit.unit_id  AND group_id IN (SELECT user_group.group_id FROM user_group, user_name WHERE user_group.user_id = user_name.user_id AND user_name.name = (SELECT current_user)) ";
		
		if ($_GET['relation']) 
		{
			$rel = $_GET['relation'];
			$field = $rel;
			if (in_array($rel, array('medium','transform','condition','measurand'))) 
			{
				$table = 'event_type';
			}
			elseif (in_array($rel, array('time'))) 
			{
				if (! $_GET['period']) 
				{
					header('Content-type: application/json');
					echo json_encode(array(array('period'=>'year'),array('period'=>'season'),array('period'=>'month'),array('period'=>'day') ));
					return;
				}
			}
			else
			{
				$table = $rel;
			}
			if (in_array($rel, array('event_type'))) 
			{
				$field = "type_id";
				$q_select = "SELECT DISTINCT ON (".$table.".".$field.") ".$table.".".$field."";
				
			}
			elseif (in_array($rel, array('height'))) 
			{
				$table = "location";
				$field = "location_id";
				$q_select = "SELECT DISTINCT ON (".$table.".".$field.") ".$table.".".$field."";
			}
			elseif (in_array($rel, array('location'))) 
			{
				$table = "location";
				$field = "location_id";
				$q_select = "SELECT DISTINCT ON (".$table.".".$field.") ".$table.".".$field."";
				
			}
			elseif (in_array($rel, array('time'))) 
			{
				$table = "event_occurrence";
				$field = "start";
				$q_select = "SELECT DISTINCT ON (".$table.".".$field.") ".$table.".".$field." as datum";
				
			}
			else
			{
				$field = $rel."_id";
				$q_select = "SELECT DISTINCT ON (".$table.".".$field.") ".$table.".".$field."";	
			}
		}
		
		if ($_GET['measurand']) 
		{
			$q_where = $q_where." AND event_type.measurand_id IN (".$_GET['measurand'].")";
		}
		if ($_GET['source']) 
		{
			$q_where = $q_where." AND source.source_id IN (".$_GET['source'].")";
		}
		if ($_GET['unit']) 
		{
			$q_where = $q_where." AND unit.unit_id IN (".$_GET['unit'].")";
		}
		if ($_GET['medium']) 
		{
			$q_where = $q_where." AND event_type.medium_id IN (".$_GET['medium'].")";
		}
		if ($_GET['transform']) 
		{
			$q_where = $q_where." AND event_type.transform_id IN (".$_GET['transform'].")";
		}
		if ($_GET['condition']) 
		{
			$q_where = $q_where." AND event_type.condition_id IN (".$_GET['condition'].")";
		}
		if ($_GET['location']) 
		{
			$q_where = $q_where." AND generic_event.location_id IN (".$_GET['location'].")";
		}
		if ($_GET['height']) 
		{
			$q_where = $q_where." AND location.z_value IN (".$_GET['height'].")";
		}
		if ($_GET['type']) 
		{
			$q_where = $q_where." AND event_type.type_id IN (".$_GET['event_type'].")";
		} 
		if ($_GET['start']) 
		{
			$q_where = $q_where." AND event_occurrence.start>='".$_GET['start']."'";
		} 
		if ($_GET['end']) 
		{
			$q_where = $q_where." AND event_occurrence.end<='".$_GET['end']."'";
		}
		
		## the 'period' option has to be last because it uses the prior WHERE statements, but for the season period
		## it overrides the otherwise completed query statement
				
		# there's something curious about these queries.
		# the "0," in each of the times makes the query run an order of magnitude faster.
		# this runs very slow when there is only 1 value in the IN (...) condition, but more than one works fine
		# hence the "0"
		
		if ($_GET['year']) 
		{
			$q_where = $q_where." AND date_part('year', event_occurrence.start) IN (0,".$_GET['year'].")";
		} 
		if ($_GET['month']) 
		{
			$q_where = $q_where." AND date_part('month', event_occurrence.start) IN (0,".$_GET['month'].")";
		} 
		if ($_GET['day']) 
		{
			$q_where = $q_where." AND date_part('day', event_occurrence.start) IN (0,".$_GET['day'].")";
		} 
		
		$q_option = $q_select.$q_from.$q_where;

		if ($_GET['season']) 
		{
			$drop = "DROP TABLE temp;";
			pg_query($dbconn, $drop);
			
			$q_select = "
CREATE TEMP TABLE temp AS
SELECT ".$table.".".$field.",
CASE WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0621' AND '0920' THEN 'summer'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0921' AND '1220' THEN 'fall'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '1221' AND '1231' THEN 'winter'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0101' AND '0320' THEN 'winter'
	WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0321' AND '0620' THEN 'spring'
	END AS season";

			$temp = $q_select.$q_from.$q_where;
			pg_query($dbconn, $temp);
			$v = array(split(',',$_GET['season']));
			
			$q_option = "SELECT DISTINCT ON (temp.".$field.") temp.".$field." FROM temp WHERE season IN ('".multi_implode("','",$v)."');";
			//echo $q_option;
		}
		
		$_option = pg_fetch_all(pg_query($dbconn, $q_option));
		
		## if there's not data, then don't continue
		if (count($_option, COUNT_RECURSIVE) <= 1) 
		{
			return;
		}
		
		//echo $q_option;
		//print_r($_option);
		
		#######################################################################
		## now that we have the id numbers, return the options in a json array
		## time is an exception, we don't use ids there
		
		if (in_array($rel, array('event_type'))) 
		{
			$q_spec = "SELECT * FROM (SELECT DISTINCT ON (".$rel.".type_id) ".$rel.".type_id, ".$rel.".name FROM ".$rel." WHERE ".$rel.".type_id in (".multi_implode(',',$_option).")) AS data ORDER BY data.name";
		} 
		elseif (in_array($rel, array('time')) && $_GET['period']) 
		{
			if ($_GET['period'] == 'season') 
			{
				$q_spec = "SELECT DISTINCT ON (season)
	CASE WHEN to_char(datum, 'MMDD') BETWEEN '0621' AND '0920' THEN 'summer'
	     WHEN to_char(datum, 'MMDD') BETWEEN '0921' AND '1220' THEN 'fall'
	     WHEN to_char(datum, 'MMDD') BETWEEN '1221' AND '1231' THEN 'winter'
	     WHEN to_char(datum, 'MMDD') BETWEEN '0101' AND '0320' THEN 'winter'
	     WHEN to_char(datum, 'MMDD') BETWEEN '0321' AND '0620' THEN 'spring'
	     END AS season

FROM (".$q_option.") S ORDER BY 1";
				
			}
			elseif ($_GET['period'] == 'year') 
			{
				$q_spec = "SELECT DISTINCT ON (year) date_part('year', S.datum) as year
				FROM (".$q_option.") S ORDER BY 1";
			} 
			elseif ($_GET['period'] == 'month')
			{
				$q_spec = "SELECT DISTINCT ON (month) date_part('month', S.datum) as month
				FROM (".$q_option.") S ORDER BY 1";
			
			}
			elseif ($_GET['period'] == 'day') 
			{
				$q_spec = "SELECT DISTINCT ON (day) date_part('day', S.datum) as day
				FROM (".$q_option.") S ORDER BY 1";
			}
		
		} 
		elseif (in_array($rel, array('location'))) 
		{
			$q_spec = "SELECT * FROM (SELECT DISTINCT ON (location.location_id) location.location_id, nm.name, nm.description 
FROM location, 

(SELECT location.location_id, location.location, location_source_name.name, location_source_name.description 
FROM location, location_source_name WHERE location.location_id = location_source_name.location_id) AS nm 

WHERE st_force_2d(location.location) = st_force_2d(nm.location)

AND location.location_id in (".multi_implode(',',$_option).")) AS data ORDER BY data.name";

		} 
		elseif (in_array($rel, array('height'))) 
		{
			$q_spec = "SELECT * FROM (SELECT DISTINCT ON (location.z_value) location.z_value as height FROM location WHERE location.location_id in (".multi_implode(',',$_option).")) AS data ORDER BY data.height";
			
		}
		else 
		{
			$q_spec = "SELECT * FROM (SELECT DISTINCT ON (".$rel.".".$rel."_id) ".$rel.".".$rel."_id, ".$rel.".name FROM ".$rel." WHERE ".$rel.".".$rel."_id in (".multi_implode(',',$_option).")) AS data ORDER BY data.name";
		}
		//echo $q_spec;
		
		$_spec = pg_fetch_all(pg_query($dbconn, $q_spec));
		
		##################################################################
		## for the location options there is some other formatting needed
		
		if (in_array($rel, array('location'))) 
		{
			// for locations with matching 2D names, collect up the location ids under each unique name/description
			// this process relies on the names being in lexical order
			//print_r($_spec);
			//$loc = array('name','description','locations'=>'1,2,3');
			$name = '';
			foreach ($_spec as $key => $value) 
			{
				$new_name = $value['name'];

				if ($new_name != $name) 
				{
					$locs[$new_name] = array('description' => $value['description'], 'locations' => array());
					$name = $new_name;
				}

				$location = $value['location_id'];
				$locs[$new_name]['locations'][] = $location;
			}
			
			$_spec = array();
			foreach ($locs as $loc => $val) 
			{
				$l = "".multi_implode(',',$locs[$loc]['locations']);
				$_spec[] = array('name' => $loc, 'description' => $locs[$loc]['description'], 'locations' => $l);
			}
		}
		
		if ($_option) 
		{
			header('Content-type: application/json');
			echo json_encode($_spec);
			//echo $q_option;
			//print_r($_spec);
		} //if the location names query was successful
	}
	#############################################################
	##                                                         ##
	##        RELATION: method=timerange                       ##
	##                    &relation=	<relation>     	       ##
	##                    &measurand=	<ids>        	       ##
	##                    &source=		<ids>                  ##
	##                    &unit=		<ids>                  ##
	##                    &medium=		<ids>                  ##
	##                    &transform=	<ids>                  ##
	##                    &condition=	<ids>                  ##
	##                    &location=	<ids>                  ##
	##                    &height=		<ids>                  ##
	##                                                         ##
	#############################################################
	elseif ($_GET['method'] == "timerange" && $dbconn) 
	{
		$q_select = "(SELECT event_occurrence.instance_id, event_occurrence.start, event_occurrence.end, event_occurrence.magnitude ";
		$q_from = "FROM event_occurrence, generic_event, location, event_type, source, location_source_name, unit ";
		$q_where = "WHERE generic_event.event_id = event_occurrence.event_id AND generic_event.location_id =  location.location_id  AND generic_event.type_id =  event_type.type_id AND location.location_id = location_source_name.location_id AND location_source_name.source_id = source.source_id AND event_type.unit_id = unit.unit_id  AND group_id IN (SELECT user_group.group_id FROM user_group, user_name WHERE user_group.user_id = user_name.user_id AND user_name.name = (SELECT current_user)) ";
		$q_orderlimit_first = "ORDER BY event_occurrence.start ASC LIMIT 1) ";
		$q_orderlimit_last  = "ORDER BY event_occurrence.end DESC LIMIT 1) ";
		$q_union = "UNION ALL \n";
		
		if ($_GET['type']) 
		{
			$q_where = $q_where." AND event_type.type_id IN (".$_GET['event_type'].") ";
		}
		
		if ($_GET['measurand']) 
		{
			$q_where = $q_where." AND event_type.measurand_id IN (".$_GET['measurand'].") ";
		}

		if ($_GET['source']) 
		{
			$q_where = $q_where." AND source.source_id IN (".$_GET['source'].") ";
		}
		
		if ($_GET['unit']) 
		{
			$q_where = $q_where." AND unit.unit_id IN (".$_GET['unit'].") ";
		}
		
		if ($_GET['medium']) 
		{
			$q_where = $q_where." AND event_type.medium_id IN (".$_GET['medium'].") ";
		}
		
		if ($_GET['transform']) 
		{
			$q_where = $q_where." AND event_typet.transform_id IN (".$_GET['transform'].") ";
		}
		
		if ($_GET['condition']) 
		{
			$q_where = $q_where." AND event_type.condition_id IN (".$_GET['condition'].") ";
		}
		
		if ($_GET['location']) 
		{
			$q_where = $q_where." AND generic_event.location_id IN (".$_GET['location'].") ";
		}
		
		if ($_GET['height']) 
		{
			$q_where = $q_where." AND location.z_value IN (".$_GET['height'].") ";
		}
		
		if ($_GET['start']) 
		{
			$q_where = $q_where." AND event_occurrence.start >= '".AS3ToMySql($_GET['start'],"FIRST")."' ";
		}
		
		if ($_GET['end']) 
		{
			$q_where = $q_where." AND event_occurrence.end <= '".AS3ToMySql($_GET['end'],"LAST")."' ";
		}
		
		$q_timerange = $q_select.$q_from.$q_where.$q_orderlimit_first.$q_union.$q_select.$q_from.$q_where.$q_orderlimit_last;
		
		$_timerange = pg_fetch_all(pg_query($dbconn, $q_timerange));
		if ($_timerange) 
		{
			header('Content-type: application/json');
			echo json_encode($_timerange);
		} //if the location names query was successful
	#############################################################
	##                                                         ##
	##        RELATION: method=spatial                         ## removeParent, addParent, getChild, getLocationTree
	##           &operation= intersect, union, symDifferene    ##
	##           &location= <ids>			           ##
	##                                                         ##
	#############################################################
		} 
		elseif ($_GET['method'] == "spatial" && $dbconn) 
		{
			if ($_GET['operation'] == 'intersect' || $_GET['operation'] == 'intersects') 
			{
				$q_select = "SELECT DISTINCT ON (location.location_id) location_source_name.name, location.location_id ";
				$q_from = "FROM location, location_source_name, (SELECT location_id FROM location_source_name WHERE lower(name) LIKE lower('%".$_GET['location']."%')) AS st ";
				$q_where = "WHERE location.location_id = location_source_name.location_id AND intersects((SELECT location FROM location WHERE location_id = st.location_id), location.location)";
				$q_option = $q_select.$q_from.$q_where;
				
				$_option = pg_fetch_all(pg_query($dbconn, $q_option));

				if ($_option) 
				{
					header('Content-type: application/json');
					echo json_encode($_option);
				} //if the location names query was successful
			} 

			elseif ($_GET['operation'] == 'contain' || $_GET['operation'] == 'contains') 
			{
				$q_select = "SELECT DISTINCT ON (location.location_id) location_source_name.name, location.location_id ";
				$q_from = "FROM location, location_source_name, (SELECT location_id FROM location_source_name WHERE lower(name) LIKE lower('%".$_GET['location']."%')) AS st ";
				$q_where = "WHERE location.location_id = location_source_name.location_id AND contains((SELECT location FROM location WHERE location_id = st.location_id), location.location)";
				$q_option = $q_select.$q_from.$q_where;
				
				$_option = pg_fetch_all(pg_query($dbconn, $q_option));
				
				if ($_option) 
				{
					header('Content-type: application/json');
					echo json_encode($_option);
				} //if the location names query was successful
			} 

			elseif ($_GET['operation'] == 'difference') 
			{
				//TODO: this sure looks like functionality, doesn't it
			}
	#############################################################
	##                                                         ##
	##        RELATION: method=getChildren                     ## removeParent, addParent, getChild, getLocationTree
	##                    &selector=	    <id>           ##
	##                    &operation= intersects | contains    ##
	##                    &includeSelectorsEvents= yes | no    ##
	##                                                         ##
	#############################################################
		} 
		elseif (strtolower($_GET['method']) == "getchildren" && $dbconn) 
		{
			$contains = "'0', '1'";
		
			if (isset($_GET['operation'])) {
				if ($_GET['operation'] == 'contains') {
					$contains = "'1'";
				} elseif ($_GET['operation'] == 'contains') {
					$contains = "'0', '1'";
				}
			}
			
			if (isset($_GET['selector'])) {
				// return a location name, height, and location_id for point locations
				$select = "SELECT DISTINCT ON (location_intersect.location_id) location_intersect.location_id, location_source_name.name, st_dimension(location.location) as dim, location_source_name.description ";
				$from = "FROM location, location_source_name, location_intersect ";
				$where = "WHERE location_intersect.selector_id IN (".$_GET['selector'].") AND location.location_id = location_source_name.location_id " ;
				$where = $where."AND location.location_id = location_intersect.location_id AND location_intersect.contains IN (".$contains.") ";
				$order = "ORDER BY location_intersect.location_id;";
				
				$query = $select.$from.$where.$order;
				
				$_option = pg_fetch_all(pg_query($dbconn, $query));
			}
			
			if ($_option) {
				// set up an array for holding replacement locations, with heights, for the 0-dimension geometries
				$heights[] = array();
				foreach ($_option as $key => $value) {
					if ($value['dim'] == 0) {
						unset($_heights);
						$select = "SELECT DISTINCT ON (height) location.location_id, st_z(location) as height, st.name||' '||st_z(location)||' m' as name, st.description ";
						$from = "FROM location, (SELECT location_id, name, description FROM location_source_name WHERE name LIKE '".$value['name']."%') AS st  ";
						$where = "WHERE location.location_id <> st.location_id ";
						$where = $where."AND (SELECT st_force_2d(location) FROM location WHERE location_id = st.location_id) = location.location ";
						$order = "ORDER BY height DESC;";
						
						$query = $select.$from.$where.$order;
				
						$_heights = pg_fetch_all(pg_query($dbconn, $query));
						
						if (is_array($_heights)) {
							// then pop out this key and add the data from the _heights array into heights
							unset($_option[$key]);
							foreach ($_heights as $_height => $_h) {
								$heights[] = $_h;
							}
							
						}
					}
				}
				foreach ($heights as $height => $h) {
					$_option[] = $h;
				}
				if (($_GET['includeSelectorsEvents'] == 'yes') || ($_GET['includeselectorsevents']) == 'yes') {
					// return a location name, height, and location_id for point locations
					$select = "SELECT location.location_id, location_source_name.name, location_source_name.description ";
					$from = "FROM location, location_source_name ";
					$where = "WHERE location.location_id IN (".$_GET['selector'].") AND location.location_id = location_source_name.location_id " ;
					$order = "ORDER BY location.location_id;";
					
					$query = $select.$from.$where.$order;
					
					$selectors = pg_fetch_all(pg_query($dbconn, $query));
					
					foreach ($selectors as $key => $s) {
						array_unshift($_option, $s);
					}
				}
				header('Content-type: application/json');
				echo json_encode($_option);
				//echo print_r($_option);
			} //if the location names query was successful
		
		#############################################################
		##                                                         ##
		##        RELATION: method=getEventLatLong                 ## 
		##                    &event_id=	    <ids>           ##
		##                                                         ##
		#############################################################
		} elseif (strtolower($_GET['method']) == "geteventlatlong" && $dbconn) {	
		
			if (isset($_GET['event_id'])) {
				$contains = $contains." AND generic_event.event_id IN (".$_GET['event_id'].") ";
			} else {
				$contains = " ";
			}

			// return a location name, height, and location_id for point locations
			$select = "SELECT st_xmin(location.location) as lat, st_ymin(location.location) as long, location.location_id, generic_event.event_id ";
			$from = "FROM location, generic_event ";
			$where = "WHERE location.location_id = generic_event.location_id " ;
			$where = $where.$contains;
			$order = "ORDER BY location.location_id;";
			
			$query = $select.$from.$where.$order;
			
			$_option = pg_fetch_all(pg_query($dbconn, $query));
			
			header('Content-type: application/json');
			echo json_encode($_option);
		
		#############################################################
		##                                                         ##
		##        RELATION: method=getLocLatLong                   ## 
		##                    &location_id=	    <ids>           ##
		##                                                         ##
		#############################################################
		} elseif (strtolower($_GET['method']) == "getloclatlong" && $dbconn) {	
	
		if (isset($_GET['location_id'])) {
			$where = "WHERE location.location_id IN (".$_GET['location_id'].") ";
		} else {
			$where = " ";
		}

		// return a location name, height, and location_id for point locations
		$select = "SELECT st_xmin(location.location) as lng, st_ymin(location.location) as lat, location.location_id as data ";
		$from = "FROM location ";
		$order = "ORDER BY location.location_id;";
		
		$query = $select.$from.$where.$order;
		
		$_option = pg_fetch_all(pg_query($dbconn, $query));

		header('Content-type: application/json');
		echo json_encode($_option);
	
	#############################################################
	##                                                         ##
	##        RELATION: method=getRootCategories               ##
	##                    &location_id=	    <ids>          ##
	##                                                         ##
	#############################################################
	} elseif (strtolower($_GET['method']) == "getrootcategories" && $dbconn) {	
	
		
		$select = "SELECT DISTINCT ON (location_category_view.category_id) location_category_view.category_id, location_category.name, location_category_view.location_id ";
		$from = "FROM location_category_view, location_category  ";
		$where = "WHERE location_category.category_id = location_category_view.category_id AND location_category_view.category_id IN (1,2,3,4);";
		
		$query = $select.$from.$where;
		
		$_option = pg_fetch_all(pg_query($dbconn, $query));
		
		header('Content-type: application/json');
		echo json_encode($_option);
	
	#############################################################
	##                                                         ##
	##        RELATION: method=getSubCategories                ##
	##                    &category_id=	    <id>          ##
	##                                                         ##
	#############################################################
	} elseif (strtolower($_GET['method']) == "getsubcategories" && $dbconn) {	
	
		if (isset($_GET['category_id'])) {
			$where = "WHERE location_category.category_id = location_category_view.child_id AND location_category_view.category_id = ".$_GET['category_id']."; ";
			$select = "SELECT location_category_view.child_id, location_category.name, location_category_view.location_id ";
			$from = "FROM location_category_view, location_category ";
			
			$query = $select.$from.$where;
			
			$_option = pg_fetch_all(pg_query($dbconn, $query));
			
			foreach ($_option as $key => $h) {
				if ($h['child_id'] == $_GET['category_id']) {
					$query = "SELECT name FROM location_name WHERE location_id = ".$h['location_id'].";";
					$name = pg_fetch_all(pg_query($dbconn, $query));
					if ($name[0]['name'] == null) {
						
					} else {
						$_option[$key]['name'] = $name[0]['name'];
					}
				}
			}
			
			header('Content-type: application/json');
			echo json_encode($_option);
		}
	} elseif (strtolower($_GET['method']) == "getsubcategorieslocations" && $dbconn) {	
	
		if (isset($_GET['category_id'])) {
				$return_value = getSubCategoriesLocations($_GET['category_id'], $dbconn);

				echo "<pre>";
				print_r($return_value);
				echo "</pre>";
			}
			
			//header('Content-type: application/json');
			echo json_encode($return_value);
		}
	
}

function getSubCategoriesLocations($id, $dbconn){
			$where = "WHERE location_category.category_id = location_category_view.child_id AND location_category_view.category_id = ".$id."; ";
			$select = "SELECT location_category_view.child_id, location_category.name, location_category_view.location_id ";
			$from = "FROM location_category_view, location_category ";
			
			$query = $select.$from.$where;
			
			$_option = pg_fetch_all(pg_query($dbconn, $query));
			
			foreach ($_option as $key => $h) {
				//echo $id;
				if ($h['child_id'] == $id) {
					$query = "SELECT location_name.name, st_xmin(location.location) as lng, st_ymin(location.location) as lat FROM location_name, location WHERE location_name.location_id = ".$h['location_id']." AND location_name.location_id = location.location_id;";
					$name = pg_fetch_all(pg_query($dbconn, $query));
					if ($name[0]['name'] == null) {
						
					} else {
						unset($_option[$key]['child_id']);
						$_option[$key]['name'] = $name[0]['name'];
						$_option[$key]['lat'] = $name[0]['lat'];
						$_option[$key]['lng'] = $name[0]['lng'];
						
					}
				}
				else {
					$_option[$key]['points'] = getSubCategoriesLocations($h['child_id'], $dbconn);
				}
			}
			return $_option;

}

// Converts AS3 time value to MySql timestamp value
function AS3ToMySql($timevalue,$order) {
	$timelist = explode(',',$timevalue);
	if(count($timelist) >= 1 && sort($timelist,SORT_NUMERIC) ) {
		if($order == "FIRST") $timevalue = $timelist[0];
		elseif($order == "LAST") $timevalue = $timelist[count($timelist)-1]; 
	}
	
	return date('Y-m-d H:i:s',$timevalue/1000);
}

function multi_implode($glue, $pieces)
{
    $string='';
   
    if(is_array($pieces))
    {
        reset($pieces);
        while(list($key,$value)=each($pieces))
        {
            $string.=$glue.multi_implode($glue, $value);
        }
    }
    else
    {
        return $pieces;
    }
   
    return trim($string, $glue);
}



?>
