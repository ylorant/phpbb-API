<?php
/**
*
* @package phpBB3 API Core extend: Static methods
^>@version $Id: core_sapi.php v0.0.1 13h37 03/08/2014 Geolim4 Exp $
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

/*******
*** SAPI methods
********/
trait core_sapi
{

	//Sapi vars
	protected $CLI_MODE;
	protected $CLI_ARGV;

	protected $sapi_modes = array(
		'aolserver', 'apache',
		'apache2filter', 'apache2handler',
		'caudium', /* 'cgi' */ //(until PHP 5.3)
		'cgi-fcgi', 'cli', 'cli-server',
		'continuity', 'embed', 'isapi', 
		'litespeed', 'milter', 'nsapi',
		'phttpd', 'pi3web', 'roxen',
		'thttpd', 'tux', 'webjames'
	);

	protected $sapi_cli_modes = array(
		'cli', 'cli-server'
	);

	protected $sapi_username;
	protected $cli_connected;
	protected $cli_authed;
	protected $cli_codename = "phpbb.api";
	protected $cli_authentication = false;


	protected function sapi_init($argv)
	{
		$this->sapi_load_argv($argv);
	}

	/***
	** SAPI detectors
	***/
	protected function sapi_load_argv($argv)
	{
		$this->CLI_ARGV = $argv;
		
		$this->sapi_username = (isset($argv[1]) ? $argv[1] : false);
	}

	protected function sapi_is_cli($sapi = '')
	{
		if(empty($sapi))
		{
			$sapi = strtolower(php_sapi_name());
		}
		return (in_array(strtolower($sapi), $this->sapi_cli_modes));
	}

	protected function sapi_is($sapi = '')
	{
		if(empty($sapi))
		{
			$sapi = strtolower(php_sapi_name());
		}
		return (in_array(strtolower($sapi), $this->sapi_modes));
	}

	protected function sapi_is_known($sapi = '')
	{
		if(empty($sapi))
		{
			$sapi = strtolower(php_sapi_name());
		}
		return (in_array(strtolower($sapi), $this->sapi_modes));
	}

	/***
	** SAPI core methods
	***/
	protected function sapi_invoke_from_argv(&$action, &$type, &$data)
	{
		if(!$this->CLI_MODE)
		{
			return;
		}

		$action = (isset($this->CLI_ARGV[1]) ? $this->CLI_ARGV[1] : null);
		$type = (isset($this->CLI_ARGV[2]) ? $this->CLI_ARGV[2] : null);
		$data = (isset($this->CLI_ARGV[3]) ? $this->CLI_ARGV[3] : null);

	}

	protected function sapi_confirm($title, $confirmed = null, $unconfirmed = null)
	{
		$user_response = $this->sapi_prompt($title, false);
		if(in_array($user_response, $this->user->lang['API_CLI_YES_ANSWERS']))
		{
			$user_confirmation = true;
			if(is_callable($confirmed))
			{
				$confirmed($user_response);
			}
		}
		else
		{
			$user_confirmation = false;
			if(is_callable($unconfirmed))
			{
				$unconfirmed($user_response);
			}
		}
		return $user_confirmation;
	}

	protected function sapi_prompt($title, $pasword = false)
	{
		if(function_exists('readline'))
		{
			$line = readline($title);
			readline_add_history($line);
		}
		else
		{
			if($pasword)
			{
				if (preg_match('/^win/i', PHP_OS))
				{
					print('@echo off');
				}
			}
			$this->sapi_print($title);
			$handle = fopen("php://stdin","r");
			$line = fgets($handle);
			fclose($handle);
		}
		return trim($line);
	}

	protected function sapi_open_url($url)
	{
		// Thanks to the internet http://www.dwheeler.com/essays/open-files-urls.html
		if (preg_match('/^win/i', PHP_OS))
		{
			exec('start ' . escapeshellcmd($url));
		}
		else if (preg_match('/^Linux/i', PHP_OS))
		{
			exec('xdg-open ' . escapeshellcmd($url));
		}
		else if (preg_match('/^Darwin/i', PHP_OS))
		{
			exec('open ' . escapeshellcmd($url));
		}
		else
		{
			$this->sapi_print($this->cli_lang('API_CLI_OS_SUPPORT'));
		}
	}

