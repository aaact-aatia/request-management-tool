<?php
/**
 * Error Page - 404 Not Found
 */

require('includes/httpscheck.php');

http_response_code(404);
?>
<!DOCTYPE html>
<html class="no-js" lang="en" dir="ltr">
    <head>
        <meta charset="utf-8">
        <title>Page not found / Page non trouvée - Request Management Tool (RMT)</title>
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <meta name="robots" content="noindex, nofollow, noarchive">
        <link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/wet-boew/css/wet-boew.min.css"/>
        <link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/wet-boew/css/theme.min.css"/>
        <noscript><link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/wet-boew/css/noscript.min.css"></noscript>
        <link rel="stylesheet" href="includes/template/app.css">
    </head>
    <body class="cnt-wdth-lmtd" vocab="http://schema.org/" resource="#wb-webpage" typeof="WebPage">
        <header>
            <div id="wb-bnr" class="container">
                <div class="row">
                    <div class="brand col-xs-9 col-sm-5 col-md-4" property="publisher" typeof="GovernmentOrganization">
                        <img src="https://www.canada.ca/etc/designs/canada/wet-boew/assets/sig-blk-en.svg" alt="Government of Canada" property="logo">
                        <span class="wb-inv"> / <span lang="fr">Gouvernement du Canada</span></span>
                        <meta property="name" content="Government of Canada">
                        <meta property="areaServed" typeof="Country" content="Canada">
                        <link property="logo" href="https://www.canada.ca/etc/designs/canada/wet-boew/assets/wmms-blk.svg">
                    </div>
                </div>
            </div>
        </header>

        <main property="mainContentOfPage" resource="#wb-main" typeof="WebPageElement" class="container">
            <h1 class="wb-inv" id="wb-cont">Page not found / <span lang="fr">Page non trouvée</span></h1>
            <p class="wb-slc hidden-xs hidden-sm"><a class="wb-sl" href="#fr">Aller à la version française</a></p>
            <div class="row">
                <div class="col-md-6 col-sm-12" id="en">
                    <h2>Page not found</h2>
                    <p><span class="label label-danger">Error 404</span></p>
                    <p class="hidden-xl hidden-md hidden-lg"><a href="#fr">Aller à la version française</a></p>
                    <p>The page you are looking for may have been moved or deleted. Check that the web address (URL) is spelled correctly. Do not include special characters or spaces in the URL.</p>
                    <p><a href="/openrequest.php?lang=en">Request Management Tool (RMT)</a></p>
                </div>

                <div class="col-md-6 col-sm-12" id="fr" lang="fr">
                    <h2>Page non trouvée</h2>
                    <p><span class="label label-danger">Erreur 404</span></p>
                    <p class="hidden-xl hidden-md hidden-lg"><a href="#en">Go to the English version</a></p>
                    <p>La page que vous souhaitez consulter a peut-être été déplacée ou supprimée. Assurez-vous que l'adresse Web (URL) est exacte. N'incluez pas de caractères spéciaux ni d'espaces dans l'URL.</p>
                    <p><a href="/openrequest.php?lang=fr">Outil de gestion des demandes (OGD)</a></p>
                </div>
            </div>
        </main>

        <!--[if gte IE 9 | !IE ]><!-->
        <script src="https://www.canada.ca/etc/designs/canada/wet-boew/js/jquery/2.2.4/jquery.min.js"></script>
        <script src="https://www.canada.ca/etc/designs/canada/wet-boew/js/wet-boew.min.js"></script>
        <!--<![endif]-->
        <!--[if lt IE 9]>
        <script src="https://www.canada.ca/etc/designs/canada/wet-boew/js/ie8-wet-boew2.min.js"></script>
        <![endif]-->
        <script src="https://www.canada.ca/etc/designs/canada/wet-boew/js/theme.min.js"></script>

        <footer id="wb-info" class="visible-sm visible-md visible-lg">
            <div class="gc-sub-footer">
                <div class="container">
                    <div class="wtrmrk pull-right">
                        <img src="https://www.canada.ca/etc/designs/canada/wet-boew/assets/wmms-blk.svg" alt="Symbol of the Government of Canada">
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>