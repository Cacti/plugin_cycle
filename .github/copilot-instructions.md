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
├── images/              # UI icons
├── locales/                # Internationalization files
├── cycle.js                   # Client-side graph rotation logic
├── cycle.php                    # Main cycle viewer/administration UI
├── functions.php                   # Shared helper functions
├── INFO                               # Plugin metadata (name, version, compat)
├── README.md
└── setup.php                            # Plugin install/uninstall/upgrade hooks
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

## References

- [Cacti main repo](https://github.com/Cacti/cacti/tree/1.2.x)
- [Cacti Documentation](https://www.github.com/Cacti/documentation)
- `README.md` for feature descriptions
- `CHANGELOG.md` for version history
