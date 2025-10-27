# GitHub Workflows Quick Start Guide

## Overview

PHPWeave includes 3 automated GitHub Actions workflows that run tests, build Docker images, and check code quality.

## What Gets Tested Automatically?

Every time you push code or create a pull request, GitHub automatically:

✅ **Tests your code** across 5 PHP versions (7.4, 8.0, 8.1, 8.2, 8.3)
✅ **Validates syntax** of all PHP files
✅ **Runs test suite** (hooks, caching, benchmarks)
✅ **Checks security** (hardcoded credentials, SQL injection)
✅ **Builds Docker images** and tests them
✅ **Analyzes code quality** with PHPStan
✅ **Verifies database integration** with MySQL

## Setup (First Time Only)

### Step 1: Enable GitHub Actions

Nothing to do! Actions are automatically enabled when you push these files.

### Step 2: Update Badge URLs

In `README.md`, replace `YOUR_USERNAME` with your GitHub username:

```markdown
[![CI Tests](https://github.com/YOUR_USERNAME/PHPWeave/workflows/CI%20Tests/badge.svg)]
```

Change to:
```markdown
[![CI Tests](https://github.com/clintcan/PHPWeave/actions/workflows/ci.yml/badge.svg?branch=main)]
```

### Step 3: Enable Package Publishing (Optional)

To publish Docker images to GitHub Container Registry:

1. Go to your repository on GitHub
2. Click **Settings** → **Actions** → **General**
3. Scroll to **Workflow permissions**
4. Select **Read and write permissions**
5. Click **Save**

Done! Your workflows are now fully configured.

## What Happens When You Push Code?

### On Every Push to `main` or `develop`:

1. **CI Tests** workflow starts (~5-8 minutes)
   - Tests across 5 PHP versions in parallel
   - Tests with MySQL database
   - Validates all syntax

2. **Code Quality** workflow starts (~2-4 minutes)
   - Checks PHP syntax
   - Runs PHPStan analysis
   - Scans for security issues
   - Lints markdown files

3. **Docker Build** workflow starts (~8-12 minutes)
   - Builds Docker image
   - Tests in container
   - Tests docker-compose setups

### On Version Tags (e.g., `v2.0.0`):

All of the above, PLUS:
- Publishes Docker image to GitHub Container Registry
- Tags image with version number
- Creates `latest` tag

## Viewing Workflow Results

1. Go to your GitHub repository
2. Click the **Actions** tab
3. You'll see all workflow runs with ✅ (pass) or ❌ (fail) indicators

Click any run to see detailed logs.

## Common Scenarios

### ✅ All checks pass

Great! Your code is ready to merge.

### ❌ CI Tests fail

Check the logs to see which test failed:
- PHP syntax error? Fix the syntax
- Test failure? Update the test or fix the bug
- Different PHP version? Check version compatibility

### ❌ Code Quality fails

Check the PHPStan output:
- Type errors? Add type hints or fix types
- Security warning? Review the flagged code
- False positive? Add to `ignoreErrors` in phpstan.neon

### ❌ Docker Build fails

Common causes:
- Dockerfile syntax error
- Missing dependency
- Platform-specific issue (check logs for amd64 vs arm64)

## Running Tests Locally (Before Pushing)

Avoid failed CI runs by testing locally first:

```bash
# Run all tests
php tests/test_hooks.php
php tests/test_docker_caching.php
php tests/benchmark_optimizations.php

# Check syntax
find coreapp -name "*.php" -exec php -l {} \;

# Test Docker
docker build -t phpweave:test .
docker run --rm phpweave:test php tests/test_hooks.php
```

## Pull Request Workflow

When you create a PR:

1. All 3 workflows run automatically
2. Status checks appear at the bottom of the PR
3. **Must pass** before merging (if branch protection is enabled)
4. Reviewers can see test results inline

## Publishing Docker Images

When you push a version tag:

```bash
git tag v2.0.0
git push origin v2.0.0
```

This triggers:
1. All tests run
2. If tests pass, Docker image is built
3. Image is published to: `ghcr.io/YOUR_USERNAME/phpweave:v2.0.0`
4. Also tagged as: `ghcr.io/YOUR_USERNAME/phpweave:latest`

Pull the published image:
```bash
docker pull ghcr.io/YOUR_USERNAME/phpweave:latest
```

## Troubleshooting

### Workflows not running?

- Check `.github/workflows/` files exist
- Verify GitHub Actions is enabled (Settings → Actions)
- Check branch name matches triggers (main, develop)

### APCu tests failing?

- This is rare - APCu is installed in workflow
- Check workflow logs for specific error
- May indicate actual bug in caching code

### Permission denied (Docker publish)?

- Enable "Read and write permissions" in Settings → Actions
- Check you're pushing to main branch or version tag

### Test passes locally but fails in CI?

- Environment difference (paths, extensions)
- PHP version difference (test on multiple versions)
- Check workflow logs for exact error

## Advanced: Custom Workflows

Add your own workflows in `.github/workflows/`:

```yaml
name: My Custom Check

on:
  push:
    branches: [ main ]

jobs:
  custom:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run custom script
        run: php my-script.php
```

## Workflow Status

Check current status of all workflows:

**Main Branch:**
- [![CI Tests](https://github.com/clintcan/PHPWeave/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/YOUR_USERNAME/PHPWeave/actions/workflows/ci.yml)
- [![Docker Build](https://github.com/clintcan/PHPWeave/actions/workflows/docker.yml/badge.svg?branch=main)](https://github.com/YOUR_USERNAME/PHPWeave/actions/workflows/docker.yml)
- [![Code Quality](https://github.com/clintcan/PHPWeave/actions/workflows/code-quality.yml/badge.svg?branch=main)](https://github.com/YOUR_USERNAME/PHPWeave/actions/workflows/code-quality.yml)

## Need Help?

- Check `.github/workflows/README.md` for detailed documentation
- Review workflow YAML files in `.github/workflows/`
- Check GitHub Actions logs for error details
- Review test scripts in `tests/` directory

---

**That's it! Your CI/CD is fully automated.** 🚀
