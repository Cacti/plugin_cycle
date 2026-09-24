<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/**
 * Installs the Cycle plugin: registers its Cacti hooks (top_header_tabs,
 * top_graph_header_tabs, config_arrays, draw_navigation_text,
 * config_settings, api_graph_save, page_head), and registers its realm
 * covering cycle.php and cycle_ajax.php (this plugin does not require
 * any dedicated database tables). Invoked by Cacti's plugin architecture
 * when an administrator installs this plugin from Console > Plugin
 * Management, and re-invoked from cycle_check_upgrade() to refresh hook
 * registrations after an upgrade.
 *
 * @return void
 */
function plugin_cycle_install() {
	api_plugin_register_hook('cycle', 'top_header_tabs',       'cycle_show_tab',             'setup.php');
	api_plugin_register_hook('cycle', 'top_graph_header_tabs', 'cycle_show_tab',             'setup.php');
	api_plugin_register_hook('cycle', 'config_arrays',         'cycle_config_arrays',        'setup.php');
	api_plugin_register_hook('cycle', 'draw_navigation_text',  'cycle_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('cycle', 'config_settings',       'cycle_config_settings',      'setup.php');
	api_plugin_register_hook('cycle', 'api_graph_save',        'cycle_api_graph_save',       'setup.php');
	api_plugin_register_hook('cycle', 'page_head',             'cycle_page_head',            'setup.php');

	api_plugin_register_realm('cycle', 'cycle.php,cycle_ajax.php', __('Plugin -> Cycle Graphs', 'cycle'), 1);

	cycle_setup_table_new();
}

/**
 * Uninstalls the Cycle plugin; currently a no-op placeholder (this
 * plugin does not create any tables or settings that require explicit
 * cleanup beyond what Cacti's plugin architecture handles automatically).
 * Invoked by Cacti's plugin architecture when an administrator
 * uninstalls this plugin from Console > Plugin Management.
 *
 * @return void
 */
function plugin_cycle_uninstall() {
	// Do any extra Uninstall stuff here
}

/**
 * Verifies the plugin's configuration by triggering its upgrade check.
 * Invoked by Cacti's plugin architecture on relevant page loads.
 *
 * @return bool Always returns true.
 */
function plugin_cycle_check_config() {
	// Here we will check to ensure everything is configured
	cycle_check_upgrade();

	return true;
}

/**
 * Performs any schema/data migrations needed when upgrading to a newer
 * version of this plugin, by delegating to cycle_check_upgrade(). Invoked
 * by Cacti's plugin architecture when an installed plugin's version
 * increases.
 *
 * @return bool Always returns false.
 */
function plugin_cycle_upgrade() {
	// Here we will upgrade to the newest version
	cycle_check_upgrade();

	return false;
}

/**
 * Detects whether the installed plugin_config version differs from this
 * plugin's INFO file version and, if so, re-registers hooks/database
 * schema (for enabled/active installs), migrates a legacy realm's user
 * permissions to the new realm id (for upgrades from pre-1.0), removes a
 * stale legacy 'config_form' hook, and updates the stored plugin_config
 * record. Only runs on index.php/plugins.php/cycle.php. Called from
 * cycle_config_settings() on every relevant page load.
 *
 * @return void
 *
 * @global array $config Reserved/declared for parity with other
 *                       functions in this file; not used directly here
 *                       (the page guard uses $_SERVER['PHP_SELF']
 *                       instead).
 */
function cycle_check_upgrade() {
	global $config;

	$files = ['index.php', 'plugins.php', 'cycle.php'];

	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files, true)) {
		return;
	}

	$info    = plugin_cycle_version();
	$current = $info['version'];
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='cycle'");

	if (cacti_sizeof($old) && $current != $old['version']) {
		// if the plugin is installed and/or active
		if ($old['status'] == 1 || $old['status'] == 4) {
			// re-register the hooks
			plugin_cycle_install();

			// perform a database upgrade
			cycle_database_upgrade();
		}

		if ($old['version'] < '1.0') {
			api_plugin_register_realm('cycle', 'cycle.php,cycle_ajax.php', 'Plugin -> Cycle Graphs', 1);

			// get the realm id's and change from old to new
			$user  = db_fetch_cell("SELECT id FROM plugin_realms WHERE file='cycle.php'") + 100;
			$users = db_fetch_assoc('SELECT user_id FROM user_auth_realm WHERE realm_id=42');

			if (sizeof($users)) {
				foreach ($users as $u) {
					db_execute('INSERT INTO user_auth_realm
						(realm_id, user_id) VALUES (' . $user . ', ' . $u['user_id'] . ')
						ON DUPLICATE KEY UPDATE realm_id=VALUES(realm_id)');
					db_execute('DELETE FROM user_auth_realm
						WHERE user_id=' . $u['user_id'] . '
						AND realm_id=' . $user);
				}
			}
		}

		// update the plugin information
		$id = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='cycle'");

		// remove legacy hook
		db_execute('DELETE FROM plugin_hooks WHERE name="cycle" AND hook="config_form"');

		db_execute_prepared('UPDATE plugin_config
			SET name = ?, author = ?, webpage = ?, version = ?
			WHERE id = ?',
			[$info['longname'], $info['author'], $info['homepage'], $info['version'], $id]);
	}
}

/**
 * Applies database schema migrations for this plugin; currently a no-op
 * placeholder (this plugin's schema has not required migrations since
 * its initial release). Called from cycle_check_upgrade() when an
 * enabled/active install's version has changed.
 *
 * @return void
 */
function cycle_database_upgrade() {
}

/**
 * Verifies that this plugin's PHP/Cacti dependencies are met; currently
 * always reports success. Invoked by Cacti's plugin architecture before
 * enabling the plugin.
 *
 * @return bool Always returns true.
 *
 * @global array $plugins Reserved/declared for parity with other
 *                         dependency-check functions; not used directly
 *                         here.
 * @global array $config  Reserved/declared for parity with other
 *                         dependency-check functions; not used directly
 *                         here.
 */
function cycle_check_dependencies() {
	global $plugins, $config;

	return true;
}

/**
 * Creates this plugin's database tables; currently a no-op placeholder
 * (this plugin does not require any dedicated database tables, relying
 * instead on Cacti core's settings tables for its configuration). Called
 * from plugin_cycle_install() during plugin installation.
 *
 * @return void
 */
function cycle_setup_table_new() {
}

/**
 * Reads this plugin's INFO file and returns its [info] section. Used by
 * Cacti's plugin architecture via the api_plugin_version hook, and
 * internally by cycle_check_upgrade() to detect/report the plugin's
 * version.
 *
 * @return array The parsed [info] section of the plugin's INFO file (keys
 *               such as name, version, author, homepage, longname).
 *
 * @global array $config Cacti global configuration array; used to locate
 *                        the plugin's base path.
 */
function plugin_cycle_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/cycle/INFO', true);

	return $info['info'];
}

