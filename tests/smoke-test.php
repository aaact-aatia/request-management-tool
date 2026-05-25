<?php
/**
 * Smoke Test - Verifies pages load without fatal errors
 * Run this after refactoring to ensure nothing is broken
 */

echo "🔥 RMT Smoke Tests\n";
echo "==================\n\n";

require_once __DIR__ . '/../app/env.php';

$baseUrl = "http://localhost:" . app_env('PORT', '8080');
$passed = 0;
$failed = 0;

function testPage($url, $name, &$passed, &$failed) {
    echo "Testing: $name... ";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Cookie: PHPSESSID=test\r\n",
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ FAILED (could not connect)\n";
        $failed++;
        return;
    }
    
    // Check for PHP errors
    if (strpos($response, 'Fatal error') !== false || 
        strpos($response, 'Parse error') !== false ||
        strpos($response, 'Warning:') !== false) {
        echo "❌ FAILED (PHP error detected)\n";
        $failed++;
        return;
    }
    
    // Check HTTP status
    if (isset($http_response_header[0])) {
        if (strpos($http_response_header[0], '200') !== false ||
            strpos($http_response_header[0], '302') !== false) {
            echo "✅ PASSED\n";
            $passed++;
        } else {
            echo "❌ FAILED ({$http_response_header[0]})\n";
            $failed++;
        }
    } else {
        echo "✅ PASSED (no errors)\n";
        $passed++;
    }
}

// Test critical pages
echo "Testing Core Pages:\n";
echo "-------------------\n";
testPage("$baseUrl/openrequest.php?lang=en", "Open Request (EN)", $passed, $failed);
testPage("$baseUrl/openrequest.php?lang=fr", "Open Request (FR)", $passed, $failed);
testPage("$baseUrl/index.php?lang=en", "Dashboard (EN)", $passed, $failed);
testPage("$baseUrl/index.php?lang=fr", "Dashboard (FR)", $passed, $failed);

echo "\n";
echo "Testing Helper Functions:\n";
echo "-------------------------\n";

// Test helper functions directly
require_once __DIR__ . '/../app/includes/helpers.php';

// Test permission helpers
$_SESSION['atype'] = 1;
echo "isAdmin() with atype=1... ";
echo (isAdmin() === true ? "✅ PASSED\n" : "❌ FAILED\n");
($passed += (isAdmin() === true ? 1 : 0));
($failed += (isAdmin() === true ? 0 : 1));

// Test value helpers
echo "hasValue('test')... ";
echo (hasValue('test') === true ? "✅ PASSED\n" : "❌ FAILED\n");
($passed += (hasValue('test') === true ? 1 : 0));
($failed += (hasValue('test') === true ? 0 : 1));

echo "hasValue('')... ";
echo (hasValue('') === false ? "✅ PASSED\n" : "❌ FAILED\n");
($passed += (hasValue('') === false ? 1 : 0));
($failed += (hasValue('') === false ? 0 : 1));

// Test date helpers
echo "getTodayDate()... ";
$today = getTodayDate();
$expected = date('Y-m-d');
echo ($today === $expected ? "✅ PASSED\n" : "❌ FAILED\n");
($passed += ($today === $expected ? 1 : 0));
($failed += ($today === $expected ? 0 : 1));

// Test language helpers
echo "detectLanguage() default... ";
unset($_GET['lang']);
unset($_SESSION['lang']);
$lang = detectLanguage();
echo ($lang === 'en' ? "✅ PASSED\n" : "❌ FAILED\n");
($passed += ($lang === 'en' ? 1 : 0));
($failed += ($lang === 'en' ? 0 : 1));

echo "\n";
echo "===================\n";
echo "Results: $passed passed, $failed failed\n";
echo "===================\n\n";

exit($failed > 0 ? 1 : 0);
