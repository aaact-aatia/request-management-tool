<?php
// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Grab MySQL connection
require('sql.php');
/** @var mysqli $link */

require('includes/calculate-bdays.php');

// grab the list of active requests
$sql = "SELECT * FROM tbltriage WHERE (status='1' OR catalogueid='2' OR catalogueid='3' OR catalogueid='4')";
//echo $sql;

$result = mysqli_query($link,$sql);
//List it
// values for prio
// Major Projects
$enterpriseProject = 20;
$sponsoredProject = 20;
$otherProject = 5;

// Audience Scores
$externalAudience = 10;
$hybridAudience = 10;
$internalAudience = 5; // and more than 100 users

// Conformance Report scores
$vpatWASCert = 10;
$vpatThirdParty = 5;

//Relevance scores Need complex check  for these
$ocmcRelevance = 20;
$reauditRelevance = 10;
$newAuditRelevance = 5;

// Control over code scores
$devInHouseCode = 10;
$cotsCode = 5;

// Maturity level scores
$level4 = 20;
$level3 = 15;
$level2 = 10;
$level1 = 5;

// timeline scores
$withinSLA = 5;
$underSLA = -10;
$underWithSignature = 20;

// Math time

$sql = "SELECT * FROM tbltriage WHERE status = 1 AND (statusid=1 OR statusid=3 OR statusid=5 OR statusid=6 OR statusid=7 OR statusid=10)";

$result = mysqli_query($link,$sql);
if(mysqli_num_rows($result)>0){
	while($row = mysqli_fetch_array($result)){
		// Switch Statements
		$prioScore = 0;
		
		switch ($row["project_id"]){
			case 1:
				$prioScore += $enterpriseProject;
				break;
			case 2:
				$prioScore += $sponsoredProject;
				break;
			case 3:
				$prioScore += $otherProject;
				break;
			default:
				$prioScore += 0;
		}		
		
		switch ($row["audience_id"]){
			case 1:
				$prioScore += $externalAudience;
				break;
			case 2:
				$prioScore += $hybridAudience;
				break;
			case 3:
				if ($row["triage_population"] == 3 || $row["triage_population"] = 4){ // 3 is 100-1000 4 is 1000+
					$prioScore += $internalAudience;
				}
				else{
					$prioScore += 0;
				}				
				break;
			default:
				$prioScore += 0;
		}		
		
		switch ($row["conformance_id"]){
			case 1:
				$prioScore += $vpatWASCert;
				break;
			case 2:
				$prioScore += $vpatThirdParty;
				break;
			default:
				$prioScore += 0;
		}

		switch ($row["triage_maturity"]){
			case 1:
				$prioScore += $level1;
				break;
			case 2:
				$prioScore += $level2;
				break;
			case 3:
				$prioScore += $level3;
				break;
			case 4:
				$prioScore += $level4;
				break;
			default:
				$prioScore += 0;
		}

		switch ($row["tech_id"]){
			case 1:
				$prioScore += $devInHouseCode;
				break;
			case 2:
				$prioScore += $cotsCode;
				break;
			default:
				$prioScore += 0;
		}

		$datereqed = $row["date_required"];
		$cata_id = $row["catalogue_id"];
		$slatimer = 0;

		switch ($cata_id){
			case 1:
				$slatimer = 5;
				break;
			case 2:
				$slatimer = 10;
				break;
			case 3:
				$slatimer = 5;
				break;
			case 4:
				$slatimer = 14;
				break;
			case 5:
				$slatimer = 10;
				break;
			case 6:
				$slatimer = 5;
				break;
			case 7:
				$slatimer = 5;
				break;
			case 8:
				$slatimer = 30;
				break;
			case 9:
				$slatimer = 5;
				break;
			case 10:
				$slatimer = 5;
				break;
			case 11:
				$slatimer = 5;
				break;
			case 12:
				$slatimer = 5;
				break;
			case 13:
				$slatimer = 5;
				break;
			case 14:
				$slatimer = 15;
				break;
			default:
				$slatimer = 0;
		}

		$datereceived = $row["date_recieved"];

		// date rec + slatimer and check if it was less or greater than required date

		// if statement for reaudit
		if ($row[""]){
			if($row["triage_management"] == 1){

			}
			else{

			}
		}
		else if ($row[""]){
			$prioScore += $underSLA;
		}
		else{
			$prioScore += 0;
		}
		$request_id = $row["id"];


		$sql2 = "UPDATE `tbltriage` SET `priority_score` = '$prioScore' WHERE `id` = '$request_id'";
	}
}


// Now redirect
header("location:/index-en.php?status=priorityupdated"); 
exit();

// Close connection
mysqli_close($link);
?>