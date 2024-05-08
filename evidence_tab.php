<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2021-2024 Petr Macek                                      |
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
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | https://github.com/xmacan/                                              |
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

chdir('../../');
include_once('./include/auth.php');
include_once('./lib/snmp.php');
include_once('./plugins/evidence/include/functions.php');

if (!isempty_request_var('find')) {
	set_request_var('action', 'find');
	unset_request_var('host_id');
}

set_default_action();

$selectedTheme = get_selected_theme();

switch (get_request_var('action')) {
	case 'ajax_hosts':

		$sql_where = '';
		get_allowed_ajax_hosts(true, 'applyFilter', $sql_where);

		break;

	case 'find':
		general_header();
		display_evidence();
		evidence_find();
		bottom_footer();

		break;

        default:
		general_header();
		display_evidence();
		bottom_footer();

		break;
}

function display_evidence() {
	global $config;

	$evidence_records = read_config_option('evidence_records');

	print get_md5_include_js($config['base_path'].'/plugins/evidence/evidence.js');

	$host_where = '';

	$host_id = get_filter_request_var('host_id');

	html_start_box('<strong>evidence</strong>', '100%', '', '3', 'center', '');

?>

	<tr>
	 <td>
	  <form name="form_evidence" action="evidence_tab.php">
		<table width="60%" cellpadding="0" cellspacing="0">
		<tr class="navigate_form">
		<td>
		       <?php print html_host_filter($host_id, 'applyFilter', $host_where);?>
		</td>
		<td>
			Find in stored data <input type='text' class='ui-button ui-corner-all ui-widget' name='find' id='find' value='<?php print get_request_var('find');?>'> 
			<input type='submit' class='ui-button ui-corner-all ui-widget' value='<?php print __('Find');?>'>
		</td>
		<td>
			<input type='button' class='ui-button ui-corner-all ui-widget' id='clear' value='<?php print __('Clear');?>' title='<?php print __esc('Clear Filters');?>'> 
		</td>
		</tr>
		</table>
	  </form>
       </td>
     <tr>
<?php
	html_end_box();

	if (in_array($host_id, plugin_evidence_get_allowed_devices($_SESSION['sess_user_id'], true))) {

		$host = db_fetch_row_prepared ("SELECT *
			FROM host
			WHERE id = ? AND
			disabled != 'on' AND
			status BETWEEN 2 AND 3",
			array($id));
// !!! resim zobrazovani
		if (!$host) {
			print __('Disabled/down device. No actual data', 'evidence') . '<br/>';

			if ($evidence_records > 0) {
				print_r (plugin_evidence_history($id), true);
//!! tady udelas skryvaci historii starsi, porovnavat mezi verzemi
			} else {
				print 'History data store disabled';
			}
		} else {
			print_r (plugin_evidence_actual_data($host), true);
//!! tady porovnat s actual
			print '<br/><br/>';

			if ($evidence_records > 0) {
				print_r (plugin_evidence_history($id), true);
//!! tady udelas skryvaci historii starsi, porovnavat mezi verzemi
			} else {
				print __('History data store disabled', 'evidence');
			}
		}
	} else {
		print __('Permission issue', 'evidence');
	}
}