/**
 * Hook implementation for Cacti's 'page_head' filter. Intended to include
 * this plugin's page-level assets; currently a no-op (the function body
 * is empty). Called by Cacti core via api_plugin_hook('page_head', ...)
 * while rendering the page <head> section.
 *
 * @return void
 */
function cycle_page_head() {
}

/**
 * Hook implementation for Cacti's 'config_settings' filter. Registers the
 * "Cycle" Settings/Graph-settings tab and its fields (delay interval,
 * default timespan, columns, graphs-per-page, height, width, legend, and
 * rotation type/list/tree options), for both the global Settings page
 * and per-user preferences. Also triggers this plugin's own upgrade
 * check. Called by Cacti core via api_plugin_hook('config_settings', ...)
 * while building the Settings page, restricted to settings.php/
 * auth_profile.php unless $force is set.
 *
 * @param bool $force Whether to register the settings regardless of the
 *                     current page (used when called directly from
 *                     validate_request_vars() rather than via the hook);
 *                     defaults to false.
 *
 * @return void
 *
 * @global array $tabs                  Cacti's registered Settings page
 *                                       tabs, extended here with the
 *                                       'cycle' tab.
 * @global array $settings              Cacti's registered Settings page
 *                                       fields, extended here with this
 *                                       plugin's global settings.
 * @global array $tabs_graphs           Cacti's registered per-graph
 *                                       settings tabs, extended here with
 *                                       the 'cycle' tab.
 * @global array $settings_user         Cacti's registered per-user
 *                                       settings fields, extended here
 *                                       with this plugin's user-level
 *                                       settings.
 * @global array $page_refresh_interval Options for the cycle rotation
 *                                       refresh delay, used to populate
 *                                       the Delay Interval field.
 * @global array $graph_timespans       Cacti's predefined graph timespan
 *                                       options, used to populate the
 *                                       Graph Timespan field.
 * @global array $cycle_width           Options for graph width, used to
 *                                       populate the Graph Width field.
 * @global array $cycle_height          Options for graph height, used to
 *                                       populate the Graph Height field.
 * @global array $cycle_cols            Options for graph columns, used to
 *                                       populate the Column Count field.
 * @global array $cycle_graphs          Options for graphs-per-page, used
 *                                       to populate the Number of Graphs
 *                                       per Page field.
 */
