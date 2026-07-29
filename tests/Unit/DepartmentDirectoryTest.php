<?php

require_once '/var/www/html/includes/department-directory.php';

$passed = 0;
$failed = 0;

function checkDepartmentDirectory(array $expected, array $actual, string $message): void
{
    global $passed, $failed;

    if ($actual === $expected) {
        $passed++;
        echo "PASS: {$message}\n";
        return;
    }

    $failed++;
    echo "FAIL: {$message}\n";
}

$rows = [
    [
        'nameen' => 'Treasury Board of Canada Secretariat',
        'namefr' => 'Secrétariat du Conseil du Trésor du Canada',
        'abbreviationen' => 'TBS',
        'abbreviationfr' => 'SCT',
    ],
    [
        'nameen' => 'Organization Without Acronym',
        'namefr' => 'Organisation sans acronyme',
        'abbreviationen' => '',
        'abbreviationfr' => null,
    ],
    [
        'nameen' => 'TREASURY  BOARD OF CANADA SECRETARIAT',
        'namefr' => 'Secrétariat du Conseil du Trésor du Canada',
        'abbreviationen' => 'TBS2',
        'abbreviationfr' => 'SCT2',
    ],
    ['nameen' => '', 'namefr' => '-'],
];

checkDepartmentDirectory(
    [
        ['name' => 'Organization Without Acronym', 'label' => 'Organization Without Acronym'],
        ['name' => 'TREASURY  BOARD OF CANADA SECRETARIAT', 'label' => 'TREASURY  BOARD OF CANADA SECRETARIAT (TBS2)'],
    ],
    rmt_department_directory_from_rows($rows, 'en'),
    'English rows use titles, acronyms, sorting, and normalized deduplication'
);
checkDepartmentDirectory(
    [
        ['name' => 'Organisation sans acronyme', 'label' => 'Organisation sans acronyme'],
        ['name' => 'Secrétariat du Conseil du Trésor du Canada', 'label' => 'Secrétariat du Conseil du Trésor du Canada (SCT2)'],
    ],
    rmt_department_directory_from_rows($rows, 'fr'),
    'French rows use titles and acronyms'
);
checkDepartmentDirectory([], rmt_department_directory_from_rows([], 'en'), 'no database rows returns no departments');
checkDepartmentDirectory(
    [
        ['name' => 'Existing department', 'label' => 'Existing department (ED)'],
        ['name' => 'Previously entered department', 'label' => 'Previously entered department'],
    ],
    rmt_department_directory_options(
        [['name' => 'Existing department', 'label' => 'Existing department (ED)']],
        'Previously entered department'
    ),
    'free-text entry is preserved when directory data is available'
);
checkDepartmentDirectory(
    [['name' => 'Existing department', 'label' => 'Existing department (ED)']],
    rmt_department_directory_options(
        [['name' => 'Existing department', 'label' => 'Existing department (ED)']],
        'Existing department (ED)'
    ),
    'matching acronym label is not duplicated'
);
checkDepartmentDirectory(
    ['Treasury Board of Canada Secretariat (TBS)'],
    [rmt_department_directory_input_value(
        [['name' => 'Treasury Board of Canada Secretariat', 'label' => 'Treasury Board of Canada Secretariat (TBS)']],
        'Treasury Board of Canada Secretariat'
    )],
    'official title is displayed with its acronym'
);
checkDepartmentDirectory(
    ['Treasury Board of Canada Secretariat'],
    [rmt_department_directory_official_title(
        [['name' => 'Treasury Board of Canada Secretariat', 'label' => 'Treasury Board of Canada Secretariat (TBS)']],
        'Treasury Board of Canada Secretariat (TBS)'
    )],
    'acronym label is normalized to the official title for storage'
);
checkDepartmentDirectory(
    ['Treasury Board of Canada Secretariat (TBS)'],
    [rmt_department_directory_input_value(
        [['name' => 'Treasury Board of Canada Secretariat', 'label' => 'Treasury Board of Canada Secretariat (TBS)']],
        'TBS'
    )],
    'stored acronym is displayed as the matching directory option'
);
checkDepartmentDirectory(
    ['Treasury Board of Canada Secretariat'],
    [rmt_department_directory_official_title(
        [['name' => 'Treasury Board of Canada Secretariat', 'label' => 'Treasury Board of Canada Secretariat (TBS)']],
        'TBS'
    )],
    'stored acronym is normalized to the official title'
);
checkDepartmentDirectory(
    [true, true, true, false],
    [
        rmt_department_directory_contains(
            [['name' => 'Treasury Board of Canada Secretariat', 'label' => 'Treasury Board of Canada Secretariat (TBS)']],
            'treasury board of canada secretariat'
        ),
        rmt_department_directory_contains(
            [['name' => 'Treasury Board of Canada Secretariat', 'label' => 'Treasury Board of Canada Secretariat (TBS)']],
            'Treasury Board of Canada Secretariat (TBS)'
        ),
        rmt_department_directory_contains(
            [['name' => 'Treasury Board of Canada Secretariat', 'label' => 'Treasury Board of Canada Secretariat (TBS)']],
            'tbs'
        ),
        rmt_department_directory_contains(
            [['name' => 'Treasury Board of Canada Secretariat', 'label' => 'Treasury Board of Canada Secretariat (TBS)']],
            'Unlisted organization'
        ),
    ],
    'directory matching accepts names, labels, and acronyms without flagging case differences'
);
checkDepartmentDirectory(
    ['Unlisted organization'],
    [rmt_department_directory_official_title([], 'Unlisted organization')],
    'unlisted free text is preserved'
);

echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);
