# GitHub Copilot Instructions

## Priority Guidelines

When generating code for this repository:

1. **Version Compatibility**: This is a Cacti plugin (`cycle`, "Cycle Graphs", version 4.3) targeting Cacti 1.1.22+
2. **Context Files**: Prioritize patterns and standards defined in this file (`.github/copilot-instructions.md`)
3. **Codebase Patterns**: When context files don't provide specific guidance, scan the codebase for established patterns
4. **Architectural Consistency**: Maintain plugin-based architecture extending Cacti core
5. **Code Quality**: Prioritize security, maintainability, and compatibility in all generated code

## Technology Stack

### Core Technologies
- **PHP**: 7.4+ (targeting Cacti 1.2.x compatibility)
- **Platform**: Cacti Plugin Architecture (Cacti 1.1.22+) — automatically cycles through a set of graphs on a console/kiosk display
- **Database**: MySQL/MariaDB via Cacti's DB abstraction layer

### Key Dependencies
- Cacti core framework (`api_plugin_*`, `db_*`)
- `cycle.js` client-side rotation/timer logic

## Project Structure

```
cycle/                # Repository root (install to plugins/cycle/ in Cacti)
├── includes/         # Library/helper files, require_once'd from the entry points
│   └── functions.php # Shared helper functions
├── images/           # UI icons
├── locales/          # Internationalization files
├── cycle.js          # Client-side graph rotation logic
├── cycle.php         # Main cycle viewer/administration UI
├── INFO              # Plugin metadata (name, version, compat)
├── README.md
└── setup.php         # Plugin install/uninstall/upgrade hooks
```

## Naming Conventions

### Function Names
Hook/lifecycle functions use the `cycle_` prefix: `cycle_show_tab()`, `cycle_config_arrays()`, `cycle_api_graph_save()`, `cycle_page_head()`. Match the existing prefix used by the function you are editing; do not introduce a new naming scheme.

### Database Tables
All plugin tables are prefixed `plugin_cycle_`.

## Code Style

### Indentation and Formatting
- **Tabs**: Use tabs (not spaces) for indentation throughout all PHP files.
- **Braces**: Opening brace on the same line for functions and control structures.
- **Spacing**: Space after control structure keywords (`if`, `foreach`, `while`).

### File Headers
ALL PHP files MUST include the standard GPL v2 license header used throughout this repository (see `setup.php`), crediting "The Cacti Group".

## Security Standards

### SQL Query Security
Use prepared statements (`db_execute_prepared()`, `db_fetch_row_prepared()`, etc.) for ALL queries with variables:

```php
// CORRECT
db_fetch_row_prepared('SELECT * FROM plugin_cycle_definitions WHERE id = ?', array($id));

// WRONG
db_fetch_row("SELECT * FROM plugin_cycle_definitions WHERE id = $id");
```

### Input Validation
Use `get_request_var()` / `get_filter_request_var()` for ALL user input, never raw `$_REQUEST`/`$_GET`/`$_POST`.

`get_filter_request_var()` (and its `gfrv()` shorthand, where available) called with only the
`$name` argument (no regex/filter as the 2nd/3rd argument) already validates the value as numeric
and returns it as a **string** -- it does not return an int, and it halts execution if the request
value is not numeric. Because of this, do NOT cast its output to `(int)` when the result is only
used for string output (e.g. `print`/`echo`, string concatenation, embedding in HTML/JS); the cast
is redundant. Only cast when the value is genuinely used in an integer/numeric context (e.g.
arithmetic, strict `===` comparisons).

### Output Escaping
Use `html_escape()` / `htmlspecialchars()` for ALL output of DB/user values in HTML context.

### Deserialization Safety
All `unserialize()` calls must use `array('allowed_classes' => false)`.

## Database Operations

Use Cacti's `db_*`/`db_*_prepared()` functions; keep schema creation/upgrades in `setup.php`'s install/upgrade lifecycle.

## Internationalization

ALL user-facing strings MUST use `__()` with the `'cycle'` text domain, e.g. `__('Plugin -> Cycle Graphs', 'cycle')`.

## Plugin Architecture

### Plugin Hooks
Register hooks in `setup.php`:

```php
api_plugin_register_hook('cycle', 'top_header_tabs',       'cycle_show_tab',             'setup.php');
api_plugin_register_hook('cycle', 'top_graph_header_tabs', 'cycle_show_tab',             'setup.php');
api_plugin_register_hook('cycle', 'config_arrays',         'cycle_config_arrays',        'setup.php');
api_plugin_register_hook('cycle', 'draw_navigation_text',  'cycle_draw_navigation_text', 'setup.php');
api_plugin_register_hook('cycle', 'config_settings',       'cycle_config_settings',      'setup.php');
api_plugin_register_hook('cycle', 'api_graph_save',        'cycle_api_graph_save',       'setup.php');
api_plugin_register_hook('cycle', 'page_head',             'cycle_page_head',            'setup.php');

api_plugin_register_realm('cycle', 'cycle.php,cycle_ajax.php', __('Plugin -> Cycle Graphs', 'cycle'), 1);
```

## Testing

Tests live in `tests/` (where present). Use Pest PHP or PHPUnit; run `php -l` lint checks before committing.

## Best Practices

1. Always use the `_prepared` DB helper variants for any query with variable input.
2. Validate all request input through `get_filter_request_var()`/`get_request_var()`.
3. Guard `unserialize()` calls with `allowed_classes => false`.
4. Wrap all user-facing strings with `__('text', 'cycle')`.