function cycle_config_settings($force = false) {
	global $tabs, $settings, $tabs_graphs, $settings_user, $page_refresh_interval, $graph_timespans;
	global $cycle_width, $cycle_height, $cycle_cols, $cycle_graphs;

	// check for an upgrade
	plugin_cycle_check_config();

	if ($force === false && isset($_SERVER['PHP_SELF']) &&
		basename($_SERVER['PHP_SELF']) != 'settings.php' &&
		basename($_SERVER['PHP_SELF']) != 'auth_profile.php') {
		return;
	}

	$tabs['cycle']        = __('Cycle', 'cycle');
	$tabs_graphs['cycle'] = __('Cycle', 'cycle');

	$treeList   = array_rekey(get_allowed_trees(), 'id', 'name');
	$tempHeader = ['cycle_header' => [
			'friendly_name' => __('Cycle Graphs', 'cycle'),
			'method'        => 'spacer',
			]];
	$temp = [
		'cycle_delay' => [
			'friendly_name' => __('Delay Interval', 'cycle'),
			'description'   => __('This is the time in seconds before the next graph is displayed.', 'cycle'),
			'method'        => 'drop_array',
			'default'       => 60,
			'array'         => $page_refresh_interval
			],
		'cycle_timespan' => [
			'friendly_name' => __('Graph Timespan', 'cycle'),
			'description'   => __('This is the default timespan that will be displayed on the page.', 'cycle'),
			'method'        => 'drop_array',
			'default'       => 5,
			'array'         => $graph_timespans
			],
		'cycle_columns' => [
			'friendly_name' => __('Column Count', 'cycle'),
			'description'   => __('In Tree Mode this is the number of columns that will be used.', 'cycle'),
			'method'        => 'drop_array',
			'default'       => 2,
			'array'         => $cycle_cols
			],
		'cycle_graphs' => [
			'friendly_name' => __('Number of Graphs per Page', 'cycle'),
			'description'   => __('Select the number of graphs to display per page', 'cycle'),
			'method'        => 'drop_array',
			'default'       => '4',
			'array'         => $cycle_graphs,
			],
		'cycle_height' => [
			'friendly_name' => __('Graph Height', 'cycle'),
			'description'   => __('This sets the graph height for the displayed graphs.', 'cycle'),
			'method'        => 'drop_array',
			'default'       => '100',
			'array'         => $cycle_height
			],
		'cycle_width' => [
			'friendly_name' => __('Graph Width', 'cycle'),
			'description'   => __('This sets the graph width for the displayed graphs.', 'cycle'),
			'method'        => 'drop_array',
			'default'       => '400',
			'array'         => $cycle_width
			],
		'cycle_legend' => [
			'friendly_name' => __('Display Legend', 'cycle'),
			'description'   => __('Check this to display legend.', 'cycle'),
			'method'        => 'checkbox',
			'default'       => ''
			],
		'cycle_cheader' => [
			'friendly_name' => __('Predefined Rotations', 'cycle'),
			'method'        => 'spacer',
			],
		'cycle_custom_graphs_type' => [
			'friendly_name' => __('Rotation Type', 'cycle'),
			'description'   => __('Select which method to use for custom graph rotation.  If you select \'Specific List\', you must define a list of Graph ID\'s', 'cycle'),
			'method'        => 'drop_array',
			'default'       => '1',
			'array'         => [0 => __('Legacy (All)', 'cycle'), 1 => __('Specific List', 'cycle'), 2 => __('Tree Mode', 'cycle')],
			],
		'cycle_custom_graphs_list' => [
			'friendly_name' => __('Custom Graph List', 'cycle'),
			'description'   => __('This must be a comma delimited list of Graph ID\'s to cycle through. For example \'1,2,3,4\'', 'cycle'),
			'method'        => 'textbox',
			'max_length'    => 255,
			],
		'cycle_custom_graphs_tree' => [
			'friendly_name' => __('Default Tree', 'cycle'),
			'description'   => __('Select the graph tree to cycle if Tree Mode is selected', 'cycle'),
			'method'        => 'drop_array',
			'default'       => 'None',
			'array'         => $treeList,
		]
	];

	if (isset($settings['cycle'])) {
		$settings['cycle'] = array_merge($settings['cycle'], $tempHeader, $temp);
	} else {
		$settings['cycle'] = array_merge($tempHeader, $temp);
	}

	if (isset($settings_user['cycle'])) {
		$settings_user['cycle'] = array_merge($settings_user['cycle'], $temp);
	} else {
		$settings_user['cycle'] = $temp;
	}
}

