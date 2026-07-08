<?php
/**
 * Unit tests for help docs hardening and grouping helpers.
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/includes/help_docs.php';

class HelpDocsTest extends TestCase
{
    public function testGroupingOrderRootThenAlphabeticalSections(): void
    {
        $docs = [
            ['relative_path' => 'future/004-database-driven-ajax-dropdowns.md', 'link_title' => 'Future 004'],
            ['relative_path' => 'README.md', 'link_title' => 'Root README'],
            ['relative_path' => 'adr/001-language-file-system.md', 'link_title' => 'ADR 001'],
            ['relative_path' => 'config-management/change-control-sop.md', 'link_title' => 'Change Control SOP'],
        ];

        $grouped = rmt_docs_group_by_top_level($docs);
        $keys = array_keys($grouped);

        $this->assertSame('__root__', $keys[0]);
        $this->assertSame('adr', $keys[1]);
        $this->assertSame('config-management', $keys[2]);
        $this->assertSame('future', $keys[3]);
    }

    public function testInvalidFileRequestIsRejected(): void
    {
        $this->assertSame('', rmt_docs_sanitize_request_doc('../secrets.md'));
        $this->assertSame('', rmt_docs_sanitize_request_doc('/etc/passwd'));
        $this->assertSame('', rmt_docs_sanitize_request_doc('config/.hidden.md'));
        $this->assertSame('', rmt_docs_sanitize_request_doc('foo\0bar.md'));

        $this->assertSame('future/004-database-driven-ajax-dropdowns.md', rmt_docs_sanitize_request_doc('future%2F004-database-driven-ajax-dropdowns.md'));
    }

    public function testFrenchHelpBehaviorDisablesDocsIndex(): void
    {
        $this->assertTrue(rmt_docs_should_show_index(true, 'en'));
        $this->assertFalse(rmt_docs_should_show_index(true, 'fr'));
        $this->assertFalse(rmt_docs_should_show_index(false, 'en'));
    }
}
