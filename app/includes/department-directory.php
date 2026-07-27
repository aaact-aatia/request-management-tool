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
    if ($selectedDepartment !== ''
        && !in_array($selectedDepartment, $departmentNames, true)
        && !in_array($selectedDepartment, $departmentLabels, true)) {
        $departments[] = ['name' => $selectedDepartment, 'label' => $selectedDepartment];
        usort($departments, static fn(array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));
    }

    return array_values($departments);
}

function rmt_department_directory_input_value(array $departments, string $department): string
{
    $department = trim($department);
    foreach ($departments as $option) {
        if (($option['name'] ?? '') === $department || ($option['label'] ?? '') === $department) {
            return (string) $option['label'];
        }
    }

    return $department;
}

function rmt_department_directory_official_title(array $departments, string $department): string
{
    $department = trim($department);
    foreach ($departments as $option) {
        if (($option['name'] ?? '') === $department || ($option['label'] ?? '') === $department) {
            return (string) $option['name'];
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