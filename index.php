<?
require_once "db.inc.php";
require_once "func.inc.php";

session_name("GULP");
session_start();

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

<table class="mainlayout">
<tr><td>

<table>
<tr><th><th>Mo<th>Tu<th>We<th>Th<th>Fr<th>Sa<th>Su
<?
if(weekday(1, $month-1, $year) > 0)
{
	echo "<tr><td><td colspan='".weekday(1, $month-1, $year)."'>&nbsp;";
}
$y = $year;
$rosterExtra = array();
for($mm=$month-1;$mm<$month+2;$mm++)
{
	$m = $mm;
	if ($m > 12)
	{
		$m -= 12;
		$y = $year + 1;
	}
	for($d=1;$d<=daysInMonth($m, $y); $d++)
	{
		if(weekday($d, $m, $y) == 0)
		{
			echo '<tr><td>';
			if ($d < 8)
				echo monthName($m);
		}
		
		if (!isAGoodDay($d, $m, $y))
		{
			echo "<td class='day bad_day'>";
		}else{
			$class = 'day';
			if ($d == $day && $m == $month)
				$class .= ' today';
			$class .= ' day_'.($m %2);
			echo "<td class='".$class."'>";
			echo $d . " " . $y . "<br>";
			
			$res = dbQuery("SELECT * FROM roster, comrades WHERE roster.date = '$y-$m-$d' AND roster.comrad_id = comrades.id");
			if (count($res) > 0)
			{
				echo "<span style='font-size: 16'>" . $res[0]->name . "</span><br>";
			}else{
				//$comrad = findBestComradFor($d, $m, $y, $rosterExtra);
				//echo "<span style='font-size: 12'>".$comrad->name."</span><br>";
				//if (!isset($rosterExtra[$comrad->ID]))
				//	$rosterExtra[$comrad->ID] = 0;
				//$rosterExtra[$comrad->ID] ++;
			}
			echo "Lunch for: ".count(getComradsOn($d, $m, $y)) . "<br>";
		}
	}
}
?>
</table>

<td>
<table><tr><th>Comrad<th>
<?
$comrades = dbQuery("SELECT * FROM comrades ORDER BY name");
foreach($comrades as $comrad)
{
	$active = countActiveDays($comrad->ID, $day, $month, $year);
	$roster = countRosterDays($comrad->ID, $day, $month, $year);
	
	echo "<tr><td>".$comrad->name;
	if ($active == 0)
		echo "<td>0";
	else
		echo "<td>".round($roster/$active*100, 2);
	
	echo "<td>".$roster." ".$active;
}
?>
</table>
</table>

Query count: <?=$_query_count?>
</body>
</html>