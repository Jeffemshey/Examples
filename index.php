<!-- Jeff Emshey 3/14/2017 -->
<link rel="stylesheet" type="text/css" href="stylesheet.css">

<!--JS Function Block Begin-->
<script><!--Ajax, delete record: params goto: delEntry.php-->
function delEntry(recordID) 
{
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function()
	{
        if (this.readyState == 4 && this.status == 200) 
		{
            location.href = 'http://localhost:8080/Foodbank/Calendar/';
        }
    };
	xmlhttp.open("GET", "delEntry.php?q=" + recordID, true);
	xmlhttp.send();
}
</script>

<script><!--Ajax, cross out record: params goto: crossEntry.php-->
function crossEntry(recordID)
 {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function()
	{
		if (this.readyState == 4 && this.status == 200) 
		{
			location.href = 'http://localhost:8080/Foodbank/Calendar/';     
        }
    };
	xmlhttp.open("GET", "crossEntry.php?q=" + recordID, true);
	xmlhttp.send();
}
</script>

<script><!--Ajax, change month: params goto: changeMonth.php-->
function changeMonth(month, buttonName)
{
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function()
	{
        if (this.readyState == 4 && this.status == 200)
		{
			location.href = 'http://localhost:8080/Foodbank/Calendar/';
        }
    };
	xmlhttp.open("GET", "changeMonth.php?q=" + month + "&btn=" + buttonName, true);
	xmlhttp.send();
}
</script>

<script><!--Ajax, change calendar department: params goto: changeCal.php-->
function changeCal(buttonName)
{
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() 
	{
        if (this.readyState == 4 && this.status == 200)
		{
			location.href = 'http://localhost:8080/Foodbank/Calendar/';
                
        }
    };
	xmlhttp.open("GET", "changeCal.php?q=" + buttonName, true);
	xmlhttp.send();
}
</script>
<!--JS Function Block End-->

<!--Generic Navigation-->
<body class="wrapper">
	<h1>
		<a href="/Foodbank/Admin/home.php">
			<img id="logo" src="/Foodbank/images/logo.gif">
		</a>
		<a href="/Foodbank/Calendar/">Calendar
	</h1></a>
	<div id="topRightNav">
	<a href="/Foodbank/TimeClock/index.php">Time Clock</a>
		<a href="/Foodbank/Admin/logout.php" class="loginButton">Logout</a>
	</div>

	<div id="mainNav">
		<ul>
			<li><a href="/Foodbank/Admin/home.php">Home</a></li>
			<li  class="active"><a href="/Foodbank/Calendar/">Calendar</a></li>
			<li>
				<a>Manage Volunteers</a>
				<ul class="dropdown">
					<li><a href="/Foodbank/Volunteer/newVolunteer.php">New Volunteer</a></li>
					<li><a href="/Foodbank/Volunteer/updateVolunteer.php">Update Volunteer</a></li>
					<li><a href="/Foodbank/Volunteer/UpdateVolunteerTime.php">Update Time Entries</a></li>
				</ul>
			</li>
			<li><a href="/Foodbank/Reports/reports.php">Reports</a></li>
		</ul>
	</div>
	<br>
<?php
session_start();

if(!isset($_SESSION['user_id'])) //Ensure the admin is logged in
{
    header('Location: /Foodbank/Admin/loginRequired.php');
}

date_default_timezone_set('America/Edmonton'); //Set the timezone for the Calendar

//New entry function, reset navigation variables on current page
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$_SESSION['calDept'] = null;
	$_SESSION['currMonth'] = null;
	header('Location: http://localhost:8080/Foodbank/Calendar/newEntry.php');
}

//Set the default calendar department
if(!isset($_SESSION['calDept']))
{
	$_SESSION['calDept'] = "Kitchen";
}

//Server variables
//HIDDEN

//Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

//Arrays used to translate data to appropiate day values and name values gotten by the getdate function.
$daysOfTheWeek = array("Sunday" => "0", "Monday" => "1", "Tuesday" => "2", "Wednesday" => "3", "Thursday" => "4", "Friday" => "5", "Saturday" => "6");
$daysOfTheWeekRev = array("0" => "Sunday", "1" => "Monday", "2" => "Tuesday", "3" => "Wednesday", "4" => "Thursday", "5" => "Friday", "6" => "Saturday");

//Set basic date variables as a starting point
$currDate = getdate();
if(!isset($_SESSION['currMonth']))
{
	$_SESSION['currMonth'] = $month = $currDate['mon'];
}
$month = $_SESSION['currMonth'];
$year = $currDate['year'];
$currDay = $currDate['weekday'];

