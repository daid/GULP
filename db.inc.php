<?
require_once "db.conf.php";
function e($s) {return "'".mysql_real_escape_string($s)."'";}

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