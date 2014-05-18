<?php
/**
*
* @package phpBB3 API Core extend: index
^>@version $Id: index.php v0.0.1 13h37 03/08/2014 Geolim4 Exp $
* @copyright (c) 2012 - 2014 Geolim4.com http://geolim4.com
* @bug/function request: http://geolim4.com/tracker
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/
namespace phpbb_api;

/**
* @ignore
*/
if (!defined('IN_PHPBB') || !defined('IN_PHPBB_API'))
{
	exit;
}

foreach (glob(API_CORE_PATH . "core_*.php") AS $trait)
{
	if (is_readable($trait))
	{
		require_once($trait);
	}
}