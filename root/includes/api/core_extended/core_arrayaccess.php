<?php
/**
*
* @package phpBB3 API Core extend: Static methods
^>@version $Id: core_arrayaccess.php v0.0.2 04h40 05/25/2014 Geolim4 Exp $
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

//Implements arrayaccess methods as a trait
trait core_arrayaccess
{
	private $container = array();

	public function offsetSet($offset, $value) 
	{
		if (is_null($offset)) 
		{
			$this->container[] = $value;
		} 
		else 
		{
			$this->container[$offset] = $value;
		}
	}

	public function offsetExists($offset) 
	{
		return isset($this->container[$offset]);
	}

	public function offsetUnset($offset) 
	{
		unset($this->container[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->container[$offset]) ? $this->container[$offset] : null;
	}
}