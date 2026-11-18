# cycle

The Cacti Cycle plugin allows you to automatically view the Cacti graphs one by
one after a specified time delay.  It allows you to select a tree, or branch, as
well as specifically selected graphs.

## Versions and compatibility

This README describes the `develop` source branch.

| Code | Plugin version | Minimum Cacti declared in `INFO` |
| --- | --- | --- |
| [Source branch](INFO) | 4.3 | 1.2.29 |
| [Published v4.1](https://github.com/Cacti/plugin_cycle/releases/tag/v4.1) | 4.1 | 1.1.22 |

Use the published release’s assets and tagged documentation when installing that
package. Branch code can include later changes, even when its version number
matches the release. Cacti’s own PHP and database requirements also apply.

Install this plugin in **`<cacti-root>/plugins/cycle`**, using the directory
name `cycle` rather than `plugin_cycle`.

See [CHANGELOG.md](CHANGELOG.md) for the version history.

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

Copy the plugin to `plugins/cycle` inside the Cacti installation, then install
and enable **Cycle** in Plugin Management. Grant the Cycle realm to the users
who need the rotation view. No `$plugins` entry in a configuration file is needed.

Configure defaults under **Settings > Cycle**. The Cycle view provides graph
selection, dimensions, columns, rotation delay, and Start/Stop/Prev/Next controls.

## Possible Bugs

Graph selection is checked against the current user’s graph permissions,
including a custom graph list. If an expected graph is missing, check the user’s
Cacti permissions and the selected tree or list.

## Future Changes

Improve the way custom graphs are setup

Cycle through Weathermaps (I need to look into how Weathermap works)

Got any ideas then please let us know.

-----------------------------------------------
Copyright (c) 2004-2026 - The Cacti Group, Inc.
