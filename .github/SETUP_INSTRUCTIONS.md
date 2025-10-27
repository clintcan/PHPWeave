# GitHub Repository Setup Instructions

## GitHub Container Registry (ghcr.io) Permissions

The Docker workflow needs permission to push images to GitHub Container Registry. Follow these steps:

### Option 1: Enable GitHub Actions Permissions (Recommended)

1. Go to your repository on GitHub
2. Click **Settings** → **Actions** → **General**
3. Scroll down to **Workflow permissions**
4. Select **Read and write permissions**
5. Check **Allow GitHub Actions to create and approve pull requests** (optional)
6. Click **Save**

### Option 2: Use a Personal Access Token (PAT)

If Option 1 doesn't work, you can create a PAT:

1. Go to GitHub → **Settings** → **Developer settings** → **Personal access tokens** → **Tokens (classic)**
2. Click **Generate new token**
3. Give it a descriptive name (e.g., "PHPWeave GHCR Push")
4. Select scopes:
   - `write:packages` - Upload packages to GitHub Package Registry
   - `read:packages` - Download packages from GitHub Package Registry
   - `delete:packages` (optional) - Delete packages from GitHub Package Registry
5. Click **Generate token** and copy the token
6. In your repository, go to **Settings** → **Secrets and variables** → **Actions**
7. Click **New repository secret**
8. Name: `GHCR_TOKEN`
9. Value: Paste your PAT
10. Click **Add secret**

## Verifying Package Visibility

After your first successful push:

1. Go to your GitHub profile
2. Click on **Packages** tab
3. Find the `phpweave` package
4. Click on it and go to **Package settings**
5. Under **Manage Actions access**, ensure your repository is listed
6. Under **Danger Zone**, you can change visibility if needed

## Troubleshooting

### "permission_denied: write_package" Error

This error means GitHub Actions doesn't have permission to push to the container registry.

**Solutions:**
1. Ensure you've completed Option 1 or Option 2 above
2. If using a fork, you may need to use Option 2 (PAT)
3. Check that the repository name is lowercase in the workflow (already handled by the workflow)

### "Package already exists" Error

If someone else owns the package name on ghcr.io:
1. Change the image name in the workflow
2. Or use Docker Hub instead of ghcr.io

## Docker Hub Alternative

If you prefer Docker Hub:

1. Create a Docker Hub account at https://hub.docker.com
2. Create a repository named `phpweave`
3. In GitHub, create two secrets:
   - `DOCKERHUB_USERNAME`: Your Docker Hub username
   - `DOCKERHUB_TOKEN`: Your Docker Hub access token
4. Update the workflow to use Docker Hub instead of ghcr.io

## Workflow Files

The repository includes these workflow files:
- `.github/workflows/docker.yml` - Builds and pushes Docker images
- `.github/workflows/ci.yml` - Runs tests
- `.github/workflows/code-quality.yml` - Runs PHPStan and security checks

All workflows are configured to run on push to `main` branch and on pull requests.