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
// !! unsetnout jen hosta nebo vsechno? Nebo nic
	unset_request_var('template_id');
	unset_request_var('entity');
	unset_request_var('template_id');

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
		evidence_display();
		evidence_find();
		bottom_footer();

		break;

        default:
		general_header();
		evidence_display();
		evidence_stats();
		bottom_footer();

		break;
}

function evidence_display() {
	global $config;

	$evidence_records   = read_config_option('evidence_records');
	$evidence_frequency = read_config_option('evidence_frequency');

	print get_md5_include_js($config['base_path'].'/plugins/evidence/evidence.js');

	$host_where = '';

	$host_id = get_filter_request_var('host_id');
	$template = get_filter_request_var('template');

	print '<form name="form_evidence" action="evidence_tab.php">';

	html_start_box('<strong>Evidence</strong>', '100%', '', '3', 'center', '');

	print "<tr class='even noprint'>";
	print "<td>";
	print "<form id='form_devices' action='host.php'>";
	print "<table class='filterTable'>";
	print "<tr>";

	print html_host_filter($host_id, 'applyFilter', $host_where, false, true);

	print "<td>";
	print __('Template');
	print "</td>";
	print "<td>";
	print "<select id='template_id' onChange='applyFilter()'>";
	print "<option value='-1'" . (get_request_var('template_id') == '-1' ? ' selected' : '') . '>' . __('Any') . '</option>';

	if (get_request_var('host_id') == 0) {
		$templates = get_allowed_graph_templates_normalized('gl.host_id=0', 'name', '', $total_rows);
	} elseif (get_request_var('host_id') > 0) {
		$templates = get_allowed_graph_templates_normalized('gl.host_id=' . get_filter_request_var('host_id'), 'name', '', $total_rows);
	} else {
		$templates = get_allowed_graph_templates_normalized('', 'name', '', $total_rows);
	}

	if (cacti_sizeof($templates)) {
		foreach ($templates as $template) {
			print "<option value='" . $template['id'] . "'"; if (get_request_var('template_id') == $template['id']) { print ' selected'; } print '>' . html_escape($template['name']) . "</option>\n";
		}
	}

	print '</select>';
	print '</td>';

	print '<td>';
	print __('Scan date', 'evidence');
	print '</td>';
	print '<td>';
//!!! dle scandate se musi zmenit i host - mozna nemusi, kdyztak zrusit applyfilter

	print '<select id="scan_date" name="scan_date" onChange="applyFilter()">';
	print '<option value="0" ' . (get_request_var('scan_date') == 0 ? 'selected="selected"' : '') . '>' . __('All', 'evidence') . '</option>';

	$scan_dates = array_column(db_fetch_assoc('SELECT DISTINCT(scan_date) FROM plugin_evidence_entity
		UNION SELECT DISTINCT(scan_date) FROM plugin_evidence_mac
		UNION SELECT DISTINCT(scan_date) FROM plugin_evidence_vendor_specific
		ORDER BY scan_date DESC'), 'scan_date');

	if (cacti_sizeof($scan_dates)) {
		foreach ($scan_dates as $scan_date) {
			print '<option value="' . $scan_date . '" ' . 
				(get_request_var('scan_date') == $scan_date ? ' selected="selected"' : '') . 
				'>' . $scan_date . '</option>';
		}
	}

	print '</select>';
	print '</td>';
	print '<td>';
	print '<input type="submit" class="ui-button ui-corner-all ui-widget" id="refresh" value="' . __('Go') . '" title="' . __esc('Set/Refresh Filters') . '">';
	print '<input type="button" class="ui-button ui-corner-all ui-widget" id="clear" value="' . __('Clear') . '" title="' . __esc('Clear Filters') . '">';
	print '</td>';
	print '</tr>';
	print '</table>';

	print "<table class='filterTable'>";
	print '<tr>';
	print '<td>';
	print 'Search';
	print '</td>';
	print '<td>';

	print '<input type="text" name="find" id="find" value="' . get_request_var('find') . '">';
	print '</td>';
	print '<td>';
	print __('Specific entity');
	print '</td>';
	print '<td>';

	print '<select id="entity" name="entity" onChange="applyFilter()">';
	print '<option value="0" ' . (get_request_var('entity') == 0 ? 'selected="selected"' : '') . '>' . __('All', 'evidence') . '</option>';

	print '<option value="descr" ' . (get_request_var('entity') == 'descr' ? 'selected="selected"' : '') . '>Descr</option>';
	print '<option value="name" ' . (get_request_var('entity') == 'name' ? 'selected="selected"' : '') . '>Name</option>';
	print '<option value="hardware_rev" ' . (get_request_var('entity') == 'hardware_rev' ? 'selected="selected"' : '') . '>Hardware revision</option>';
	print '<option value="firmware_rev" ' . (get_request_var('entity') == 'firmware_rev' ? 'selected="selected"' : '') . '>Firmware revision</option>';
	print '<option value="software_rev" ' . (get_request_var('entity') == 'software_rev' ? 'selected="selected"' : '') . '>Software revision</option>';
	print '<option value="serial_num" ' . (get_request_var('entity') == 'serial_num' ? 'selected="selected"' : '') . '>Serial number</option>';
	print '<option value="mfg_name" ' . (get_request_var('entity') == 'mfg_name' ? 'selected="selected"' : '') . '>Manufacturer name</option>';
	print '<option value="model_name" ' . (get_request_var('entity') == 'model_name' ? 'selected="selected"' : '') . '>Model name</option>';
	print '<option value="alias" ' . (get_request_var('entity') == 'alias' ? 'selected="selected"' : '') . '>Alias name</option>';
	print '<option value="asset_id" ' . (get_request_var('entity') == 'asset_id' ? 'selected="selected"' : '') . '>Asset ID</option>';
	print '<option value="mfg_date" ' . (get_request_var('entity') == 'mfg_date' ? 'selected="selected"' : '') . '>Manufacturing date</option>';
	print '<option value="uuid" ' . (get_request_var('entity') == 'uuid' ? 'selected="selected"' : '') . '>UUID</option>';
	print '</td>';
	print '</tr>';

	print '</table>';

	print '</form>';

	html_end_box();
}

function evidence_find() {


	$host_id = get_filter_request_var('host_id');
	$template = get_filter_request_var('template');
	$scan_date = get_request_var('scan_date'); //!! tohle osetrit
	$entity = get_request_var('entity'); //!! tohle osetrit
	
	$find = get_filter_request_var('find', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z0-9_\-\.:]{3,})
	if (strlen($find) < 3 || strlen($find > 20) {
		print __(Search string must be 3-20 characters';
		return false;
	}

	if (in_array($host_id, plugin_evidence_get_allowed_devices($_SESSION['sess_user_id'], true))) {

// !! tady asi muzou byt i disabled
// 			disabled != 'on' AND
// 			status BETWEEN 2 AND 3

		$host = db_fetch_row_prepared ('SELECT *
			FROM host
			WHERE id = ?',
			array($host_id));

		if ($host) { 
			if ($host['disabled'] == 'on' || ($host['status'] == 2 || $host['status'] == 3)) {
				print __('Disabled/down device. No actual data', 'evidence') . '<br/>';

				if ($evidence_records > 0) {
					print_r (plugin_evidence_history($host_id), true);
	//!! tady udelas skryvaci historii starsi, porovnavat mezi verzemi
				} else {
					print 'History data store disabled';
				}
			} else {
				print_r (plugin_evidence_actual_data($host), true);
		//!! tady porovnat s actual
				print '<br/><br/>';

				if ($evidence_records > 0) {
					print_r (plugin_evidence_history($host_id), true);
	//!! tady udelas skryvaci historii starsi, porovnavat mezi verzemi
				} else {
					print __('History data store disabled', 'evidence');
				}
			}
		}
	} elseif (empty($host_id)) {

		if ($evidence_frequency == 0 || $evidence_records == 0) {
			print __('No data. Allow periodic scan and store history in settings');
		}

		print '<br/><br/>';
		print __('You can display all information about specific host, all devices with the same template.') . '<br/>';
		print __('You can display for example only serial numbers for all host via specify entity.') . '<br/>';
		print __('You can search any string in all data. ') . '<br/>';

		$ent = db_fetch_cell ('SELECT COUNT(*) FROM plugin_evidence_entity');
		$mac = db_fetch_cell ('SELECT COUNT(*) FROM plugin_evidence_mac');
		$ven = db_fetch_cell ('SELECT COUNT(*) FROM plugin_evidence_vendor_specific');
		$old = db_fetch_cell ('SELECT MIN(scan_date) FROM plugin_evidence_entity');

		print '<br/><br/>';
		print '<strong>' . __('Number of records') . ':</strong><br/>';
		print 'Entity MIB: ' . $ent . '<br/>';
		print 'MAC adresses: ' . $mac . '<br/>';
		print 'Vendor specific data: ' . $ven . '<br/>';
		print 'Oldest record: ' . $old . '<br/>';
	}
}

function evidence_stats() {
	global $config;

	$evidence_records   = read_config_option('evidence_records');
	$evidence_frequency = read_config_option('evidence_frequency');

	if ($evidence_frequency == 0 || $evidence_records == 0) {
		print __('No data. Allow periodic scan and store history in settings');
	}

	print '<br/><br/>';
	print __('You can display all information about specific host, all devices with the same template.') . '<br/>';
	print __('You can display for example only serial numbers for all host via specify entity.') . '<br/>';
	print __('You can search any string in all data. ') . '<br/>';

	$ent = db_fetch_cell ('SELECT COUNT(*) FROM plugin_evidence_entity');
	$mac = db_fetch_cell ('SELECT COUNT(*) FROM plugin_evidence_mac');
	$ven = db_fetch_cell ('SELECT COUNT(*) FROM plugin_evidence_vendor_specific');
	$old = db_fetch_cell ('SELECT MIN(scan_date) FROM plugin_evidence_entity');

	print '<br/><br/>';
	print '<strong>' . __('Number of records') . ':</strong><br/>';
	print 'Entity MIB: ' . $ent . '<br/>';
	print 'MAC adresses: ' . $mac . '<br/>';
	print 'Vendor specific data: ' . $ven . '<br/>';
	print 'Oldest record: ' . $old . '<br/>';
}



