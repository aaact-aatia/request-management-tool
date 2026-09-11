<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../scripts/sync-notification-templates.php';

class NotificationTemplateSyncTest extends TestCase
{
    public function testCleanPlaceholdersStripsBackticks(): void
    {
        $input = "Hello `{{client_fname}} {{client_lname}}`, request `{{requestid}}` updated.";
        $expected = "Hello {{client_fname}} {{client_lname}}, request {{requestid}} updated.";

        $this->assertEquals($expected, clean_placeholders($input));
    }

    public function testExtractPlaceholders(): void
    {
        $input = "Hello {{client_fname}} {{client_lname}}, request {{requestid}} on {{url}}.";
        $extracted = extract_placeholders($input);

        $this->assertEquals(['client_fname', 'client_lname', 'requestid', 'url'], array_values($extracted));
    }

    public function testParseMarkdownTemplatesFromDocs(): void
    {
        $docPath = __DIR__ . '/../../docs/notification-templates.md';
        $this->assertFileExists($docPath);

        $templates = parse_markdown_templates($docPath);

        $this->assertArrayHasKey('client', $templates);
        $this->assertArrayHasKey('employee', $templates);

        $this->assertArrayHasKey('request_created', $templates['client']);
        $this->assertArrayHasKey('resolved', $templates['client']);

        $this->assertArrayHasKey('request_created', $templates['employee']);
        $this->assertArrayHasKey('reassigned', $templates['employee']);
        $this->assertArrayHasKey('resolved', $templates['employee']);
        $this->assertArrayHasKey('status_changed', $templates['employee']);

        foreach (['en', 'fr'] as $lang) {
            $this->assertNotEmpty($templates['client']['request_created'][$lang]['subject']);
            $this->assertNotEmpty($templates['client']['request_created'][$lang]['body']);
            $this->assertNotEmpty($templates['employee']['reassigned'][$lang]['subject']);
            $this->assertNotEmpty($templates['employee']['reassigned'][$lang]['body']);
        }
    }

    public function testValidateTemplatesPassesForDocs(): void
    {
        $docPath = __DIR__ . '/../../docs/notification-templates.md';
        $templates = parse_markdown_templates($docPath);
        $validation = validate_templates($templates);

        $this->assertTrue($validation['valid'], 'Validation failed with errors: ' . implode(', ', $validation['errors']));
        $this->assertEquals(12, $validation['total']);
        $this->assertEmpty($validation['errors']);
    }

    public function testGenerateSqlProducesValidQuery(): void
    {
        $docPath = __DIR__ . '/../../docs/notification-templates.md';
        $templates = parse_markdown_templates($docPath);
        $sql = generate_sql($templates);

        $this->assertStringContainsString('INSERT INTO `tblnotificationtemplates`', $sql);
        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $sql);
        $this->assertStringContainsString('Your accessibility request {{requestid}} has been received', $sql);
    }
}
