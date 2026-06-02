<?php
// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Grab HTTPS check
require('includes/httpscheck.php');

// Grab MySQL connection
require('sql.php');
/** @var mysqli $link */

// Security check
require('includes/loggedincheck-en.php');
?>
<!DOCTYPE html>
<!--[if lt IE 9]><html class="no-js lt-ie9" lang="en" dir="ltr"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en" dir="ltr"><!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<!-- Web Experience Toolkit (WET) / Boîte à outils de l'expérience Web (BOEW) wet-boew.github.io/wet-boew/License-en.html / wet-boew.github.io/wet-boew/Licence-fr.html -->
		<title>Client satisfaction surveys - Request Management Tool - IT Accessibility Office</title>
		<meta content="width=device-width,initial-scale=1" name="viewport">
		<!-- Meta data -->
		<meta name="description" content="">
		<!-- Meta data-->
		<?php include 'includes/refTop.php';?>
		
	</head>
	<body vocab="https://schema.org/" typeof="WebPage">
		<div id="def-top">
		</div>
		<?php include 'includes/appTop.php';?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont">Latest survey results</h1>
			
			<?php
			// Construct SQL statement
			$sql = "SELECT * FROM tblcss WHERE status = '1' ORDER BY requestid DESC LIMIT 2000";
			//echo $sql;
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables table table-striped table-hover table-sm" data-wb-tables='{"paging": true,"pageLength": 50, "columnDefs": [{ "type": "html-num", "targets": 0 }]}'>
				<thead>
					<tr>
						<th>Request #</th>
						<th>Title</th>
						<th>Over-all satisfaction</th>
						<th>Response time</th>
						<th>Comments</th>
						<th style="display:none;">Catalogue Name</th>
						<th style="display:none;">Service Name</th>
					</tr>
				</thead>
				<tbody>
				<?php
				while($row = mysqli_fetch_array($result)){
					// Check if clientlname or clientfname is not empty
					$requestid = $row['requestid'];
					$overall = $row['overall'];
					$response = $row['response'];
					$comments = $row['comments'];
										
					// Get request title
					$result2 = mysqli_query($link, "SELECT requestid,title,catalogueid,serviceid FROM tbltriage WHERE id = '$requestid'");
					$row2 = mysqli_fetch_array($result2);
					$requestidnum = $row2[0];
					$title = $row2[1];
                    $catalog_id = $row[2];
                    $service_id = $row[3];
                    
                    $result2 = mysqli_query($link, "SELECT nameen FROM tblcatalogue WHERE id = '$catalog_id'");
					$row2 = mysqli_fetch_array($result2);
                    $catalog_name = $row2[0];
                    
                    
                    $result2 = mysqli_query($link, "SELECT nameen FROM tblservices WHERE id = '$service_id'");
					$row2 = mysqli_fetch_array($result2);
                    $service_name = $row2[0];
				?>
					<tr>
						<td><a href="viewrequest-en.php?erid=<?php echo base64_encode($requestid);?>">a11y-<?php echo $requestidnum ?> <span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv">details</span></a></td>
						<td><?php echo $title ?></td>
						<td><?php echo $overall ?>/10</td>
						<td><?php echo $response ?>/10</td>
						<td><?php echo $comments ?></td>
						<td style="display:none;"><?php echo $catalog_name ?></td>
						<td style="display:none;"><?php echo $service_name ?></td>
					</tr>
			<?php } ?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong>No surveys available!</strong></p>
			<?php } ?>
			
			<div id="def-preFooter">
			</div>
			<?php include 'includes/preFooter.php';?>
		</main>
		<div id="def-footer">
		</div>
		<?php include 'includes/appFooter.php';?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>