	protected function prompt_silent($prompt = '', $title = '') 
	{
		$this->sapi_print($prompt);
		if (preg_match('/^win/i', PHP_OS)) 
		{
			$vbscript = API_TEMP_PATH . 'prompt_password.vbs';
			file_put_contents(
				$vbscript, 'wscript.echo(InputBox("'
				. addslashes($prompt)
				. '", "'
				. addslashes($title)
				.'", ""))'
			);
			$command = "cscript //nologo " . escapeshellarg($vbscript);
			$password = rtrim(shell_exec($command));
			unlink($vbscript);
			$this->sapi_print(str_repeat('*', 10), false, true);

			return $password;
		} 
		else 
		{
			$command = "/usr/bin/env bash -c 'echo OK'";
			if (rtrim(shell_exec($command)) !== 'OK') 
			{
				trigger_error($this->cli_lang('API_CLI_CANT_INVOKE_BASH'));
				return;
			}
			$command = "/usr/bin/env bash -c 'read -s -p \""
			. addslashes($prompt)
			. "\" mypassword && echo \$mypassword'";
			$password = rtrim(shell_exec($command));
			$this->sapi_print(str_repeat('*', 10), false, true);

			print "\n";
			return $password;
		}
	}

	protected function sapi_print($str, $new_line_before = true, $newline_after = false)
	{
		if($new_line_before)
		{
			print(PHP_EOL);
		}

		print(functions\utf8_cleaning($str));
		
		if($newline_after)
		{
			print(PHP_EOL);
		}
	}
	
	protected function cli_output($array, $tab = 1, $clean_utf8_chars = true)
	{
		if($clean_utf8_chars)
		{
			$array = array_map('\phpbb_api\functions\utf8_cleaning', $array);
		}

		// Remove useless things in CLI mode
		unset($array["timing"], $array["status"], $array["errno"]);
		if(!empty($array["backtrace"]))
		{
			$this->sapi_confirm($this->cli_lang('API_CLI_SEE_BACKTRACE'),
				function() use (&$array)
				{
				},
				function() use (&$array)
				{
					unset($array['backtrace']);
				}
			);
		}

		$indenter = str_repeat("    ", $tab);
		foreach($array AS $key => $value)
		{
			if(is_array($value))
			{
				print("{$indenter}{$key}:\n");
				$this->cli_output($value, $tab + 1, $clean_utf8_chars);
				print("\n");
			}
			else
			{
				print("{$indenter}{$key}: {$value}\n");
			}
		}
	}
	
	protected function cli_get_prefix()
	{
		return "{$this->user->data['username']}@{$this->cli_codename}:" . API_VERSION . ">";
	}

	// Overload the user class
	protected function cli_lang()
	{
		$lang = call_user_func_array(array($this->user, "lang"), func_get_args());
		if(is_array($lang))
		{
			$lang = array_map('\phpbb_api\functions\utf8_cleaning', $lang);
		}
		else
		{
			$lang = functions\utf8_cleaning($lang);
		}
		return $lang;
	}

	protected function sapi_parse($command)
	{
		$command = explode(' ', $command);
		$cmd_array = array(
			'action' => isset($command[0]) ? trim($command[0]) : null,
			'type' => isset($command[1]) ? trim($command[1]) : null,
			'data' => '',
		);
		foreach($command AS $key => $value)
		{
			//Skip action && type
			if($key <= 1)
			{
				continue;
			}
			$cmd_array['data'] .= (($key > 2 ? ' ': '') . $value);
		}

		//Reset the error status
		$this->error_triggered = false;

		$this->invoke($cmd_array['action'], $cmd_array['type'], $cmd_array['data'], true);
		return true;
	}
}