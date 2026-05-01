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
            'atype' => 1,
            'pid' => 1
        ];
    }

    // ========================================================================
    // PERMISSION TESTS
    // ========================================================================

    public function testIsAdmin()
    {
        $_SESSION['atype'] = 1;
        $this->assertTrue(isAdmin());
        
        $_SESSION['atype'] = 2;
        $this->assertFalse(isAdmin());
    }

    public function testCanEditRequests()
    {
        $allowedTypes = [1, 2, 3, 4, 6];
        
        foreach ($allowedTypes as $type) {
            $_SESSION['atype'] = $type;
            $this->assertTrue(canEditRequests(), "Account type $type should be able to edit requests");
        }
        
        $_SESSION['atype'] = 5;
        $this->assertFalse(canEditRequests());
    }

    public function testCanManageSLA()
    {
        $allowedTypes = [1, 2, 3, 4];
        
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
        
        $_SESSION['atype'] = 1;
        $this->assertFalse(isReadOnly());
    }

    // ========================================================================
    // VALUE HELPER TESTS
    // ========================================================================

    public function testHasValue()
    {
        $this->assertTrue(hasValue('test'));
        $this->assertTrue(hasValue('0'));
        $this->assertTrue(hasValue(1));
        
        $this->assertFalse(hasValue(''));
        $this->assertFalse(hasValue(0));
        $this->assertFalse(hasValue(null));
    }

    public function testGetPostValue()
    {
        $_POST['test'] = "Hello World<script>";
        
        // Mock mysqli_real_escape_string
        $GLOBALS['link'] = new class {
            public function real_escape_string($str) {
                return addslashes($str);
            }
        };
        
        $result = getPostValue('test', 'default');
        $this->assertStringContainsString('Hello', $result);
        
        $result = getPostValue('nonexistent', 'default');
        $this->assertEquals('default', $result);
        
        unset($_POST['test']);
        $GLOBALS['link'] = null;
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
        $this->assertEquals('includes/header-fr.php', $path);
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
