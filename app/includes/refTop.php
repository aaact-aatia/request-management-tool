<?php
$lang_code = $_SESSION['lang'] ?? 'en';
?>
<!-- Keep CDTS JS loaded during migration while replacing docwrite-injected head assets with explicit links -->
<script src="https://www.canada.ca/etc/designs/canada/cdts/gcweb/rn/cdts/compiled/soyutils.js"></script>
<script src="https://www.canada.ca/etc/designs/canada/cdts/gcweb/rn/cdts/compiled/wet-<?= htmlspecialchars($lang_code, ENT_QUOTES, 'UTF-8') ?>.js"></script>

<!-- Core WET/GCWeb head assets for the application -->
<link rel="icon" href="https://www.canada.ca/etc/designs/canada/wet-boew/assets/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/wet-boew/css/wet-boew.min.css">
<link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/wet-boew/css/theme.min.css">
<noscript><link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/wet-boew/css/noscript.min.css"></noscript>

<!-- Application look from CDTS isApplication=true (documented as cdtsapps.css) -->
<link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/cdts/gcweb/v5_0_2/cdts/cdtsapps.css">
<link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/cdts/gcweb/v5_0_2/cdts/cdtsfixes.css">

<!-- Local bridge stylesheet for app-specific overrides during migration -->
<link rel="stylesheet" href="/public/rmt.css">