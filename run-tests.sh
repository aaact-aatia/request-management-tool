#!/bin/bash
# Quick test runner for RMT project

set -e

echo "🧪 RMT Testing Suite"
echo "===================="
echo ""

# Check if PHPUnit is installed
if [ ! -f "vendor/bin/phpunit" ]; then
    echo "⚠️  PHPUnit not installed. Installing dependencies..."
    composer require --dev phpunit/phpunit
fi

echo "📝 Running Unit Tests..."
echo "------------------------"
vendor/bin/phpunit tests/Unit --colors=always

echo ""
echo "🔗 Running Integration Tests..."
echo "--------------------------------"
php tests/Integration/RequestWorkflowTest.php

echo ""
echo "✅ All tests completed!"
