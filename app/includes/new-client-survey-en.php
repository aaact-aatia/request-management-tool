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

$_SESSION['lang'] = 'en';

// Security check
require('includes/loggedincheck-en.php');

$pageTitle = 'Latest survey results';
$pageDescription = '';

include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>
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
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		<?php include 'includes/template/footer.php'; ?>
		<?php include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>