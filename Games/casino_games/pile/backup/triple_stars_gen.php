<?PHP

error_reporting(0);
require('../scripts/connect.php');
///////////////////// functions //////////////////////
function calWinLimit(){
	$locID=LOCID;
	$userid=USERID;
	$type=$locID."51";


list($coef) = mysql_fetch_row(mysql_query("SELECT coef FROM settings WHERE `type`='$type'"));
list($resetdate) = mysql_fetch_row(mysql_query("SELECT date FROM transactions WHERE gameid=0 AND type='co' AND comments='$userid' ORDER BY transid ASC LIMIT 1"));


	list($balance) = mysql_fetch_row(mysql_query("SELECT SUM(amount) FROM `transactions` WHERE `date`>$resetdate AND ((type='w' AND userid='$userid') OR (type='l' AND userid='$userid') OR (type='co' AND comments='$userid'))"));
	$balance=-$balance;
	$maxwin=$balance*$coef/100;
	if($maxwin<0){ $maxwin=0; }else{
	//Force Lost
	$chance=rand(0,99);
	if ($chance > $coef) {
		$maxwin=0;
	}
	}

	return $maxwin;
}
//
function Gen(){
	$res = rand(0, 100);
	if ($res < 10) { $n = 6; }
	if ( ($res >= 10) && ($res < 25) ) { $n = 5; }
	if ( ($res >= 25) && ($res < 45) ) { $n = 4; }
	if ( ($res >= 40) && ($res < 60) ) { $n = 3; }
	if ( ($res >= 60) && ($res < 80) ) { $n = 2; }
	if ($res >= 80 && ($res < 85)) { $n = 1; }
	if ($res >= 85) { $n = 0; }
	return $n;
}
//

function countLine($a, $b, $c) {
	global $win, $bet;
	if (($a == $b) && ($b == $c)) {
		setWinByNum($a);
	} elseif ((($a == $b) && ($c == 1) || ($b == $c) && ($a == 1)) && ($b != 0)) {
		// wild - any 2 eq symbols
		setWinByNum($b);
	} elseif (($a == $c) && ($b == 1)) {
		// wild - any 2 eq symbols	
		setWinByNum($c);
	} elseif (($a == $c) && ($a == 1)) {
		// wild - any - wild
		if ($b != 0) {
			setWinByNum($b);
		} else {
			setWinByNum(7);
		}
		setWinByNum($b);
	} elseif (($b == $c) && ($b == 1)) {
		// any - wild - wild
		if ($a != 0) {
			setWinByNum($a);
		} else {
			setWinByNum(7);
		}
	} elseif (($b == $a) && ($b == 1)) {
		// wild - wild - any
		if ($c != 0) {
			setWinByNum($c);
		} else {
			setWinByNum(7);
		}
	} else {
		if ($a > 0 && $b > 0 && $c > 0) {
			setWinByNum(8);
		//Any one wild
		} else if ($a == 1 || $b == 1 || $c == 1) {
			setWinByNum(9);
		}
	}
}

function setWinByNum($num) {
	global $win, $bet;
	switch ($num) {
		case 1: $win = 1500 * $bet; break;
		case 2: $win = 150 * $bet; break;
		case 3: $win = 80 * $bet; break;
		case 4: $win = 40 * $bet; break;
		case 5: $win = 25 * $bet; break;
		case 6: $win = 20 * $bet; break;
		case 7: $win = 10 * $bet; break;
		case 8: $win = 5 * $bet; break;
		case 9: $win = 2 * $bet; break;
	}
}


///////////////////// MAIN //////////////////////
if( !isset($_POST["b"]) ){ exit; }
if( isset($_POST["uid"]) ){ $sid=$_POST["uid"]; }else{ exit; }
$result=mysql_query("select `userid` from `session` where `sid`='$sid'");
$userid=mysql_result($result, 0, "userid");
$bet=$_POST["b"];

$total_bet = sprintf ("%01.2f", $bet); 

// Real Time check for user balance
list($user_balance) = mysql_fetch_row(mysql_query("SELECT SUM(amount) FROM transactions WHERE userid='$userid'"));
$user_balance = sprintf ("%01.2f", $user_balance); 
if ($user_balance < $total_bet) { exit; }

//Location ID
define("LOCID", substr($userid,0,3));
define("USERID",$userid);

// Update User
//mysql_query("update `users` set `lplay_date`=".time()." where `userid`=$userid");
mysql_query("update `session` set `time`=".time()." where `userid`='$userid'");

//Genuine User Check
//if ($userid == 0) { exit; }

// start game;
do{
	$win=0;
	$n1=Gen(); 
	$n2=Gen();
	$n3=Gen();
	$n4=Gen();
	$n5=Gen();
	$n6=Gen();
	$n7=Gen();
	$n8=Gen();
	$n9=Gen();

//remove blanks from 1st and 3rd lines
	if ($n1==0){ $n1=rand(1, 6);}
	if ($n2==0){ $n2=rand(1, 6);}
	if ($n3==0){ $n3=rand(1, 6);}
	if ($n7==0){ $n7=rand(1, 6);}
	if ($n8==0){ $n8=rand(1, 6);}
	if ($n9==0){ $n9=rand(1, 6);}


// Test mode. Disable!
/*
	$n4=0;
	$n5=0;
	$n6=0;
*/
// Test mode. Disable!

	countLine($n4, $n5, $n6);
	$maxwin=calWinLimit();
}while($win>$maxwin);

//update games and transactions
$win_limit = sprintf ("%01.2f", $maxwin); 
$diff=$win-$bet;
$diff = sprintf ("%01.2f", $diff);
if($diff>=0){$rg="w";}else{$rg="l";}
	//auto cashout
	$co_amount=$bet/10;
	$co_amount = sprintf ("%01.2f", $co_amount);
//	$co_amount = -$co_amount;
	$tm=time();
	mysql_query("insert into `transactions` values('', '0', '0', '$tm', 'co', '$co_amount', '$userid')");

$tm=time();
mysql_query("insert into `games` values('', '$userid', '$tm', '$bet', '-1', '$rg', '$diff', '51')");
$gameid=mysql_insert_id();
mysql_query("insert into `transactions` values('', '$userid', '$gameid', '$tm', '$rg', '$diff', '$win_limit')");

//user balance
//Optimized
list($user_balance) = mysql_fetch_row(mysql_query("SELECT SUM(amount) FROM transactions WHERE userid='$userid'"));
$user_balance = sprintf ("%01.2f", $user_balance); 

// send answer
$answer="&ans=res&ub=".$user_balance."&wn=".$win."&n1=".$n1."&n2=".$n2."&n3=".$n3."&n4=".$n4."&n5=".$n5."&n6=".$n6."&n7=".$n7."&n8=".$n8."&n9=".$n9;
echo $answer;
?>