//Get the number of days for the specified month
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year); 

//Create an array that will hold and format all days for the specified month, such that they are represented as they are stored in the database.
$dateArray = array();
for($i = 1; $i <= $daysInMonth; $i++)
{
$tempString = "{$year}";
	
	if($month < 10)
	{
		$tempString .= "-0{$month}";
	}
	else
	{
		$tempString .= "-{$month}";
	}
	if($i < 10)
	{
		$tempString .= "-0{$i}";
	}
	else
	{
		$tempString .= "-{$i}";
	}
	array_push($dateArray, $tempString);
}

//Variables for calendar structure - requires clean up
$startOfMonth = unixtojd(mktime(0, 0, 0, $month, 2, $year));
$test = (cal_from_jd($startOfMonth, CAL_GREGORIAN));
$temp = $daysOfTheWeek[$test['dayname']];
$dayOne = "";
$days = 1;
$entryID = 1;

//Get all entries that are scheduled for that month + calendar department
$sql = "SELECT calendar_entry_id, volunteer_id, calendar_date, calendar_dept, calendar_shift, crossed_out FROM calendar_entry WHERE calendar_dept='".$_SESSION['calDept'] . "'";
$result = $conn->query($sql);

//Variables used to keep all data and records in the Calendar in sync.
$k = 0;
$o = 0;
$scheduledDates= array();
$calendarEntryIDs = array();
$calendarFName = array();
$calendarLName = array();
$calendarVolIDs = array();
$cross_out = array();
$shiftArray = array();

while($row = $result->fetch_assoc())
{
	$findVolunteer = "Select volunteer_fname, volunteer_lname FROM volunteer where volunteer_id='{$row['volunteer_id']}'";
	$findVolunteerResult = $conn->query($findVolunteer);
	$findVolunteerFetch = $findVolunteerResult->fetch_assoc();
	
	array_push($scheduledDates, $row['calendar_date']);
	array_push($calendarEntryIDs, $row['calendar_entry_id']);
	array_push($calendarVolIDs, $row['volunteer_id']);
	array_push($calendarFName, $findVolunteerFetch['volunteer_fname']);
	array_push($calendarLName, $findVolunteerFetch['volunteer_lname']);
	array_push($cross_out, $row['crossed_out']);
	array_push($shiftArray, $row['calendar_shift']);
}
?>

<!--Calendar Department Buttons-->
<center>
<div style='width: 90%; height:35px; overflow: hidden;'>
	<button style='float: right;' <?php if($_SESSION['calDept'] == "Kitchen"){echo "disabled";} ?> onclick='changeCal("Kitchen")'>Kitchen</button>
	<button style='float: right;'<?php if($_SESSION['calDept'] == "Volunteer Intake Coordinator"){echo "disabled";} ?> onclick='changeCal("Volunteer Intake Coordinator")'>Volunteer Intake Coordinator</button>
	<button style='float: right;'<?php if($_SESSION['calDept'] == "Front"){echo "disabled";} ?> onclick='changeCal("Front")'>Front</button>
	<button style='float: right;'<?php if($_SESSION['calDept'] == "Warehouse"){echo "disabled";} ?> onclick='changeCal("Warehouse")'>Warehouse</button>
	<br>
</div>
</center>

<?php
//Date variables for building calendar
$monthNum  = $_SESSION['currMonth'];
$dateObj   = DateTime::createFromFormat('!m', $monthNum);
$monthName = $dateObj->format('F');
$temp2 = $monthName;
$firstOcc = true;

//Calendar header
echo "<center>";
echo "<table>";
echo "<caption style='border: solid 1px; background: white; margin-bottom: 10px; font-size: 40px; font-weight: bold;'>"; ?> <button style='margin: 10px 0px 10px 10px; float: left;' onclick='changeMonth(<?php echo $_SESSION['currMonth']; ?>, "back")' <?php if($_SESSION['currMonth'] == 1) echo "disabled"; ?><?php echo "><</button>";?> <button style='margin: 10px 10px 10px 0px; float: left;' onclick='changeMonth(<?php echo $_SESSION['currMonth']; ?>, "forward")' <?php if($_SESSION['currMonth'] == 12) echo "disabled"; ?>>></button><form method='post' action='' style=" margin: 10px 10px 0px 10px; float: right;">
	<button id='newEntry' name='newEntry'>New Entry</button>
</form><?php echo "{$temp2} {$year}"; ?>  <?php echo "</caption>";
echo "</table>";

//Create the column headers: monday, tuesday..
for($i = 0; $i < 7; $i++)
	{
		echo "<div class='tableRowDiv' style='text-align: center; font-size: 20px;'><b>{$daysOfTheWeekRev[$i]}</b></div>";
	}
