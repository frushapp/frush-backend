# GitHub Actions Automated Deployment Setup

This guide will help you set up automated deployments for your FRUSH backend to your production server.

## Prerequisites

1. SSH access to your production server
2. Git installed on your production server
3. Your Laravel project already deployed on the server

## Setup Instructions

### Step 1: Generate SSH Key (if you don't have one)

On your **local machine** or **server**, run:

```bash
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy"
```

- Save it to a location like `~/.ssh/github_actions_deploy`
- **DO NOT** set a passphrase (leave it empty for automated deployments)

This will generate two files:
- `github_actions_deploy` (private key)
- `github_actions_deploy.pub` (public key)

### Step 2: Add Public Key to Server

Copy the **public key** to your server's authorized keys:

```bash
# On your server
mkdir -p ~/.ssh
echo "your-public-key-content-here" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh
```

Or use ssh-copy-id:
```bash
ssh-copy-id -i ~/.ssh/github_actions_deploy.pub user@your-server-ip
```

### Step 3: Add Secrets to GitHub Repository

Go to your GitHub repository:
1. Navigate to **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Add the following secrets:

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `SSH_HOST` | Your server IP or domain | `123.45.67.89` or `api.frush.com` |
| `SSH_USERNAME` | SSH username | `root` or `ubuntu` or your username |
| `SSH_PRIVATE_KEY` | The **entire content** of your private key file | Contents of `github_actions_deploy` file |
| `SSH_PORT` | SSH port (usually 22) | `22` |
| `PROJECT_PATH` | Full path to your Laravel project on server | `/var/www/html/frush` or `/home/user/frush` |

### Step 4: Test the Deployment

1. **Manual Test**: Go to your GitHub repository → **Actions** → **Deploy to Production Server** → **Run workflow**
2. **Automatic Test**: Push any change to the `main` branch

### Step 5: Verify Deployment

After pushing to `main`, you should see:
1. A new workflow run in the **Actions** tab
2. If successful, your changes will be live on the server
3. Laravel caches will be cleared automatically

## Troubleshooting

### Permission Denied (publickey)
- Make sure the public key is added to `~/.ssh/authorized_keys` on the server
- Ensure the private key in GitHub secrets is complete (including BEGIN/END lines)
- Check SSH port is correct (default is 22)

### Git Pull Fails
- Ensure the server has access to pull from GitHub (SSH key or token)
- Check if the project directory path is correct

### Artisan Commands Fail
- Make sure PHP is in the server's PATH
- The `php artisan` commands assume you're using the default PHP installation
- You might need to use full path: `/usr/bin/php artisan` or `php8.1 artisan`

## Important Notes

⚠️ **First Deployment**: The GitHub Action will only trigger on **future pushes** to main. For the current changes, you still need to deploy manually once.

🔒 **Security**: Never commit your SSH private key to the repository. Always use GitHub Secrets.

🔄 **Rollback**: If a deployment fails, you can manually SSH into the server and run `git checkout` to the previous commit.

## Manual Deployment Commands

If you ever need to deploy manually, SSH into your server and run:

```bash
cd /path/to/your/frush/backend
git pull origin main
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
```

---

**Created by**: Antigravity AI  
**Date**: 2026-01-07
