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
define('CVS_REVISION', '$RCSfile$ - $Revision: 45033 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cphome', 'cpuser', 'global', 'cprofilefield');

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$log_vars = array();
if (!empty($_REQUEST['prefixsetid']))
{
	$log_vars[] = 'prefixsetid = ' . htmlspecialchars_uni($_REQUEST['prefixsetid']);
}
if (!empty($_REQUEST['prefixid']))
{
	$log_vars[] = 'prefixid = ' . htmlspecialchars_uni($_REQUEST['prefixid']);
}
log_admin_action(implode(', ', $log_vars));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################


$soap_installed = class_exists('SoapClient');

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'upload';
}

//Let's get the session if we have one.
$sessionid = $_COOKIE['vr_sessionid'];

require_once DIR . '/includes/class_encryption.php';
$key = $vbulletin->userinfo['salt'];
$decrypter = new vB_Encrypt($key);
$sessionid = $decrypter->decrypt($sessionid);

// ########################  GO TO ACCOUNT ##############################
if ($_REQUEST['do'] == 'go_login')
{
	if (!$soap_installed)
	{
		print_cp_header($vbphrase['verticalresponse']);
		print_description_row($vbphrase['no_soap_client_desc']) ;
	}
	else
	{
		require_once DIR . '/vb/verticalresponse.php';

		$vbulletin->input->clean_array_gpc('r', array(
			'userid'  => TYPE_STR,
			'password'  => TYPE_STR,
			'sessionid'  => TYPE_STR
			));

		if ($vbulletin->GPC_exists['userid'] AND $vbulletin->GPC_exists['password'])
		{
			$client = new vB_VerticalResponse();
			$sessionid = $client->login($vbulletin->GPC['userid'], $vbulletin->GPC['password']);

			if ($sessionid)
			{
				setcookie('vr_sessionid', $decrypter->encrypt($sessionid) , time() + 7200);
				printUpload($sessionid);
			}
			else
			{
				printLogin($vbphrase['vr_login_failed']);
			}
		}
		else
		{
			printLogin($vbphrase['vr_login_failed']);
		}
	}
}

// ########################  UPLOAD FILES ##############################
if ($_REQUEST['do'] == 'upload')
{
	if (!$soap_installed)
	{
		print_cp_header($vbphrase['verticalresponse']);
		echo $vbphrase['no_soap_client_desc'];
	}
	else if ($sessionid)
	{
		require_once DIR . '/vb/verticalresponse.php';

		printUpload($sessionid);
	}
	else
	{
		printLogin($vbphrase['vr_login_first_desc']);
	}
}

// ########################  UPLOAD FILES ##############################
if ($_REQUEST['do'] == 'confirm_upload')
{

	if (!$soap_installed)
	{
		print_cp_header($vbphrase['verticalresponse']);
		echo $vbphrase['no_soap_client_desc'];
	}
	else if ($sessionid)
	{
		require_once DIR . '/vb/verticalresponse.php';
		doConfirmUpload($sessionid);
	}
}

// ########################  UPLOAD FILES ##############################
if ($_REQUEST['do'] == 'do_upload')
{

	if (!$soap_installed)
	{
		print_cp_header($vbphrase['verticalresponse']);
		echo $vbphrase['no_soap_client_desc'];
	}
	else if ($sessionid)
	{
		require_once DIR . '/vb/verticalresponse.php';
		doUpload($sessionid);
	}
}



print_cp_footer();

