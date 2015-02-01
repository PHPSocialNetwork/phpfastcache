<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 4.2.1 - Licence Number VBF02D260D
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 36270 $');
define('VB_API_CHAIN', true);

// Set to 1 to build correct SESSIONIDHASH
$_REQUEST['api'] = 1;

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_apiclient.php');

/*
$calls = array(
	'methods' => array(
		'login_login', 'api_init'
	),
	'login_login' => array(
		'POST' => array(
			'vb_login_username' => 'admin',
			'vb_login_password' => 'password',
		),
	),
	'api_init' => array(
		'sessionhash' => '{session.dbsessionhash}'
	)
);
*/

$vbulletin->input->clean_array_gpc('r', array(
	'calls' => TYPE_STR,
	'sig'   => TYPE_STR,
	'debug' => TYPE_BOOL
));

if (!$vbulletin->GPC['calls'])
{
	header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
	die();
}

$calls = @json_decode($vbulletin->GPC['calls'], true);

if (!$calls)
{
	header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
	die();
}

// Validate signature
$signtoverify = md5($vbulletin->GPC['calls'] . $vbulletin->session->vars['dbsessionhash'] . $vbulletin->userinfo['securitytoken_raw']);
if ($vbulletin->GPC['sig'] !== $signtoverify AND !($vbulletin->debug AND $vbulletin->GPC['debug']))
{
	$error = array(
		'response' => array(
			'errormessage' => array('invalid_apichain_signature')
		)
	);

	echo json_encode($error);
	die;
}


$api = new vB_APIClient($vbulletin);
$api->setSessionhash($vbulletin->session->vars['dbsessionhash']);
$api->setSecuritytoken($vbulletin->userinfo['securitytoken_raw']);

$data = array();

foreach ($calls['methods'] as $method)
{
	if (!$calls[$method]['POST'])
	{
		$calls[$method]['POST'] = array();
	}
	if (!$calls[$method]['GET'])
	{
		$calls[$method]['GET'] = array();
	}

	if ($data)
	{
		foreach ($calls[$method]['POST'] as &$v)
		{
			api_chain_parse($v, $data);
		}
		foreach ($calls[$method]['GET'] as &$v)
		{
			api_chain_parse($v, $data);
		}
		foreach ($calls[$method] as &$v)
		{
			if (!is_array($v))
			{
				api_chain_parse($v, $data);
			}
		}
	}

	if ($calls[$method]['sessionhash'])
	{
		$api->setSessionhash($calls[$method]['sessionhash']);
	}
	if ($calls[$method]['securitytoken'])
	{
		$api->setSecuritytoken($calls[$method]['securitytoken']);
	}

	$data = $api->call($method, $calls[$method]['GET'], $calls[$method]['POST'], 1);
}

echo json_encode($data);
exit;

function api_chain_parse(&$value, &$data)
{
	if (preg_match('/\{([a-z0-9.]+)\}/', $value, $match))
	{
		$value = $match[1];

		$datacopy = $data;
		foreach (explode('.', $value) as $part)
		{
			$datacopy = $datacopy[$part];
		}

		$value = $datacopy;
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 35584 $
|| ####################################################################
\*======================================================================*/
