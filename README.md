# cycle

The Cacti Cycle plugin allows you to automatically view the Cacti graphs one by
one after a specified time delay.  It allows you to select a tree, or branch, as
well as specifically selected graphs.

## Features

* You can set cycle time delay.

* Can set permissions on who can view.

* Graph height and width can be specified.

* Graphs are not displayed if the user does not have access to them.

* You can use the Prev/Next buttons to change graph.

* You can stop the rotation with the Stop button.

* The time until the next graph change is displayed under the title.

* It can use a custom graph list and only cycle through those.

## Installation

To install put the cycle directory and all files into the plugins directory.

Edit your includes/config.php and add cycle to your $plugins list.

You can find the plugin settings under the Misc tab.

## Possible Bugs

The custom graph rotation is very simple and does not check for permissions.

## Future Changes

Improve the way custom graphs are setup

Cycle through Weathermaps (I need to look into how Weathermap works)

Got any ideas then please let us know.

-----------------------------------------------
Copyright (c) 2004-2024 - The Cacti Group, Inc.
