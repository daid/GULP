<?php
require_once "PHPMailer/PHPMailerAutoload.php";
require_once "func.inc.php";

date_default_timezone_set("Europe/Amsterdam");
$localtime = localtime(time(), true);
$d = $localtime['tm_mday'];
$m = $localtime['tm_mon'] + 1;
$y = $localtime['tm_year'] + 1900;

if (!isAGoodDay($d, $m, $y))
{
	echo "No need to send mail today.";
	die();
}

while(weekday($d, $m, $y) != 0)
	dayPlusOne($d, $m, $y);

echo "<head><style>";
echo file_get_contents('style.css');
echo "</style></head>";
echo "<table><tr><th>Mo<th>Tu<th>We<th>Th<th>Fr<th>Sa<th>Su";
$extra = array();
for($w=0; $w<3; $w++)
{
	echo "<tr>";
	for($n=0; $n<7; $n++)
	{
		if (!isAGoodDay($d, $m, $y))
		{
			echo "<td class='bad_day'>";
		}else{
			echo "<td>".$d."<br>";
			$comrad = dbQuery("SELECT * FROM comrades, roster WHERE roster.`date` = '$y-$m-$d' AND roster.comrad_id = comrades.ID");
			if (count($comrad) > 0)
			{
				echo $comrad[0]->name;
			}else{
				$comrad = findBestComradFor($d, $m, $y, $extra);
				if (!isset($extra[$comrad->ID]))
					$extra[$comrad->ID] = 0;
				$extra[$comrad->ID] += 1;
				
				echo $comrad->name;
				
				if ($w == 0)
				{
					dbInsert("INSERT INTO roster(date, comrad_id) VALUES ('$y-$m-$d', '".$comrad->ID."')");
				}
			}
		}
		dayPlusOne($d, $m, $y);
	}
}
echo "</table>";

$d = $localtime['tm_mday'];
$m = $localtime['tm_mon'] + 1;
$y = $localtime['tm_year'] + 1900;

dayPlusOne($d, $m, $y);
while(!isAGoodDay($d, $m, $y))
{
	dayPlusOne($d, $m, $y);
}

$roster = dbQuery("SELECT * FROM roster, comrades WHERE `date` = '$y-$m-$d' AND roster.comrad_id = comrades.ID");
if (count($roster) < 0)
	die();
$roster = $roster[0];

$dd = $d; $mm = $m; $yy = $y;
dayMinusOne($dd, $mm, $yy);
while(!isAGoodDay($dd, $mm, $yy))
{
	dayMinusOne($dd, $mm, $yy);
}

$prev_roster = dbQuery("SELECT * FROM roster, comrades WHERE `date` = '$yy-$mm-$dd' AND roster.comrad_id = comrades.ID");
if (count($prev_roster) < 0)
	die();
$prev_roster = $prev_roster[0];

$dd = $d; $mm = $m; $yy = $y;
dayPlusOne($dd, $mm, $yy);
while(!isAGoodDay($dd, $mm, $yy))
{
	dayPlusOne($dd, $mm, $yy);
}

$next_roster = dbQuery("SELECT * FROM roster, comrades WHERE `date` = '$yy-$mm-$dd' AND roster.comrad_id = comrades.ID");
if (count($next_roster) < 0)
	die();
$next_roster = $next_roster[0];

$days = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");

$c = "<html><body>";
$c .= "<h2>Hello comrad $roster->name</h2>";
$c .= "You have been carefully selected to supply all of us with food on: ".$days[weekDay($d, $m, $y)]." $y-$m-$d<br>";
$c .= "You will be dining with ".count(getComradsOn($d, $m, $y))." comrades<br>";
$c .= "This is your reminder email.<br>";
$c .= "<br>";
$c .= "The payment pas should be at: $prev_roster->name<br>and the next person to get the pas is $next_roster->name<br>3023<br>";
$c .= "<br>";
$c .= "One for the lunch, lunch for all!<br>";
$c .= "</body></html>";

$subject = "GULP - You have been chosen! ".$days[weekDay($d, $m, $y)]." $y-$m-$d";
$to = $roster->email;
$headers = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= 'From: GULP <gulp@ultimaker.com>' . "\r\n";
//$mail = new PHPMailer;
//$mail->isSMTP();
//$mail->Host = 'smtp.online.nl';
//$mail->From = 'gulp@ultimaker.com';
//$mail->FromName = 'GULP';
//$mail->addAddress($to);
//$mail->isHTML(true);
//$mail->Subject = $subject;
//$mail->Body = $c;

echo "<H1>".$subject."</H1>";
echo $c;
echo "<H1>----------</H1>";
if ($roster->confirmed)
{
	echo "Already send";
}else{
	echo "Sending...<br>";
	//if (!$mail->send())
	if (!mail($to, $subject, $c, $headers))
	//if (false)
	{
		echo "Error sending mail.";
	}else{
		echo "Done.<br>";
		dbInsert("UPDATE roster SET confirmed = 1 WHERE `date` = '$roster->date'");
	}
}
?>
