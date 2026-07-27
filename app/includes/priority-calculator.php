<?php

function rmt_calculate_priority_score(array $request, ?string $slaDueDate = null): int
{
    $score = 0;

    $score += [1 => 20, 2 => 20, 3 => 5][(int) ($request['project_id'] ?? 0)] ?? 0;

    $audienceId = (int) ($request['audience_id'] ?? 0);
    if (in_array($audienceId, [1, 2], true)) {
        $score += 10;
    } elseif ($audienceId === 3 && in_array((int) ($request['triage_population'] ?? 0), [3, 4], true)) {
        $score += 5;
    }

    $score += [1 => 10, 2 => 5][(int) ($request['conformance_id'] ?? 0)] ?? 0;
    $score += [1 => 5, 2 => 10, 3 => 15, 4 => 20][(int) ($request['triage_maturity'] ?? 0)] ?? 0;
    $score += [1 => 10, 2 => 5][(int) ($request['tech_id'] ?? 0)] ?? 0;

    $requiredDate = trim((string) ($request['daterequired'] ?? ''));
    if (!rmt_priority_valid_date($requiredDate) || !rmt_priority_valid_date((string) $slaDueDate)) {
        return $score;
    }

    if ($requiredDate >= $slaDueDate) {
        return $score + 5;
    }

    return $score + (((int) ($request['triage_management'] ?? 0) === 1) ? 20 : -10);
}

function rmt_priority_valid_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date && $date !== '1900-01-01';
}