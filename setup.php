<?php
/*******************************************************************************

    Author ......... Matt Emerick-Law
    Contact ........ matt@emericklaw.co.uk
    Home Site ...... http://emericklaw.co.uk
    Program ........ Cycle Graphs
    Version ........ 0.4
    Purpose ........ Automatically cycle through cacti graphs

*******************************************************************************/

function plugin_init_cycle() {
	global $plugin_hooks;
	$plugin_hooks['top_header_tabs']['cycle'] = 'cycle_show_tab';
	$plugin_hooks['top_graph_header_tabs']['cycle'] = 'cycle_show_tab';
	$plugin_hooks['config_arrays']['cycle'] = 'cycle_config_arrays';
	$plugin_hooks['draw_navigation_text']['cycle'] = 'cycle_draw_navigation_text';
	$plugin_hooks['config_form']['cycle'] = 'cycle_config_form';
	$plugin_hooks['config_settings']['cycle'] = 'cycle_config_settings';
	$plugin_hooks['api_graph_save']['cycle'] = 'cycle_api_graph_save';
}

function cycle_version () {
	return array( 'name'     => 'Cycle Graphs',
		'version'     => '0.4',
		'longname'    => 'Cycle Graphs',
		'author'    => 'Matt Emerick-Law',
		'homepage'    => 'http://emericklaw.co.uk',
		'email'    => 'matt@emericklaw.co.uk',
		'url'        => 'http://cactiusers.org/cacti/versions.php'
	);
}

function cycle_config_settings () {
	global $tabs, $settings;
	$tabs["cycle"] = "Cycle";

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
		"cycle_custom_graphs_list" => array(
			"friendly_name" => "Custom Graph List",
			"description" => "This is a list of the graph IDs that you want to include in thr rotation.",
			"method" => "textbox",
			"max_length" => 255,
			),
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
	$nav["cycle.php:"] = array("title" => "Cycling", "mapping" => "index.php:", "url" => "cycle.php", "level" => "1");
	return $nav;
}

function cycle_api_graph_save ($save) {
}
?>
