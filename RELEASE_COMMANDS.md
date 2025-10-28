# PHPWeave v2.2.0 Release Commands

Follow these steps to release PHPWeave v2.2.0:

## 1. Verify All Changes Are Committed

```bash
cd "D:\Projects\misc\Frameworks\PHPWeave"
git status
```

**Expected output:** "working tree clean" or only untracked files

If you have uncommitted changes:
```bash
git add .
git commit -m "Release v2.2.0: Multi-database support

- Added support for MySQL, PostgreSQL, SQLite, SQL Server, ODBC
- Fixed critical bugs in DBConnection class
- Enhanced Docker images with all PDO drivers
- Added comprehensive documentation
- Full backward compatibility maintained
"
```

## 2. Create Annotated Git Tag

```bash
git tag -a v2.2.0 -m "PHPWeave v2.2.0: Multi-Database Support

Major Features:
- Multi-database driver support (MySQL, PostgreSQL, SQLite, SQL Server, ODBC)
- New configuration variables: DBDRIVER, DBPORT, DBDSN
- Docker images with all PDO extensions pre-installed
- Comprehensive database support documentation

Bug Fixes:
- Fixed critical ODBC fallthrough bug
- Fixed SQL Server DSN format
- Fixed PostgreSQL charset handling
- Fixed SQLite credential handling

Documentation:
- Added docs/DOCKER_DATABASE_SUPPORT.md (550+ lines)
- Updated all Docker and database documentation
- Added CHANGELOG.md with complete version history

Backward Compatibility:
- 100% backward compatible with v2.1.x
- Default values for all new configuration variables
- Old .env files work without changes
"
```

## 3. Verify Tag Was Created

```bash
git tag -l -n9 v2.2.0
```

## 4. Push Changes to GitHub

```bash
# Push commits
git push origin main

# Push the tag
git push origin v2.2.0
```

## 5. Create GitHub Release

### Option A: Using GitHub CLI (gh)

```bash
gh release create v2.2.0 \
  --title "PHPWeave v2.2.0: Multi-Database Support" \
  --notes-file RELEASE_NOTES_v2.2.0.md
```

### Option B: Using GitHub Web Interface

1. Go to: https://github.com/clintcan/PHPWeave/releases/new
2. Select tag: `v2.2.0`
3. Release title: `PHPWeave v2.2.0: Multi-Database Support`
4. Copy contents from `RELEASE_NOTES_v2.2.0.md` into description
5. Check "Set as the latest release"
6. Click "Publish release"

## 6. Verify GitHub Actions

After pushing, verify workflows pass:
```bash
# View workflow status
gh run list --limit 5

# Or visit:
# https://github.com/clintcan/PHPWeave/actions
```

## 7. Update Docker Hub (if applicable)

If you publish to Docker Hub:

```bash
# Build and tag
docker build -t clintcan/phpweave:2.2.0 .
docker tag clintcan/phpweave:2.2.0 clintcan/phpweave:latest

# Push to Docker Hub
docker push clintcan/phpweave:2.2.0
docker push clintcan/phpweave:latest
```

## 8. Update Packagist (if using Composer)

If PHPWeave is on Packagist, it should auto-update from the GitHub tag.

Visit: https://packagist.org/packages/clintcan/phpweave
Click "Update" if it doesn't auto-update.

## 9. Announce the Release

### GitHub Discussions (if enabled)
1. Go to Discussions
2. Create announcement post
3. Link to release notes

### Social Media / Community
Share the release:
- Twitter/X
- Reddit (r/PHP)
- Dev.to
- PHP community forums

Example announcement:
```
🚀 PHPWeave v2.2.0 is now available!

🎉 Major new feature: Multi-database support
✅ MySQL, PostgreSQL, SQLite, SQL Server, ODBC
🐳 Enhanced Docker images with all PDO drivers
📚 Comprehensive documentation
🔄 100% backward compatible

Release notes: https://github.com/clintcan/PHPWeave/releases/tag/v2.2.0
```

## 10. Create Next Development Branch (Optional)

```bash
git checkout -b development
git push origin development
```

---

## Verification Checklist

Before releasing, verify:

- [ ] All files committed
- [ ] CHANGELOG.md updated with v2.2.0
- [ ] Version numbers updated (if applicable)
- [ ] Tests passing locally
- [ ] Documentation reviewed
- [ ] RELEASE_NOTES_v2.2.0.md created
- [ ] Tag created with proper message
- [ ] Tag pushed to GitHub
- [ ] GitHub release created
- [ ] GitHub Actions workflows passing
- [ ] Docker images building successfully

---

## Rollback (if needed)

If you need to rollback:

```bash
# Delete local tag
git tag -d v2.2.0

# Delete remote tag
git push origin :refs/tags/v2.2.0

# Delete GitHub release (via web interface or gh cli)
gh release delete v2.2.0
```

---

## Post-Release Tasks

- [ ] Update README with release badge (if using shields.io)
- [ ] Monitor GitHub Issues for bug reports
- [ ] Monitor GitHub Actions for any failures
- [ ] Update any external documentation or wikis
- [ ] Start planning next release features

---

**Ready to release!** 🎉

Just run the commands above in order, and PHPWeave v2.2.0 will be live!
