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


