<?php
###########################
##	get_data.php created 3/27/2012 by Joshua Komusin
##
##	Allows for a user to request all data of a query with a single command,
##	as opposed to the original 'get.php' script which forces the user to
##	query b*s*p times for number of panels 'p', bands 'b', stacks 's'.
##
##	Arguments to this script should be of the following format:
##		bands=<field>
##		stacks=<field>
##		panels=<field>
##		constants=<field>
##	with each argument's <field> consisting of a comma-separated list of the
##	type of constraint equal to its specified id
##		ex: get_data.php?bands=measurand=1,measurand=2,measurand=3
##	NOTE: This function will NOT check if these are legal constraints,
##	behaviour for unknown constraints is undefined.
##
##	This function will query similar to the original 'get.php' script for each combination
##	of the three visual category ('bands', 'stacks', 'panels') constraints possible,
##	with all queries including all provided 'constants'.
##	Because of this, constants cannot be of the same type as any constraints
##	provided as bands, stacks, or panels (for obvious reasons, this would not
##	make semantic sense).
##
##	It will return a JSON packet in the form of a 3-dimensional array where
##	the arrays are indexed as follows:
##		[x][][] specifies the panel
##		[][x][] specifies the stack
##		[][][x] specifies the return value of the original 'get.php' for the band 
##	For example, to find the event array in the band constrained by the 2nd panel
##	constraint in the comma-separated list, the 1st stack constraint, and the 4th
##	band constraint, we would retrieve the object from our returned 'eArray' at 
##	(assuming 0-indexing):
##		eArr[1][0][3]
##	NOTE: The value returned per band may be an array, and has its own
##	specific format. Please see below for more information.
##
###########################

error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
if (!$_SESSION['loggedin'] == true)
{
	echo "You are not logged in!";
	return;
}

$conn_string = 	"host=" . $_SESSION['host'] . " port=5432 user=" . $_SESSION['user'];
$conn_string .= " password=" . $_SESSION['pass'] . " dbname=" . $_SESSION['database'];
$dbconn = pg_connect($conn_string);

$result = array(); 

$bands = 		explode(",", $_GET['bands']);
$stacks = 		explode(",", $_GET['stacks']);
$panels = 		explode(",", $_GET['panels']);
$constants = 	explode(",", $_GET['constants']);
$const_str = 	implode("&", $constants);	// string to be included with every query

// We assume a hierarchical structure of bands, stacks, and panels; that is,
//	you cannot have panels without stacks, you cannot have stacks without bands,
//	however, you may have stacks and bands without panels.
// TODO: Needs better looping structure, too much redundancy
if ($panels)
{
	foreach ($panels as $p)
	{
		$pArr = array(); // Array of stacks in the panel
		foreach ($stacks as $s)
		{
			$sArr = array(); // Array of bands in the stack
			foreach ($bands as $b)
			{
				$params = $b . "&" . $s . "&" . $p . "&" . $const_str;
				$sArr[] = getData($params);
			}
			$pArr[] = $sArr;
		}
		$result[] = $pArr;
	}
}
else if ($stacks) // No panels, try stacks
{
	$pArr = array(); 
	foreach ($stacks as $s)
	{
		$sArr = array(); 
		foreach ($bands as $b)
		{
			$params = $b . "&" . $s . "&" . $const_str;
			$sArr[] = getData($params);
		}
		$pArr[] = $sArr;
	}
	$result[] = $pArr;
}
else if ($bands) // No stacks, try bands
{
	$pArr = array(); 
	$sArr = array();
	foreach ($bands as $b)
	{
		$params = $b . "&" . $const_str;
		$sArr[] = getData($params);
	}
	$pArr[] = $sArr;
	$result[] = $pArr;
}
else // No bands, just query constants
{
	$pArr = array(); 
	$sArr = array();
	$sArr[] = getData($const_str);
}

// Return results
echo json_encode($result);





function getData($params)
{
	$paramArr = explode("&", $params);
	$paramDict = array();

	foreach ($paramArr as $p)
	{
		$c = explode("=", $p);
		$key = $c[0];
		$val = $c[1];
		$paramDict["$key"] = $val;
	}

	return queryData($paramDict);
}