## Common Pitfalls to Avoid

```php
// WRONG - direct request superglobal access
$id = $_REQUEST['id'];

// CORRECT
$id = get_filter_request_var('id');
```

## Version Control

Document all changes in `CHANGELOG.md`; use descriptive commit messages referencing issue/PR numbers when applicable.

## CI & Dependency Baselines

- Do not commit a `composer.json` or `composer.lock` in this plugin's own repo root — the shared CI workflow installs Pest/dev dependencies into Cacti's own Composer-managed vendor tree (checked out alongside the plugin). Use Cacti's `composer.json`, not a plugin-local one.
- Do not add a plugin-local `.phpstan.neon`/`phpstan.neon` or `.php-cs-fixer.php`/`.php-cs-fixer.dist.php` — lint/static-analysis steps run against Cacti's own config from the Cacti core checkout, targeting this plugin's directory. Use the Cacti version, not a plugin-local config.
- Prefer Cacti's `cacti_count()`/`cacti_sizeof()` wrappers over the raw `count()`/`sizeof()` builtins in new or edited code.

## Internationalization (i18n)

- Translatable strings are managed with GNU gettext via `locales/build_gettext.sh`. `locales/po/cacti.pot` is the source template; Weblate owns syncing the per-language `.po`/`.mo` files from it.
- **Never commit the per-language `.po` or compiled `.mo` files** (`locales/po/*.po`, `locales/LC_MESSAGES/*.mo`) in a plugin PR. Weblate is the sole owner of those catalogs, and regenerating them here produces spurious diffs and merge conflicts. `locales/po/cacti.pot` is the ONLY translation artifact a PR may add or modify.
- When a pull request adds or changes a string wrapped in `__()`/`__n()`/`__esc()`/`__x()`/`__xn()`/`__gettext()`, run `locales/build_gettext.sh` before pushing and stage `locales/po/cacti.pot` only. `build_gettext.sh` also rewrites the `.po`/`.mo` files as a side effect; revert those before committing (`git checkout -- locales/po/*.po locales/LC_MESSAGES`), or run only the `xgettext` step that targets `cacti.pot`.

## References

- [Cacti main repo](https://github.com/Cacti/cacti/tree/1.2.x)
- [Cacti Documentation](https://www.github.com/Cacti/documentation)
- `README.md` for feature descriptions
- `CHANGELOG.md` for version history

## Security & Quality Conventions

These conventions apply across the Cacti plugin fleet and should be followed whenever touching
existing code or adding new code, not just in dedicated cleanup passes:

- **No hardcoded third-party hosts.** Never hardcode a third-party IP address, hostname, or URL
  in plugin code (even for tooling/download helpers). Expose it as a plugin setting instead, with
  secure-by-default values (e.g. an SSL-verification setting that defaults to verify-on).
- **Prepared statements over `db_qstr()`.** Build dynamic `WHERE` clauses using the
  `$sql_where`/`$sql_params` prepared-statement pattern, not string concatenation via `db_qstr()`.
- **Use `html_escape_request_var()`.** Prefer it over the `html_escape(get_request_var(...))` call
  chain.
- **Harden `unserialize()`.** Always pass `['allowed_classes' => false]` as the second argument.
- **i18n text domain.** Every `__()`/`__esc()` call must include this plugin's text domain as the
  final argument, except when deliberately comparing against a literal, untranslated Cacti-core
  label.
- **File inclusion uses `require`/`require_once`.** Always use `require`/`require_once` (never
  `include`/`include_once`) so a missing dependency fails fast and loudly. Keep library/helper files
  (e.g. `functions.php`) under `includes/` and reference them from that path; entry points
  (`cycle.php`, `setup.php`) stay in the plugin root.
- **Plugin table-creation API.** Use `api_plugin_db_table_create()`/`api_plugin_db_add_column()`
  (from Cacti core's `lib/plugins.php`) instead of raw `CREATE TABLE`/`ALTER TABLE ... ADD COLUMN`.
  Both are idempotent (safe no-ops when already applied), so the same call can run unconditionally
  from both the install AND upgrade paths.
- **PHPDoc shape.** Every function gets a PHPDoc block: a one-line description, a blank comment
  line, `@param` lines, a blank comment line, then `@return`. Infer parameter/return types from
  actual usage; don't change the function's real type-hints in the same pass (let static analysis
  flag mismatches separately). Skip vendored third-party library files.

## File manifest & upgrade pruning

The plugin ships a root `manifest.json` with three arrays: `tombstones` (files/directories older versions shipped that have since moved or been removed), `expected` (the top-level files and directories that ship today, directories written with a trailing `/`), and `whitelist` (paths holding user data that must never be touched). Keep `expected` current: CI runs `tests/bin/validate-manifest.php`, which fails on any drift between `expected` and the real top-level tree (it ignores `tests/`, `phpunit.xml`, `.git*`, `.md*`, and whitelisted paths). Custom customer CSS/theme files belong in `expected`, and stylesheets live in `css/` (not `themes/`). On upgrade, `plugin_cycle_prune_files()` deletes the tombstoned paths, the dev-only `tests/` tree, and the `phpunit.xml` test config, leaves `whitelist`, `.git*`, and `.md*` alone, and logs (without removing) any top-level entry the manifest does not account for. As a safety measure it refuses any tombstone that resolves outside the plugin directory (a tampered manifest.json) and logs a warning for any file or directory it cannot remove. When you move or delete a shipped file, add its old path to `tombstones` and update `expected` in the same change.
