# ChangeLog

--- develop ---

* issue: Save filter uses get method instead of post causing save failures
* issue: The use of strlen() for null strings causes an error in PHP8+

--- 4.3 ---

* issue#19: Correct warnings in the latest Cacti with cycle_config_form not existing

--- 4.2 ---

* issue#10: Non-numeric value when clicking 'Cycle' tab
* issue#11: Unable to edit user settings
* issue#13: Graphs do not show when entering 'Cycle' tab from another page
* issue#14: Countdown timer does not stop when requested
* issue#16: Cycle Javascript does not always refresh during updates

--- 4.1 ---

* feature: Change layout to be responsive
* feature: Change filtering to be more consistent with Cacti framework

--- 4.0 ---

* feature: Support for Cacti 1.0
* issue#3: Legend is not functioning properly in Cycle
* issue#4: Errors in Cacti log when specific graph list is used

--- 3.1 ---

* feature: Compatibility with new Themes engine

--- 2.3 ---

* bug#0002113: Several graph settings are not preserved during session
* bug#0002115: The filtering did not work as expected
* bug#0002114: Graphs are not cycled correctly
* bug#0002120: No graphs not shown if default deny policy used
* fix: Ordering of Tree Graph ID's breaks cycling

--- 2.2 ---

* fix: defaults keep getting reset

--- 2.1 ---

* feature: Support for Ugroup Plugin

--- 2.0 ---

* feature: Allow searching through graphs with regular expression
* feature: Allow selecting both tree and leaves
* feature: Add Columns and Graphs Per Page to Interface
* feature: Make the Cycle Settings page more readable
* feature: Convert all Ajax calls to use jQuery instead
* bug: Make the scanning for next and previous graphs more efficient
* bug: Remove 'Graphs' dropdown for all cases

--- 1.3 ---

* bug: the legend view does not work when refresh page.
* bug: 'Prev' and 'Next' does not work correctly with dropdown menu.
* bug: delay interval does not work correctly.
* bug: the graph cannot be duplicated.
* feature: host tree support.
* feature: permission check support.

--- 1.2 ---

* bug: Fix issue where realms can be damaged when upgrading

--- 1.1 ---

* compat: Allow proper navigation text generation

--- 1.0 ---

* feature: Adding support for 0.8.7f
* compat: Dropping support for PIA 1.x

--- 0.8 ---

* feature: Fixed custom tree mod view
* feature: Fixed timespan function in default view
* feature: Fixed guest access permission
* feature: Improved control panel
* feature: Added tree selector(Custom graph rotation only)
* feature: Added graph selector
* feature: Added legend display control
* feature: Added graph control buttons

--- 0.7 ---

* feature: Add timespan selector and graph window
* feature: Implement buttons and not links for next/pref/stop/start
* feature: Make PIA2.x compatible
* feature: Auto Upgrade

--- 0.6 ---

* feature: Ability for cycle to use a Tree and cycle through the Leaf's instead
  of just graphs.

--- 0.3 ---

* feature: Converted the rotation code to use AJAX so the page does not fully
  refresh every time.

* feature: Added custom graph rotation

--- 0.2 ---

* feature; Added Previous/Next/Stop Buttons

--- 0.1 ---

* Initial release

-----------------------------------------------
Copyright (c) 2004-2024 - The Cacti Group, Inc.