############################
##
##	Query data function to hit database
##
##	Originally by Jake Emerson,
##	modified for legibility(and sorting) by Josh Komusin
##
############################
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
############################
function queryData($paramDict)
{
	global $dbconn;

	$q_select =	"SELECT DISTINCT ON (event_occurrence.start, event_occurrence.end) ";
	$q_select .=	"event_type.type_id, event_type.measurand_id, source.source_id, unit.unit_id, ";
	$q_select .=	"event_type.medium_id, event_type.transform_id, event_type.condition_id, location.location_id, ";
	$q_select .=	"location.z_value, event_occurrence.instance_id, event_occurrence.start, event_occurrence.end, ";
	$q_select .=	"event_occurrence.magnitude ";

	$q_from = 	"FROM event_occurrence, generic_event, location, event_type, source, unit ";

	$q_where = 	"WHERE generic_event.event_id = event_occurrence.event_id AND generic_event.location_id = location.location_id ";
	$q_where .=		"AND generic_event.type_id = event_type.type_id AND event_type.source_id = source.source_id ";
	$q_where .=		"AND event_type.unit_id = unit.unit_id AND group_id IN ";
	$q_where .=		"(SELECT user_group.group_id ";
	$q_where .=			"FROM user_group, user_name ";
	$q_where .=			"WHERE user_group.user_id = user_name.user_id AND user_name.name = (SELECT current_user)) ";
		
	if ($paramDict["type"]) {
		$q_where .= " AND event_type.type_id IN (" . $paramDict["type"] . ") ";
	}
	if ($paramDict["measurand"]) {
		$q_where .= " AND event_type.measurand_id IN (" . $paramDict["measurand"] . ") ";
	}
	if ($paramDict["source"]) {
		$q_where .= " AND source.source_id IN (" . $paramDict["source"] . ") ";
	}
	if ($paramDict["unit"]) {
		$q_where .= " AND unit.unit_id IN (" . $paramDict["unit"] . ") ";
	}
	if ($paramDict["medium"]) {
		$q_where .= " AND event_type.medium_id IN (" . $paramDict["medium"] . ") ";
	}
	if ($paramDict["transform"]) {
		$q_where .= " AND event_type.transform_id IN (" . $paramDict["transform"] . ") ";
	}
	if ($paramDict["condition"]) {
		$q_where .= " AND event_type.condition_id IN (" . $paramDict["condition"] . ") ";
	}
	if ($paramDict["location"]) {
		$q_where .= " AND generic_event.location_id IN (" . $paramDict["location"] . ") ";
	}
	if ($paramDict["height"]) {
		$q_where .= " AND location.z_value IN (" . $paramDict["height"] . ") ";
	}
	if ($paramDict["year"]) {
		$q_where .= " AND date_part('year', event_occurrence.start) IN (0," . $paramDict["year"] . ") ";
	} 
	if ($paramDict["month"]) {
		$q_where .= " AND date_part('month', event_occurrence.start) IN (0," . $paramDict["month"] . ") ";
	} 
	if ($paramDict["day"]) {
		$q_where .= " AND date_part('day', event_occurrence.start) IN (0," . $paramDict["day"] . ") ";
	} 
	if ($paramDict["start"] || $paramDict["end"]) {
		$q_starts = explode(',',$paramDict["start"]);
		$q_ends   = explode(',',$paramDict["end"]);
		if($paramDict["start"] && $paramDict["end"] && (count($q_starts) == count($q_ends))) {
			$q_where .= " AND ( FALSE";
			for($i = 0;$i < count($q_starts); $i++) {
				$q_where .= " OR ";	
				$q_where .= "(event_occurrence.start >= '".AS3ToMySql($q_starts[$i]).")'";
				$q_where .= " AND event_occurrence.end <= '".AS3ToMySql($q_ends[$i])."')";
			}
			$q_where = $q_where.")";	
		} else if ($paramDict["start"] && (count($q_starts) == 1)) {
			$q_where = $q_where." AND event_occurrence.start >= '".AS3ToMySql($paramDict["start"],"FIRST")."'";
		} else if ($paramDict["end"] && (count($q_ends) == 1)) {
			$q_where = $q_where." AND event_occurrence.end <= '".AS3ToMySql($paramDict["end"],"LAST")."'";
		} else {
			//Error of some kind
		}
	}

	$q_order = " ORDER BY event_occurrence.start ASC, event_occurrence.end ASC ";

	$q_count = $q_select . $q_from . $q_where . $q_order;

	// Legacy; this *may* be broken
	if ($paramDict["season"]) {
	
		$drop = "DROP TABLE temp;";
		pg_query($dbconn, $drop);
		
		$q_select = 	"CREATE TEMP TABLE temp AS ";
		$q_select .=	"SELECT event_type.type_id, event_type.measurand_id, source.source_id, unit.unit_id, ";
		$q_select .=		"event_type.medium_id, event_type.transform_id, event_type.condition_id, location.location_id, ";
		$q_select .=		"location.z_value, event_occurrence.instance_id, event_occurrence.start, event_occurrence.end, event_occurrence.magnitude, ";
		$q_select .=		"CASE WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0621' AND '0920' THEN 'summer' ";
		$q_select .=			"WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0921' AND '1220' THEN 'fall' ";
		$q_select .=			"WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '1221' AND '1231' THEN 'winter' ";
		$q_select .=			"WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0101' AND '0320' THEN 'winter' ";
		$q_select .=			"WHEN to_char(event_occurrence.start, 'MMDD') BETWEEN '0321' AND '0620' THEN 'spring' ";
		$q_select .=	"END AS season";

		$temp = $q_select . $q_from . $q_where;
		pg_query($dbconn, $temp);
		$v = array(split(',', $paramDict["season"]));
		
		$q_count = 	"SELECT temp.type_id, temp.measurand_id, temp.source_id, temp.unit_id, temp.medium_id, ";
		$q_count .= 	"temp.transform_id, temp.condition_id, temp.location_id, temp.z_value, temp.instance_id, ";
		$q_count .=		"temp.start, temp.end, temp.magnitude ";
		$q_count .=	"FROM temp ";
		$q_count .=	"WHERE season IN ('".multi_implode("','",$v)."');";
	}
		
	$tuples = pg_fetch_all(pg_query($dbconn, $q_count));
	
	if ($tuples) {
		$i = 0;
		$heads = array ('type_id', 'measurand_id', 'source_id', 'unit_id', 'medium_id', 'transform_id', 'condition_id', 'location_id', 'z_value');
		$tails = array ('instance_id', 'start', 'end', 'magnitude');
		foreach ($tuples as $tuple => $keys) {
			if ($i == 0) {
				$header = array ();
				foreach ($heads as $head) {
					foreach ($keys as $key => $value) {
						if ($head == $key) {
							$header[$head] = $value;
						}	
					}
				}
				
				$tailer = array();
				foreach ($tails as $tail) {
					foreach ($keys as $key => $value) {
						if ($key == 'start') {
							$tailer[$tail] = $value;
							$keywords = preg_split("/ /", $value);
							$date = preg_split("/-/", $keywords[0]);
							$tailer['year'] = $date[0];
							$tailer['month'] = $date[1];
							$tailer['day'] = $date[2];
						} else if ($tail == $key) {
							$tailer[$tail] = $value;
						}
					}
				}
				
				$data[$i]['header'] = $header;
				$data[$i]['occurrence'][] = $tailer;
				$i = $i + 1;
				
			} else {
				$header = array ();
				foreach ($heads as $head) {
					foreach ($keys as $key => $value) {
						if ($head == $key) {
							$header[$head] = $value;
						}	
					}
				}
				
				$tailer = array();
				foreach ($tails as $tail) {
					foreach ($keys as $key => $value) {
						if ($key == 'start') {
							$tailer[$tail] = $value;
							$keywords = preg_split("/ /", $value);
							$date = preg_split("/-/", $keywords[0]);
							$tailer['year'] = $date[0];
							$tailer['month'] = $date[1];
							$tailer['day'] = $date[2];
						} else if ($tail == $key) {
							$tailer[$tail] = $value;
						}
					}
				}
				
				$found = 0;
				
				for ($j=0; $j < $i; $j++) {
					if ( $data[$j]['header'] == $header) {
						$data[$j]['occurrence'][] = $tailer;
						$found = 1;
						break;
					} 
					
				}
				if ($found == 0) {
					$data[$i]['header'] = $header;
					$data[$i]['occurrence'][] = $tailer;
					$i = $i + 1; 
				}
			}  
		}

		//return json_encode($data);
		return $data;
	}	
}


?>
