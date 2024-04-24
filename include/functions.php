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

function plugin_evidence_config_settings() {
	global $tabs, $settings, $config;

	$tabs['evidence'] = 'evidence';

	$settings['evidence'] = array(
		'evidence_hosts_processed' => array(
			'friendly_name' => 'Run periodically and store evidence history',
			'description'   => 'If enabled, every poller run evidence detects information about several devices and store results.',
			'method'        => 'drop_array',
			'array'         => array(
				'0'    => 'Disabled',
				'10'   => '10 devices',
				'50'   => '50 devices',
				'100'  => '100 devices',
			),
			'default'       => '0',
		),
		'evidence_records' => array(
		'friendly_name' => 'How many changes store',
			'description'   => 'How many history (changed) records keep for each device',
			'method'        => 'drop_array',
			'array'         => array(
				'0'    => 'Without history',
				'1'    => '1 record',
				'5'   => '5 records',
				'10'   => '10 records',
			),
			'default'       => '5',
		),
		'evidence_history' => array(
			'friendly_name' => 'Recheck after',
			'description'   => 'The shortest possible interval after which new testing will occur',
			'method'        => 'drop_array',
			'array'         => array(
				'1'    => '1 day',
				'7'    => '7 days',
				'30'   => '30 days',
				'100'  => '100 days',
			),
			'default'       => '30',
		),
		'evidence_email_notify' => array(
			'friendly_name' => 'Send email on evidence information change',
			'description'   => 'If evidence find change, send email',
			'method'        => 'checkbox',
			'default'       => 'off',
		),
		'evidence_email_notify_exclude' => array(
			'friendly_name' => 'Excluded notification Host IDs',
			'description'   => 'Some devices report hw changes too often. You can exclude these host from email notification. Insert Host IDs, comma separator',
			'method'        => 'textbox',
			'max_length'	=> '500',
			'default'       => '',
		),
	);
}


function plugin_evidence_poller_bottom () {
	global $config;

	include_once('./plugins/evidence/functions.php');

	list($micro,$seconds) = explode(" ", microtime());
	$start = $seconds + $micro;

	$now = time();
	$done = 0;

	$number_of_hosts = read_config_option('evidence_hosts_processed');
	$evidence_history = read_config_option('evidence_history');
	$evidence_records = read_config_option('evidence_records');

	if ($number_of_hosts > 0) {
		// new/not tested hosts
		$hosts1 = db_fetch_assoc ("(SELECT h1.id as id,last_check as xx FROM host AS h1 LEFT JOIN plugin_evidence_history AS h2 
			ON h1.id=h2.host_id WHERE h1.disabled != 'on' AND h1.status BETWEEN 2 AND 3 AND h2.last_check IS NULL)
			LIMIT " . $number_of_hosts);

		$returned = cacti_sizeof($hosts1);

		// already tested hosts
		$hosts2 = db_fetch_assoc ("select h1.host_id as id,h1.last_check as xx, host.description as description,
			host.hostname as hostname 
			from plugin_evidence_history as h1 join host on host.id=h1.host_id 
			where host.disabled != 'on' and host.status between 2 and 3 
				and h1.last_check = (select max(h2.last_check) 
					from plugin_evidence_history as h2 where h1.host_id = h2.host_id) 
					having now() > date_add(xx, interval " . $evidence_history . " day)
					limit " . ($number_of_hosts-$returned) );

		$hosts = array_merge($hosts1,$hosts2);

		if (cacti_sizeof($hosts) > 0) {
			foreach ($hosts as $host) {

				$data_act = plugin_evidence_get_info($host['id']);

				$data_his = db_fetch_row_prepared ('SELECT * FROM plugin_evidence_history 
					WHERE host_id = ? ORDER BY last_check DESC LIMIT 1', array($host['id']));

				if ($data_his) {

					$data_his = stripslashes($data_his['data']);

					if (strcmp ($data_his, $data_act) === 0) {	// only update last check
						db_execute ('UPDATE plugin_evidence_history set last_check = now() 
							WHERE host_id = ' . $host['id'] . ' ORDER BY last_check DESC LIMIT 1');
					} else {

						db_execute ("INSERT INTO plugin_evidence_history (host_id,data,last_check) VALUES (" .
						$host['id'] . ",'" . addslashes($data_act) . "', now())");

// !!! tenhle mazaci je asi spatne, maze toho moc, mozna uz opraveno
						db_execute_prepared ('DELETE FROM plugin_evidence_history WHERE host_id = ? ORDER BY last_check LIMIT ? OFFSET ?',
							array($host['id'], 100, $evidence_records));

						$excluded = explode(',', read_config_option('evidence_email_notify_exclude'));

						if (read_config_option('evidence_email_notify')) {
							if (in_array($host['id'], $excluded)) {
								cacti_log('Plugin evidence - host changed (id:' . $host['id'] . '),  excluded from notification');
							} else {

								$emails = db_fetch_cell_prepared ('SELECT emails, host.* FROM plugin_notification_lists 
									LEFT JOIN host
									ON plugin_notification_lists.id = host.thold_host_email
									WHERE host.id = ?', array($host['id']));

								 send_mail($emails,
									read_config_option('settings_from_email'),
									'Plugin evidence - device ' . $host['description'] . ' changed',
									'I have found any HW/serial number change on host ' . $host['description'] . ' (' . $host['hostname'] . '):<br/>' . PHP_EOL .
									$data_act . '<br/><br/>' . PHP_EOL . 'Older data:<br/>' . PHP_EOL . $data_his, '', '', true); 

								cacti_log('Plugin evidence - host changed (id:' . $host['id'] . '), sending email notification');
							}

						} else { // only log
							cacti_log('Plugin evidence - host changed (id:' . $host['id'] . '),  only logging');
						}
					}
				} else {
					db_execute ("INSERT INTO plugin_evidence_history (host_id,data,last_check) VALUES (" .
						$host['id'] . ",'" . addslashes($data_act) . "', now())");
				}
				$done++;
			}
		}
	}

	list($micro,$seconds) = explode(" ", microtime());
	$total_time = $seconds + $micro - $start;

	cacti_log('evidence STATS: hosts processed/max: ' . $done . '/' . $number_of_hosts . '. Duration: ' . round($total_time,2));
}


function plugin_evidence_device_remove($device_id) {

	db_execute_prepared('DELETE FROM plugin_evidence_history WHERE host_id = ?', array($device_id));
}




function evidence_show_tab () {
	global $config;

	if (api_user_realm_auth('evidence.php')) {
		$cp = false;
		if (basename($_SERVER['PHP_SELF']) == 'evidence.php') {
			$cp = true;
		}

		print '<a href="' . $config['url_path'] . 'plugins/evidence/evidence_tab.php"><img src="' . $config['url_path'] . 'plugins/evidence/images/tab_evidence' . ($cp ? '_down': '') . '.gif" alt="evidence" align="absmiddle" border="0"></a>';
	}
}







function plugin_evidence_device_edit_top_links (){
	print "<br/><span class='linkMarker'>* </span><a id='evidence_info' data-evidence_id='" . get_request_var('id') . "' href=''>" . __('evidence') . "</a>";
}


function plugin_evidence_host_edit_bottom () {
	global $config;
	print get_md5_include_js($config['base_path'].'/plugins/evidence/evidence.js');
}



