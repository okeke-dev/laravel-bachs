# Release checklist

Step-by-step guide for publishing a new release of **laravel-bachs**.

## Pre-release

1. **Run the full quality gate:**

   ```bash
   composer check
   ```

   This runs Pest tests, PHPStan (level 6), and Pint. All must pass.

2. **Review the diff:**

   ```bash
   git log --oneline main
   git diff HEAD~10 --stat
   ```

3. **Verify CHANGELOG.md** has an `[Unreleased]` section with all new
   entries, and a versioned section with today's date ready to ship.

## Version & tag

4. **Move `[Unreleased]` to the new version** in `CHANGELOG.md`:

   ```markdown
   ## Unreleased

   _No changes yet._

   ## v0.2.0 — 2026-09-15

   ### Added
   - ...
   ```

5. **Commit the changelog update:**

   ```bash
   git add CHANGELOG.md
   git commit -m "docs: prepare v0.2.0 release"
   ```

6. **Create the git tag:**

   ```bash
   git tag -a v0.2.0 -m "Release v0.2.0"
   ```

7. **Push commit and tag together:**

   ```bash
   git push origin main --tags
   ```

## Packagist

8. **Submit to Packagist** (first release only):

   - Go to <https://packagist.org/packages/okeke-dev/laravel-bachs>
   - Click "Register" and enter the GitHub repository URL
   - Packagist will auto-detect `composer.json` and set up webhooks

9. **Subsequent releases** are picked up automatically via the GitHub
   webhook. If not, click "Update" on the Packagist package page.

## Post-release

10. **Verify the tag on GitHub:**

    ```bash
    git tag -l
    gh release list
    ```

11. **Verify Packagist** shows the new version at
    <https://packagist.org/packages/okeke-dev/laravel-bachs>.

12. **Test installation** in a fresh Laravel project:

    ```bash
    composer require okeke-dev/laravel-bachs:^0.2.0
    php artisan bachs:install
    ```

## Versioning policy

This project follows [Semantic Versioning](https://semver.org/):

- **MAJOR** — breaking API changes (not expected until 1.0)
- **MINOR** — new features, backward-compatible
- **PATCH** — bug fixes, backward-compatible

While in `0.x`, minor bumps may contain breaking changes. They will be
documented in `CHANGELOG.md`.
