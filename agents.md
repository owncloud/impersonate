# agents.md -- Impersonate

## Repository Overview

ownCloud Server 10 app that allows administrators and group admins to impersonate other users for support and troubleshooting purposes. Licensed under AGPL-3.0, with a strategic migration to Apache 2.0 planned as part of the ownCloud OSPO initiative.

## Architecture & Key Paths

- `appinfo/` -- ownCloud app metadata and registration
- `lib/` -- PHP application logic
- `controller/` -- HTTP controllers
- `templates/` -- Server-side templates
- `js/` -- Frontend JavaScript
- `css/` -- Stylesheets
- `l10n/` -- Translation files
- `tests/` -- Unit and integration tests
- `Makefile` -- Build and test automation
- `composer.json` -- PHP dependencies
- `package.json` -- Node.js dependencies (for frontend build)

## Development Conventions

- PHP code follows ownCloud coding standards (phpcs via `vendor-bin/owncloud-codestyle/`)
- Static analysis with PHPStan (`phpstan.neon`)
- SonarCloud for quality gate and coverage
- Translations managed via Transifex

## Build & Test Commands

```bash
make build-dep          # Install dependencies
make js-templates       # Build frontend templates
make dist               # Create distribution tar file
make test-php-unit      # Run PHP unit tests
make clean              # Clean build artifacts
```

## Important Constraints

- Licensed under AGPL-3.0 (copyleft). The OSPO is working toward Apache 2.0 migration, but this requires CLA/DCO audit and copyleft dependency resolution.
- All contributions require a DCO sign-off.
- The LICENSE file in the repository root is the authoritative license source.


## OSPO Policy Constraints

### GitHub Actions
- **Only** use actions owned by `owncloud`, created by GitHub (`actions/*`), verified on the GitHub Marketplace, or verified by the ownCloud Maintainers.
- Pin all actions to their full commit SHA (not tags): `uses: actions/checkout@<SHA> # vX.Y.Z`
- Never introduce actions from unverified third parties.

### Dependency Management
- Dependabot is configured for automated dependency updates.
- Review and merge Dependabot PRs as part of regular maintenance.
- Do not introduce new dependencies without discussion in an issue first.

### Git Workflow
- **Rebase policy**: Always rebase; never create merge commits. Use `git pull --rebase` and `git rebase` before pushing.
- **Signed commits**: All commits **must** be PGP/GPG signed (`git commit -S -s`).
- **DCO sign-off**: Every commit needs a `Signed-off-by` line (`git commit -s`).
- **Conventional Commits & Squash Merge**: Use the [Conventional Commits](https://www.conventionalcommits.org/) format where the repository enforces it. Many repos use squash merge, where the PR title becomes the commit message on the default branch — apply Conventional Commits format to PR titles as well. A reusable GitHub Actions workflow enforces this.

## Context for AI Agents

This is a classic OC10 PHP app with a standard ownCloud app structure. Changes to PHP code should follow PSR-4 autoloading conventions and ownCloud's app framework patterns. The `appinfo/info.xml` defines the app's metadata and version constraints.
