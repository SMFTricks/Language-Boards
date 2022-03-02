<?php

/**
 * @package Language Boards
 * @version 1.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

if (!defined('SMF'))
	die('No direct access...');

class LanguageBoards
{
	/**
	 * @var string The name of the magnificent column
	 */
	private static $_column = 'board_language';

	/**
	 * @var array Store the forum languages
	 */
	private static $_languages = [];

	/**
	 * @var string Saves the languages in usable html options
	 */
	private static $_select_lang;

	/**
	 * @var array Will store boards that we want to hide per user in boardindex/messageindex
	 */
	private static $_hide_boards = [];

	/**
	 * @var array CTE boards array for support?
	 */
	private static $_cte_boards = [];

	/**
	 * LanguageBoards::pre_boardtree()
	 *
	 * Append our column to the board tree
	 * 
	 * @param array $boardColumns An array containing the additional columns
	 * @return void
	 */
	public static function pre_boardtree(&$boardColumns)
	{
		$boardColumns[] = 'b.' . self::$_column;
	}

	/**
	 * LanguageBoards::boardtree_board()
	 *
	 * Add the appropiate board rows with the information from the column
	 * 
	 * @param array $row An array containing board results
	 * @return void
	 */
	public static function boardtree_board($row)
	{
		global $boards;

		if (!empty($row['id_board']))
			$boards[$row['id_board']][self::$_column] = $row[self::$_column];
	}

	/**
	 * LanguageBoards::edit_board()
	 *
	 * Include our board settings for admin and board managers to edit
	 * 
	 * @return void
	 */
	public static function edit_board()
	{
		global $context, $txt;

		// Load our language
		loadLanguage('LanguageBoards/');

		// By default, nothing
		if (isset($context['board']['is_new']) && $context['board']['is_new'] === true) 
			$context['board'][self::$_column] = '';

		// Get the options
		self::$_languages = self::select_lang();

		$context['custom_board_settings'][self::$_column] = [
			'dt' => '<strong>'. $txt['LanguageBoards_setting']. '</strong><br /><span class="smalltext">'. $txt['LanguageBoards_setting_desc']. '</span>',
			'dd' => '
				<select name="'. self::$_column. '">
					<option value=""' . (empty($context['board'][self::$_column]) || !in_array($context['board'][self::$_column], $context['languages']) ? ' selected' : '') . '>
						' . $txt['permissions_option_any'] . '
					</option>
					<optgroup label="' . $txt['language_configuration'] . '">
						' . self::$_languages . '
					</optgroup>
				</select>',
		];
	}

	/**
	 * LanguageBoards::select_lang()
	 *
	 * Just a silly tiny function to setup the options
	 * 
	 * @return array
	 */
	public static function select_lang()
	{
		global $context;

		// Get the forum languages
		getLanguages();

		// Store the current languages?
		self::$_select_lang = '';
		foreach ($context['languages'] as $forum_lang)
			// Build the options
			self::$_select_lang .= '
				<option value="'. $forum_lang['filename'] . '"' . (!empty($context['board'][self::$_column]) && $context['board'][self::$_column] == $forum_lang['filename'] ? ' selected' : '') . '>' . $forum_lang['name'] . '</option>';

		return self::$_select_lang;
	}

	/**
	 * LanguageBoards::create_board()
	 *
	 * Set a default value for the board option
	 * 
	 * @param array $boardOptions An array containing the board options
	 * @return void
	 */
	public static function create_board(&$boardOptions)
	{
		$boardOptions[self::$_column] = '';
	}

	/**
	 * LanguageBoards::modify_board()
	 *
	 * Update the value accordinly
	 * 
	 * @param int $id The board id
	 * @param array $boardOptions An array containing the board options
	 * @param array $boardUpdates All things that will be updated in the database
	 * @param array $boardUpdateParameters The new values
	 * @return void
	 */
	public static function modify_board($id, $boardOptions, &$boardUpdates, &$boardUpdateParameters)
	{
		global $context, $smcFunc;

		// Store it
		$boardOptions[self::$_column] = !empty($_POST[self::$_column]) ? (string) $smcFunc['htmlspecialchars']($_POST[self::$_column], ENT_QUOTES) : '';

		// Just in case there's nonsense in the info
		getLanguages();

		// Get a list of the languages on the fly
		foreach ($context['languages'] as $forum_lang)
			self::$_languages[] = $forum_lang['filename'];

		// And then... Do we actually have that language in the forum?
		if (!in_array($boardOptions[self::$_column], self::$_languages))
			$boardOptions[self::$_column] = '';

		// Save it then
		if (isset($boardOptions[self::$_column]))
		{
			$boardUpdates[] = self::$_column .' = {string:' . self::$_column . '}';
			$boardUpdateParameters[self::$_column] = $boardOptions[self::$_column] ? $boardOptions[self::$_column] : '';
		}
	}

	/**
	 * LanguageBoards::pre_boardindex()
	 *
	 * Include the column for some fun use in the board array
	 * 
	 * @param array $board_index_selects An array containing the new board columns
	 * @return void
	 */
	public static function pre_boardindex(&$board_index_selects)
	{
		global $smcFunc;

		// check compatibility
		if ($smcFunc['db_cte_support']())
		{
			// Cache the boards
			if ((self::$_cte_boards = cache_get_data('language_boards_cte', 3600)) === null)
			{
				// Get the boards language
				$no_rec_boards = $smcFunc['db_query']('', '
					SELECT b.id_board, b.' . self::$_column . '
					FROM {db_prefix}boards AS b',
					[]
				);
				while ($row = $smcFunc['db_fetch_assoc']($no_rec_boards))
					self::$_cte_boards[$row['id_board']] = $row[self::$_column];
				$smcFunc['db_free_result']($no_rec_boards);

				// Cache
				cache_put_data('language_boards_cte', self::$_cte_boards, 3600);
			}

			// Done here, no columns
			return;
		}

		// Add the column to the board query
		$board_index_selects[] = 'b.' . self::$_column;
	}

	/**
	 * LanguageBoards::boardindex_board()
	 *
	 * Load the column in the data array
	 * 
	 * @param array $this_category An array containing the category data
	 * @param array $row_board An array containing the board data
	 * @return void
	 */
	public static function boardindex_board(&$this_category, $row_board)
	{
		global $smcFunc;

		// Add the board language, and check for cte compatibility
		$this_category[$row_board['id_board']][self::$_column] = $smcFunc['db_cte_support']() ? (isset(self::$_cte_boards[$row_board['id_board']]) ? self::$_cte_boards[$row_board['id_board']] : '') : $row_board[self::$_column];
	}

	/**
	 * LanguageBoards::getboardtree()
	 *
	 * This is before returning the huge array of categories and boards
	 * 
	 * @param array $category An array containing the categories or a category data
	 * @return void
	 */
	public static function getboardtree($board_index_options, &$category)
	{
		global $user_info, $board;

		self::$_hide_boards = [];

		// Only select this in the boardindex, or messageindex gets mad :(
		if (empty($board))
		{
			// Loop through the categories
			foreach ($category as $cat)
			{
				// Loop through the boards
				foreach ($cat['boards'] as $id_board => $lang_board)
				{
					// Save the boards that have a language, only if the user doesn't manage the boards
					if (!allowedTo('manage_boards') && !empty($lang_board[self::$_column]))
					{
						self::$_hide_boards[$id_board] = [
							'cat' => $lang_board['id_cat'],
							'lang' => $lang_board[self::$_column]
						];
					}
				}
			}

			//  HIde these boards if: the board has a specific language and the user language doesn't match the board language
			foreach (self::$_hide_boards as $hide_id => $hide_info)
			{
				if ($user_info['language'] != $hide_info['lang'])
					unset($category[$hide_info['cat']]['boards'][$hide_id]);
			}
		}
	}

	/**
	 * LanguageBoards::load_board()
	 *
	 *  Adding the column to the board query
	 * 
	 * @param array $custom_column_selects An array containing the column
	 * @return void
	 */
	public static function load_board(&$custom_column_selects)
	{
		global $modSettings;

		// Add the column if we are denying access
		if (!empty($modSettings['lb_board_deny']) && !allowedTo('manage_boards'))
			$custom_column_selects[] = 'b.' . self::$_column;
	}

	/**
	 * LanguageBoards::board_info()
	 *
	 *  Deny access if the option is enabled
	 * 
	 * @param array $board_info An array containing the board data
	 * @param array $row An array containing the query results
	 * @return void
	 */
	public static function board_info(&$board_info, $row)
	{
		global $user_info;

		// Is the column already loaded?
		if (!empty($row[self::$_column]) && $user_info['language'] != $row[self::$_column])
		{
			// $board_info[self::$_column] = $row[self::$_column];
			// Deny access then...
			$board_info['error'] = 'access';
		}
	}

	/**
	 * LanguageBoards::user_info()
	 *
	 *  Manipulate the 'query_see_board' queries to include our language playground
	 * 
	 * @return void
	 */
	public static function user_info()
	{
		global $user_info, $modSettings;

		if (!empty($modSettings['lb_board_deny']))
		{
			$temp = self::query_board($user_info['id']);
			$user_info['query_see_board'] = $temp['query_see_board'];
			$user_info['query_see_message_board'] = $temp['query_see_message_board'];
			$user_info['query_see_topic_board'] = $temp['query_see_topic_board'];
			$user_info['query_wanna_see_board'] = $temp['query_wanna_see_board'];
			$user_info['query_wanna_see_message_board'] = $temp['query_wanna_see_message_board'];
			$user_info['query_wanna_see_topic_board'] = $temp['query_wanna_see_topic_board'];
		}
	}

	/**
	 * LanguageBoards::query_board()
	 * 
	 * Build query_wanna_see_board and query_see_board for a userid
	 *
	 * Returns array with keys query_wanna_see_board and query_see_board
	 *
	 * @param int $userid of the user
	 * @return array
	 */
	public static function query_board($userid)
	{
		global $user_info, $modSettings, $smcFunc, $db_prefix, $language;

		$query_part = array();

		// If we come from cron, we can't have a $user_info.
		if (isset($user_info['id']) && $user_info['id'] == $userid && SMF != 'BACKGROUND')
		{
			$groups = $user_info['groups'];
			$can_see_all_boards = $user_info['is_admin'] || $user_info['can_manage_boards'];
			$ignoreboards = !empty($user_info['ignoreboards']) ? $user_info['ignoreboards'] : null;
			$user_lang = !empty($user_info['language']) ? $user_info['language'] : $language;
		}
		else
		{
			$request = $smcFunc['db_query']('', '
				SELECT mem.ignore_boards, mem.id_group, mem.additional_groups, mem.id_post_group, mem.lngfile
				FROM {db_prefix}members AS mem
				WHERE mem.id_member = {int:id_member}
				LIMIT 1',
				array(
					'id_member' => $userid,
				)
			);

			$row = $smcFunc['db_fetch_assoc']($request);

			if (empty($row['additional_groups']))
				$groups = array($row['id_group'], $row['id_post_group']);
			else
				$groups = array_merge(
					array($row['id_group'], $row['id_post_group']),
					explode(',', $row['additional_groups'])
				);

			// Because history has proven that it is possible for groups to go bad - clean up in case.
			foreach ($groups as $k => $v)
				$groups[$k] = (int) $v;

			$can_see_all_boards = in_array(1, $groups) || (!empty($modSettings['board_manager_groups']) && count(array_intersect($groups, explode(',', $modSettings['board_manager_groups']))) > 0);

			$ignoreboards = !empty($row['ignore_boards']) && !empty($modSettings['allow_ignore_boards']) ? explode(',', $row['ignore_boards']) : array();

			$user_lang = !empty($row['lngfile']) ? $row['lngfile'] : $language;
		}

		// Just build this here, it makes it easier to change/use - administrators can see all boards.
		if ($can_see_all_boards)
			$query_part['query_see_board'] = '1=1';
		// Otherwise just the groups in $user_info['groups'].
		else
		{
			$query_part['query_see_board'] = '
				EXISTS (
					SELECT bpv.id_board, bul.board_language
					FROM ' . $db_prefix . 'board_permissions_view AS bpv
						LEFT JOIN '. $db_prefix . 'boards AS bul ON bul.id_board = bpv.id_board
					WHERE bpv.id_group IN ('. implode(',', $groups) .')
						AND bpv.deny = 0
						AND bpv.id_board = b.id_board
						AND (bul.board_language = \'' . $user_lang . '\' OR bul.board_language = \'' . '' . '\')
				)';

			if (!empty($modSettings['deny_boards_access']))
				$query_part['query_see_board'] .= '
				AND NOT EXISTS (
					SELECT bpv.id_board, bul.board_language
					FROM ' . $db_prefix . 'board_permissions_view AS bpv
						LEFT JOIN ' . $db_prefix . 'boards AS bul ON bul.id_board = bpv.id_board
					WHERE bpv.id_group IN ( '. implode(',', $groups) .')
						AND bpv.deny = 1
						AND bpv.id_board = b.id_board
						AND (bul.board_language = \'' . $user_lang . '\' OR bul.board_language = \'' . '' . '\')
				)';
		}

		$query_part['query_see_message_board'] = str_replace('b.', 'm.', $query_part['query_see_board']);
		$query_part['query_see_topic_board'] = str_replace('b.', 't.', $query_part['query_see_board']);

		// Build the list of boards they WANT to see.
		// This will take the place of query_see_boards in certain spots, so it better include the boards they can see also

		// If they aren't ignoring any boards then they want to see all the boards they can see
		if (empty($ignoreboards))
		{
			$query_part['query_wanna_see_board'] = $query_part['query_see_board'];
			$query_part['query_wanna_see_message_board'] = $query_part['query_see_message_board'];
			$query_part['query_wanna_see_topic_board'] = $query_part['query_see_topic_board'];
		}
		// Ok I guess they don't want to see all the boards
		else
		{
			$query_part['query_wanna_see_board'] = '(' . $query_part['query_see_board'] . ' AND b.id_board NOT IN (' . implode(',', $ignoreboards) . '))';
			$query_part['query_wanna_see_message_board'] = '(' . $query_part['query_see_message_board'] . ' AND m.id_board NOT IN (' . implode(',', $ignoreboards) . '))';
			$query_part['query_wanna_see_topic_board'] = '(' . $query_part['query_see_topic_board'] . ' AND t.id_board NOT IN (' . implode(',', $ignoreboards) . '))';
		}

		return $query_part;
	}

	/**
	 * LanguageBoards::settings()
	 *
	 *  Add the lonely setting for this mod
	 * 
	 * @return void
	 */
	public static function settings(&$config_vars)
	{
		global $txt;

		// Load our language file
		loadLanguage('LanguageBoards/');

		// Add the setting
		$config_vars[]= '';
		$config_vars[]= ['check', 'lb_board_deny'];
	}

	/**
	 * LanguageBoards::helpadmin()
	 *
	 *  Load the help text... This should be loaded automatically :/
	 * 
	 * @return void
	 */
	public static function helpadmin()
	{
		// Load our language file
		loadLanguage('LanguageBoards/');
	}
}