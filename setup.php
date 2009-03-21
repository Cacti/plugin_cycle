<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2009 The Cacti Group                                 |
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

function plugin_init_cycle() {
	global $plugin_hooks;

	$plugin_hooks['top_header_tabs']['cycle']       = 'cycle_show_tab';
	$plugin_hooks['top_graph_header_tabs']['cycle'] = 'cycle_show_tab';
	$plugin_hooks['config_arrays']['cycle']         = 'cycle_config_arrays';
	$plugin_hooks['draw_navigation_text']['cycle']  = 'cycle_draw_navigation_text';
	$plugin_hooks['config_form']['cycle']           = 'cycle_config_form';
	$plugin_hooks['config_settings']['cycle']       = 'cycle_config_settings';
	$plugin_hooks['api_graph_save']['cycle']        = 'cycle_api_graph_save';
}

function plugin_cycle_install() {
	api_plugin_register_hook('cycle', 'top_header_tabs',       'cycle_show_tab',             "setup.php");
	api_plugin_register_hook('cycle', 'top_graph_header_tabs', 'cycle_show_tab',             "setup.php");
	api_plugin_register_hook('cycle', 'config_arrays',         'cycle_config_arrays',        "setup.php");
	api_plugin_register_hook('cycle', 'draw_navigation_text',  'cycle_draw_navigation_text', "setup.php");
	api_plugin_register_hook('cycle', 'config_form',           'cycle_config_form',          "setup.php");
	api_plugin_register_hook('cycle', 'config_settings',       'cycle_config_settings',      "setup.php");
	api_plugin_register_hook('cycle', 'api_graph_save',        'cycle_api_graph_save',       "setup.php");

	cycle_setup_table_new ();
}

function plugin_cycle_uninstall () {
	/* Do any extra Uninstall stuff here */
}

function plugin_cycle_check_config () {
	/* Here we will check to ensure everything is configured */
	cycle_check_upgrade();
	return true;
}

function plugin_cycle_upgrade () {
	/* Here we will upgrade to the newest version */
	cycle_check_upgrade();
	return false;
}

function plugin_cycle_version () {
	return cycle_version();
}