//This confirms the settings for upload of a list of members
function doConfirmUpload($sessionid)
{
	global $vbphrase, $vbulletin;
	global $_HIDDENFIELDS;
	//first let's make sure we have a valid session and valid list
	if (!$sessionid)
	{
		return;
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'listname'  => TYPE_STR,
		'listid'  => TYPE_UINT,
		'do_percycle' => TYPE_UINT,
		'user'              => TYPE_ARRAY,
		'profile'           => TYPE_ARRAY,
		'display'           => TYPE_ARRAY_BOOL,
		'orderby'           => TYPE_STR,
		'startat'        	  => TYPE_UINT,
		'serializedprofile' => TYPE_STR,
		'serializeduser'    => TYPE_STR,
		'serializeddisplay' => TYPE_STR,
		'condition'         => TYPE_STR
		));

	$client = new vB_VerticalResponse();

	if (!$vbulletin->GPC_exists['do_percycle'] OR !intval($vbulletin->GPC['do_percycle']))
	{
		$vbulletin->GPC['do_percycle'] = 1000;
	}

	if (!$client->checkStatus($sessionid))
	{
		printLogin($vbphrase['vr_login_first_desc']);
		return;
	}

	require_once(DIR . '/includes/adminfunctions_user.php');
	require_once(DIR . '/includes/adminfunctions_profilefield.php');

	if ($vbulletin->GPC_exists['listname'] AND !empty($vbulletin->GPC['listname']))
	{
		$listid = $client->createList($sessionid, $vbulletin->GPC['listname']);
		if (!$listid)
		{
			return;
		}
	}
	else if ($vbulletin->GPC_exists['listid'])
	{
		$listid = $vbulletin->GPC['listid'];

		if (intval($vbulletin->GPC['startat']) == 0)
		{
			$client->setCustomListFields($sessionid, array('userid', 'username'));
			//if we're just starting, clear the existing records
			$client->eraseListMembers($sessionid, $listid);
		}
	}
	else
	{
		return false;
	}

	//we appear to have good data. Let's go ahead and compose the sql

	if (!empty($vbulletin->GPC['serializeduser']))
	{
		$vbulletin->GPC['user']    = @unserialize(verify_client_string($vbulletin->GPC['serializeduser']));
		$vbulletin->GPC['profile'] = @unserialize(verify_client_string($vbulletin->GPC['serializedprofile']));
	}

	if (!empty($vbulletin->GPC['serializeddisplay']))
	{
		$vbulletin->GPC['display'] = @unserialize(verify_client_string($vbulletin->GPC['serializeddisplay']));
	}

	if (@array_sum($vbulletin->GPC['display']) == 0)
	{
		$vbulletin->GPC['display'] = array('username' => 1, 'options' => 1, 'email' => 1, 'joindate' => 1, 'lastactivity' => 1, 'posts' => 1);
	}

	$condition = fetch_user_search_sql($vbulletin->GPC['user'], $vbulletin->GPC['profile']);

	if (empty($condition))
	{
		$condition = "1 = 1";
	}
	$searchquery = "
		SELECT COUNT(*) AS count
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (userfield.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (usertextfield.userid = user.userid)
		WHERE $condition"	;

	$count = $vbulletin->db->query_first($searchquery);
	if (!$count)
	{
		return false;
	}
	$count = $count['count'];

	print_cp_header($vbphrase['verticalresponse']);
	print_form_header('verticalresponse', 'do_upload', false, true, 'verticalresponse');
	print_table_header($vbphrase['upload_list'],2);
	print_description_row(construct_phrase($vbphrase['upload_count_x_desc'], $count));
	$_HIDDENFIELDS['condition'] = htmlspecialchars($condition);
	$_HIDDENFIELDS['do_percycle'] = $vbulletin->GPC['do_percycle'];
	$_HIDDENFIELDS['startat'] = 0;
	$_HIDDENFIELDS['count'] = $count;
	$_HIDDENFIELDS['listid'] = $listid;

	print_hidden_fields();
	print_submit_row($vbphrase['submit'], 0);
	print_table_footer();
}

//This does the upload of a list of members
function doUpload($sessionid)
{
	global $vbphrase, $vbulletin;
	global $_HIDDENFIELDS;
	//first let's make sure we have a valid session and valid list
	if (!$sessionid)
	{
		return;
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'listid'  => TYPE_UINT,
		'do_percycle' => TYPE_UINT,
		'startat'        	  => TYPE_UINT,
		'count'        	  => TYPE_UINT,
		'condition'         => TYPE_STR
		));

	$client = new vB_VerticalResponse();

	if (!$vbulletin->GPC_exists['do_percycle'] OR !intval($vbulletin->GPC['do_percycle']))
	{
		$vbulletin->GPC['do_percycle'] = 1000;
	}
	else
	{
		$vbulletin->GPC['do_percycle'] = min(40000, $vbulletin->GPC['do_percycle']);
	}

	if (!$client->checkStatus($sessionid))
	{
		printLogin($vbphrase['vr_login_first_desc']);
		return;
	}

	if ($vbulletin->GPC_exists['listid'] AND $vbulletin->GPC_exists['condition'])
	{
		$listid = $vbulletin->GPC['listid'];

		if (intval($vbulletin->GPC['startat']) == 0)
		{
			$client->setCustomListFields($sessionid, array('userid', 'username'));
			//if we're just starting, clear the existing records
			$client->eraseListMembers($sessionid, $listid);
		}
	}
	else
	{
		return false;
	}

	//we appear to have good data. Let's see how many records this will return

	$condition = $vbulletin->GPC['condition'];
	$searchquery = "
		SELECT
		user.userid, user.username, user.email AS email_address
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (userfield.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (usertextfield.userid = user.userid)
		WHERE $condition
		ORDER BY userid LIMIT " . intval($vbulletin->GPC['startat']) . ", " . $vbulletin->GPC['do_percycle']
	;


	$users = $vbulletin->db->query_read($searchquery);
	$members = '';
	//we've got the info, let's query and build the resultset
	if ($users)
	{
		while($user = $vbulletin->db->fetch_array($users))
		{
			$members .= $user['userid']. ',"' . str_replace('"','""' , trim($user['username'])) .
						'","' . str_replace('"','""' , trim($user['email_address'])) ."\"\n";
		}
	}

	print_cp_header($vbphrase['verticalresponse']);
	if (empty($members))
	{
		//We're done.
		echo $vbphrase['vr_upload_complete'];
		return;
	}
	else
	{
		$client->addListMembers($sessionid, $listid, $members, array('userid', 'username', 'email_address'));
	}


	print_form_header('verticalresponse', 'do_upload', false, true, 'verticalresponse');
	print_table_header($vbphrase['upload_list'],2);
	$_HIDDENFIELDS['condition'] = htmlspecialchars($condition);
	$_HIDDENFIELDS['do_percycle'] = $vbulletin->GPC['do_percycle'];
	$_HIDDENFIELDS['startat'] = intval($vbulletin->GPC['startat']) + intval($vbulletin->GPC['do_percycle']);
	$_HIDDENFIELDS['count'] = $vbulletin->GPC['count'];
	$_HIDDENFIELDS['listid'] = $listid;
	print_hidden_fields();

	//let's make a nice display:

	if ($vbulletin->GPC_exists['count'] AND (intval($vbulletin->GPC['count'])> 0))
	{
		$last = min(intval($vbulletin->GPC['startat']) + intval($vbulletin->GPC['do_percycle']), $vbulletin->GPC['count']);
		$width = intval(400 * intval($vbulletin->GPC['startat'])/intval($vbulletin->GPC['count']) );
		}
	else
	{
		echo "fred 2<br />\n";
		$last = intval($vbulletin->GPC['startat']) + intval($vbulletin->GPC['do_percycle']);
		$width = 0;
	}

	$status = construct_phrase($vbphrase['uploading_user_x_to_y_of_z'], $vbulletin->GPC['startat'],
			$last, $vbulletin->GPC['count']);
	$display = "<div style=\"width:400px;height:25px;border:2px solid;text-align:" . vB_Template_Runtime::fetchStyleVar('left') .
		";float:" . vB_Template_Runtime::fetchStyleVar('left') .
		";\" class=\"textarea\"><div class=\"button\" style=\"width:" . $width . "px;height:25px;float:" .
		vB_Template_Runtime::fetchStyleVar('left') .	"\"></div></div>";
	print_cells_row(array($status ,  $display));

	print_submit_row($vbphrase['next_page'], 0);
	print_form_auto_submit('verticalresponse');
	print_table_footer();
}



