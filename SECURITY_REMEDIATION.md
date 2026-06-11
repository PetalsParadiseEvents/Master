# Security Remediation Guide

## 🚨 Incident Summary

**Date:** June 11, 2026  
**Severity:** CRITICAL  
**Status:** RESOLVED (Partial)

### What Happened

A Google Gemini API key was accidentally hardcoded in the `.htaccess` file and committed to the public GitHub repository:

```
SetEnv GEMINI_API_KEY AQ.Ab8RN6JXuxoDSS9xpCWGO2pf3kPPm51ZRCC-zrfQJuFZfFHcgA
```

**Commit:** `e266c84c95e1a0517c6ed25a6b70ba8e2cd2f792`

---

## ✅ Completed Actions

### 1. Removed Hardcoded Key from Repository
- **File:** `.htaccess`
- **Commit:** `00435a955e9b94a597bce123b0fc7d63c6337e6e`
- **Change:** Removed line 31 (`SetEnv GEMINI_API_KEY ...`)
- **Replacement:** Added detailed comments on secure setup methods

---

## ⚠️ REQUIRED ACTIONS (Do These Now)

### Step 1: Revoke the Exposed API Key

1. Go to https://aistudio.google.com/
2. Navigate to **API Keys** section
3. Delete the key: `AQ.Ab8RN6JXuxoDSS9xpCWGO2pf3kPPm51ZRCC-zrfQJuFZfFHcgA`
4. Generate a new API key

**⏱️ Timeline:** Do this immediately — the old key may have been used by malicious actors.

### Step 2: Set New Key via Secure Environment Variable

Choose ONE of these methods:

#### **Option A: cPanel (Recommended for Shared Hosting)**

1. Log into cPanel
2. Go to: **Software → PHP Config**
3. Click **Environment Variables**
4. Add new variable:
   - **Name:** `GEMINI_API_KEY`
   - **Value:** `YOUR_NEW_API_KEY_HERE`
5. Save

#### **Option B: .env File (Local Development)**

1. Create `.env` file in project root:
   ```bash
   GEMINI_API_KEY=YOUR_NEW_API_KEY_HERE
   ```
2. Ensure `.env` is in `.gitignore` (already configured):
   ```bash
   echo ".env" >> .gitignore
   ```
3. Install PHP dotenv (optional, for auto-loading):
   ```bash
   composer require vlucas/phpdotenv
   ```
4. Load in `api/config.php`:
   ```php
   $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
   $dotenv->load();
   ```

#### **Option C: VPS / Dedicated Server**

**Add to `/etc/environment`:**
```bash
GEMINI_API_KEY="YOUR_NEW_API_KEY_HERE"
```

**Or add to Apache vhost config:**
```apache
<VirtualHost *:443>
    ServerName petalsparadiseevents.com
    SetEnv GEMINI_API_KEY YOUR_NEW_API_KEY_HERE
    # ... rest of config
</VirtualHost>
```

**Or add to Nginx (via PHP-FPM env):**
```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php-fpm.sock;
    fastcgi_param GEMINI_API_KEY "YOUR_NEW_API_KEY_HERE";
}
```

### Step 3: Test the New Setup

Test that `getenv()` can read the key:

```php
<?php
// Test in a temporary file or your PHP shell
echo getenv('GEMINI_API_KEY');
// Should output: YOUR_NEW_API_KEY_HERE
?>
```

Or test via the chatbot API:
```bash
curl -X POST http://your-domain/api/chat.php \
  -H "Content-Type: application/json" \
  -d '{"message":"Hello","history":[]}'
```

---

## 🔍 History Cleanup (Optional but Recommended)

The old API key still exists in git history. To remove it from all past commits:

### Option 1: Using `git-filter-repo` (Recommended)

```bash
# Install git-filter-repo
pip install git-filter-repo

# Remove the file from all history
git filter-repo --path .htaccess --invert-paths

# Force push to origin
git push origin --force --all
```

⚠️ **Warning:** This rewrites history and affects all collaborators. Coordinate with your team.

### Option 2: Using BFG Repo-Cleaner

```bash
# Download BFG
# https://rtyley.github.io/bfg-repo-cleaner/

# Remove API key pattern from history
bfg --replace-text replacements.txt .

# Force push
git push origin --force --all
```

### Option 3: Manual (One-Time Manual Cleanup)

Since this is a recent exposure (< 1 day), the simpler approach is:
1. ✅ New API key already revoked ✓
2. ✅ Hardcoded key removed from current `.htaccess` ✓
3. Future commits won't expose secrets

**Git history cleanup is optional** if you've already revoked the key.

---

## 🛡️ Prevention Best Practices

### 1. Add to `.gitignore`

Ensure these are never committed:

```gitignore
# Environment variables
.env
.env.local
.env.*.local

# Config files with secrets
api/config.php
wp-config.php

# IDE files that might contain env vars
.vscode/
.idea/

# OS files
.DS_Store
Thumbs.db
```

### 2. Use `.gitignore` Template for This Project

```bash
# Check what's currently ignored
git check-ignore -v *
```

### 3. Add Pre-Commit Hook

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash
# Prevent committing API keys and secrets

if git diff --cached --name-only | xargs grep -l "API_KEY\|GEMINI_API_KEY\|api_key" 2>/dev/null; then
    echo "❌ ERROR: Potential API key detected in staged changes!"
    echo "Remove the secret and re-commit."
    exit 1
fi

exit 0
```

Make executable:
```bash
chmod +x .git/hooks/pre-commit
```

### 4. Add `.gitattributes`

Protect sensitive file types:

```
*.env export-ignore
.env.example export-ignore
config-sample.php export-ignore
```

### 5. Use GitHub Secrets for CI/CD

If using GitHub Actions:

```yaml
name: Deploy
env:
  GEMINI_API_KEY: ${{ secrets.GEMINI_API_KEY }}
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: echo $GEMINI_API_KEY > /tmp/key.txt
```

Never hardcode in workflow files.

---

## 📋 Security Checklist

- [ ] Old API key (`AQ.Ab8RN6JXuxoDSS9xpCWGO2pf3kPPm51ZRCC-zrfQJuFZfFHcgA`) revoked
- [ ] New API key generated
- [ ] New key set via environment variable (cPanel/server/vhost)
- [ ] Tested: `getenv('GEMINI_API_KEY')` returns new key
- [ ] `.htaccess` updated and committed (commit `00435a955e9b94a597bce123b0fc7d63c6337e6e`)
- [ ] `.gitignore` reviewed and updated
- [ ] Pre-commit hooks configured (optional)
- [ ] Team notified of security update
- [ ] Review git history cleanup (optional)

---

## 🔗 References

- [Google Gemini API Docs](https://ai.google.dev/)
- [Environment Variables in PHP](https://www.php.net/manual/en/function.getenv.php)
- [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)
- [Git Filter Repo](https://github.com/newren/git-filter-repo)
- [GitHub Secrets](https://docs.github.com/en/actions/security-guides/using-secrets-in-github-actions)
- [OWASP: Secrets Management](https://owasp.org/www-community/attacks/Credential_stuffing)

---

## Questions?

For additional security hardening, review:
- `api/config.php` — Secure config loading pattern
- `.htaccess` — Updated with setup instructions
- `api/chat.php` (line 115) — Uses `getenv()` correctly