function cycle_check_upgrade () {
	global $config;

	$files = array('index.php', 'plugins.php', 'cycle.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$current = plugin_cycle_version();
	$current = $current['version'];
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='cycle'");
	if (sizeof($old) && $current != $old["version"]) {
		/* if the plugin is installed and/or active */
		if ($old["status"] == 1 || $old["status"] == 4) {
			/* re-register the hooks */
			plugin_cycle_install();

			/* perform a database upgrade */
			cycle_database_upgrade();
		}

		/* update the plugin information */
		$info = plugin_cycle_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='cycle'");
		db_execute("UPDATE plugin_config
			SET name='" . $info["longname"] . "',
			author='"   . $info["author"]   . "',
			webpage='"  . $info["homepage"] . "',
			version='"  . $info["version"]  . "'
			WHERE id='$id'");
	}
}

function cycle_database_upgrade () {
}

function cycle_check_dependencies() {
	global $plugins, $config;

	return true;
}

function cycle_setup_table_new () {
}

function cycle_version () {
	return array(
		'name'     => 'Cycle Graphs',
		'version'  => '0.7',
		'longname' => 'Cycle Graphs',
		'author'   => 'The Cacti Group',
		'homepage' => 'http://www.cacti.net',
		'email'    => 'larryjadams@comcast.net',
		'url'      => 'http://versions.cactiusers.org/'
	);
}

function cycle_config_settings () {
	global $tabs, $settings, $page_refresh_interval, $graph_timespans;

	/* check for an upgrade */
	plugin_cycle_check_config();

	if (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) != 'settings.php')
		return;

	$tabs["cycle"] = "Cycle";

	$treeList = array_rekey(db_fetch_assoc("SELECT id, name FROM graph_tree ORDER BY name"), 'id', 'name');

	$temp = array(
		"cycle_header" => array(
			"friendly_name" => "Cycle Graphs",
			"method" => "spacer",
			),
		"cycle_delay" => array(
			"friendly_name" => "Delay Interval",
			"description" => "This is the time in seconds before the next graph is displayed.",
			"method" => "drop_array",
			"default" => 5,
			"array" => $page_refresh_interval
			),
		"cycle_timespan" => array(
			"friendly_name" => "Graph Timespan",
			"description" => "This is the default timespan that will be displayed on the page.",
			"method" => "drop_array",
			"default" => 5,
			"array" => $graph_timespans
			),
		"cycle_columns" => array(
			"friendly_name" => "Column Count",
			"description" => "In Tree Mode this is the number of columns that will be used.",
			"method" => "drop_array",
			"default" => 2,
			"array" => array( 1=>1, 2=>2, 3=>3, 4=>4, 5=>5),
		),
		"cycle_height" => array(
			"friendly_name" => "Graph Height",
			"description" => "This sets the graph height for the displayed graphs.",
			"method" => "textbox",
			"max_length" => 5,
			),
		"cycle_width" => array(
			"friendly_name" => "Graph Width",
			"description" => "This sets the graph width for the displayed graphs.",
			"method" => "textbox",
			"max_length" => 5,
			),
		"cycle_font_size" => array(
			"friendly_name" => "Title Font Size",
			"description" => "This is the font size in pixels for the title. (1 - 100)",
			"method" => "textbox",
			"max_length" => 3,
			),
		"cycle_font_face" => array(
			"friendly_name" => "Title Font Face",
			"description" => "This is the font face for the title.",
			"method" => "textbox",
			"max_length" => 100,
			),
		"cycle_font_color" => array(
			"friendly_name" => "Title Font Color",
			"description" => "This is the font color for the title.",
			"method" => "drop_color",
			"max_length" => 10,
			),
		"cycle_custom_graphs" => array(
			"friendly_name" => "Use Custom Graph Rotation",
			"description" => "Check this to use the graphs listed below.",
			"method" => "checkbox",
			),
		"cycle_custom_graphs_type" => array(
			"friendly_name" => "Custom Graph Type",
			"description" => "Select which method to use for custom graph rotation.",
			"method" => "drop_array",
			"default" => "1",
			"array" => array( 1 => "Specific List", 2 => "Tree Mode"),
		),
		"cycle_custom_graphs_list" => array(
			"friendly_name" => "Custom Graph List",
			"description" => "This is a list of the graph IDs that you want to include in the rotation. (1,2)",
			"method" => "textbox",
			"max_length" => 255,
			),
		"cycle_custom_graphs_tree" => array(
			"friendly_name" => "Custom Graph Tree",
			"description" => "Select the graph tree to cycle if Tree Mode is selected",
			"method" => "drop_array",
			"default" => "None",
			"array" => $treeList,
		)
	);

	if (isset($settings["cycle"])) {
		$settings["cycle"] = array_merge($settings["cycle"], $temp);
	}else {
		$settings["cycle"] = $temp;
	}
}

function cycle_show_tab () {
	global $config, $user_auth_realms, $user_auth_realm_filenames;

	$realm_id2 = 0;

	if (isset($user_auth_realm_filenames{basename('cycle.php')})) {
		$realm_id2 = $user_auth_realm_filenames{basename('cycle.php')};
	}

	if ((db_fetch_assoc("select user_auth_realm.realm_id
		from user_auth_realm where user_auth_realm.user_id='" . $_SESSION["sess_user_id"] . "'
		and user_auth_realm.realm_id='$realm_id2'")) || (empty($realm_id2))) {

		if (substr_count($_SERVER["REQUEST_URI"], "cycle.php")) {
			print '<a href="' . $config['url_path'] . 'plugins/cycle/cycle.php"><img src="' . $config['url_path'] . 'plugins/cycle/images/tab_cycle_down.gif" alt="Cycle" align="absmiddle" border="0"></a>';
		}else{
			print '<a href="' . $config['url_path'] . 'plugins/cycle/cycle.php"><img src="' . $config['url_path'] . 'plugins/cycle/images/tab_cycle.gif" alt="Cycle" align="absmiddle" border="0"></a>';
		}
	}
}

function cycle_config_arrays () {
	global $user_auth_realms, $user_auth_realm_filenames;

	$user_auth_realms[42] = 'View Cycle Graphs';
	$user_auth_realm_filenames['cycle.php'] = 42;
}

function cycle_draw_navigation_text ($nav) {
	$nav["cycle.php:"] = array("title" => "Cycling", "mapping" => "", "url" => "cycle.php", "level" => "1");
	return $nav;
}

function cycle_api_graph_save ($save) {
}
?>
