<?php

/**
 * @package Language Boards
 * @version 1.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');

elseif (!defined('SMF'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	global $smcFunc, $context;

	db_extend('packages');

	if (empty($context['uninstalling']))
	{
		// Add a column for board language
		$smcFunc['db_add_column'](
			'{db_prefix}boards', 
			[
				'name' => 'board_language',
				'type' => 'varchar',
				'size' => 255,
				'default' => ' ',
				'not_null' => true,
			]
		);
	}