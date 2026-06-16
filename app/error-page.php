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
        <title>Page not found - Canada.ca theme / Page non trouvée - Thème Canada.ca</title>
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <meta name="robots" content="noindex, nofollow, noarchive">
        <link href="https://wet-boew.github.io/themes-dist/GCWeb/GCWeb/assets/favicon.ico" rel="icon" type="image/x-icon">
        <link rel="stylesheet" href="https://wet-boew.github.io/themes-dist/GCWeb/GCWeb/css/theme-srv.css">
        <link rel="stylesheet" href="https://wet-boew.github.io/themes-dist/GCWeb/GCWeb/css/theme.min.css">
        <noscript><link rel="stylesheet" href="https://wet-boew.github.io/themes-dist/GCWeb/wet-boew/css/noscript.css"></noscript>
    </head>
    <body class="cnt-wdth-lmtd" vocab="http://schema.org/" resource="#wb-webpage" typeof="WebPage">
        <header>
            <div id="wb-bnr" class="container">
                <div class="row">
                    <div class="brand col-xs-9 col-sm-5 col-md-4" property="publisher" typeof="GovernmentOrganization">
                        <a href="https://wet-boew.github.io/GCWeb/" property="url">
                            <img src="https://wet-boew.github.io/themes-dist/GCWeb/GCWeb/assets/sig-blk-en.svg" alt="Government of Canada" property="logo">
                            <span class="wb-inv"> / <span lang="fr">Gouvernement du Canada</span></span>
                        </a>
                        <meta property="name" content="Government of Canada">
                        <meta property="areaServed" typeof="Country" content="Canada">
                        <link property="logo" href="https://wet-boew.github.io/themes-dist/GCWeb/GCWeb/assets/wmms-blk.svg">
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row"></div>
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

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js" integrity="sha384-rY/jv8mMhqDabXSo+UCggqKtdmBfd3qC2/KvyTDNQ6PcUJXaxK1tMepoQda4g5vB" crossorigin="anonymous"></script>
        <script src="https://wet-boew.github.io/themes-dist/GCWeb/wet-boew/js/wet-boew.min.js"></script>
        <script src="https://wet-boew.github.io/themes-dist/GCWeb/GCWeb/js/theme.min.js"></script>

        <footer class="py-3">
            <div class="container" property="publisher" resource="#wb-publisher" typeof="GovernmentOrganization">
                <div class="align-self-end">
                    <img id="wmms" src="https://wet-boew.github.io/themes-dist/GCWeb/GCWeb/assets/wmms-blk.svg" alt="Symbol of the Government of Canada" property="logo">
                </div>
            </div>
        </footer>
    </body>
</html>