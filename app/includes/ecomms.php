<?php
// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Determine language
$lang = $_SESSION['lang'] ?? 'en';
$translations = require(__DIR__ . '/../lang/' . $lang . '.php');

// Grab MySQL connection
require('../sql.php');

// Now first get the ID
$triageid = $_GET['id'];
if (!empty($triageid)) {
?>
<section id="filter-id" class="modal-dialog modal-lg modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $translations['ecomms_title'] ?? ($lang === 'fr' ? 'Journal des communications' : 'Communications log'); ?></h2>
	</header>
	<div class="modal-body">		
		<?php
		// Check if the account is admin level to show this option 
		if ($_SESSION['atype']==1 || $_SESSION['atype']==2 || $_SESSION['atype']==3 || $_SESSION['atype']==4 || $_SESSION['atype'] == '6') {

		// Construct SQL statement
		$sql2 = "SELECT * FROM tbladminlog WHERE triageid = '$triageid' AND status = '1' ORDER BY id DESC";
		//echo $sql;
		
		$result2 = mysqli_query($link,$sql2);
		//List it
		if(mysqli_num_rows($result2)>0) {
		?>
		<dl>
			<?php
			while($row2 = mysqli_fetch_array($result2)){
				// Check if clientlname or clientfname is not empty
				$dateadded = $row2['dateadded'];
				$anotes = $row2['notes'];
				$annotes = nl2br(htmlspecialchars($anotes));
				$creatorid = $row2['creatorid'];
				// Get the name of the user
				$result3 = mysqli_query($link, "SELECT firstname, lastname FROM tblusers WHERE id = '$creatorid'");
				$row3 = mysqli_fetch_array($result3);
				$cfname = htmlspecialchars($row3['firstname']);
				$clname = htmlspecialchars($row3['lastname']);
			?>
			<dt><?php echo $dateadded ?><?php if($creatorid!=0) {?> - <?php echo $clname ?>, <?php echo $cfname ?><?php } ?></dt>
			<dd><?php echo ($annotes) ?></dd>
			<?php } ?>
		</dl>
		<?php } else { ?>
		<p><?php echo $translations['ecomms_no_comms'] ?? ($lang === 'fr' ? 'Aucune communication disponible!' : 'No communications available!'); ?></p>
		<?php } ?>
		<?php } ?>
	</div>
</section>
<?php
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
