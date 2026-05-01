<?php
$lang_code = $_SESSION['lang'] ?? 'en';
?>
<!-- Load closure template scripts -->
		<script src="https://www.canada.ca/etc/designs/canada/cdts/gcweb/rn/cdts/compiled/soyutils.js"></script>
		<script src="https://www.canada.ca/etc/designs/canada/cdts/gcweb/rn/cdts/compiled/wet-<?= $lang_code ?>.js"></script>


		<!-- Write closure template -->
		<script>
			document.write(wet.builder.refTop({
				"isApplication": true
			}));
		</script>