/**
 * Hook implementation for Cacti's 'top_header_tabs'/'top_graph_header_tabs'
 * filters. Prints a clickable tab icon linking to cycle.php, using a
 * different icon when cycle.php is the currently displayed page. Called
 * by Cacti core via api_plugin_hook('top_header_tabs'/
 * 'top_graph_header_tabs', ...) while rendering the page header tabs, for
 * users with access to cycle.php.
 *
 * @return void Outputs HTML directly.
 *
 * @global array $config Cacti global configuration array; used to build
 *                        the tab's image/link URLs.
 */
function cycle_show_tab() {
	global $config;

	if (api_user_realm_auth('cycle.php')) {
		if (substr_count($_SERVER['REQUEST_URI'], 'cycle.php')) {
			print '<a href="' . $config['url_path'] . 'plugins/cycle/cycle.php"><img src="' . $config['url_path'] . 'plugins/cycle/images/tab_cycle_down.gif" alt="' . __('Cycle') . '"></a>';
		} else {
			print '<a href="' . $config['url_path'] . 'plugins/cycle/cycle.php"><img src="' . $config['url_path'] . 'plugins/cycle/images/tab_cycle.gif" alt="' . __('Cycle') . '"></a>';
		}
	}
}

/**
 * Hook implementation for Cacti's 'config_arrays' filter. Populates the
 * shared $cycle_graphs, $cycle_cols, $cycle_height, and $cycle_width
 * lookup arrays used throughout this plugin's filter/settings UI (as
 * graphs-per-page, column-count, and pixel-size options). Called by
 * Cacti core via api_plugin_hook('config_arrays', ...) while building the
 * navigation menu.
 *
 * @return bool Always returns true.
 *
 * @global array $cycle_graphs Populated here with the graphs-per-page
 *                              options.
 * @global array $cycle_cols   Populated here with the column-count
 *                              options.
 * @global array $cycle_width  Populated here with the graph-width pixel
 *                              options.
 * @global array $cycle_height Populated here with the graph-height pixel
 *                              options.
 */
