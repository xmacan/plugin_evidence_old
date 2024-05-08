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

$evidence_records = read_config_option('evidence_records');

$id = get_filter_request_var('host_id');

$allowed = plugin_evidence_get_allowed_devices($_SESSION['sess_user_id'], true);

if (is_array($allowed) && in_array($id, $allowed)) {

	$host = db_fetch_row_prepared ("SELECT *
		FROM host
		WHERE id = ? AND
		disabled != 'on' AND
		status BETWEEN 2 AND 3",
		array($id));

	if (!$host) {
		print __('Disabled/down device. No actual data', 'evidence') . '<br/>';

		if ($evidence_records > 0) {
			print_r (plugin_evidence_history($id), true);
		} else {
			print 'History data store disabled';
		}
	} else {
		print_r (plugin_evidence_actual_data($host), true);

		print '<br/><br/>';

		if ($evidence_records > 0) {
			print_r (plugin_evidence_history($id), true);
		} else {
			print __('History data store disabled', 'evidence');
		}
	}
} else {
	print __('Permission issue', 'evidence');
}