echo "<br clear='both'>";

//Create the cells for the calendar with appropiate day values and entries
for($j = 0; $j < 6; $j++) //each row
{
	for($i = 0; $i < 7; $i++) //gets 7 days
	{
		if($j == 0 && $i < $daysOfTheWeek[$test['dayname']])
		{	
			echo "<div class='tableCellDiv'>&nbsp;<hr></div>"; //that contain nothing if those cells don't line up with days in the month
		}
			else
			{
				if($days <= $daysInMonth) //or contains the day value if it does
				{
					echo "<div class='tableCellDiv'>";
					if($days == date("j") && $_SESSION['currMonth'] == date("n"))
					{
						echo"<b>";
						echo "{$days}";
						echo "<hr>";
						echo"</b>";
					}
					else
					{
						echo "{$days}";
						echo "<hr>";
					}
						echo "<div class='morningShift'>"; //grab all entries for the morning shift
						foreach($scheduledDates as $index => $scheduledDay)
						{
							if($scheduledDay == $dateArray[$days -1] && $shiftArray[$index] == 'Morning')
							{
								if($firstOcc == true)
								{
									echo "<center>Morning</center>";
									$firstOcc = false;
								}
								echo "<div class='div2' id='{$calendarEntryIDs[$index]}'><button style='padding: 0px 0px 0px 0px; margin: 0px 0px 0px 5px;'onclick='delEntry({$calendarEntryIDs[$index]})'>X</button><button style='padding: 0px 0px 0px 0px; margin: 0px 5px 0px 0px;' onclick='crossEntry({$calendarEntryIDs[$index]})'><strike>abc</strike></button><a href='http://localhost:8080/Foodbank/Calendar/updateEntry.php?id={$calendarEntryIDs[$index]}'"; if($cross_out[$index] == 1){echo" style='text-decoration: line-through';";} echo">{$calendarFName[$index]} {$calendarLName[$index]} : {$calendarVolIDs[$index]}</a></div>";
								$k++;
							}
						}
					echo "</div>";
					$firstOcc = true;
					echo "<div class='afternoonShift'>"; //grab all entries for the afternoon shift
						foreach($scheduledDates as $index => $scheduledDay)
						{
							if($scheduledDay == $dateArray[$days -1] && $shiftArray[$index] == 'Afternoon')
							{
								if($firstOcc == true)
								{
									echo "<hr><center>Afternoon</center>";
									$firstOcc = false;
								};
								echo "<div class='div2' id='{$calendarEntryIDs[$index]}'><button style='padding: 0px 0px 0px 0px; margin: 0px 0px 0px 5px;'onclick='delEntry({$calendarEntryIDs[$index]})'>X</button><button style='padding: 0px 0px 0px 0px; margin: 0px 5px 0px 0px;' onclick='crossEntry({$calendarEntryIDs[$index]})'><strike>abc</strike></button><a href='http://localhost:8080/Foodbank/Calendar/updateEntry.php?id={$calendarEntryIDs[$index]}'"; if($cross_out[$index] == 1){echo" style='text-decoration: line-through';";} echo">{$calendarFName[$index]} {$calendarLName[$index]} : {$calendarVolIDs[$index]}</a></div>";
								$o++;
							}
						}
						echo "</div>";
					echo "</div>";					
					$firstOcc = true;				
					$days++;
				}
				else
					echo "<div class='tableCellDiv'>&nbsp;<hr></div>";
			}
	}
	echo "<br clear='both'>";
}
echo "</center>";
$conn->close();
?>
</body>

<!--CSS used to test this format-->
<style type='text/css'>
table
{
	font-size: 14px;
	border-collapse:collapse;
	white-space: nowrap;
	width: 100%;
	table-layout:fixed;
}


td
{
	display:table-cell;
	background: white;
	border:1px solid #000; 
	width: 150px;
	height: 150px;
    overflow: auto;
}

button
{
	margin: 0px 0px 0px 0px;
	font-size: 12px;
}
.div2
{
	width: 100%;
	text-align: left;
	height: 30px;
	white-space: nowrap;
}
.tableCellDiv
{
	width: 14.12%;
	height: 135px;
    margin: 0;
    padding: 0;
	border: 1px solid;
	overflow-x: auto;
	float: left;
	font-size: 14px;
}
.tableRowDiv
{
	width: 14.12%;
	height: 30px;
    margin: 0;
    padding: 0;
	border: 1px solid;
	overflow: auto;
	float: left;
}

</style>
