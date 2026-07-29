<?php

$passed = 0;
$failed = 0;

function checkRequestCard(bool $condition, string $message): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "PASS: {$message}\n";
        return;
    }

    $failed++;
    echo "FAIL: {$message}\n";
}

function renderRequestCard(array $card): string
{
    $requestCard = $card;
    ob_start();
    include '/var/www/html/includes/template/request-card.php';
    return (string) ob_get_clean();
}

$title = 'REQ-26-005 - ESDC - System <review>';
$cardHtml = renderRequestCard([
    'requestUrl' => 'viewrequest.php?id=5&amp;lang=en',
    'requestCode' => 'a11y-REQ-26-005',
    'title' => $title,
    'statusPrefix' => 'Status',
    'statusText' => 'New',
]);

checkRequestCard(
    str_contains($cardHtml, '>REQ-26-005 - ESDC - System &lt;review&gt;</a>'),
    'request title is the overview card link text and is escaped'
);
checkRequestCard(
    substr_count($cardHtml, 'REQ-26-005 - ESDC - System &lt;review&gt;') === 1,
    'request title is not duplicated below the card link'
);
checkRequestCard(
    !str_contains($cardHtml, '>a11y-REQ-26-005</a>'),
    'ticket code is not used as link text when a title exists'
);

$legacyCardHtml = renderRequestCard([
    'requestUrl' => 'viewrequest.php?id=6',
    'requestCode' => 'a11y-REQ-20-001',
    'title' => '',
    'statusPrefix' => 'Status',
    'statusText' => 'New',
]);
checkRequestCard(
    str_contains($legacyCardHtml, '>a11y-REQ-20-001</a>'),
    'legacy request without a title falls back to the ticket code'
);

$whitespaceTitleHtml = renderRequestCard([
    'requestUrl' => 'viewrequest.php?id=7',
    'requestCode' => 'a11y-REQ-20-002',
    'title' => " \t\n ",
    'statusPrefix' => 'Status',
    'statusText' => 'New',
]);
checkRequestCard(
    str_contains($whitespaceTitleHtml, '>a11y-REQ-20-002</a>'),
    'whitespace-only legacy title falls back to the ticket code'
);
checkRequestCard(
    str_contains($cardHtml, 'request-card-title'),
    'request title link uses the scoped wrapping class'
);

echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);