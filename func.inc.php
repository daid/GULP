<?
require_once "db.inc.php";

function daysInMonth($month, $year)
{
	if ($month > 12)
	{
		$month -= 12;
		$year ++;
	}
	return cal_days_in_month(CAL_GREGORIAN, $month, $year);
}
function weekday($day, $month, $year)
{
	//Return the day of the week where monday is 0 and sunday is 6
	return (localtime(mktime(12, 0, 0, $month, $day, $year), true)['tm_wday'] + 6) % 7;
}
function monthName($month)
{
	return date("F", mktime(0, 0, 0, $month, 10));
}
function isAGoodDay($day, $month, $year)
{
	//Is today a day that we GULP?
	if (weekday($day, $month, $year) > 4) return false;
	
	//There are no good days before the start of GULP.
	if ($year < 2014) return false;
	if ($year == 2014 && $month < 7) return false;
	if ($year == 2014 && $month == 7 && $day < 7) return false;
	if (count(dbQuery("SELECT * FROM `bad_day` WHERE `date` = '$year-$month-$day'")) > 0)
		return false;
	return true;
}

function countActiveDays($id, $day, $month, $year)
{
	//Calculate the number of days this comrad joined actively in the GULP up to the specified date.
	
	$date = explode("-", dbQuery("SELECT join_date FROM comrades WHERE ID = '$id'")[0]->join_date);
	$y = (int)$date[0];
	$m = (int)$date[1];
	$d = (int)$date[2];
	if ($y > $year) return -1;
	if ($y == $year && $m > $month) return -2;
	if ($y == $year && $m == $month && $d > $day) return -3;
	$count = 0;
	while($d != $day || $m != $month || $y != $year)
	{
		if (isAGoodDay($d, $m, $y))
		{
			if (count(dbQuery("SELECT * FROM comrad_vacation WHERE comrad_id = '$id' AND `date` = '$y-$m-$d'")) < 1)
				$count ++;
		}
		
		$d++;
		if ($d > daysInMonth($m, $y))
		{
			$m++;
			$d = 1;
			if ($m > 12)
			{
				$m = 1;
				$y ++;
			}
		}
	}
	return $count;
}

function countRosterDays($id, $day, $month, $year)
{
	//Calculate the number of days this comrad was in the roster for GULP up to the specified date.
	
	$date = explode("-", dbQuery("SELECT join_date FROM comrades WHERE ID = '$id'")[0]->join_date);
	$y = (int)$date[0];
	$m = (int)$date[1];
	$d = (int)$date[2];
	if ($y > $year) return -1;
	if ($y == $year && $m > $month) return -2;
	if ($y == $year && $m == $month && $d > $day) return -3;
	$count = 0;
	while($d != $day || $m != $month || $y != $year)
	{
		if (isAGoodDay($d, $m, $y))
		{
			if (count(dbQuery("SELECT * FROM comrad_vacation WHERE comrad_id = '$id' AND `date` = '$y-$m-$d'")) < 1)
				if (count(dbQuery("SELECT * FROM roster WHERE `date` = '$y-$m-$d' AND comrad_id = '$id'")) > 0)
					$count ++;
		}
		
		dayPlusOne($d, $m, $y);
	}
	return $count;
}

function isDirectlyAfterVacation($d, $m, $y, $comrad_id)
{
	do
	{
		$d -= 1;
		if ($d < 1)
		{
			$m -= 1;
			if ($m < 1)
			{
				$m = 12;
				$y -= 1;
			}
			$d = daysInMonth($m, $y);
		}
	} while(!isAGoodDay($d, $m, $y));
	if (count(dbQuery("SELECT * FROM comrad_vacation WHERE comrad_id = '".$comrad_id."' AND `date` = '$y-$m-$d'")) > 0)
		return true;
	return false;
}

function getComradsOn($d, $m, $y)
{
	$ret = array();
	foreach(dbQuery("SELECT * FROM comrades WHERE join_date < '$y-$m-$d'") as $comrad)
	{
		if (count(dbQuery("SELECT * FROM comrad_vacation WHERE comrad_id = '".$comrad->ID."' AND `date` = '$y-$m-$d'")) > 0)
			continue;
		$ret[] = $comrad;
	}
	return $ret;
}

function findBestComradFor($d, $m, $y, $extraRoster=array())
{
	$bestList = false;
	foreach(dbQuery("SELECT * FROM comrades") as $comrad)
	{
		if (count(dbQuery("SELECT * FROM comrad_vacation WHERE comrad_id = '".$comrad->ID."' AND `date` = '$y-$m-$d'")) > 0)
			continue;
		if (isDirectlyAfterVacation($d, $m, $y, $comrad->ID))
			continue;
		
		$active = countActiveDays($comrad->ID, $d, $m, $y);
		$roster = countRosterDays($comrad->ID, $d, $m, $y);
		if (isset($extraRoster[$comrad->ID]))
			$roster += $extraRoster[$comrad->ID];
		$comrad->score = $roster/$active;
		if ($bestList === false)
		{
			$bestList = array($comrad);
		}else if ($comrad->score < $bestList[0]->score)
		{
			$bestList = array($comrad);
		}else if ($comrad->score == $bestList[0]->score)
		{
			$bestList[] = $comrad;
		}
	}
	return $bestList[0];
}

function dayPlusOne(&$d, &$m, &$y)
{
	$d++;
	if ($d > daysInMonth($m, $y))
	{
		$m++;
		$d = 1;
		if ($m > 12)
		{
			$m = 1;
			$y ++;
		}
	}
}

function dayMinusOne(&$d, &$m, &$y)
{
	$d -= 1;
	if ($d < 1)
	{
		$m -= 1;
		if ($m < 1)
		{
			$m = 12;
			$y -= 1;
		}
		$d = daysInMonth($m, $y);
	}
}
?>