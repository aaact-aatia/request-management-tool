<?php

require_once '/var/www/html/includes/priority-calculator.php';

$passed = 0;
$failed = 0;

function checkPriority(int $expected, array $request, ?string $slaDueDate, string $message): void
{
    global $passed, $failed;

    $actual = rmt_calculate_priority_score($request, $slaDueDate);
    if ($actual === $expected) {
        $passed++;
        echo "PASS: {$message}\n";
        return;
    }

    $failed++;
    echo "FAIL: {$message}; expected {$expected}, received {$actual}\n";
}

$maximumRequest = [
    'project_id' => 1,
    'audience_id' => 1,
    'conformance_id' => 1,
    'triage_maturity' => 4,
    'tech_id' => 1,
    'daterequired' => '2026-08-20',
    'triage_management' => 0,
];
checkPriority(75, $maximumRequest, '2026-08-20', 'all maximum dimensions use the documented weights');

$internalSmallAudience = [
    'audience_id' => 3,
    'triage_population' => 2,
];
checkPriority(0, $internalSmallAudience, null, 'small internal audience receives no audience points');

$internalLargeAudience = $internalSmallAudience;
$internalLargeAudience['triage_population'] = 4;
checkPriority(5, $internalLargeAudience, null, 'large internal audience receives five points');

$urgentRequest = [
    'daterequired' => '2026-08-10',
    'triage_management' => 0,
];
checkPriority(-10, $urgentRequest, '2026-08-20', 'unapproved date inside the SLA loses ten points');

$urgentRequest['triage_management'] = 1;
checkPriority(20, $urgentRequest, '2026-08-20', 'approved date inside the SLA gains twenty points');

checkPriority(0, ['daterequired' => '1900-01-01'], '2026-08-20', 'placeholder date has no timeline effect');

echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);