function cycle_config_arrays() {
	global $cycle_graphs, $cycle_cols, $cycle_width, $cycle_height;

	$cycle_graphs = [
		1  => __('%d Graph', 1, 'cycle'),
		2  => __('%d Graphs', 2, 'cycle'),
		4  => __('%d Graphs', 4, 'cycle'),
		6  => __('%d Graphs', 6, 'cycle'),
		8  => __('%d Graphs', 8, 'cycle'),
		10 => __('%d Graphs', 10, 'cycle')
	];

	$cycle_cols   = [
		1 => __('%d Column', 1, 'cycle'),
		2 => __('%d Columns', 2, 'cycle'),
		3 => __('%d Columns', 3, 'cycle'),
		4 => __('%d Columns', 4, 'cycle'),
		5 => __('%d Columns', 5, 'cycle')
	];

	$cycle_height = [
		75  => __('%d Pixels', 75, 'cycle'),
		100 => __('%d Pixels', 100, 'cycle'),
		125 => __('%d Pixels', 125, 'cycle'),
		150 => __('%d Pixels', 150, 'cycle'),
		175 => __('%d Pixels', 175, 'cycle'),
		200 => __('%d Pixels', 200, 'cycle'),
		250 => __('%d Pixels', 250, 'cycle'),
		300 => __('%d Pixels', 300, 'cycle'),
		350 => __('%d Pixels', 350, 'cycle'),
		400 => __('%d Pixels', 400, 'cycle'),
		500 => __('%d Pixels', 500, 'cycle')
	];

	$cycle_width  = [
		100 => __('%d Pixels', 100, 'cycle'),
		125 => __('%d Pixels', 125, 'cycle'),
		150 => __('%d Pixels', 150, 'cycle'),
		175 => __('%d Pixels', 175, 'cycle'),
		200 => __('%d Pixels', 200, 'cycle'),
		250 => __('%d Pixels', 250, 'cycle'),
		300 => __('%d Pixels', 300, 'cycle'),
		350 => __('%d Pixels', 350, 'cycle'),
		400 => __('%d Pixels', 400, 'cycle'),
		500 => __('%d Pixels', 500, 'cycle'),
		550 => __('%d Pixels', 550, 'cycle'),
		600 => __('%d Pixels', 600, 'cycle'),
		650 => __('%d Pixels', 650, 'cycle'),
		700 => __('%d Pixels', 700, 'cycle')
	];

	return true;
}

/**
 * Hook implementation for Cacti's 'draw_navigation_text' filter. Adds
 * breadcrumb entries for cycle.php's default, view, graphs, and save
 * actions. Called by Cacti core via
 * api_plugin_hook('draw_navigation_text', ...) while rendering the page
 * breadcrumb trail.
 *
 * @param array $nav The existing breadcrumb map contributed by Cacti
 *                    core and other plugins.
 *
 * @return array The $nav array with this plugin's breadcrumb entries
 *               added.
 */
function cycle_draw_navigation_text($nav) {
	$nav['cycle.php:']       = ['title' => __('Cycling', 'cycle'), 'mapping' => '', 'url' => 'cycle.php', 'level' => '1'];
	$nav['cycle.php:view']   = ['title' => __('Cycling', 'cycle'), 'mapping' => '', 'url' => 'cycle.php', 'level' => '1'];
	$nav['cycle.php:graphs'] = ['title' => __('Cycling', 'cycle'), 'mapping' => '', 'url' => 'cycle.php', 'level' => '1'];
	$nav['cycle.php:save']   = ['title' => __('Cycling', 'cycle'), 'mapping' => '', 'url' => 'cycle.php', 'level' => '1'];

	return $nav;
}

/**
 * Hook implementation for Cacti's 'api_graph_save' filter. Intended to
 * react to graphs being created/updated; currently a no-op (the function
 * body is empty). Called by Cacti core via
 * api_plugin_hook('api_graph_save', ...) after a graph is saved.
 *
 * @param array $save The graph values that were saved.
 *
 * @return void
 */
function cycle_api_graph_save($save) {
}
?>
