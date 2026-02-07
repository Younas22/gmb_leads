# Composer Commands Guide

## Overview
This guide explains all the Composer commands available in the Admin Settings → Performance tab and when to use each one.

---

## 1. **Composer Install**
### Command:
```bash
composer install --no-interaction --prefer-dist
```

### When to Use:
- **First time setup**: After cloning a new project from Git
- **After git pull**: When someone else updated dependencies
- **Production deployment**: Install exact versions from composer.lock
- **After deleting vendor folder**: Reinstall all dependencies

### What it Does:
- Reads `composer.lock` file (if it exists)
- Installs exact versions specified in the lock file
- Does NOT update packages to newer versions
- Creates/updates the `vendor/` directory

### Example Scenarios:
✅ You just cloned the project
✅ Teammate added a new package and pushed composer.lock
✅ Setting up project on a new server

---

## 2. **Composer Update**
### Command:
```bash
composer update --no-interaction --prefer-dist
```

### When to Use:
- **Update all packages**: Get latest versions within version constraints
- **After changing composer.json**: When you manually edit dependency versions
- **Security updates**: Update packages with known vulnerabilities
- **Feature updates**: Get new features from packages

### What it Does:
- Reads `composer.json` file
- Updates packages to latest versions matching constraints
- Updates `composer.lock` file with new versions
- Can break compatibility if not careful

### ⚠️ Warning:
This can introduce breaking changes. Always test after running update.

### Example Scenarios:
✅ You want to update Laravel from 10.x to 10.y
✅ A package has a security fix
✅ You changed version constraint in composer.json
❌ **NOT** for production deployment

---

## 3. **Composer Dump-Autoload**
### Command:
```bash
composer dump-autoload --optimize
```

### When to Use:
- **After creating new classes**: PHP can't find your new class
- **After moving/renaming files**: Class autoloading is broken
- **After adding PSR-4 namespaces**: New namespace not recognized
- **Performance optimization**: Optimize autoloader for production

### What it Does:
- Regenerates the `vendor/autoload.php` file
- Scans directories for PHP classes
- Creates optimized class map
- **Does NOT** download or update packages

### Example Scenarios:
✅ Created new Controller but getting "Class not found"
✅ Moved files to different folder
✅ Added new namespace to composer.json
✅ Before deploying to production (optimization)

---

## 4. **Composer Clear Cache**
### Command:
```bash
composer clear-cache
```

### When to Use:
- **Download errors**: Package download keeps failing
- **Corrupted cache**: Getting weird errors during install/update
- **Disk space issues**: Clear old cached packages
- **Force fresh download**: Ensure you get latest package files

### What it Does:
- Clears Composer's internal cache directory
- Forces fresh download on next install/update
- Removes cached package archives
- Typically located in `~/.composer/cache`

### Example Scenarios:
✅ "Package download failed" error
✅ Corrupted zip file errors
✅ Composer showing outdated package info
✅ Need to free up disk space

---

## 5. **Composer Diagnose**
### Command:
```bash
composer diagnose
```

### When to Use:
- **Troubleshooting**: Composer not working properly
- **System check**: Verify Composer installation
- **Network issues**: Check if Packagist is reachable
- **Permission problems**: Verify file/folder permissions

### What it Does:
- Checks PHP version and extensions
- Tests network connectivity to Packagist
- Verifies git configuration
- Checks file permissions
- Validates Composer installation

### Example Scenarios:
✅ Composer commands failing randomly
✅ Can't download packages
✅ After server configuration changes
✅ Debugging installation issues

---

## 6. **Composer Validate**
### Command:
```bash
composer validate --no-check-all --no-check-publish
```

### When to Use:
- **Before committing**: Validate composer.json changes
- **After manual edits**: Check for syntax errors
- **CI/CD pipelines**: Automated validation
- **Pull request reviews**: Ensure valid composer files

### What it Does:
- Validates `composer.json` syntax
- Checks for required fields
- Validates version constraints
- Checks `composer.lock` consistency
- Reports warnings and errors

### Example Scenarios:
✅ Manually edited composer.json
✅ Before git commit
✅ Getting errors during install/update
✅ Contributing to open source (validate before PR)

---

## Common Workflow Examples

### 📥 **Setting up a new project**
```bash
1. git clone <repository>
2. Run: composer install
3. Copy .env.example to .env
4. Run: php artisan key:generate
```

### 🔄 **After git pull with new dependencies**
```bash
1. git pull origin main
2. Run: composer install
3. Run: php artisan migrate
```

### 📝 **After creating new classes/files**
```bash
1. Create new Controller/Model
2. Run: composer dump-autoload
3. Clear application cache if needed
```

### 🔧 **Fixing composer issues**
```bash
1. Run: composer diagnose
2. If cache issues: composer clear-cache
3. Run: composer install
```

### ✅ **Before committing code**
```bash
1. Run: composer validate
2. Test the application
3. git commit
```

### 🚀 **Production Deployment**
```bash
1. git pull origin main
2. Run: composer install --no-dev --optimize-autoloader
3. Run: php artisan config:cache
4. Run: php artisan route:cache
5. Run: php artisan view:cache
```

---

## Best Practices

### ✅ DO:
- Run `composer install` in production (not update)
- Use `composer dump-autoload` after creating new classes
- Run `composer validate` before committing
- Keep `composer.lock` in version control
- Use `--optimize-autoloader` flag in production

### ❌ DON'T:
- Run `composer update` in production without testing
- Delete composer.lock file
- Manually edit vendor/ directory
- Ignore composer warnings
- Skip validation before committing

---

## Performance Tab Commands Summary

| Command | Purpose | Frequency | Risk Level |
|---------|---------|-----------|------------|
| **Clear Cache** | Clear Laravel cache | Daily/As needed | Low |
| **Optimize Database** | Optimize DB tables | Weekly/Monthly | Low |
| **Composer Install** | Install dependencies | After git pull | Low |
| **Composer Update** | Update packages | Monthly | Medium |
| **Dump Autoload** | Refresh class map | After new files | Low |
| **Clear Composer Cache** | Fix download issues | As needed | Low |
| **Diagnose** | Troubleshoot issues | When problems occur | Low |
| **Validate** | Check composer files | Before commits | Low |

---

## Troubleshooting

### Problem: "Class not found" error
**Solution**: Run `composer dump-autoload`

### Problem: Package download fails
**Solution**:
1. Run `composer diagnose`
2. Run `composer clear-cache`
3. Try `composer install` again

### Problem: Out of memory during update
**Solution**: Increase PHP memory limit
```bash
php -d memory_limit=-1 /path/to/composer.phar update
```

### Problem: Slow composer install
**Solution**:
1. Clear cache: `composer clear-cache`
2. Use `--prefer-dist` flag
3. Enable parallel downloads in composer.json

---

## Need Help?

- Official Docs: https://getcomposer.org/doc/
- Laravel Docs: https://laravel.com/docs/packages
- Stack Overflow: https://stackoverflow.com/questions/tagged/composer-php

---

**Last Updated**: February 2026
**Version**: 1.0
