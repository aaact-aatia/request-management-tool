<?php
/**
 * Configuration Helper
 * Loads app configuration from config.json
 */

function get_app_config() {
    static $config = null;
    
    if ($config === null) {
        $configFile = __DIR__ . '/../config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
        } else {
            // Fallback defaults if config.json doesn't exist
            $config = [
                'app' => [
                    'name' => [
                        'en' => 'Request Management Tool (RMT)',
                        'fr' => 'Outil de gestion des demandes (OGD)'
                    ],
                    'organization' => [
                        'en' => 'Accessibility, Accommodation and Adaptive Computer Technology (AAACT)',
                        'fr' => 'Accessibilité, adaptation et technologie informatique adaptée (AATIA)'
                    ],
                    'organization_url' => [
                        'en' => 'https://www.canada.ca/en/shared-services/services/employees-accessibility/aaact-program.html',
                        'fr' => 'https://www.canada.ca/fr/services-partages/services/employes-accessibilite/programme-aatia.html'
                    ]
                ]
            ];
        }
    }
    
    return $config;
}

/**
 * Get language toggle URL
 */
function get_language_toggle_url($disabled_on_pages = ['openrequest2']) {
    $lang = $_SESSION['lang'] ?? 'en';
    $toggleLang = ($lang === 'en') ? 'fr' : 'en';
    
    $currenturl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
        . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    // Check if toggle should be disabled on this page
    foreach ($disabled_on_pages as $page) {
        if (strpos($currenturl, $page) !== false) {
            return null; // Disabled
        }
    }
    
    // Handle new bilingual pages with ?lang= parameter
    if (strpos($currenturl, "?lang={$lang}") !== false) {
        return str_replace("?lang={$lang}", "?lang={$toggleLang}", $currenturl);
    } elseif (strpos($currenturl, "&lang={$lang}") !== false) {
        return str_replace("&lang={$lang}", "&lang={$toggleLang}", $currenturl);
    } elseif (strpos($currenturl, 'lang=') === false && strpos($currenturl, '.php') !== false) {
        // No lang parameter - add it
        if (strpos($currenturl, '?') !== false) {
            return $currenturl . "&lang={$toggleLang}";
        } else {
            return $currenturl . "?lang={$toggleLang}";
        }
    } else {
        // Legacy pages with -en.php / -fr.php
        return str_replace("-{$lang}.php", "-{$toggleLang}.php", $currenturl);
    }
}
?>
