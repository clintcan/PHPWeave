# Contributing to PHPWeave

Thank you for your interest in contributing to PHPWeave! We welcome contributions from the community and appreciate your effort to make this framework better.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Requirements](#testing-requirements)
- [Security](#security)
- [Documentation](#documentation)
- [Pull Request Process](#pull-request-process)
- [Community](#community)

---

## Code of Conduct

PHPWeave follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code. Please report unacceptable behavior to mosaicked_pareja@aleeas.com.

## Getting Started

### Prerequisites

- PHP 7.4+ (PHP 8.0+ recommended for development)
- Composer
- Git
- MySQL/PostgreSQL/SQLite (for database testing)
- Docker (optional, for containerized testing)

### Fork and Clone

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/PHPWeave.git
   cd PHPWeave
   ```

3. **Add upstream remote**:
   ```bash
   git remote add upstream https://github.com/clintcan/PHPWeave.git
   ```

### Environment Setup

1. **Copy environment configuration**:
   ```bash
   cp .env.sample .env
   ```

2. **Configure database** in `.env`:
   ```ini
   DBHOST=localhost
   DBNAME=phpweave_dev
   DBUSER=root
   DBPASSWORD=your_password
   DBCHARSET=utf8mb4
   DEBUG=1
   ```

3. **Install dependencies** (requires PHP 8.1+ for dev tools):
   ```bash
   composer install
   ```

4. **Run tests** to verify setup:
   ```bash
   php tests/test_hooks.php
   php tests/test_models.php
   php tests/test_controllers.php
   ```

---

## Development Workflow

### Branching Strategy

- `main` - Stable production releases
- `develop` - Development branch (submit PRs here)
- `feature/*` - New features
- `bugfix/*` - Bug fixes
- `hotfix/*` - Critical production fixes

### Creating a Feature Branch

```bash
git checkout develop
git pull upstream develop
git checkout -b feature/your-feature-name
```

### Making Changes

1. **Keep commits atomic** - One logical change per commit
2. **Write clear commit messages**:
   ```
   Add connection pooling for database performance

   - Implement ConnectionPool class with size limits
   - Add automatic connection reuse
   - Update documentation with pooling guide

   Improves performance by 6-30% for high-traffic apps
   ```

3. **Update documentation** as you code
4. **Add tests** for new features
5. **Follow coding standards** (see below)

### Syncing with Upstream

```bash
git fetch upstream
git checkout develop
git merge upstream/develop
git push origin develop
```

---

## Coding Standards

### PHP Standards

PHPWeave follows **PSR-12** coding style with some framework-specific conventions.

#### General Rules

- **Indentation**: 4 spaces (no tabs)
- **Line Length**: 120 characters maximum (80-100 preferred)
- **PHP Tags**: Always use `<?php` (never short tags)
- **Encoding**: UTF-8 without BOM
- **EOL**: Unix (LF), not Windows (CRLF)

#### Naming Conventions

**Classes:**
```php
// Class names: PascalCase
class UserController extends Controller {
    // ...
}

class ConnectionPool {
    // ...
}
```

**Methods and Functions:**
```php
// Methods: camelCase
public function getUserById($id) {
    // ...
}

// Private methods: camelCase with underscore prefix (optional)
private function _validateInput($data) {
    // ...
}
```

**Variables:**
```php
// Variables: camelCase or snake_case (framework uses both)
$userId = 123;
$user_model = new UserModel();

// Global objects: PascalCase with $ prefix
global $PW;  // PHPWeave global object
```

**Constants:**
```php
// Constants: UPPERCASE with underscores
define('PHPWEAVE_ROOT', __DIR__);
define('DEFAULT_TIMEOUT', 30);
```

**Files:**
- Controllers: lowercase, e.g., `blog.php`, `user_profile.php`
- Models: lowercase with `_model` suffix, e.g., `user_model.php`
- Libraries: lowercase, e.g., `string_helper.php`
- Core classes: lowercase, e.g., `router.php`, `hooks.php`

#### Code Structure

**Classes:**
```php
<?php
/**
 * Brief class description
 *
 * Detailed description of what this class does,
 * its purpose, and usage examples if needed.
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Routing
 * @author     Your Name
 * @version    2.2.0
 */
class Router {
    /**
     * Public properties
     */
    public $routes = [];

    /**
     * Private properties
     */
    private static $instance = null;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     * @return void
     */
    public function __construct($config = []) {
        // Constructor code
    }

    /**
     * Method description
     *
     * Detailed explanation of what this method does.
     *
     * @param string $path   The URL path
     * @param string $method HTTP method
     * @return array|null Matched route or null
     */
    public function match($path, $method = 'GET') {
        // Method code
    }
}
```

**Documentation Comments:**
```php
/**
 * Brief one-line description
 *
 * Longer description if needed. Explain:
 * - What the function/method does
 * - Important usage notes
 * - Side effects or state changes
 *
 * @param string $param1  Description of param1
 * @param int    $param2  Description of param2 (default: 10)
 * @param bool   $param3  Description of param3 (optional)
 * @return mixed Returns description
 * @throws ExceptionType When and why
 */
```

#### Control Structures

```php
// if/else
if ($condition) {
    // Code
} elseif ($otherCondition) {
    // Code
} else {
    // Code
}

// for loop
for ($i = 0; $i < $count; $i++) {
    // Code
}

// foreach
foreach ($items as $key => $value) {
    // Code
}

// while
while ($condition) {
    // Code
}

// switch
switch ($value) {
    case 'option1':
        // Code
        break;
    case 'option2':
        // Code
        break;
    default:
        // Code
        break;
}
```

#### SQL and Database

**Always use prepared statements:**
```php
// ✅ Good - Prepared statement
$stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// ✅ Good - Named parameters
$stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);

// ❌ Bad - SQL injection vulnerability
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];
$this->pdo->query($sql);
```

#### Output and Views

**Always escape HTML output:**
```php
// ✅ Good - Escaped output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ✅ Good - Helper function (if available)
echo h($userInput);

// ❌ Bad - XSS vulnerability
echo $_GET['name'];
echo $user->comment;
```

#### File Paths

**Use constants and validate:**
```php
// ✅ Good - Use constant
include PHPWEAVE_ROOT . '/coreapp/router.php';

// ✅ Good - Whitelist validation
$allowed = ['home', 'about', 'contact'];
if (in_array($page, $allowed)) {
    include PHPWEAVE_ROOT . "/views/{$page}.php";
}

// ❌ Bad - Path traversal vulnerability
include $_GET['page'] . '.php';
```

---

## Testing Requirements

All contributions **must include tests** and pass existing test suites.

### Running Tests

**Local tests:**
```bash
# Core system tests
php tests/test_hooks.php
php tests/test_models.php
php tests/test_controllers.php

# Connection pooling tests (v2.2.0+)
php tests/test_connection_pool.php

# Docker caching tests
php tests/test_docker_caching.php

# Performance benchmarks
php tests/benchmark_optimizations.php
```

**Static analysis:**
```bash
# PHPStan (type checking)
composer phpstan

# Psalm (security analysis)
composer psalm-security

# Run both
composer check
```

### Writing Tests

Tests should be placed in the `tests/` directory and follow this structure:

```php
<?php
/**
 * Test: Feature Name
 *
 * Description of what this test suite covers.
 */

// Setup
require_once __DIR__ . '/../coreapp/feature.php';

// Test counter
$passed = 0;
$failed = 0;

// Test 1: Description
echo "Test 1: Testing basic functionality... ";
try {
    // Test code
    $result = someFunction();

    if ($result === expected) {
        echo "✓ PASS\n";
        $passed++;
    } else {
        echo "✗ FAIL (expected X, got Y)\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL (exception: {$e->getMessage()})\n";
    $failed++;
}

// More tests...

// Summary
echo "\n========================================\n";
echo "Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "========================================\n";

exit($failed > 0 ? 1 : 0);
```

### Test Coverage

Aim for the following coverage:
- **Core classes**: 80%+ coverage
- **Security-critical code**: 100% coverage
- **New features**: 70%+ coverage
- **Bug fixes**: Must include regression test

---

## Security

Security is a top priority for PHPWeave.

### Security Requirements

All code must:
1. ✅ **Pass Psalm taint analysis** (no TaintedSql, TaintedHtml, etc.)
2. ✅ **Use prepared statements** for all database queries
3. ✅ **Escape all HTML output** from user input
4. ✅ **Validate file paths** before includes
5. ✅ **Sanitize headers** before sending
6. ✅ **Follow OWASP Top 10** best practices

### Running Security Checks

```bash
# Automated security analysis
composer psalm-security

# Basic security patterns
grep -rn "eval(" coreapp/ controller/
grep -rn "\$_GET.*query" coreapp/ controller/
```

### Reporting Security Vulnerabilities

**DO NOT** open public issues for security vulnerabilities!

Instead:
1. Email: mosaicked_pareja@aleeas.com
2. Or use GitHub Security Advisories (private)
3. Include: Detailed description, reproduction steps, impact assessment

See [SECURITY.md](SECURITY.md) for full policy.

---

## Documentation

Good documentation is as important as good code.

### What to Document

1. **Code comments** - For complex logic
2. **PHPDoc blocks** - For all public methods/functions
3. **README updates** - For user-facing changes
4. **Guides in docs/** - For new features
5. **CHANGELOG** - For all changes (we'll handle this)

### Documentation Style

**Code comments:**
```php
// Brief single-line comments for simple explanations

/**
 * Multi-line doc blocks for classes/methods
 * Follow PHPDoc standard
 */
```

**Markdown files:**
- Use clear headings (H1 for title, H2 for sections)
- Include code examples with syntax highlighting
- Add "Why" and "How" sections
- Use tables, lists, and diagrams where helpful

### Creating Documentation

For new features, create a guide in `docs/`:

```markdown
# Feature Name

Brief description of the feature.

## Overview

What problem does this solve?

## Quick Start

Simple example to get started.

## Usage

Detailed usage examples.

## Configuration

Available options and settings.

## Best Practices

Recommended patterns and tips.

## Troubleshooting

Common issues and solutions.
```

Update `docs/README.md` to link to your new guide.

---

## Pull Request Process

### Before Submitting

Checklist:
- [ ] Code follows [coding standards](#coding-standards)
- [ ] All tests pass locally
- [ ] New tests added for new features
- [ ] PHPStan analysis passes (`composer phpstan`)
- [ ] Psalm security analysis passes (`composer psalm-security`)
- [ ] Documentation updated
- [ ] Commits are clean and well-described
- [ ] Branch is up-to-date with `develop`

### Submitting PR

1. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

2. **Create Pull Request** on GitHub:
   - **Base branch**: `develop` (not `main`)
   - **Title**: Clear, concise description
   - **Description**: Use the template below

3. **PR Template**:
   ```markdown
   ## Summary
   Brief description of changes (1-3 sentences)

   ## Changes
   - Added X feature
   - Fixed Y bug
   - Updated Z documentation

   ## Motivation
   Why are these changes needed?

   ## Testing
   - [ ] All existing tests pass
   - [ ] Added new tests for X
   - [ ] Manually tested Y scenario
   - [ ] PHPStan passes
   - [ ] Psalm security passes

   ## Breaking Changes
   List any breaking changes (or "None")

   ## Documentation
   - [ ] Updated docs/
   - [ ] Updated code comments
   - [ ] Updated CLAUDE.md (if needed)

   ## Screenshots (if applicable)
   Add screenshots for UI changes
   ```

### CI/CD Checks

Your PR will automatically run:
1. ✅ **PHP Syntax Check** (PHP 7.4-8.4)
2. ✅ **PHPStan Analysis** (level 5)
3. ✅ **Psalm Security Analysis** (taint analysis)
4. ✅ **Basic Security Checks** (credentials, SQL patterns)
5. ✅ **Markdown Lint**

All checks must pass before merge.

### Review Process

1. **Automated checks** run first
2. **Maintainer review** (1-3 days typically)
3. **Feedback and revisions** (if needed)
4. **Approval and merge** to `develop`

### After Merge

1. Your feature will be included in the next release
2. You'll be credited in release notes
3. Delete your feature branch:
   ```bash
   git branch -d feature/your-feature-name
   git push origin --delete feature/your-feature-name
   ```

---

## Community

### Getting Help

- **Documentation**: Start with [docs/README.md](docs/README.md)
- **Issues**: Check existing [GitHub Issues](https://github.com/clintcan/PHPWeave/issues)
- **Questions**: Open a discussion or issue (not for security!)

### Areas to Contribute

We especially welcome contributions in:

#### 🚀 Features
- New router features (middleware, route groups)
- Enhanced validation library
- Session management improvements
- File upload helpers
- API response helpers

#### 🐛 Bug Fixes
- Check [open issues](https://github.com/clintcan/PHPWeave/issues)
- Look for `good first issue` label

#### 📚 Documentation
- Improve existing guides
- Add more examples
- Create tutorials
- Translate documentation

#### 🧪 Testing
- Increase test coverage
- Add integration tests
- Performance benchmarks
- Edge case testing

#### 🔒 Security
- Security audits
- Vulnerability scanning
- Best practice improvements
- Security documentation

### Recognition

Contributors are credited in:
- Release notes
- GitHub contributors page
- Special recognition for significant contributions

---

## Release Cycle

- **Minor versions** (2.x.0): Every 2-3 months
- **Patch versions** (2.2.x): As needed for bugs
- **Major versions** (3.0.0): When breaking changes needed

---

## Questions?

If you have questions about contributing:
- Open a [GitHub Discussion](https://github.com/clintcan/PHPWeave/discussions)
- Email: mosaicked_pareja@aleeas.com

Thank you for contributing to PHPWeave! 🎉
