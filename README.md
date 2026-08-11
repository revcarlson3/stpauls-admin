# stpauls-admin

St. Paul's Admin — WordPress plugin for managing events, teams, and volunteers.

The wordpress plugin currently known as stpauls-admin

Requirements
- PHP 8.5
- WordPress 7

Installation
1. Copy the `stpauls-admin` folder into `wp-content/plugins/` on your dev site.
2. Activate the plugin in WP Admin → Plugins.
3. Visit WP Admin → St. Paul's Admin to configure and test.

Testing
- Use a development database and backup before running on production.

Versioning
- Releases use monotonically increasing SemVer-style versions: `MAJOR.MINOR.PATCH`, with `-alpha`, `-beta`, or `-rc` prerelease labels when applicable.
- Never reuse or lower a released version. The current release is `0.1.21-beta2`, which is newer than the previous `0.1.20-beta`.
- Database schema revisions are tracked separately from the plugin version in `SPA_DB_VERSION`.

License: GPL2
