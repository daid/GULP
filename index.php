<?
require_once "db.inc.php";
require_once "func.inc.php";

session_name("GULP");
session_start();

if (!isset($_SESSION['active_comrad']))
	$_SESSION['active_comrad'] = 0;
if (isset($_GET['active_comrad']))
{
	$_SESSION['active_comrad'] = (int)$_GET['active_comrad'];
	header("Location: .");
	die();
}
if (isset($_GET['action']))
{
	if ($_GET['action'] == 'take')
	{
		$d = (int)$_GET['d'];
		$m = (int)$_GET['m'];
		$y = (int)$_GET['y'];
		dbInsert("INSERT INTO roster(`date`, `comrad_id`) VALUES ('$y-$m-$d', '".$_SESSION['active_comrad']."');");
	}
	if ($_GET['action'] == 'vacation')
	{
		$d = (int)$_GET['d'];
		$m = (int)$_GET['m'];
		$y = (int)$_GET['y'];
		dbInsert("INSERT INTO comrad_vacation(`date`, `comrad_id`) VALUES ('$y-$m-$d', '".$_SESSION['active_comrad']."');");
		dbInsert("DELETE FROM roster WHERE `date` = '$y-$m-$d' AND `comrad_id` = '".$_SESSION['active_comrad']."';");
	}
	if ($_GET['action'] == 'no_vacation')
	{
		$d = (int)$_GET['d'];
		$m = (int)$_GET['m'];
		$y = (int)$_GET['y'];
		dbInsert("DELETE FROM comrad_vacation WHERE `date` = '$y-$m-$d' AND comrad_id = '".$_SESSION['active_comrad']."';");
	}
	header("Location: .");
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

<table class="mainlayout">
<?
if ($_SESSION['active_comrad'] > 0)
{
	$comrad = dbQuery("SELECT * FROM comrades WHERE ID = '".$_SESSION['active_comrad']."'")[0];
	echo "<tr><td><a href='?active_comrad=0'>Logout</a> " . $comrad->name;
}
?>
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
for($m=$month-1;$m<$month+2;$m++)
{
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
			if (count(dbQuery("SELECT * FROM comrad_vacation WHERE comrad_id = '".$_SESSION['active_comrad']."' AND `date` = '$y-$m-$d'")) > 0)
				$class .= ' vacation';
			$class .= ' day_'.($m %2);
			echo "<td class='".$class."'>";
			echo $d . "<br>";
			
			$res = dbQuery("SELECT * FROM roster, comrades WHERE roster.date = '$y-$m-$d' AND roster.comrad_id = comrades.id");
			if (count($res) > 0)
			{
				echo "<span style='font-size: 16'>" . $res[0]->name . "</span><br>";
			}else if ($_SESSION['active_comrad'] > 0)
			{
				//echo "<a href='?action=take&d=$d&m=$m&y=$y'>Take</a><br>";
			}else{
				$comrad = findBestComradFor($d, $m, $y, $rosterExtra);
				echo "<span style='font-size: 12'>".$comrad->name."</span><br>";
				if (!isset($rosterExtra[$comrad->ID]))
					$rosterExtra[$comrad->ID] = 0;
				$rosterExtra[$comrad->ID] ++;
			}
			echo "Lunch for: ".count(getComradsOn($d, $m, $y)) . "<br>";
			if ($_SESSION['active_comrad'] > 0)
			{
				if (count(dbQuery("SELECT * FROM comrad_vacation WHERE comrad_id = '".$_SESSION['active_comrad']."' AND `date` = '$y-$m-$d'")) > 0)
					echo "<br><a href='?action=no_vacation&d=$d&m=$m&y=$y'>No Vacation :(</a>";
				else
					echo "<br><a href='?action=vacation&d=$d&m=$m&y=$y'>Vacation</a>";
			}
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
	
	echo "<tr><td><a href='?active_comrad=".$comrad->ID."'>".$comrad->name."</a>";
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