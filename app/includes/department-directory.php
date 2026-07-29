<?php

function rmt_department_directory_entry(string $title, string $abbreviation = ''): array
{
    $title = trim($title);
    $abbreviation = trim($abbreviation);

    return [
        'name' => $title,
        'label' => ($abbreviation !== '' && $abbreviation !== '-')
            ? $title . ' (' . $abbreviation . ')'
            : $title,
    ];
}

function rmt_department_directory_key(string $name): string
{
    $name = str_replace(["\u{2018}", "\u{2019}"], "'", trim($name));
    $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

    return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
}

function rmt_department_directory_abbreviation(array $department): string
{
    $label = trim((string)($department['label'] ?? ''));
    if (preg_match('/\(([^()]*)\)$/u', $label, $matches) !== 1) {
        return '';
    }

    return trim($matches[1]);
}

function rmt_department_directory_contains(array $departments, string $department): bool
{
    $departmentKey = rmt_department_directory_key($department);
    if ($departmentKey === '') {
        return false;
    }

    foreach ($departments as $option) {
        $values = [
            (string)($option['name'] ?? ''),
            (string)($option['label'] ?? ''),
            rmt_department_directory_abbreviation($option),
        ];
        foreach ($values as $value) {
            if ($value !== '' && rmt_department_directory_key($value) === $departmentKey) {
                return true;
            }
        }
    }

    return false;
}

function rmt_department_directory_from_rows(array $rows, string $lang): array
{
    $titleField = $lang === 'fr' ? 'namefr' : 'nameen';
    $abbreviationField = $lang === 'fr' ? 'abbreviationfr' : 'abbreviationen';
    $departments = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $title = trim((string) ($row[$titleField] ?? ''));
        if ($title !== '' && $title !== '-') {
            $abbreviation = trim((string) ($row[$abbreviationField] ?? ''));
            $departments[rmt_department_directory_key($title)] = rmt_department_directory_entry($title, $abbreviation);
        }
    }

    uksort($departments, 'strnatcasecmp');
    return array_values($departments);
}

function rmt_department_directory_options(array $departments, string $selectedDepartment): array
{
    $selectedDepartment = trim($selectedDepartment);
    $departmentNames = array_column($departments, 'name');
    $departmentLabels = array_column($departments, 'label');
    $departmentAbbreviations = array_map('rmt_department_directory_abbreviation', $departments);
    if ($selectedDepartment !== ''
        && !in_array($selectedDepartment, $departmentNames, true)
        && !in_array($selectedDepartment, $departmentLabels, true)
        && !in_array($selectedDepartment, $departmentAbbreviations, true)) {
        $departments[] = ['name' => $selectedDepartment, 'label' => $selectedDepartment];
        usort($departments, static fn(array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));
    }

    return array_values($departments);
}

function rmt_department_directory_input_value(array $departments, string $department): string
{
    $department = trim($department);
    foreach ($departments as $option) {
        if (($option['name'] ?? '') === $department
            || ($option['label'] ?? '') === $department
            || rmt_department_directory_abbreviation($option) === $department) {
            return (string) $option['label'];
        }
    }

    return $department;
}

function rmt_department_directory_official_title(array $departments, string $department): string
{
    $department = trim($department);
    foreach ($departments as $option) {
        if (($option['name'] ?? '') === $department
            || ($option['label'] ?? '') === $department
            || rmt_department_directory_abbreviation($option) === $department) {
            return (string) $option['name'];
        }
    }

    return $department;
}

function rmt_department_directory_title_component(array $departments, string $department, string $lang): string
{
    $department = trim($department);
    foreach ($departments as $option) {
        if (($option['name'] ?? '') === $department
            || ($option['label'] ?? '') === $department
            || rmt_department_directory_abbreviation($option) === $department) {
            $abbreviation = rmt_department_directory_abbreviation($option);
            return $abbreviation !== '' ? $abbreviation : (string) $option['name'];
        }
    }

    if ($department !== '') {
        return $department;
    }

    return $lang === 'fr' ? 'Organisation non fournie' : 'Organization not provided';
}

function rmt_department_title_component(mysqli $link, string $department, string $lang): string
{
    $department = trim($department);
    if ($department === '') {
        return $lang === 'fr' ? 'Organisation non fournie' : 'Organization not provided';
    }

    $result = mysqli_query(
        $link,
        'SELECT nameen, namefr, abbreviationen, abbreviationfr FROM tblorganizations WHERE status = 1'
    );
    if (!$result) {
        return $department;
    }

    $departmentKey = rmt_department_directory_key($department);
    while ($row = mysqli_fetch_assoc($result)) {
        $englishEntry = rmt_department_directory_entry((string) $row['nameen'], (string) ($row['abbreviationen'] ?? ''));
        $frenchEntry = rmt_department_directory_entry((string) $row['namefr'], (string) ($row['abbreviationfr'] ?? ''));
        $matches = [
            $englishEntry['name'],
            $englishEntry['label'],
            rmt_department_directory_abbreviation($englishEntry),
            $frenchEntry['name'],
            $frenchEntry['label'],
            rmt_department_directory_abbreviation($frenchEntry),
        ];

        foreach ($matches as $match) {
            if ($match !== '' && rmt_department_directory_key($match) === $departmentKey) {
                $targetEntry = $lang === 'fr' ? $frenchEntry : $englishEntry;
                $abbreviation = rmt_department_directory_abbreviation($targetEntry);
                return $abbreviation !== '' ? $abbreviation : $targetEntry['name'];
            }
        }
    }

    return $department;
}

function rmt_get_department_directory(mysqli $link, string $lang): array
{
    $lang = $lang === 'fr' ? 'fr' : 'en';
    $result = mysqli_query(
        $link,
        'SELECT nameen, namefr, abbreviationen, abbreviationfr
         FROM tblorganizations
         WHERE status = 1
         ORDER BY ' . ($lang === 'fr' ? 'namefr' : 'nameen') . ' ASC'
    );
    if (!$result) {
        return [];
    }

    return rmt_department_directory_from_rows(mysqli_fetch_all($result, MYSQLI_ASSOC), $lang);
}