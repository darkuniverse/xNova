<?php

/**
 * @project XG Proyect
 * @version 2.10.x build 0000
 * @copyright Copyright (C) 2008 - 2012
 */

if (version_compare(PHP_VERSION, "5.2.0", "<"))
	die('<!DOCTYPE html><html><head><title>¡Error!</title><meta charset="UTF-8"></head><body>¡Error! Tu servidor debe tener al menos php 5.2.0</body></html>');

// -1 permitirá guardar todos los errores
error_reporting(-1);


$user          	= array();
$lang          	= array();
$link          	= "";
$IsUserChecked 	= FALSE;

include_once(XGP_ROOT.'includes/constants.php');
include_once(XGP_ROOT.'includes/GeneralFunctions.php');
include_once(XGP_ROOT.'includes/classes/class.simple_html_dom.php');
include_once(XGP_ROOT.'includes/classes/class.debug.php');
include_once(XGP_ROOT.'includes/classes/class.xml.php');
include_once(XGP_ROOT.'includes/classes/class.Format.php');
include_once(XGP_ROOT.'includes/classes/class.NoobsProtection.php');
include_once(XGP_ROOT.'includes/classes/class.Production.php');
include_once(XGP_ROOT.'includes/classes/class.Fleets.php');

$debug 		= new debug();

if (filesize(XGP_ROOT.'config.php') == 0 && (( ! defined('INSTALL')) OR ( ! INSTALL)))
{
	exit(header('location:'.XGP_ROOT.'install/'));
}

if (filesize(XGP_ROOT.'config.php') != 0)
{
	$game_version	=	read_config('version');

	define('VERSION', ($game_version == '') ? "" : "v" . $game_version );
}

if ( ! defined('INSTALL') OR ( ! INSTALL))
{
	include(XGP_ROOT.'includes/vars.php');
	include(XGP_ROOT.'includes/functions/CreateOneMoonRecord.php');
	include(XGP_ROOT.'includes/functions/CreateOnePlanetRecord.php');
	include(XGP_ROOT.'includes/functions/SendSimpleMessage.php');
	include(XGP_ROOT.'includes/functions/calculateAttack.php');
	include(XGP_ROOT.'includes/functions/formatCR.php');
	include(XGP_ROOT.'includes/functions/GetBuildingTime.php');
	include(XGP_ROOT.'includes/functions/HandleElementBuildingQueue.php');
	include(XGP_ROOT.'includes/functions/PlanetResourceUpdate.php');

	$game_lang	=	read_config('lang');

	define('DEFAULT_LANG', ($game_lang  == '') ? "spanish" : $game_lang);

	includeLang('INGAME');

	include(XGP_ROOT.'includes/classes/class.CheckSession.php');

	$Result        	= new CheckSession();
	$Result			= $Result->CheckUser($IsUserChecked);
	$IsUserChecked 	= $Result['state'];

	if (isset($InLogin) && $InLogin && $IsUserChecked)
	{
		header('Location: game.php?page=overview');
	}
	elseif (( ! isset($InLogin) OR ( ! $InLogin)) && ( ! $IsUserChecked))
	{
		header('Location: '.XGP_ROOT);
	}
	$user          	= $Result['record'];

	if (read_config('game_disable') == 0 && $user['authlevel'] == 0)
	{
		message(stripslashes(read_config('close_reason')), '', '', FALSE, FALSE);
	}

	if ((time() >= (read_config('stat_last_update') + (60 * read_config ( 'stat_update_time' )))))
	{
		include(XGP_ROOT.'adm/statfunctions.php');
		$result	= MakeStats();
		update_config('stat_last_update', $result['stats_time']);
	}

	if ( ! empty($user))
	{
		include( XGP_ROOT.'includes/classes/class.FlyingFleetHandler.php');
		$_fleets = doquery("SELECT fleet_start_galaxy,fleet_start_system,fleet_start_planet,fleet_start_type FROM {{table}} WHERE `fleet_start_time` <= '".time()."' and `fleet_mess` ='0' order by fleet_id asc;", 'fleets'); // OR fleet_end_time <= ".time()

		while ($row = mysql_fetch_array($_fleets))
		{
			$array 					= array();
			$array['galaxy'] 		= $row['fleet_start_galaxy'];
			$array['system'] 		= $row['fleet_start_system'];
			$array['planet'] 		= $row['fleet_start_planet'];
			$array['planet_type'] 	= $row['fleet_start_type'];

			$temp = new FlyingFleetHandler($array);
		}

		mysql_free_result($_fleets);

		$_fleets = doquery("SELECT fleet_end_galaxy,fleet_end_system,fleet_end_planet ,fleet_end_type FROM {{table}} WHERE `fleet_end_time` <= '" . time() . " order by fleet_id asc';", 'fleets'); // OR fleet_end_time <= ".time()

		while ($row = mysql_fetch_array($_fleets))
		{
			$array 					= array();
			$array['galaxy'] 		= $row['fleet_end_galaxy'];
			$array['system'] 		= $row['fleet_end_system'];
			$array['planet'] 		= $row['fleet_end_planet'];
			$array['planet_type'] 	= $row['fleet_end_type'];

			$temp = new FlyingFleetHandler($array);
		}

		mysql_free_result($_fleets);
		unset($_fleets);

		if (defined('IN_ADMIN'))
		{
			includeLang('ADMIN');
			include(XGP_ROOT.'adm/AdminFunctions/Autorization.php');

			define('DPATH', "../".DEFAULT_SKINPATH);
		}
		else
		{
			define('DPATH', (( ! isset($user["dpath"]) OR (empty($user["dpath"]))) ? DEFAULT_SKINPATH : SKIN_PATH . $user["dpath"] . '/'));
		}

		if (isset($user['current_planet']))
		{
			include(XGP_ROOT.'includes/functions/SetSelectedPlanet.php');
			SetSelectedPlanet($user);

			$planetrow = doquery("SELECT * FROM `{{table}}` WHERE `id` = '".$user['current_planet']."';", "planets", TRUE);
		}
		// Include the plugin system 0.3
		include(XGP_ROOT.'includes/plugins.php');
	}
}
else
{
	define('DPATH' , "../".DEFAULT_SKINPATH);
}

include('includes/classes/class.SecurePage.php'); // include the class
$SecureSqlInjection	= new SecureSqlInjection(); // load the class
$SecureSqlInjection->secureGlobals(); // run the main class function


/* End of file global.php */
/* Location: ./global.php */