function printLogin($message = false)
{
	print_cp_header($vbphrase['verticalresponse']);
	global $vbphrase ;
	print_form_header('verticalresponse', 'go_login');
	print_table_header($vbphrase['log_in'], 2);

	if ($message)
	{
		print_description_row($message);
	}
	//We can pre-populate the email.
	print_cells_row(array($vbphrase['verticalresponse_email_desc'] , "<input id=\"$acct_field\" type=\"text\" name=\"userid\" value=\"" .
	$vbulletin->options['webmasteremail'] . "\"/>") );
	print_cells_row(array($vbphrase['password'] , "<input id=\"$acct_field\" type=\"password\" name=\"password\" />") );

	print_submit_row($vbphrase['submit']);

	print_table_footer();
}

function printUpload($sessionid)
{
	global $vbphrase ;
	print_cp_header($vbphrase['verticalresponse']);
	require_once(DIR . '/includes/adminfunctions_user.php');
	require_once(DIR . '/includes/adminfunctions_profilefield.php');
	print_form_header('verticalresponse', 'confirm_upload', false, true, 'verticalresponse');

	print_table_header($vbphrase['upload_list'],2);

	//get the current lists
	if ($sessionid)
	{
		$client = new vB_VerticalResponse();

		if (!$client->checkStatus($sessionid))
		{
			printLogin($vbphrase['vr_login_first_desc']);
			return;
		}
		$lists = $client->enumerateLists($sessionid);

	}

	if ($lists)
	{
		$current_lists = '';
		foreach ($lists as $list)
		{
			if ($list->status == 'active')
			{
				$current_lists .= "<option value=\"" . $list->id . "\">" .
					htmlspecialchars_uni($list->name) . '- ' . '- '. $list->size .
					"</option>". "\n";
			}
		}

		if (!empty($current_lists))
		{
			$current_lists = "<option value=\"\">   </option>". "\n" . $current_lists;
			print_cells_row(array($vbphrase['select_vr_list_desc'] , "<select id=\"listid\" name=\"listid\"/>$current_lists</select>") );
		}
	}
	print_cells_row(array($vbphrase['verticalresponse_list_desc'] ,  "<input id=\"listname\" type=\"text\" name=\"listname\"/ value=\"\">"));
	print_cells_row(array($vbphrase['vr_dopercycle_desc'] ,  "<input id=\"do_percycle\" type=\"text\" name=\"do_percycle\"/ value=\"1000\">"));
	print_table_break();
	print_user_search_rows(true);
	print_submit_row($vbphrase['submit']);

	print_table_footer();
}


/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 45033 $
|| ####################################################################
\*======================================================================*/
