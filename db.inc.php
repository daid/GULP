<?
require_once "db.conf.php";
function e($s) {return "'".mysql_real_escape_string($s)."'";}
$_query_count = 0;
$_query_log = array();

function dbInsert($query)
{
	$res = mysql_query($query);
	if ($res === false)
	{
		echo "SQL error: " . mysql_error() . "<br>";
		return false;
	}
	return mysql_insert_id();
}
function dbQuery($query)
{
	global $_query_count, $_query_log;
	$_query_count += 1;
	if (!isset($_query_log[$query]))
		$_query_log[$query] = 1;
	else
		$_query_log[$query] += 1;
	
	$res = mysql_query($query);
	if ($res === false)
	{
		echo "SQL error: " . mysql_error() . "<br>";
		return array();
	}
	$ret = array();
	while($row = mysql_fetch_object($res))
	{
		$ret[] = $row;
	}
	return $ret;
}
?>