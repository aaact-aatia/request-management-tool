<?php
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Determine language
$lang = $_SESSION['lang'] ?? 'en';
$translations = require(__DIR__ . '/../lang/' . $lang . '.php');
$nameField = $lang === 'fr' ? 'namefr' : 'nameen';

// Grab MySQL connection
require('../sql.php');

// Include file for calculating business days
require('calculate-bdays.php');
require('sla-calculator.php');
// Now first get the ID
$triageid = $_GET['id'];

// Construct SQL statement
$sql = "SELECT * FROM tbltriage WHERE id='$triageid'";

$result = mysqli_query($link,$sql);
//List it
if(mysqli_num_rows($result)>0){
	while($row = mysqli_fetch_array($result)){
		
		// We need to calculate if ticket is close to SLA (or on the date) or if past SLA and grab the names
		$subserviceid = $row['subserviceid'];
		$serviceid = $row['serviceid'];
		$catalogueid = $row['catalogueid'];
		
		$sla = 0;
		$overdue = false;
		$closedue = false;
							
		if ($subserviceid!=0) {
			// Sub-service is not empty so grab the name
			$result2 = mysqli_query($link, "SELECT $nameField,sds FROM tblsubservices WHERE id = '$subserviceid'");
			$row2 = mysqli_fetch_array($result2);
			$subservicename = $row2[0];
			$sla = $row2[1];
		}
		
		if ($serviceid!=0) {
			// Sub-service is not empty so grab the name
			$result2 = mysqli_query($link, "SELECT $nameField,sds FROM tblservices WHERE id = '$serviceid'");
			$row2 = mysqli_fetch_array($result2);
			$servicename = $row2[0];
			if ($sla==0) {
				$sla = $row2[1];
			}
		}
		
		if ($catalogueid!=0) {
			// Sub-service is not empty so grab the name
			$result2 = mysqli_query($link, "SELECT $nameField FROM tblcatalogue WHERE id = '$catalogueid'");
			$row2 = mysqli_fetch_array($result2);
			$cataloguename = $row2[0];
		}
		
		// Grab the date it was received
		$datereceived = $row['datereceived'];
		
		// Calculate the business days
		//$cBdays = getWorkingDays($datereceived,date('Y-m-d'),$holidays);
		$cBdays = calculateSLA($link, $row['requestid'], $datereceived);

		$sla2 = $sla - 2;
		// Now check if the SLA is close
		if ($cBdays > $sla) {
			$overdue = true;
		}
		if ($cBdays == $sla) {
			$closedue = true;
		}
		
		if ($cBdays >= $sla2) {
			$closedue = true;
		}
		
		// Get labels
		$sla_past = $translations['view_details_sla_past'] ?? ($lang === 'fr' ? 'La demande est passée SLA' : 'Request is past SLA');
		$sla_close = $translations['view_details_sla_close'] ?? ($lang === 'fr' ? 'La demande est proche du SLA' : 'Request is close to SLA');
?>
<section id="filter-id" class="modal-dialog modal-lg modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $translations['view_details_title'] ?? ($lang === 'fr' ? 'Détails' : 'Details'); ?> - a11y-<?php echo $row['requestid'] ?><?php if ($overdue) { ?> - <span class="glyphicon glyphicon-warning-sign"></span> <?php echo $sla_past; ?><?php } elseif ($closedue) { ?> - <span class="glyphicon glyphicon-warning-sign"></span> <?php echo $sla_close; ?><?php } ?></h2>
	</header>
	<div class="modal-body">
		<dl class="colcount-sm-2">
			<dt><?php echo $translations['view_details_title_label'] ?? ($lang === 'fr' ? 'Titre' : 'Title'); ?></dt>
			<dd><?php echo $row['title'] ?></dd>
			<?php if ($row['clientlname']!="") { ?>
			<dt><?php echo $translations['view_details_lname'] ?? ($lang === 'fr' ? 'Nom' : 'Last name'); ?></dt>			
			<dd><?php echo $row['clientlname'] ?></dd>
			<?php } ?>
			<?php if ($row['clientfname']!="") { ?>
			<dt><?php echo $translations['view_details_fname'] ?? ($lang === 'fr' ? 'Prénom' : 'First name'); ?></dt>
			<dd><?php echo $row['clientfname'] ?></dd>
			<?php } ?>
			<?php if ($row['clientemail']!="") { ?>
			<dt><?php echo $translations['view_details_email'] ?? ($lang === 'fr' ? 'Courriel client' : 'Client email'); ?></dt>
			<dd><?php echo $row['clientemail'] ?></dd>
			<?php } ?>
			<?php if ($row['clientphone']!="") { ?>
			<dt><?php echo $translations['view_details_phone'] ?? ($lang === 'fr' ? 'Numéro de téléphone client' : 'Client phone #'); ?></dt>
			<dd><?php echo $row['clientphone'] ?></dd>
			<?php } ?>
			<?php if ($row['productid']!=0) { ?>
			<dt><?php echo $translations['view_details_product'] ?? ($lang === 'fr' ? 'Produit' : 'Product'); ?></dt>
			<?php
			// Grab the product name
			$productid = $row['productid'];
			$result2 = mysqli_query($link, "SELECT $nameField FROM tblproducts WHERE id = '$productid'");
			$row2 = mysqli_fetch_array($result2);
			$productname = $row2[0];
			?>
			<dd><?php echo $productname ?></dd>
			<?php } ?>
			<?php
			// Grab the source name
			$sourceid = $row['sourceid'];
			$result2 = mysqli_query($link, "SELECT $nameField FROM tblsources WHERE id = '$sourceid'");
			$row2 = mysqli_fetch_array($result2);
			$sourcename = $row2[0];
			?>
			<dt><?php echo $translations['view_details_source'] ?? ($lang === 'fr' ? 'Source' : 'Source'); ?></dt>
			<dd><?php echo $sourcename ?></dd>
			<dt><?php echo $translations['view_details_date_received'] ?? ($lang === 'fr' ? 'Date reçu' : 'Date received'); ?></dt>
			<dd><?php echo $row['datereceived'] ?></dd>
			<?php if ($row['dateupdated']!="") { ?>
			<dt><?php echo $translations['view_details_date_updated'] ?? ($lang === 'fr' ? 'Date mise à jour' : 'Date updated'); ?></dt>
			<dd><?php echo $row['dateupdated'] ?></dd>
			<?php } ?>
			<?php if ($row['daterequired']!="") { ?>
			<dt><?php echo $translations['view_details_date_required'] ?? ($lang === 'fr' ? 'Date requis' : 'Date required'); ?></dt>
			<dd><?php echo $row['daterequired'] ?></dd>
			<?php } ?>
			<?php if ($row['dateresolved']!="") { ?>
			<dt><?php echo $translations['view_details_date_resolved'] ?? ($lang === 'fr' ? 'Date résolue' : 'Date resolved'); ?></dt>
			<dd><?php echo $row['dateresolved'] ?></dd>
			<?php } ?>
			<?php
			// Grab the source name
			$statusid = $row['statusid'];
			$result2 = mysqli_query($link, "SELECT $nameField FROM tblstatus WHERE id = '$statusid'");
			$row2 = mysqli_fetch_array($result2);
			$statusname = $row2[0];
			?>
			<dt><?php echo $translations['view_details_status'] ?? ($lang === 'fr' ? 'Statut' : 'Status'); ?></dt>
			<dd><?php echo $statusname ?></dd>
			<?php if ($row['nsd']!=0) { ?>
			<dt><?php echo $translations['view_details_nsd'] ?? ($lang === 'fr' ? '# billet NSD' : 'NSD ticket #'); ?></dt>
			<dd><a href="http://arweb.prv/SRMIS.htm?Ticket=<?php echo $row['nsd'];?>" target="_blank"><?php echo $row['nsd'];?><span class="glyphicon glyphicon-new-window"></span><span class="wb-inv"> <?php echo $translations['view_details_new_window'] ?? ($lang === 'fr' ? 'détails (s\'ouvrira dans une nouvelle fenêtre)' : 'details (will open in a new window)'); ?></span></a></dd>
			<?php } ?>
			<?php 
			if ($catalogueid!=0) {
			?>
			<dt><?php echo $translations['view_details_catalogue'] ?? ($lang === 'fr' ? 'Nom du catalogue' : 'Catalogue name'); ?></dt>
			<dd><?php echo $cataloguename ?></dd>
			<?php
			}
			if ($serviceid!=0) {
			?>
			<dt><?php echo $translations['view_details_service'] ?? ($lang === 'fr' ? 'Nom du service' : 'Service name'); ?></dt>
			<dd><?php echo $servicename ?></dd>
			<?php
			}
			if ($subserviceid!=0) {
			?>
			<dt><?php echo $translations['view_details_subservice'] ?? ($lang === 'fr' ? 'Nom du sous-service' : 'Sub-service name'); ?></dt>
			<dd><?php echo $subservicename ?></dd>
			<?php
			}
			?>
		</dl>
	</div>
</section>
<?php
	}
} else { 
// Wrong ID so display an error message
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $translations['error_heading'] ?? ($lang === 'fr' ? 'Oups, quelque chose s\'est mal passé!' : 'Oops something went wrong!'); ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo $translations['error_message'] ?? ($lang === 'fr' ? 'Désolé, une erreur s\'est produite avec votre demande, veuillez réessayer!' : 'Sorry something went wrong with your request, please try again!'); ?></p>
	</div>
</section>
<?php
}
// Close connection
mysqli_close($link);
?>
