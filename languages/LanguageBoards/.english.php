<?php

/**
 * @package Language Boards
 * @version 1.0.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2021, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

 global $helptxt;

// Board settings
$txt['LanguageBoards_setting'] = 'Language for the board';
$txt['LanguageBoards_setting_desc'] = 'Board will only be available if it matches the user\'s forum language';

// Mod settings
$txt['lb_board_deny'] = 'Deny access to the board and topics depending on the language';
$txt['lb_board_deny_desc'] = 'By default, users can still access to the boards and topics if they know the URL or through their posts and the recent posts page, even if the board language does not match the user language. Enabling this option will deny access to those boards and topics too.';
$helptxt['lb_board_deny'] = $txt['lb_board_deny_desc'];