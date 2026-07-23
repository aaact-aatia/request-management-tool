# RMT Testing Suite

Automated tests for the Request Management Tool refactoring project.

## Running Tests

### Intake Integration Tests

```bash
./run-tests.sh
```

The runner starts a dedicated tmpfs MySQL database, waits for health, and executes the integration test inside a PHP 8.2 container. PHP is not required on the host. The test project is removed afterward and does not read from, write to, reset, or stop the development database.

### Unit Tests Only

Tests individual helper functions in isolation:

```bash
vendor/bin/phpunit tests/Unit
```

Or specific test file:

```bash
vendor/bin/phpunit tests/Unit/HelpersTest.php
```

### Smoke Tests

Quick validation that pages load without errors:

```bash
php tests/smoke-test.php
```

## What's Being Tested

### Unit Tests (`tests/Unit/HelpersTest.php`)

Tests all functions in `app/includes/helpers.php`:

- **Permission helpers**: `isAdmin()`, `canEditRequests()`, `canManageSLA()`, `isReadOnly()`
- **Value helpers**: `hasValue()`, `getPostValue()`, `getGetValue()`
- **Database helpers**: `getDropdownOptions()`, `getServicesByCategory()`, etc.
- **HTML rendering**: `renderTextInput()`, `renderDateInput()`, `renderSelect()`, etc.
- **Date helpers**: `getDateRange()`, `getTodayDate()`
- **Language helpers**: `detectLanguage()`, `getIncludePath()`

**Current Coverage**: ~20 test methods covering all helper functions

### Integration Tests (`tests/Integration/RequestWorkflowTest.php`)

Tests the database-driven intake contract:

- Services and subservices have valid parents
- Valid catalogue, service, and subservice combinations are accepted
- Services without subservices normalize the child ID to `0`
- Terminal catalogues are accepted without a service
- Cross-catalogue, inactive, missing, and unknown IDs are rejected

### Smoke Tests (`tests/smoke-test.php`)

Quick validation:

- Pages load without fatal PHP errors
- Critical routes are accessible (EN/FR)
- Helper functions work in production context
- No parse errors or warnings

## Test Results Format

```
✅ PASS: Test description
❌ FAIL: Test description
```

## Adding New Tests

### Adding Unit Tests

Edit `tests/Unit/HelpersTest.php`:

```php
public function testMyNewHelper()
{
    $result = myNewHelper('input');
    $this->assertEquals('expected', $result);
}
```

### Adding Integration Tests

Edit `tests/Integration/RequestWorkflowTest.php`:

```php
private function testMyWorkflow()
{
    echo "Testing my workflow...\n";
    
    // Setup test data
    $_POST['field'] = 'value';
    
    // Test logic
    $result = processData($_POST);
    
    // Assert
    $this->assert(
        $result === 'expected',
        "Should process data correctly",
        "Details about the test"
    );
}
```

## CI/CD Integration

Add to your deployment pipeline:

```bash
# In azure-pipelines.yml or GitHub Actions
- run: composer install --dev
- run: ./run-tests.sh
```

## Benefits of Automated Testing

- **Catch regressions** - Ensure refactoring does not break existing functionality
- **Document behavior** - Tests serve as living documentation
- **Faster development** - Validate changes without local PHP
- **Isolation** - Preserve developer and production-like data during tests

## Current Test Coverage

- **Helper Functions**: 100% (all 15+ functions tested)
- **Request Workflows**: ~60% (major paths covered)
- **Edge Cases**: ~40% (improving over time)

## Future Improvements

- [ ] Browser automation tests (Selenium/Playwright)
- [ ] API endpoint tests
- [ ] Performance benchmarks
- [ ] Code coverage reports
- [ ] Continuous integration setup
