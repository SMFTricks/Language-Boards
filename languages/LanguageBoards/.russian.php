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
$txt['LanguageBoards_setting'] = 'Язык раздела';
$txt['LanguageBoards_setting_desc'] = 'Раздел будет доступен только пользователям, использующим выбранный язык';

// Mod settings
$txt['lb_board_deny'] = 'Ограничить доступ к разделам и темам в зависимости от языка пользователя';
$txt['lb_board_deny_desc'] = 'По умолчанию пользователи имеют доступ к разделам и темам, если они знают URL-адрес раздела или имеют сообщения в разделе, даже если язык раздела не соответствует языку пользователя. Включение этой опции ограничит доступ к таким разделам и темам.';
$helptxt['lb_board_deny'] = $txt['lb_board_deny_desc'];