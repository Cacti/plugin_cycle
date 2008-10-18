<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2008 The Cacti Group                                 |
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
	/* Let's only run this check if we are on a page
	   that actually needs the data */
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
		'version'  => '0.6',
		'longname' => 'Cycle Graphs',
		'author'   => 'Matt Emerick-Law',
		'homepage' => 'http://emericklaw.co.uk',
		'email'    => 'matt@emericklaw.co.uk',
		'url'      => 'http://cactiusers.org/cacti/versions.php'
	);
}

function cycle_config_settings () {
	global $tabs, $settings;

	$tabs["cycle"] = "Cycle";

	$treeList = array_rekey(get_graph_tree_array(null, true), 'id', 'name');
	$temp = array(
		"cycle_header" => array(
			"friendly_name" => "Cycle Graphs",
			"method" => "spacer",
			),
		"cycle_delay" => array(
			"friendly_name" => "Delay Interval",
			"description" => "This is the time in seconds before the next graph is displayed.  (1 - 300)",
			"method" => "textbox",
			"max_length" => 3,
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
			"method" => "textbox",
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
			"description" => "This is a list of the graph IDs that you want to include in thr rotation.",
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
		$settings["cycle"]=$temp;
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

		print '<a href="' . $config['url_path'] . 'plugins/cycle/cycle.php"><img src="' . $config['url_path'] . 'plugins/cycle/images/tab_cycle.gif" alt="cycle" align="absmiddle" border="0"></a>';
	}

	$r      = read_config_option("cycle_delay");
	$sql    = "select * from settings where name='cycle_delay'";
	$result = db_fetch_assoc($sql);

	if (!isset($result[0]['name'])) {
		if ($r == '' or $r < 1 or $r > 300) {
			if ($r == '') {
				$sql = "replace into settings values ('cycle_delay','300')";
			}else if ($r == NULL) {
				$sql = "insert into settings values ('cycle_delay','300')";
			}else {
				$sql = "update settings set value = '300' where name = 'cycle_delay'";
			}

			$result = mysql_query($sql) or die (mysql_error());

			kill_session_var("sess_config_array");
		}
	}

	$r      = read_config_option("cycle_font_size");
	$sql    = "select * from settings where name='cycle_font_size'";
	$result = db_fetch_assoc($sql);

	if (!isset($result[0]['name'])) {
		if ($r == '' or $r < 1 or $r > 100) {
			if ($r == '') {
				$sql = "replace into settings values ('cycle_font_size','36')";
			}else if ($r == NULL) {
				$sql = "insert into settings values ('cycle_font_size','36')";
			}else {
				$sql = "update settings set value = '36' where name = 'cycle_font_size'";
			}

			$result = mysql_query($sql) or die (mysql_error());

			kill_session_var("sess_config_array");
		}
	}

	$r      = read_config_option("cycle_font_face");
	$sql    = "select * from settings where name='cycle_font_face'";
	$result = db_fetch_assoc($sql);

	if (!isset($result[0]['name'])) {
		if ($r == '') {
			$sql = "replace into settings values ('cycle_font_face','Verdana')";
		}else if ($r == NULL) {
			$sql = "insert into settings values ('cycle_font_face','Verdana')";
		}else {
			$sql = "update settings set value = 'Verdana' where name = 'cycle_font_face'";
		}
	}

	$result = mysql_query($sql) or die (mysql_error());

	kill_session_var("sess_config_array");

	$r      = read_config_option("cycle_font_color");
	$sql    = "select * from settings where name='cycle_font_color'";
	$result = db_fetch_assoc($sql);

	if (!isset($result[0]['name'])) {
		if ($r == '') {
			$sql = "replace into settings values ('cycle_font_color','#000000')";
		}else if ($r == NULL) {
			$sql = "insert into settings values ('cycle_font_color','#000000')";
		}else {
			$sql = "update settings set value = 'Verdana' where name = 'cycle_font_color'";
		}
	}

	$result = mysql_query($sql) or die (mysql_error());

	kill_session_var("sess_config_array");

	$r      = read_config_option("cycle_width");
	$sql    = "select * from settings where name='cycle_width'";
	$result = db_fetch_assoc($sql);

	if (!isset($result[0]['name'])) {
		if ($r == '') {
			$sql = "replace into settings values ('cycle_width','500')";
		}else if ($r == NULL) {
			$sql = "insert into settings values ('cycle_width','500')";
		}else {
			$sql = "update settings set value = '500' where name = 'cycle_width'";
		}
	}

	$result = mysql_query($sql) or die (mysql_error());

	kill_session_var("sess_config_array");

	$r      = read_config_option("cycle_height");
	$sql    = "select * from settings where name='cycle_height'";
	$result = db_fetch_assoc($sql);

	if (!isset($result[0]['name'])) {
		if ($r == '') {
			$sql = "replace into settings values ('cycle_height','120')";
		}else if ($r == NULL) {
			$sql = "insert into settings values ('cycle_height','120')";
		}else {
			$sql = "update settings set value = '120' where name = 'cycle_height'";
		}
	}

	$result = mysql_query($sql) or die (mysql_error());

	kill_session_var("sess_config_array");
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
