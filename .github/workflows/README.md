# GitHub Workflows

This directory contains GitHub Actions workflows for automated testing, code quality checks, and Docker image building.

## Workflows

### 1. CI Tests (`ci.yml`)

**Triggers:**
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

**Jobs:**

#### Test Matrix
- Tests across PHP versions: 7.4, 8.0, 8.1, 8.2, 8.3
- Installs APCu extension for caching tests
- Runs all test suites:
  - `tests/test_hooks.php` - 8 hook system tests
  - `tests/test_docker_caching.php` - APCu and Docker detection tests
  - `tests/benchmark_optimizations.php` - Performance benchmarks
- Validates PHP syntax for all core files, controllers, models, and hooks

#### MySQL Integration Test
- Sets up MySQL 8.0 service
- Creates test database and tables
- Tests database connection with DBConnection class
- Runs all test suites with database integration

**Status Badge:**
```markdown
![CI Tests](https://github.com/YOUR_USERNAME/PHPWeave/workflows/CI%20Tests/badge.svg)
```

---

### 2. Docker Build (`docker.yml`)

**Triggers:**
- Push to `main` branch
- Version tags (`v*`)
- Pull requests to `main` branch

**Jobs:**

#### Build and Test
- Builds Docker image using Dockerfile
- Verifies PHP, APCu, and PDO extensions
- Runs test suite inside Docker container
- Tests APCu functionality in containerized environment

#### Build and Push
- Only runs on `main` branch or version tags
- Pushes images to GitHub Container Registry (ghcr.io)
- Multi-platform builds (linux/amd64, linux/arm64)
- Tags images with version numbers and `latest`

#### Test Docker Compose
- Tests both `docker-compose.yml` and `docker-compose.dev.yml`
- Verifies service health
- Runs tests inside docker-compose environment

**Status Badge:**
```markdown
![Docker Build](https://github.com/YOUR_USERNAME/PHPWeave/workflows/Docker%20Build/badge.svg)
```

**Published Images:**
```
ghcr.io/YOUR_USERNAME/phpweave:latest
ghcr.io/YOUR_USERNAME/phpweave:main
ghcr.io/YOUR_USERNAME/phpweave:v2.0.0
```

---

### 3. Code Quality (`code-quality.yml`)

**Triggers:**
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

**Jobs:**

#### PHP Syntax Check
- Validates syntax for all PHP files
- Checks core, public, tests, controllers, models, hooks, and jobs
- Ensures no syntax errors before deployment

#### PHPStan Static Analysis
- Runs PHPStan level 5 analysis
- Detects potential bugs and type errors
- Analyzes core application and public files

#### Security Checks
- Scans for hardcoded credentials
- Detects potential SQL injection patterns
- Checks for dangerous `eval()` usage
- Verifies `.env` is not committed
- Ensures `.env.sample` exists

#### Markdown Lint
- Validates all markdown documentation
- Ensures consistent documentation formatting

**Status Badge:**
```markdown
![Code Quality](https://github.com/YOUR_USERNAME/PHPWeave/workflows/Code%20Quality/badge.svg)
```

---

## Setup Instructions

### 1. Enable GitHub Actions

GitHub Actions are automatically enabled when you push these workflow files to your repository.

### 2. Configure Secrets (Optional)

For Docker image pushing to other registries, add these secrets in your repository settings:

- `DOCKER_USERNAME` - Docker Hub username
- `DOCKER_PASSWORD` - Docker Hub password or access token

### 3. Enable GitHub Packages

To push Docker images to GitHub Container Registry:

1. Go to repository Settings → Actions → General
2. Under "Workflow permissions", select "Read and write permissions"
3. Save changes

### 4. Add Status Badges to README

Add these badges to your README.md:

```markdown
[![CI Tests](https://github.com/YOUR_USERNAME/PHPWeave/workflows/CI%20Tests/badge.svg)](https://github.com/YOUR_USERNAME/PHPWeave/actions/workflows/ci.yml)
[![Docker Build](https://github.com/YOUR_USERNAME/PHPWeave/workflows/Docker%20Build/badge.svg)](https://github.com/YOUR_USERNAME/PHPWeave/actions/workflows/docker.yml)
[![Code Quality](https://github.com/YOUR_USERNAME/PHPWeave/workflows/Code%20Quality/badge.svg)](https://github.com/YOUR_USERNAME/PHPWeave/actions/workflows/code-quality.yml)
```

Replace `YOUR_USERNAME` with your GitHub username or organization name.

---

## Testing Locally

You can test workflows locally using [act](https://github.com/nektos/act):

```bash
# Install act
brew install act  # macOS
# or
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash

# Run CI workflow
act -j test

# Run Docker workflow
act -j build-and-test

# Run code quality checks
act -j php-syntax
```

---

## Workflow Performance

**Average Run Times:**
- CI Tests: ~5-8 minutes (matrix of 5 PHP versions)
- Docker Build: ~8-12 minutes (multi-platform builds)
- Code Quality: ~2-4 minutes

**Total for all workflows: ~15-24 minutes**

---

## Troubleshooting

### APCu Tests Failing

If APCu tests fail in CI:
- Check that `apc.enable_cli=1` is set in PHP configuration
- Verify APCu extension is installed correctly
- Review workflow logs for specific error messages

### Docker Build Failing

Common issues:
- Missing or invalid Dockerfile
- Insufficient permissions for GitHub Packages
- Platform-specific build errors (arm64 vs amd64)

### PHPStan Errors

PHPStan may report issues that don't affect functionality:
- Use `ignoreErrors` in phpstan.neon for known false positives
- Gradually increase analysis level (currently at 5)

---

## Contributing

When adding new features:

1. Ensure all tests pass locally before pushing
2. Add new tests for new functionality
3. Update workflows if new dependencies are required
4. Check that all three workflows pass before merging

---

## Continuous Deployment

To enable automatic deployment:

1. Add deployment jobs to `docker.yml`
2. Configure production server credentials as secrets
3. Set up deployment hooks in workflow

Example deployment job:
```yaml
deploy:
  needs: build-and-push
  if: github.ref == 'refs/heads/main'
  runs-on: ubuntu-latest
  steps:
    - name: Deploy to production
      # Add deployment steps here
```

---

## Future Enhancements

Potential workflow improvements:

- [ ] Code coverage reporting with Codecov
- [ ] Automated changelog generation
- [ ] Performance regression testing
- [ ] Automated security scanning with Snyk
- [ ] Automatic version bumping
- [ ] Release notes generation from commits
- [ ] Slack/Discord notifications for build status

---

**All workflows configured and ready to use!**
