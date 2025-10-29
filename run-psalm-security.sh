#!/bin/bash
# PHPWeave - Run Psalm Security Analysis Locally
# This script runs Psalm taint analysis to detect security vulnerabilities

echo "============================================"
echo "PHPWeave Psalm Security Analysis"
echo "============================================"
echo ""

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "ERROR: Composer not found in PATH"
    echo "Please install Composer: https://getcomposer.org/"
    exit 1
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install --no-interaction
    echo ""
fi

# Check if Psalm is installed
if [ ! -f "vendor/bin/psalm" ]; then
    echo "Installing Psalm 6.x (PHP 8.4 compatible)..."
    composer require --dev vimeo/psalm:^6.0
    echo ""
fi

echo "Running Psalm Taint Analysis (Security Scan)..."
echo "This may take 30-60 seconds..."
echo ""

vendor/bin/psalm --taint-analysis --no-cache

if [ $? -eq 0 ]; then
    echo ""
    echo "============================================"
    echo "SUCCESS: No security vulnerabilities found!"
    echo "============================================"
else
    echo ""
    echo "============================================"
    echo "WARNING: Potential security issues detected"
    echo "Please review the output above"
    echo "============================================"
fi

echo ""
echo "To run standard analysis: vendor/bin/psalm"
echo "To run both: composer check"
echo ""
