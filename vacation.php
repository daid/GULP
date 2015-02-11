<?
require_once "db.inc.php";
require_once "func.inc.php";

session_name("GULP");
session_start();

if (!isset($_GET['id']) || !isset($_GET['auth']))
	die('hacker!');
$id = (int)$_GET['id'];
$comrad = dbQuery("SELECT * FROM comrades WHERE ID = ".e($id).";")[0];
if ($comrad->auth_token != $_GET['auth'])
	die('hacker!');

$url = "vacation.php?id=".$id."&auth=".$comrad->auth_token;

if (isset($_GET['action']))
{
	if ($_GET['action'] == 'vacation')
	{
		$d = (int)$_GET['d'];
		$m = (int)$_GET['m'];
		$y = (int)$_GET['y'];
		dbInsert("INSERT INTO comrad_vacation(`date`, `comrad_id`) VALUES ('$y-$m-$d', '".$id."');");
		dbInsert("DELETE FROM roster WHERE `date` = '$y-$m-$d' AND `comrad_id` = '".$id."';");
	}
	if ($_GET['action'] == 'no_vacation')
	{
		$d = (int)$_GET['d'];
		$m = (int)$_GET['m'];
		$y = (int)$_GET['y'];
		dbInsert("DELETE FROM comrad_vacation WHERE `date` = '$y-$m-$d' AND comrad_id = '".$id."';");
	}
	header("Location: ".$url);
	die();
}

date_default_timezone_set("Europe/Amsterdam");
$localtime = localtime(time(), true);
$day = $localtime['tm_mday'];
$month = $localtime['tm_mon'] + 1;
$year = $localtime['tm_year'] + 1900;

?>
<html>
<head><title>G.U.L.P.</title></head>
<link rel="stylesheet" href="style.css" type="text/css" />
<body>

<table>
<tr><th><th>Mo<th>Tu<th>We<th>Th<th>Fr<th>Sa<th>Su
<?
while(weekday($day, $month, $year) != 0)
	dayPlusOne($day, $month, $year);
$d = $day;
$m = $month;
$y = $year;
for($w=0;$w<12;$w++)
{
	echo '<tr><td>';
	echo monthName($m);
	for($n=0;$n<7;$n++)
	{
		if (!isAGoodDay($d, $m, $y))
		{
			echo "<td class='day bad_day'>";
		}else{
			$class = 'day day_'.($m%2);
			if (comradVacation($id, $d, $m, $y))
				$class .= ' vacation';
			echo "<td class='".$class."'>".$d;
			if (comradVacation($id, $d, $m, $y))
			{
				echo "<br><a href='$url&action=no_vacation&d=$d&m=$m&y=$y'>Cancel vacation :(</a>";
			}else{
				echo "<br><a href='$url&action=vacation&d=$d&m=$m&y=$y'>Plan vacation</a>";
			}
		}
		dayPlusOne($d, $m, $y);
	}
}
?>
</table>

Query count: <?=$_query_count?>
</body>
</html>