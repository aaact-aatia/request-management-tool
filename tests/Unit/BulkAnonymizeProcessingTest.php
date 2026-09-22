<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/includes/bulk-anonymize-processing.php';

class BulkAnonymizeProcessingTest extends TestCase
{
    public function testNormalizesPositiveUniqueIntegerIds(): void
    {
        $this->assertSame([4, 2], rmt_bulk_anonymize_normalize_ids(['4', 2, '4', 0, -1, 'invalid']));
    }

    public function testParsesCommaSeparatedExcludedServiceIds(): void
    {
        $this->assertSame([46, 7], rmt_bulk_anonymize_parse_excluded_services('46, 7, 46, invalid'));
    }

    public function testBuildsParameterizedCriteriaWithoutInterpolatingValues(): void
    {
        [$where, $types, $params] = rmt_bulk_anonymize_matching_where([1, 4], [46, 47]);

        $this->assertSame('catalogueid IN (?, ?) AND (serviceid IS NULL OR serviceid NOT IN (?, ?))', $where);
        $this->assertSame('iiii', $types);
        $this->assertSame([1, 4, 46, 47], $params);
    }
}
