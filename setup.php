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

function plugin_cycle_install() {
	api_plugin_register_hook('cycle', 'top_header_tabs',       'cycle_show_tab',             "setup.php");
	api_plugin_register_hook('cycle', 'top_graph_header_tabs', 'cycle_show_tab',             "setup.php");
	api_plugin_register_hook('cycle', 'config_arrays',         'cycle_config_arrays',        "setup.php");
	api_plugin_register_hook('cycle', 'draw_navigation_text',  'cycle_draw_navigation_text', "setup.php");
	api_plugin_register_hook('cycle', 'config_form',           'cycle_config_form',          "setup.php");
	api_plugin_register_hook('cycle', 'config_settings',       'cycle_config_settings',      "setup.php");
	api_plugin_register_hook('cycle', 'api_graph_save',        'cycle_api_graph_save',       "setup.php");
	api_plugin_register_hook('cycle', 'page_head',             'cycle_page_head',            "setup.php");

	api_plugin_register_realm('cycle', 'cycle.php,cycle_ajax.php', 'Plugin -> Cycle Graphs', 1);

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

		if ($old < "1.0") {
			api_plugin_register_realm('cycle', 'cycle.php,cycle_ajax.php', 'Plugin -> Cycle Graphs', 1);

			/* get the realm id's and change from old to new */
			$user  = db_fetch_cell("SELECT id FROM plugin_realms WHERE file='cycle.php'")+100;
			$users = db_fetch_assoc("SELECT user_id FROM user_auth_realm WHERE realm_id=42");
			if (sizeof($users)) {
				foreach($users as $u) {
					db_execute("INSERT INTO user_auth_realm
						(realm_id, user_id) VALUES ($user, " . $u["user_id"] . ")
						ON DUPLICATE KEY UPDATE realm_id=VALUES(realm_id)");
					db_execute("DELETE FROM user_auth_realm
						WHERE user_id=" . $u["user_id"] . "
						AND realm_id=$user");
				}
			}
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
		'version'  => '2.3',
		'longname' => 'Cycle Graphs',
		'author'   => 'The Cacti Group',
		'homepage' => 'http://www.cacti.net',
		'email'    => 'larryjadams@comcast.net',
		'url'      => 'http://versions.cactiusers.org/'
	);
}

function cycle_page_head() {
	if (basename($_SERVER["PHP_SELF"]) == "cycle.php") {
		?>
		<script type="text/javascript" src="cycle.js"></script>
		<script type="text/javascript" src="jquery.js"></script>
		<script type="text/javascript" src="jquery.autocomplete.js"></script>
		<?php
	}
}

function cycle_config_settings () {
	global $tabs, $settings, $page_refresh_interval, $graph_timespans;
	global $cycle_width, $cycle_height, $cycle_cols, $cycle_graphs;

	/* check for an upgrade */
	plugin_cycle_check_config();

	if (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) != 'settings.php')
		return;

	$tabs["cycle"] = "Cycle";

	$treeList = array_rekey(get_graph_tree_array(null, true), 'id', 'name');
	$temp = array(
		"cycle_header" => array(
			"friendly_name" => "Cycle Graphs",
			"method" => "spacer",
			),
		"cycle_delay" => array(
			"friendly_name" => "Delay Interval",
			"description" => "This is the time in seconds before the next graph is displayed.",
			"method" => "drop_array",
			"default" => 60,
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
			"array" => $cycle_cols
			),
		"cycle_graphs" => array(
			"friendly_name" => "Number of Graphs per Page",
			"description" => "Select the number of graphs to display per page",
			"method" => "drop_array",
			"default" => "4",
			"array" => $cycle_graphs,
			),
		"cycle_height" => array(
			"friendly_name" => "Graph Height",
			"description" => "This sets the graph height for the displayed graphs.",
			"method" => "drop_array",
			"default" => "100",
			"array" => $cycle_height
			),
		"cycle_width" => array(
			"friendly_name" => "Graph Width",
			"description" => "This sets the graph width for the displayed graphs.",
			"method" => "drop_array",
			"default" => "400",
			"array" => $cycle_width
			),
		"cycle_font_size" => array(
			"friendly_name" => "Title Font Size",
			"description" => "This is the font size in pixels for the title. (1 - 100)",
			"method" => "textbox",
			"default" => "8",
			"max_length" => 3,
			"size" => 4
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
			"default" => "1"
			),
		"cycle_legend" => array(
			"friendly_name" => "Display Legend",
			"description" => "Check this to display legend.",
			"method" => "checkbox",
			"default" => ""
			),
		"cycle_cheader" => array(
			"friendly_name" => "Predefined Rotations",
			"method" => "spacer",
			),
		"cycle_custom_graphs_type" => array(
			"friendly_name" => "Rotation Type",
			"description" => "Select which method to use for custom graph rotation.  If you select 'Specific List', you must define a list of graph id's",
			"method" => "drop_array",
			"default" => "1",
			"array" => array(0 => "Legacy (All)", 1 => "Specific List", 2 => "Tree Mode"),
			),
		"cycle_custom_graphs_list" => array(
			"friendly_name" => "Custom Graph List",
			"description" => "This must be a comma delimited list of graph id's to cycle through. For example '1,2,3,4'",
			"method" => "textbox",
			"max_length" => 255,
			),
		"cycle_custom_graphs_tree" => array(
			"friendly_name" => "Default Graph Tree",
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
	global $config;

	if (api_user_realm_auth('cycle.php')) {
		if (substr_count($_SERVER["REQUEST_URI"], "cycle.php")) {
			print '<a href="' . $config['url_path'] . 'plugins/cycle/cycle.php"><img src="' . $config['url_path'] . 'plugins/cycle/images/tab_cycle_down.gif" alt="Cycle" align="absmiddle" border="0"></a>';
		}else{
			print '<a href="' . $config['url_path'] . 'plugins/cycle/cycle.php"><img src="' . $config['url_path'] . 'plugins/cycle/images/tab_cycle.gif" alt="Cycle" align="absmiddle" border="0"></a>';
		}
	}
}

function cycle_config_arrays () {
	global $cycle_graphs, $cycle_cols, $cycle_width, $cycle_height;
	
	$cycle_graphs = array(
		1 => "1 Graph", 
		2 => "2 Graphs", 
		4 => "4 Graphs", 
		6 => "6 Graphs", 
		8 => "8 Graphs", 
		10 => "10 Graphs"
	);

	$cycle_cols   = array(
		1 => "1 Column", 
		2 => "2 Columns", 
		3 => "3 Columns", 
		4 => "4 Columns", 
		5 => "5 Columns"
	);

	$cycle_height = array(
		75  => "75 Pixels", 
		100 => "100 Pixels", 
		125 => "125 Pixels", 
		150 => "150 Pixels", 
		175 => "175 Pixels", 
		200 => "200 Pixels", 
		250 => "250 Pixels", 
		300 => "300 Pixels", 
		350 => "350 Pixels", 
		400 => "400 Pixels", 
		500 => "500 Pixels"
	);

	$cycle_width  = array(
		100 => "100 Pixels", 
		125 => "125 Pixels", 
		150 => "150 Pixels", 
		175 => "175 Pixels", 
		200 => "200 Pixels", 
		250 => "250 Pixels", 
		300 => "300 Pixels", 
		350 => "350 Pixels", 
		400 => "400 Pixels", 
		500 => "500 Pixels", 
		600 => "600 Pixels", 
		700 => "700 Pixels"
	);

	return true;
}

function cycle_draw_navigation_text ($nav) {
	$nav["cycle.php:"] = array("title" => "Cycling", "mapping" => "", "url" => "cycle.php", "level" => "1");
	return $nav;
}

function cycle_api_graph_save ($save) {
}
?>
