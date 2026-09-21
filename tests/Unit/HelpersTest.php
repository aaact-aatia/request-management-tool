<?php
/**
 * Unit Tests for Helper Functions
 */

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset session state before each test
        $_SESSION = [
            'lang' => 'en',
            'atype' => 3,
            'primary_atype' => 3,
            'is_role_test_mode' => 0,
            'is_superuser' => 0,
            'is_admin' => 0,
            'pid' => 1
        ];
    }

    // ========================================================================
    // PERMISSION TESTS
    // ========================================================================

    /**
     * @dataProvider administrativeAccessCases
     */
    public function testAdministrativeAccessUsesPrivilegeFlagsOnly(
        int $accountType,
        int $isSuperuser,
        int $isAdmin,
        int $isRoleTestMode,
        bool $expectedAccess
    ): void {
        $_SESSION['atype'] = $accountType;
        $_SESSION['is_superuser'] = $isSuperuser;
        $_SESSION['is_admin'] = $isAdmin;
        $_SESSION['is_role_test_mode'] = $isRoleTestMode;

        $this->assertSame($expectedAccess, rmt_has_admin_access());
    }

    public static function administrativeAccessCases(): array
    {
        return [
            'legacy superadmin type without flag' => [1, 0, 0, 0, false],
            'legacy admin type without flag' => [2, 0, 0, 0, false],
            'manager without flag' => [3, 0, 0, 0, false],
            'team lead without flag' => [4, 0, 0, 0, false],
            'employee without flag' => [5, 0, 0, 0, false],
            'director without flag' => [6, 0, 0, 0, false],
            'admin privilege on manager' => [3, 0, 1, 0, true],
            'admin privilege on director' => [6, 0, 1, 0, true],
            'superadmin privilege' => [3, 1, 1, 0, true],
            'admin privilege suspended during role test' => [3, 0, 1, 1, false],
            'superadmin privilege suspended during role test' => [5, 1, 1, 1, false],
        ];
    }

    public function testRoleTestModePreservesSuperadminIdentityWithoutAccess(): void
    {
        $_SESSION['is_superuser'] = 1;
        $_SESSION['is_admin'] = 1;
        $_SESSION['is_role_test_mode'] = 1;

        $this->assertTrue(rmt_is_superadmin_identity());
        $this->assertFalse(rmt_has_superadmin_access());
        $this->assertFalse(rmt_has_admin_access());

        $_SESSION['is_role_test_mode'] = 0;

        $this->assertTrue(rmt_has_superadmin_access());
        $this->assertTrue(rmt_has_admin_access());
    }

    public function testIsAdmin()
    {
        $_SESSION['is_admin'] = 1;
        $this->assertTrue(isAdmin());

        $_SESSION['is_admin'] = 0;
        $_SESSION['is_superuser'] = 1;
        $this->assertFalse(isAdmin());

        $_SESSION['is_role_test_mode'] = 1;
        $this->assertFalse(isAdmin());

        $_SESSION['is_superuser'] = 0;
        $_SESSION['atype'] = 1;
        $this->assertFalse(isAdmin());
        $this->assertFalse(isSuperAdmin());

        $_SESSION['is_role_test_mode'] = 0;
        $_SESSION['is_admin'] = 1;
        $this->assertTrue(rmt_has_admin_access());
        $_SESSION['is_role_test_mode'] = 1;
        $this->assertFalse(rmt_has_admin_access());
    }

    public function testRoleTestModeUsesExplicitSessionState()
    {
        $_SESSION['is_superuser'] = 1;
        $this->assertFalse(isRoleTestMode());

        $_SESSION['is_role_test_mode'] = 1;
        $this->assertTrue(isRoleTestMode());

        $_SESSION['atype'] = 5;
        $_SESSION['primary_atype'] = 5;
        $this->assertTrue(isRoleTestMode());
    }

    public function testCanEditRequests()
    {
        $allowedTypes = [3, 4, 5];
        
        foreach ($allowedTypes as $type) {
            $_SESSION['atype'] = $type;
            $this->assertTrue(canEditRequests(), "Account type $type should be able to edit requests");
        }
        
        $_SESSION['atype'] = 6;
        $this->assertFalse(canEditRequests());
    }

    public function testCanManageSLA()
    {
        $allowedTypes = [3];
        
        foreach ($allowedTypes as $type) {
            $_SESSION['atype'] = $type;
            $this->assertTrue(canManageSLA(), "Account type $type should manage SLA");
        }
        
        $_SESSION['atype'] = 6;
        $this->assertFalse(canManageSLA());
    }

    public function testIsReadOnly()
    {
        $_SESSION['atype'] = 6;
        $this->assertTrue(isReadOnly());
        
        $_SESSION['is_superuser'] = 1;
        $this->assertFalse(isReadOnly());
    }

    // ========================================================================
    // VALUE HELPER TESTS
    // ========================================================================

    public function testHasValue()
    {
        $this->assertTrue(hasValue('test'));
        $this->assertTrue(hasValue(1));
        
        $this->assertFalse(hasValue(''));
        $this->assertFalse(hasValue('0'));
        $this->assertFalse(hasValue(0));
        $this->assertFalse(hasValue(null));
    }

    public function testGetPostValue()
    {
        $_POST['test'] = "Hello World<script>";
        
        $result = getPostValue('nonexistent', 'default');
        $this->assertEquals('default', $result);
        
        unset($_POST['test']);
        $GLOBALS['link'] = null;
    }

    public function testManagerRecipientFilteringUsesOnlyTheStoredManagerWhenEnabled(): void
    {
        $result = rmt_filter_notification_recipients_by_manager(
            ['lead@example.com', 'manager-one@example.com', 'manager-two@example.com'],
            ['manager-one@example.com'],
            ['lead@example.com'],
            true
        );

        $this->assertSame(['lead@example.com', 'manager-one@example.com'], $result);
    }

    public function testManagerRecipientFilteringFallsBackToTeamRecipientsWhenNoValidManagerIsSet(): void
    {
        $result = rmt_filter_notification_recipients_by_manager(
            ['lead@example.com', 'manager-one@example.com', 'manager-two@example.com'],
            [],
            ['lead@example.com', 'manager-one@example.com', 'manager-two@example.com'],
            false
        );

        $this->assertSame(['lead@example.com', 'manager-one@example.com', 'manager-two@example.com'], $result);
    }

    // ========================================================================
    // DATE HELPER TESTS
    // ========================================================================

    public function testGetDateRange()
    {
        $range = getDateRange(1);
        
        $this->assertArrayHasKey('min', $range);
        $this->assertArrayHasKey('max', $range);
        
        // Verify format is Y-m-d
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $range['min']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $range['max']);
        
        // Min should be in the past, max in the future
        $this->assertLessThan(date('Y-m-d'), $range['min']);
        $this->assertGreaterThan(date('Y-m-d'), $range['max']);
    }

    public function testGetTodayDate()
    {
        $today = getTodayDate();
        $expected = date('Y-m-d');
        
        $this->assertEquals($expected, $today);
    }

    // ========================================================================
    // LANGUAGE HELPER TESTS
    // ========================================================================

    public function testDetectLanguageFromGet()
    {
        $_GET['lang'] = 'fr';
        $lang = detectLanguage();
        
        $this->assertEquals('fr', $lang);
        $this->assertEquals('fr', $_SESSION['lang']);
        
        unset($_GET['lang']);
    }

    public function testDetectLanguageFromSession()
    {
        $_SESSION['lang'] = 'fr';
        $lang = detectLanguage();
        
        $this->assertEquals('fr', $lang);
        
        $_SESSION['lang'] = 'en';
    }

    public function testDetectLanguageDefaultsToEnglish()
    {
        unset($_SESSION['lang']);
        unset($_GET['lang']);
        
        $lang = detectLanguage();
        
        $this->assertEquals('en', $lang);
    }

    public function testDetectLanguageRejectsInvalid()
    {
        $_GET['lang'] = 'invalid';
        $lang = detectLanguage();
        
        // Should default to 'en' for invalid language
        $this->assertEquals('en', $lang);
        
        unset($_GET['lang']);
    }

    public function testGetIncludePath()
    {
        $path = getIncludePath('includes/header.php', 'en');
        $this->assertEquals('includes/header.php', $path);
        
        $path = getIncludePath('includes/header.php', 'fr');
        $this->assertEquals('includes/header.php', $path);
    }

    // ========================================================================
    // HTML RENDERING TESTS
    // ========================================================================

    public function testRenderTextInput()
    {
        $html = renderTextInput('test_id', 'Test Label', 'Test Value', true, false);
        
        $this->assertStringContainsString('id="test_id"', $html);
        $this->assertStringContainsString('Test Label', $html);
        $this->assertStringContainsString('value="Test Value"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('(required)', $html);
    }

    public function testRenderTextInputReadonly()
    {
        $html = renderTextInput('test_id', 'Test Label', 'Value', false, true);
        
        $this->assertStringContainsString('readonly="readonly"', $html);
        $this->assertStringNotContainsString('required', $html);
    }

    public function testRenderTextInputEscaping()
    {
        $html = renderTextInput('test_id', 'Label', '<script>alert("xss")</script>', false, false);
        
        // Should escape the value
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderDateInput()
    {
        $html = renderDateInput('date_id', 'Date Label', '2025-01-01', true, '2024-01-01', '2026-01-01');
        
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('id="date_id"', $html);
        $this->assertStringContainsString('value="2025-01-01"', $html);
        $this->assertStringContainsString('min="2024-01-01"', $html);
        $this->assertStringContainsString('max="2026-01-01"', $html);
        $this->assertStringContainsString('required', $html);
    }

    public function testRenderTextarea()
    {
        $html = renderTextarea('notes_id', 'Notes', 'Some content', true, false, 5);
        
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('id="notes_id"', $html);
        $this->assertStringContainsString('rows="5"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('Some content', $html);
    }

    public function testRenderSelect()
    {
        $options = [
            ['id' => 1, 'name' => 'Option 1'],
            ['id' => 2, 'name' => 'Option 2']
        ];
        
        $html = renderSelect('select_id', 'Select Label', $options, 2, true, 'Choose one');
        
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('id="select_id"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('Choose one', $html);
        $this->assertStringContainsString('Option 1', $html);
        $this->assertStringContainsString('Option 2', $html);
        $this->assertStringContainsString('value="2" selected', $html);
    }

    public function testRenderSelectSimpleArray()
    {
        $options = ['apple', 'banana', 'cherry'];
        
        $html = renderSelect('fruit_id', 'Fruit', $options, 'banana', false, '');
        
        $this->assertStringContainsString('value="apple"', $html);
        $this->assertStringContainsString('value="banana" selected', $html);
        $this->assertStringContainsString('>cherry<', $html);
    }
}
