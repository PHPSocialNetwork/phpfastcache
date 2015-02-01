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
define('CVS_REVISION', '$RCSfile$ - $Revision: 48073 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('subscription', 'cpuser', 'stats');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_paid_subscription.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'userid'         => TYPE_INT,
	'subscriptionid' => TYPE_INT,
));

// ############################# LOG ACTION ###############################
log_admin_action(!empty($vbulletin->GPC['userid']) ? "user id = " . $vbulletin->GPC['userid'] : !empty($vbulletin->GPC['subscriptionid']) ? "subscriptionid id = " . $vbulletin->GPC['subscriptionid'] : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['subscription_manager']);
$subobj = new vB_PaidSubscription($vbulletin);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start Add #######################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{

	$OUTERTABLEWIDTH = '95%';
	$INNERTABLEWIDTH = '100%';
	?>
	<script type="text/javascript">
	function doRemove(str)
	{
		for (var i =0; i < document.forms.cpform.elements.length; i++)
		{
			var elm = document.forms.cpform.elements[i];
			if (elm.name.substring(0, str.length) == str)
			{
				switch (elm.type)
				{
					case 'text':
						elm.value = 0;
					break;
					case 'select-one':
						elm.selectedIndex = 0;
					break;
				}
			}
		}
		return false;
	}
	</script>
	<?php
	print_form_header('subscriptions', 'update', 0, 0);
	?>
	<table cellpadding="0" cellspacing="0" border="0" width="<?php echo $OUTERTABLEWIDTH; ?>" align="center"><tr valign="top"><td>
	<table cellpadding="4" cellspacing="0" border="0" align="center" width="100%" class="tborder">
	<?php

	if ($_REQUEST['do'] == 'add')
	{
		print_table_header($vbphrase['add_new_subscription']);
		$sub['active'] = true;
		$sub['displayorder'] = 1;
	}
	else
	{
		$sub = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "subscription
			WHERE subscriptionid = " . $vbulletin->GPC['subscriptionid'] . "
		");

		$sub['cost'] = unserialize($sub['cost']);
		$sub = array_merge($sub, convert_bits_to_array($sub['options'], $subobj->_SUBSCRIPTIONOPTIONS));
		$sub = array_merge($sub, convert_bits_to_array($sub['adminoptions'], $vbulletin->bf_misc_adminoptions));
		$title = 'sub' . $sub['subscriptionid'] . '_title';
		$desc = 'sub' . $sub['subscriptionid'] . '_desc';

		$phrases = $db->query_read("
			SELECT varname, text
			FROM " . TABLE_PREFIX . "phrase
			WHERE languageid = 0 AND
					fieldname = 'subscription' AND
					varname IN ('$title', '$desc')
		");
		while ($phrase = $db->fetch_array($phrases))
		{
			if ($phrase['varname'] == $title)
			{
				$sub['title'] = $phrase['text'];
				$sub['titlevarname'] = 'sub' . $sub['subscriptionid'] . '_title';
			}
			else if ($phrase['varname'] == $desc)
			{
				$sub['description'] = $phrase['text'];
				$sub['descvarname'] = 'sub' . $sub['subscriptionid'] . '_desc';
			}
		}

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['subscription'], htmlspecialchars_uni($sub['title']), $sub['subscriptionid']));
		construct_hidden_code('subscriptionid', $sub['subscriptionid']);
	}

	if ($sub['title'])
	{
		print_input_row($vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=subscription&varname=$sub[titlevarname]&t=1", 1)  . '</dfn>', 'title', $sub['title']);
	}
	else
	{
		print_input_row($vbphrase['title'], 'title');
	}
	if ($sub['description'])
	{
		print_textarea_row($vbphrase['description'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=subscription&varname=$sub[descvarname]&t=1", 1)  . '</dfn>', 'description', $sub['description']);
	}
	else
	{
		print_textarea_row($vbphrase['description'], 'description');
	}
	print_yes_no_row($vbphrase['active'], 'sub[active]', $sub['active']);
	print_input_row($vbphrase['display_order'], 'sub[displayorder]', $sub['displayorder'], true, 5);

	print_table_header($vbphrase['paypal_only']);
	print_yes_no_row($vbphrase['tax'], 'options[tax]', $sub['tax']);
	print_select_row($vbphrase['shipping_address'], 'shipping', array(0 => $vbphrase['none'], 2 => $vbphrase['optional'], 4 => $vbphrase['required']), ($sub['options'] & $subobj->_SUBSCRIPTIONOPTIONS['shipping1']) + ($sub['options'] & $subobj->_SUBSCRIPTIONOPTIONS['shipping2']));

	print_table_break('', '100%');
	print_table_header($vbphrase['admin_override_options']);
	foreach ($vbulletin->bf_misc_adminoptions AS $field => $value)
	{
		print_yes_no_row($vbphrase['keep_' . $field], 'adminoptions[' . $field . ']', $sub["$field"]);
	}


	?>
	</table>
	</td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
	<table cellpadding="4" cellspacing="0" border="0" align="center" width="100%" class="tborder">
	<?php
	// USERGROUP SECTION
	print_table_header($vbphrase['usergroup_options']);
	print_chooser_row($vbphrase['primary_usergroup'], 'sub[nusergroupid]', 'usergroup', $sub['nusergroupid'], $vbphrase['no_change']);
	print_membergroup_row($vbphrase['additional_usergroups'], 'membergroup', 0, $sub);
	?>
	</table>
	</tr>
	<?php

	print_table_break('', $OUTERTABLEWIDTH);
	print_table_header($vbphrase['forums']);
	print_description_row($vbphrase['here_you_can_select_which_forums_the_user']);

	//require_once(DIR . '/includes/functions_databuild.php');
	//cache_forums();
	if ($old_sub_masks = @unserialize($sub['forums']) AND is_array($old_sub_masks))
	{
		$forums = array_keys($old_sub_masks);
	}
	else
	{
		$forums = explode(',', $sub['forums']);
	}

	if (is_array($vbulletin->forumcache))
	{
		foreach ($vbulletin->forumcache AS $forumid => $forum)
		{
			if (array_search($forum['forumid'], $forums) !== false)
			{
				$sel = 1;
			}
			else
			{
				$sel = -1;
			}
			$radioname = 'forums[' . $forum['forumid'] . ']';
			print_label_row(construct_depth_mark($forum['depth'], '- - ') . ' ' . $forum['title'], "<span class=\"smallfont\"><strong>
				<label for=\"rb_1_$radioname\"><input type=\"radio\" name=\"$radioname\" value=\"1\" id=\"rb_1_$radioname\" tabindex=\"1\"" . iif($sel==1, ' checked="checked"') . " />" . $vbphrase['yes'] . "</label>
				<label for=\"rb_0_$radioname\"><input type=\"radio\" name=\"$radioname\" value=\"-1\" for=\"rb_0_$radioname\" tabindex=\"1\"" . iif($sel==-1, ' checked="checked"') . " />" . $vbphrase['default'] . "</label>
			</strong></span>
			");
		}
	}
	print_table_break('', $OUTERTABLEWIDTH);
	print_table_header($vbphrase['cost'], 10);

	print_cells_row(array(
		$vbphrase['us_dollars'],
		$vbphrase['pounds_sterling'],
		$vbphrase['euros'],
		$vbphrase['aus_dollars'],
		$vbphrase['cad_dollars'],
		$vbphrase['subscription_length'],
		$vbphrase['recurring'],
		$vbphrase['ccbill_subid'],
		$vbphrase['twocheckout_prodid'],
		$vbphrase['options']
	), 1);
	$direction = verify_text_direction('');
	$sub['cost'][] = array();
	foreach ($sub['cost'] AS $i => $sub_occurence)
	{
		$usd = '<input type="text" class="bginput" name="sub[time][' . $i . '][cost][usd]" dir="' . $direction . '" tabindex="1" size="7" value="' . number_format($sub_occurence['cost']['usd'], 2, '.', '') . '" />';
		$gbp = '<input type="text" class="bginput" name="sub[time][' . $i . '][cost][gbp]" dir="' . $direction . '" tabindex="1" size="7" value="' . number_format($sub_occurence['cost']['gbp'], 2, '.', '') . '" />';
		$eur = '<input type="text" class="bginput" name="sub[time][' . $i . '][cost][eur]" dir="' . $direction . '" tabindex="1" size="7" value="' . number_format($sub_occurence['cost']['eur'], 2, '.', '') . '" />';
		$aud = '<input type="text" class="bginput" name="sub[time][' . $i . '][cost][aud]" dir="' . $direction . '" tabindex="1" size="7" value="' . number_format($sub_occurence['cost']['aud'], 2, '.', '') . '" />';
		$cad = '<input type="text" class="bginput" name="sub[time][' . $i . '][cost][cad]" dir="' . $direction . '" tabindex="1" size="7" value="' . number_format($sub_occurence['cost']['cad'], 2, '.', '') . '" />';
		$length = '<input type="text" class="bginput" name="sub[time][' . $i . '][length]" dir="' . $direction . '" tabindex="1" size="7" value="' . $sub_occurence['length'] . '" />';
		$length .= '<select name="sub[time][' . $i . '][units]" tabindex="1" class="bginput">' .
		construct_select_options(array('D' => $vbphrase['days'], 'W' => $vbphrase['weeks'], 'M' => $vbphrase['months'], 'Y' => $vbphrase['years']), $sub_occurence['units']) .
		"</select>\n";
		$recurring = '<input type="checkbox" name="sub[time][' . $i . '][recurring]" value="1" tabindex="1"' . ($sub_occurence['recurring'] ? ' checked="checked"' : '') . ' />';
		$ccbill = '<input type="text" class="bginput" name="sub[time][' . $i . '][ccbillsubid]" dir="' . $direction . '" tabindex="1" size="7" value="' . $sub_occurence['ccbillsubid'] . '" />';
		$twocheckout = '<input type="text" class="bginput" name="sub[time][' . $i . '][twocheckout_prodid]" dir="' . $direction . '" tabindex="1" size="7" value="' . $sub_occurence['twocheckout_prodid'] . '" />';
		$options = '<a href="#" onclick="return doRemove(\'sub[time][' . $i . ']\');">' . $vbphrase['delete'] . '</a>';
		print_cells_row(array($usd, $gbp, $eur, $aud, $cad, $length, $recurring, $ccbill, $twocheckout, $options));
	}
	$tableadded = 1;
	print_submit_row(iif($_REQUEST['do'] == 'add', $vbphrase['save'], $vbphrase['update']), '_default_', 10);

}

// ###################### Start Update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'sub'          => TYPE_ARRAY,
		'forums'       => TYPE_ARRAY_BOOL,
		'membergroup'  => TYPE_ARRAY_UINT,
		'options'      => TYPE_ARRAY_UINT,
		'adminoptions' => TYPE_ARRAY_UINT,
		'shipping'     => TYPE_UINT,
		'title'        => TYPE_STR,
		'description'  => TYPE_STR,
	));

	if ($vbulletin->GPC['shipping'] == 2)
	{
		$vbulletin->GPC['options']['shipping1'] = 1;
	}
	else if ($vbulletin->GPC['shipping'] == 4)
	{
		$vbulletin->GPC['options']['shipping2'] = 1;
	}

	require_once(DIR . '/includes/functions_misc.php');
	$vbulletin->GPC['sub']['options'] = convert_array_to_bits($vbulletin->GPC['options'], $subobj->_SUBSCRIPTIONOPTIONS);
	$vbulletin->GPC['sub']['adminoptions'] = convert_array_to_bits($vbulletin->GPC['adminoptions'], $vbulletin->bf_misc_adminoptions);

	$sub =& $vbulletin->GPC['sub'];

	$sub['active'] = intval($sub['active']);
	$sub['displayorder'] = intval($sub['displayorder']);

	$clean_times = array();
	$lengths = array('D' => 'days', 'W' => 'weeks', 'M' => 'months', 'Y' => 'years');

	$counter = 0;
	if (is_array($vbulletin->GPC['sub']['time']))
	{
		foreach ($vbulletin->GPC['sub']['time'] AS $key => $moo)
		{
			$havecurrency = false;
			$counter++;
			$moo['length'] = intval($moo['length']);
			foreach ($moo['cost'] AS $currency => $value)
			{
				if ($value != '0.00')
				{
					$havecurrency = true;
				}
				$moo['cost']["$currency"] = number_format($value, 2, '.', '');
			}
			if ($moo['length'] == 0)
			{
				if ($havecurrency)
				{
					print_stop_message('enter_subscription_length_for_subscription_x', $counter);
				}
				continue;
			}
			else if (!$havecurrency)
			{
				print_stop_message('enter_cost_information_for_subscription_x', $counter);
			}

			if (strtotime("now + $moo[length] " . $lengths["$moo[units]"]) <= 0 OR $moo['length'] <= 0)
			{
				print_stop_message('invalid_subscription_length');
			}
			$moo['recurring'] = intval($moo['recurring']);
			$moo['ccbillsubid'] = intval($moo['ccbillsubid']) ? intval($moo['ccbillsubid']) : '';
			$clean_times[$key] = $moo;
		}
		unset($vbulletin->GPC['sub']['time']);
	}
	else
	{
		print_stop_message('variables_missing_suhosin');
	}
	$sub['cost'] = serialize($clean_times);

	$aforums = array();
	if (is_array($vbulletin->GPC['forums']))
	{
		foreach ($vbulletin->GPC['forums'] AS $key => $value)
		{
			if ($value == 1)
			{
				$aforums[] = intval($key);
			}
		}
	}
	else
	{
		print_stop_message('variables_missing_suhosin');
	}

	$sub['membergroupids'] = '';
	if (!empty($vbulletin->GPC['membergroup']))
	{
		$sub['membergroupids'] = implode(',', $vbulletin->GPC['membergroup']);
	}
	$sub['forums'] = implode(',', $aforums);

	if (empty($clean_times))
	{
		$sub['active'] = 0;
	}

	if (empty($vbulletin->GPC['title']))
	{
		print_stop_message('please_complete_required_fields');
	}
	if (in_array($sub['nusergroupid'], $vbulletin->GPC['membergroup']))
	{
		print_stop_message('primary_equals_secondary');
	}

	if (empty($vbulletin->GPC['subscriptionid']))
	{
		$db->query_write(fetch_query_sql($sub, 'subscription'));
		$vbulletin->GPC['subscriptionid'] = $db->insert_id();
		$insert_default_deny_perms = true;
	}
	else
	{
		$db->query_write(fetch_query_sql($sub, 'subscription', "WHERE subscriptionid=" . $vbulletin->GPC['subscriptionid']));
		$insert_default_deny_perms = false;
	}

	if ($insert_default_deny_perms)
	{
		// by default, deny buy permission to users awaiting moderation or email confirmation
		$db->query_write($q="
			REPLACE INTO " . TABLE_PREFIX . "subscriptionpermission
			(usergroupid, subscriptionid)
			VALUES
			(3, " . $vbulletin->GPC['subscriptionid'] . "),
			(4, " . $vbulletin->GPC['subscriptionid'] . ")
		");
	}

	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "phrase
			(languageid, fieldname, varname, text, product, username, dateline, version)
		VALUES
			(
				0,
				'subscription',
				'sub" . $db->escape_string($vbulletin->GPC['subscriptionid']) . "_title',
				'" . $db->escape_string($vbulletin->GPC['title']) .  "',
				'vbulletin',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($vbulletin->options['templateversion']) . "'
			),
			(
				0,
				'subscription',
				'sub" . $db->escape_string($vbulletin->GPC['subscriptionid']) . "_desc',
				'" . $db->escape_string($vbulletin->GPC['description']) . "',
				'vbulletin',
				'" . $db->escape_string($vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'" . $db->escape_string($vbulletin->options['templateversion']) . "'
			)
	");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	toggle_subs();

	define('CP_REDIRECT', 'subscriptions.php?do=modify');
	print_stop_message('saved_subscription_x_successfully', htmlspecialchars_uni($vbulletin->GPC['title']));

}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'remove')
{
	print_delete_confirmation('subscription', $vbulletin->GPC['subscriptionid'],
		'subscriptions', 'kill', 'subscription', 0,
		$vbphrase['doing_this_will_remove_additional_access_subscription'],
		'subscriptionid'
	);
}

// ###################### Start Kill #######################
if ($_POST['do'] == 'kill')
{
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phrase
		WHERE fieldname = 'subscription' AND
				varname IN ('sub" . $vbulletin->GPC['subscriptionid'] . "_title', 'sub" . $vbulletin->GPC['subscriptionid'] . "_desc')
	");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	$users = $db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "subscriptionlog
		WHERE subscriptionid = " . $vbulletin->GPC['subscriptionid'] . " AND
		status = 1
	");
	while ($user = $db->fetch_array($users))
	{
		$subobj->delete_user_subscription($vbulletin->GPC['subscriptionid'], $user['userid']);
	}

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "subscription WHERE subscriptionid = " . $vbulletin->GPC['subscriptionid']);
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "subscriptionlog WHERE subscriptionid = " . $vbulletin->GPC['subscriptionid']);

	toggle_subs();

	define('CP_REDIRECT', 'subscriptions.php?do=modify');
	print_stop_message('deleted_subscription_successfully');

}

// ###################### Start find #######################
if ($_REQUEST['do'] == 'find')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'status'      => TYPE_INT,
		'orderby'     => TYPE_NOHTML,
		'limitstart'  => TYPE_INT,
		'limitnumber' => TYPE_INT,
	));

	$condition = '1=1';
	$condition .= iif($vbulletin->GPC['subscriptionid'], " AND subscriptionid=" . $vbulletin->GPC['subscriptionid']);
	$condition .= ($vbulletin->GPC['status'] > -1) ? ' AND status = ' . $vbulletin->GPC['status'] : '';

	switch($vbulletin->GPC['orderby'])
	{
		case 'subscriptionid':
			$orderby = 'subscriptionid, username';
			break;
		case 'startdate':
			$orderby = 'regdate';
			break;
		case 'enddate':
			$orderby = 'expirydate';
			break;
		case 'status':
			$orderby = 'status, username';
			break;
		case 'username':
		default:
			$vbulletin->GPC['orderby'] = 'username';
			$orderby = 'username';
	}

	if (empty($vbulletin->GPC['limitstart']))
	{
		$vbulletin->GPC['limitstart'] = 0;
	}
	else
	{
		$vbulletin->GPC['limitstart']--;
	}

	if (empty($vbulletin->GPC['limitnumber']) OR $vbulletin->GPC['limitnumber'] == 0)
	{
		$vbulletin->GPC['limitnumber'] = 25;
	}

	$searchquery = "
		SELECT *
		FROM " . TABLE_PREFIX . "subscriptionlog AS subscriptionlog
		LEFT JOIN " . TABLE_PREFIX . "user AS user USING (userid)
		WHERE $condition
			AND user.userid = subscriptionlog.userid
		ORDER BY $orderby
		LIMIT " . $vbulletin->GPC['limitstart'] . ", " . $vbulletin->GPC['limitnumber'] . "
	";

	$countusers = $db->query_first("
		SELECT COUNT(*) AS users
		FROM " . TABLE_PREFIX . "subscriptionlog AS subscriptionlog
		LEFT JOIN " . TABLE_PREFIX . "user AS user USING (userid)
		WHERE $condition
			AND user.userid = subscriptionlog.userid
	");

	$users = $db->query_read($searchquery);

	if (!$countusers['users'])
	{
		print_stop_message('no_matches_found');
	}
	else
	{
		$limitfinish = $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'];

		$subs = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "subscription ORDER BY subscriptionid");
		while ($sub = $db->fetch_array($subs))
		{
			$subcache["{$sub['subscriptionid']}"] = htmlspecialchars_uni($vbphrase['sub' . $sub['subscriptionid'] . '_title']);
		}
		$db->free_result($subs);

		print_form_header('subscriptions', 'find');
		print_table_header(
			construct_phrase(
				$vbphrase['showing_subscriptions_x_to_y_of_z'],
				($vbulletin->GPC['limitstart'] + 1),
				iif($limitfinish > $countusers['users'], $countusers['users'], $limitfinish),
				$countusers[users]
				), 6);

		$addon  = "&amp;subscriptionid=" . $vbulletin->GPC['subscriptionid'];
		$addon .= "&amp;status=" . $vbulletin->GPC['status'];
		$addon .= "&amp;limitnumber=" . $vbulletin->GPC['limitnumber'];
		$addon .= "&amp;limitstart=" . $vbulletin->GPC['limitstart'];

		$headings = array();

		if ($vbulletin->GPC['orderby'] == 'subscriptionid')
		{
			$headings[] = $vbphrase['title'];
		}
		else
		{
			$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=find&amp;orderby=subscriptionid" . $addon . "\" title=\"" . $vbphrase['order_by_title'] . "\">" . $vbphrase['title'] . "</a>";
		}
		if ($vbulletin->GPC['orderby'] == 'username')
		{
			$headings[] = $vbphrase['username'];
		}
		else
		{
			$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=find&amp;orderby=username" . $addon . "\" title=\"" . $vbphrase['order_by_username'] . "\">" . $vbphrase['username'] . "</a>";
		}
		if ($vbulletin->GPC['orderby'] == 'startdate')
		{
			$headings[] = $vbphrase['start_date'];
		}
		else
		{
			$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=find&amp;orderby=startdate" . $addon . "\" title=\"" . $vbphrase['order_by_start_date'] . "\">" . $vbphrase['start_date'] . "</a>";
		}
		if ($vbulletin->GPC['orderby'] == 'enddate')
		{
			$headings[] = $vbphrase['end_date'];
		}
		else
		{
			$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=find&amp;orderby=enddate" . $addon . "\" title=\"" . $vbphrase['order_by_end_date'] . "\">" . $vbphrase['end_date'] . "</a>";
		}
		if ($vbulletin->GPC['orderby'] == 'status')
		{
			$headings[] = $vbphrase['status'];
		}
		else
		{
			$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=find&amp;orderby=status" . $addon . "\" title=\"" . $vbphrase['order_by_status'] . "\">" . $vbphrase['status'] . "</a>";
		}
		$headings[] = $vbphrase['controls'];

		print_cells_row($headings, 1);
		// now display the results
		while ($user = $db->fetch_array($users))
		{
			$cell = array();
			$cell[] = $subcache["{$user['subscriptionid']}"];
			$cell[] = "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=$user[userid]\"><b>$user[username]</b></a>&nbsp;";
			$cell[] = vbdate($vbulletin->options['dateformat'], $user['regdate']);
			$cell[] = vbdate($vbulletin->options['dateformat'], $user['expirydate']);
			$cell[] = iif($user['status'], $vbphrase['active'], $vbphrase['disabled']);
			$cell[] = construct_button_code($vbphrase['edit'], "subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=adjust&subscriptionlogid=$user[subscriptionlogid]");
			print_cells_row($cell);
		}

		construct_hidden_code('subscriptionid', $vbulletin->GPC['subscriptionid']);
		construct_hidden_code('status', $vbulletin->GPC['status']);
		construct_hidden_code('limitnumber', $vbulletin->GPC['limitnumber']);
		construct_hidden_code('orderby', $vbulletin->GPC['orderby']);

		if ($vbulletin->GPC['limitstart'] == 0 AND $countusers['users'] > $vbulletin->GPC['limitnumber'])
		{
			construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
			print_submit_row($vbphrase['next_page'], 0, 6);
		}
		else if ($limitfinish < $countusers['users'])
		{
			construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
			print_submit_row($vbphrase['next_page'], 0, 6, $vbphrase['prev_page'], '', true);
		}
		else if ($vbulletin->GPC['limitstart'] > 0 AND $limitfinish >= $countusers['users'])
		{
			print_submit_row($vbphrase['first_page'], 0, 6, $vbphrase['prev_page'], '', true);
		}
		else
		{
			print_table_footer();
		}

	}
}

// ###################### Start status #######################
if ($_POST['do'] == 'status')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'subscriptionlogid' => TYPE_INT,
		'status'            => TYPE_INT,
		'regdate'           => TYPE_ARRAY_INT,
		'expirydate'        => TYPE_ARRAY_INT,
		'username'          => TYPE_NOHTML,
	));

	require_once(DIR . '/includes/functions_misc.php');
	$regdate = vbmktime($vbulletin->GPC['regdate']['hour'], $vbulletin->GPC['regdate']['minute'], 0, $vbulletin->GPC['regdate']['month'], $vbulletin->GPC['regdate']['day'], $vbulletin->GPC['regdate']['year']);
	$expirydate = vbmktime($vbulletin->GPC['expirydate']['hour'], $vbulletin->GPC['expirydate']['minute'], 0, $vbulletin->GPC['expirydate']['month'], $vbulletin->GPC['expirydate']['day'], $vbulletin->GPC['expirydate']['year']);

	if ($expirydate < 0 OR $expirydate <= $regdate)
	{
		print_stop_message('invalid_subscription_length');
	}
	if ($vbulletin->GPC['userid'])
	{ // already existing entry
		if (!$vbulletin->GPC['status'])
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "subscriptionlog
				SET regdate = $regdate, expirydate = $expirydate
				WHERE userid = " . $vbulletin->GPC['userid'] . "
					AND subscriptionid = " . $vbulletin->GPC['subscriptionid'] . "
			");
			$subobj->delete_user_subscription($vbulletin->GPC['subscriptionid'], $vbulletin->GPC['userid']);
		}
		else
		{
			$subobj->build_user_subscription($vbulletin->GPC['subscriptionid'], -1, $vbulletin->GPC['userid'], $regdate, $expirydate, false);
		}
	}
	else
	{
		$userinfo = $db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "user
			WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'
		");

		if (!$userinfo['userid'])
		{
			print_stop_message('no_users_matched_your_query');
		}

		$subobj->build_user_subscription($vbulletin->GPC['subscriptionid'], -1, $userinfo['userid'], $regdate, $expirydate, false);

	}

	define('CP_REDIRECT', "subscriptions.php?do=find&status=1&subscriptionid=" . $vbulletin->GPC['subscriptionid']);
	print_stop_message('saved_subscription_x_successfully', htmlspecialchars_uni($vbphrase['sub' . $vbulletin->GPC['subscriptionid'] . '_title']));
}

// ###################### Start status #######################
if ($_REQUEST['do'] == 'adjust')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'subscriptionlogid' => TYPE_INT
	));

	print_form_header('subscriptions', 'status');


	$subobj->cache_user_subscriptions();
	if (empty($subobj->subscriptioncache))
	{
		print_stop_message('nosubscriptions', $vbulletin->options['bbtitle']);
	}

	$sublist = array();
	foreach ($subobj->subscriptioncache AS $key => $subscription)
	{
		if (empty($vbulletin->GPC['subscriptionid']) AND empty($sublist))
		{
			$vbulletin->GPC['subscriptionid'] = $subscription['subscriptionid'];
		}
		$sublist["$subscription[subscriptionid]"] = htmlspecialchars_uni($vbphrase['sub' . $subscription['subscriptionid'] . '_title']);
	}

	if ($vbulletin->GPC['subscriptionlogid'])
	{ // already exists
		$sub = $db->query_first("
			SELECT subscriptionlog.*, username FROM " . TABLE_PREFIX . "subscriptionlog AS subscriptionlog
			LEFT JOIN " . TABLE_PREFIX . "user USING(userid)
			WHERE subscriptionlogid = " . $vbulletin->GPC['subscriptionlogid'] . "
		");
		print_table_header(construct_phrase($vbphrase['edit_subscription_for_x'], $sub['username']));
		construct_hidden_code('userid', $sub['userid']);
		$vbulletin->GPC['subscriptionid'] = $sub['subscriptionid'];
		print_select_row($vbphrase['subscription'], 'subscriptionid', $sublist, $vbulletin->GPC['subscriptionid']);
	}
	else
	{
		print_table_header($vbphrase['add_user']);
		$subinfo = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "subscription WHERE subscriptionid = " . $vbulletin->GPC['subscriptionid']);

		$cost_length = unserialize($subinfo['cost']);

		reset($cost_length);
		$first_sub = current($cost_length);
		if (!empty($first_sub['units']))
		{
			$expiry = $subobj->fetch_proper_expirydate(TIMENOW, $first_sub['length'], $first_sub['units']);
		}
		else
		{
			$expiry = TIMENOW + 60;
		}

		$sub = array(
			'regdate'    => TIMENOW,
			'status'     => 1,
			'expirydate' => $expiry
		);
		print_select_row($vbphrase['subscription'], 'subscriptionid', $sublist, $vbulletin->GPC['subscriptionid']);
		if ($vbulletin->GPC['userid'])
		{
			$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
			if (!$userinfo)
			{
				print_stop_message('invalid_user_specified');
			}
		}
		else
		{
			$userinfo = array('username' => '');
		}
		print_input_row($vbphrase['username'], 'username', $userinfo['username'], false);
	}

	print_time_row($vbphrase['start_date'], 'regdate', $sub['regdate']);
	print_time_row($vbphrase['expiry_date'], 'expirydate', $sub['expirydate']);
	print_radio_row($vbphrase['active'], 'status', array(
		0 => $vbphrase['no'],
		1 => $vbphrase['yes']
	), $sub['status'], 'smallfont');
	print_submit_row();
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{

	$options = array(
		'edit' => $vbphrase['edit'],
		'remove' => $vbphrase['delete'],
		'view' => $vbphrase['view_users'],
		'addu' => $vbphrase['add_user']
	);

	?>
	<script type="text/javascript">
	function js_forum_jump(sid)
	{
		var action = eval("document.cpform.s" + sid + ".options[document.cpform.s" + sid + ".selectedIndex].value");
		if (action != '')
		{
			switch (action)
			{
				case 'edit': page = "subscriptions.php?do=edit&subscriptionid="; break;
				case 'remove': page = "subscriptions.php?do=remove&subscriptionid="; break;
				case 'view': page = "subscriptions.php?do=find&status=1&subscriptionid="; break;
				case 'addu': page = "subscriptions.php?do=adjust&subscriptionid="; break;
			}
			document.cpform.reset();
			jumptopage = page + sid + "&s=<?php echo $vbulletin->session->vars['sessionhash']; ?>";
			window.location = jumptopage;
		}
		else
		{
			alert('<?php echo addslashes_js($vbphrase['invalid_action_specified']); ?>');
		}
	}
	</script>
	<?php

	print_form_header('subscriptions', 'doorder');
	print_table_header($vbphrase['subscription_manager'], 6);
	print_cells_row(array($vbphrase['title'], $vbphrase['active'], $vbphrase['completed'], $vbphrase['total'], $vbphrase['display_order'], $vbphrase['controls']), 1, 'tcat', 1);
	$totals = $db->query_read("SELECT COUNT(*) as total, subscriptionid FROM " . TABLE_PREFIX . "subscriptionlog GROUP BY subscriptionid");
	while ($total = $db->fetch_array($totals))
	{
		$t_cache["{$total['subscriptionid']}"] = $total['total'];
	}
	unset($total);
	$db->free_result($totals);

	$totals = $db->query_read("SELECT COUNT(*) as total, subscriptionid FROM " . TABLE_PREFIX . "subscriptionlog WHERE status = 1 GROUP BY subscriptionid");
	while ($total = $db->fetch_array($totals))
	{
		$ta_cache["{$total['subscriptionid']}"] = $total['total'];
	}

	$subobj->cache_user_subscriptions();
	if (is_array($subobj->subscriptioncache))
	{
		foreach ($subobj->subscriptioncache AS $key => $subscription)
		{
			$cells = array();

			$subscription['title'] = htmlspecialchars_uni($vbphrase['sub' . $subscription['subscriptionid'] . '_title']);
			if (!$subscription['active'])
			{
				$cells[] = "<em>$subscription[title]</em>";
			}
			else
			{
				$cells[] = "<strong>$subscription[title]</strong>";
			}

			// active
			$cells[] = iif(!$ta_cache["{$subscription['subscriptionid']}"], 0, "<a href=\"subscriptions.php?do=find&amp;subscriptionid=$subscription[subscriptionid]&amp;status=1\"><span style=\"color: green;\">" . $ta_cache["{$subscription['subscriptionid']}"] . "</span></a>");
			// completed
			$completed = intval($t_cache["{$subscription['subscriptionid']}"] - $ta_cache["{$subscription['subscriptionid']}"]);
			$cells[] = iif(!$completed, 0, "<a href=\"subscriptions.php?do=find&amp;subscriptionid=$subscription[subscriptionid]&amp;status=0\"><span style=\"color: red;\">" . $completed . "</span></a>");
			// total
			$cells[] = iif(!$t_cache["{$subscription['subscriptionid']}"], 0, "<a href=\"subscriptions.php?do=find&amp;subscriptionid=$subscription[subscriptionid]&amp;status=-1\">" . $t_cache["{$subscription['subscriptionid']}"] . "</a>");
			// display order
			$cells[] = "<input type=\"text\" class=\"bginput\" name=\"order[$subscription[subscriptionid]]\" value=\"$subscription[displayorder]\" tabindex=\"1\" size=\"3\" title=\"" . $vbphrase['edit_display_order'] . "\" />";
			// controls
			$cells[] = "\n\t<select name=\"s$subscription[subscriptionid]\" onchange=\"js_forum_jump($subscription[subscriptionid]);\" class=\"bginput\">\n" . construct_select_options($options) . "\t</select>\n\t<input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_forum_jump($subscription[subscriptionid]);\" />\n\t";
			print_cells_row($cells, 0, '', 1);
		}
	}
	print_table_footer(6, "<input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['save_display_order'] . "\" accesskey=\"s\" />" . construct_button_code($vbphrase['add_new_subscription'], "subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=add"));

}

// ###################### Start do order #######################
if ($_POST['do'] == 'doorder')
{
	$vbulletin->input->clean_array_gpc('p', array('order' => TYPE_ARRAY));

	if (is_array($vbulletin->GPC['order']))
	{
		$subobj->cache_user_subscriptions();
		if (is_array($subobj->subscriptioncache))
		{
			$casesql = '';
			$subscriptionids = '';
			foreach($subobj->subscriptioncache AS $sub)
			{
				if (!isset($vbulletin->GPC['order']["$sub[subscriptionid]"]))
				{
					continue;
				}

				$displayorder = intval($vbulletin->GPC['order']["$sub[subscriptionid]"]);
				if ($sub['displayorder'] != $displayorder)
				{
					$casesql .= "WHEN subscriptionid = $sub[subscriptionid] THEN $displayorder\n";
					$subscriptionids .= ",$sub[subscriptionid]";
				}
			}

			if (!empty($casesql))
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "subscription
					SET displayorder =
						CASE
							$casesql
							ELSE 1
						END
					WHERE subscriptionid IN (-1$subscriptionids)
				");
			}
		}
	}

	define('CP_REDIRECT', 'subscriptions.php?do=modify');
	print_stop_message('saved_display_order_successfully');
}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'apirem')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'paymentapiid' => TYPE_INT
	));
	print_delete_confirmation('paymentapi', $vbulletin->GPC['paymentapiid'], 'subscriptions', 'apikill', 'paymentapi');
}

// ###################### Start Kill #######################
if ($_POST['do'] == 'apikill')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'paymentapiid' => TYPE_INT
	));

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "paymentapi WHERE paymentapiid = " . $vbulletin->GPC['paymentapiid']);

	toggle_subs();

	define('CP_REDIRECT', 'subscriptions.php?do=api');
	print_stop_message('deleted_paymentapi_successfully');

}

// ###################### Start Api Edit #######################
if ($_REQUEST['do'] == 'apiedit' OR $_REQUEST['do'] == 'apiadd')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'paymentapiid' => TYPE_INT
	));

	print_form_header('subscriptions', 'apiupdate');
	if ($_REQUEST['do'] == 'apiadd')
	{
		print_table_header($vbphrase['add_new_paymentapi']);
	}
	else
	{
		$api = $db->query_first("
			SELECT * FROM " . TABLE_PREFIX . "paymentapi
			WHERE paymentapiid = " . $vbulletin->GPC['paymentapiid'] . "
		");
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['paymentapi'], $api['title'], $api['paymentapiid']));
		construct_hidden_code('paymentapiid', $api['paymentapiid']);
	}

	print_input_row($vbphrase['title'], 'api[title]', $api['title']);
	print_radio_row($vbphrase['active'], 'api[active]', array(
		0 => $vbphrase['no'],
		1 => $vbphrase['yes']
	), $api['active'], 'smallfont');
	if ($vbulletin->debug)
	{
		print_input_row($vbphrase['classname'], 'api[classname]', $api['classname']);
		print_input_row($vbphrase['supported_currency'], 'api[currency]', $api['currency']);
		print_radio_row($vbphrase['supports_recurring'], 'api[recurring]', array(
			0 => $vbphrase['no'],
			1 => $vbphrase['yes']
		), $api['recurring'], 'smallfont');
	}
	else
	{
		print_label_row($vbphrase['classname'], $api['classname']);
		print_label_row($vbphrase['supported_currency'], $api['currency']);
		print_label_row($vbphrase['supports_recurring'], ($api['recurring'] ? $vbphrase['yes'] : $vbphrase['no']));
	}

	if ($_REQUEST['do'] == 'apiedit')
	{
		$settings = unserialize($api['settings']);
		if (is_array($settings))
		{
			// $info is an array
			foreach ($settings AS $key => $info)
			{
				print_description_row(
					'<div>' . $vbphrase["setting_{$api[classname]}_{$key}_title"] . "</div>",
					0, 2, "optiontitle\""
				);
				$name = "settings[$key]";
				$description = "<div class=\"smallfont\">" . $vbphrase["setting_{$api[classname]}_{$key}_desc"] . '</div>';
				switch ($info['type'])
				{
					case 'yesno':
					print_yes_no_row($description, $name, $info['value']);
					break;

					default:
					print_input_row($description, $name, $info['value'], 1, 40);
					break;
				}
			}
		}
	}

	print_submit_row(iif($_REQUEST['do'] == 'apiadd', $vbphrase['save'], $vbphrase['update']));
}

// ###################### Start Update #######################
if ($_POST['do'] == 'apiupdate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'api'			=> TYPE_ARRAY,
		'settings'		=> TYPE_ARRAY,
		'paymentapiid'	=> TYPE_UINT,
	));

	$api =& $vbulletin->GPC['api'];

	if (!empty($vbulletin->GPC['paymentapiid']) AND !empty($vbulletin->GPC['settings']))
	{
		$currentinfo = $db->query_first("SELECT settings FROM " . TABLE_PREFIX . "paymentapi WHERE paymentapiid = " . $vbulletin->GPC['paymentapiid']);
		$settings = unserialize($currentinfo['settings']);
		$updatesettings = false;

		foreach ($vbulletin->GPC['settings'] AS $key => $value)
		{
			if (isset($settings["$key"]) AND $settings["$key"]['value'] != $value)
			{
				switch ($settings["$key"]['validate'])
				{
					case 'number':
						$value += 0;
						break;
					case 'boolean':
						$value = $value ? 1 : 0;
						break;
					case 'string':
						$value = trim($value);
						break;
				}
				$settings["$key"]['value'] = $value;
				$updatesettings = true;
			}
		}
		if ($updatesettings)
		{
			$api['settings'] = serialize($settings);
		}
	}

	$api['title'] = htmlspecialchars_uni($api['title']);
	$api['active'] = intval($api['active']);

	if (isset($api['classname']))
	{
		$api['classname'] = preg_replace('#[^a-z0-9_]#i', '', $api['classname']);
		if (empty($api['classname']))
		{
			print_stop_message('please_complete_required_fields');
		}
	}

	if (isset($api['currency']))
	{
		if (empty($api['currency']))
		{
			print_stop_message('please_complete_required_fields');
		}
	}

	if (isset($api['recurring']))
	{
		$api['recurring'] = intval($api['recurring']);
	}

	if (empty($api['title']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if (empty($vbulletin->GPC['paymentapiid']))
	{
		/*insert query*/
		$db->query_write(fetch_query_sql($api, 'paymentapi'));
	}
	else
	{
		$db->query_write(fetch_query_sql($api, 'paymentapi', "WHERE paymentapiid=" . $vbulletin->GPC['paymentapiid']));
	}

	toggle_subs();

	define('CP_REDIRECT', 'subscriptions.php?do=api');
	print_stop_message('saved_paymentapi_x_successfully', $api['title']);

}

// ###################### Start api #######################
if ($_REQUEST['do'] == 'api')
{

	$options = array(
		'edit' => $vbphrase['edit']
	);

	if ($vbulletin->debug)
	{
		$options['remove'] = $vbphrase['delete'];
	}

	?>
	<script type="text/javascript">
	function js_forum_jump(pid)
	{
		var action = eval("document.cpform.p" + pid + ".options[document.cpform.p" + pid + ".selectedIndex].value");
		if (action != '')
		{
			switch (action)
			{
				case 'edit': page = "subscriptions.php?do=apiedit&paymentapiid="; break;
				case 'remove': page = "subscriptions.php?do=apirem&paymentapiid="; break;
			}
			document.cpform.reset();
			jumptopage = page + pid + "&s=<?php echo $vbulletin->session->vars['sessionhash']; ?>";
			window.location = jumptopage;
		}
		else
		{
			alert('<?php echo addslashes_js($vbphrase['invalid_action_specified']); ?>');
		}
	}
	</script>
	<?php
	print_form_header('subscriptions');
	// PHRASE ME
	print_table_header($vbphrase['payment_api_manager'], 3);
	print_cells_row(array($vbphrase['title'], $vbphrase['active'], $vbphrase['controls']), 1, 'tcat', 1);
	$apis = $db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "paymentapi
	");

	while ($api = $db->fetch_array($apis))
	{
		$cells = array();
		$cells[] = $api['title'];
		if ($api['active'])
		{
			$yesno = 'yes';
		}
		else
		{
			$yesno = 'no';
		}

		$cells[] = "<img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/cp_tick_$yesno.gif\" alt=\"\" />";
		$cells[] = "\n\t<select name=\"p$api[paymentapiid]\" onchange=\"js_forum_jump($api[paymentapiid]);\" class=\"bginput\">\n" . construct_select_options($options) . "\t</select>\n\t<input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_forum_jump($api[paymentapiid]);\" />\n\t";
		print_cells_row($cells, 0, '', 1);
	}

	print_table_footer(3);
}

// ###################### Start find #######################
if ($_REQUEST['do'] == 'transdetails')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'paymenttransactionid' => TYPE_UINT,
	));

	if (!($payment = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "paymenttransaction WHERE paymenttransactionid = " . $vbulletin->GPC['paymenttransactionid'])))
	{
		print_stop_message('no_matches_found');
	}

	$request = unserialize($payment['request']);
	if (empty($request['GET']) AND empty($request['POST']))
	{
		print_stop_message('no_matches_found');
	}
	else
	{
		print_form_header('', '');

		print_table_header($vbphrase['transaction_details']);
		print_table_break();
		if (!empty($request['vb_error_code']))
		{
			print_table_header('API');
			print_label_row('vb_error_code', htmlspecialchars_uni($request['vb_error_code']));
		}
		if ($get = unserialize($request['GET']))
		{
			print_table_header('GET');
			foreach($get AS $key => $value)
			{
				print_label_row(htmlspecialchars_uni($key), htmlspecialchars_uni($value));
			}
		}
		if ($post = unserialize($request['POST']))
		{
			print_table_header('POST');
			foreach($post AS $key => $value)
			{
				print_label_row(htmlspecialchars_uni($key), htmlspecialchars_uni($value));
			}
		}
		print_table_footer();
	}
}

// ###################### Start find #######################
if ($_REQUEST['do'] == 'transactions')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'state'          => TYPE_INT,
		'orderby'        => TYPE_NOHTML,
		'limitstart'     => TYPE_INT,
		'limitnumber'    => TYPE_INT,
		'paymentapiid'   => TYPE_UINT,
		'transactionid'  => TYPE_STR,
		'currency'       => TYPE_NOHTML,
		'exact'          => TYPE_BOOL,
		'start'          => TYPE_ARRAY_UINT,
		'end'            => TYPE_ARRAY_UINT,
		'type'           => TYPE_NOHTML,
		'scope'          => TYPE_NOHTML,
		'subscriptionid' => TYPE_UINT,
		'userid'         => TYPE_UINT,
		'username'       => TYPE_NOHTML
	));

	$userinfo = array();
	if ($vbulletin->GPC['username'])
	{
		if (!($userinfo = $db->query_first("SELECT username, userid FROM " . TABLE_PREFIX . "user WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'")))
		{
			print_stop_message('invalid_user_specified');
		}
	}
	else if ($vbulletin->GPC['userid'])
	{
		if (!($userinfo = $db->query_first("SELECT username, userid FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC['userid'] . "")))
		{
			print_stop_message('invalid_user_specified');
		}
	}

	if (empty($vbulletin->GPC['start']) AND !$vbulletin->GPC['transactionid'])
	{
		$vbulletin->GPC['start'] = TIMENOW - 3600 * 24 * 365;
	}

	if (empty($vbulletin->GPC['end']))
	{
		$vbulletin->GPC['end'] = TIMENOW;
	}

	if (empty($vbulletin->GPC['limitstart']))
	{
		$vbulletin->GPC['limitstart'] = 0;
	}
	else
	{
		$vbulletin->GPC['limitstart']--;
	}

	if (empty($vbulletin->GPC['limitnumber']) OR $vbulletin->GPC['limitnumber'] == 0)
	{
		$vbulletin->GPC['limitnumber'] = 25;
	}

	$subobj->cache_user_subscriptions();
	$sublist = array('' => $vbphrase['all_subscriptions']);
	foreach ($subobj->subscriptioncache AS $key => $subscription)
	{
		if (empty($vbulletin->GPC['subscriptionid']) AND empty($sublist))
		{
			$vbulletin->GPC['subscriptionid'] = $subscription['subscriptionid'];
		}
		$sublist["$subscription[subscriptionid]"] = htmlspecialchars_uni($vbphrase['sub' . $subscription['subscriptionid'] . '_title']);
	}

	$apicache = array(0 => $vbphrase['all_processors']);
	// get the settings for all the API stuff
	$paymentapis = $db->query_read("
		SELECT paymentapiid, title
		FROM " . TABLE_PREFIX . "paymentapi
		ORDER BY title
	");
	while ($paymentapi = $db->fetch_array($paymentapis))
	{
		$apicache["$paymentapi[paymentapiid]"] = $paymentapi['title'];
	}

	if (!$vbulletin->GPC['scope'])
	{
		$vbulletin->GPC['state'] = -1;
	}

	if ($vbulletin->GPC['type'] == 'stats')
	{
		switch ($vbulletin->GPC['orderby'])
		{
			case 'date_asc':
				$orderby = 'dateline ASC';
				break;
			case 'total_asc':
				$orderby = 'total ASC';
				break;
			case 'total_desc':
				$orderby = 'total DESC';
				break;
			default:
				$orderby = 'dateline DESC';
				$vbulletin->GPC['orderby'] = 'date_desc';
		}

		print_form_header('subscriptions', 'transactions');

		print_table_header($vbphrase['transaction_stats']);
		construct_hidden_code('type', 'stats');
		print_time_row($vbphrase['start_date'], 'start', $vbulletin->GPC['start'], false);
		print_time_row($vbphrase['end_date'], 'end', $vbulletin->GPC['end'], false);
		if (!empty($subobj->subscriptioncache))
		{
			print_select_row($vbphrase['subscription'], 'subscriptionid', $sublist, $vbulletin->GPC['subscriptionid']);
		}
		print_select_row($vbphrase['processor'], 'paymentapiid', $apicache, $vbulletin->GPC['paymentapiid']);
		print_select_row($vbphrase['currency'], 'currency', array(
			''    => $vbphrase['all_currency'],
			'usd' => $vbphrase['us_dollars'],
			'gbp' => $vbphrase['pounds_sterling'],
			'eur' => $vbphrase['euros'],
			'aud' => $vbphrase['aus_dollars'],
			'cad' => $vbphrase['cad_dollars'],
		), $vbulletin->GPC['currency']);
		print_select_row($vbphrase['type'], 'state', array(
			'-1'   => $vbphrase['all_types'],
			'0' => $vbphrase['failure'],
			'1'  => $vbphrase['charge'],
			'2'  => $vbphrase['reversal'],
		), $vbulletin->GPC['state']);
		print_select_row($vbphrase['scope'], 'scope', array('daily' => $vbphrase['daily'], 'weekly' => $vbphrase['weekly'], 'monthly' => $vbphrase['monthly']), $vbulletin->GPC['scope']);
		print_select_row($vbphrase['order_by'], 'orderby', array(
			'date_asc'   => $vbphrase['date_ascending'],
			'date_desc'  => $vbphrase['date_descending'],
			'total_asc'  => $vbphrase['total_ascending'],
			'total_desc' => $vbphrase['total_descending'],
		), $vbulletin->GPC['orderby']);
		print_submit_row($vbphrase['go']);
	}

	if ($vbulletin->GPC['type'] == 'log')
	{
		switch($vbulletin->GPC['orderby'])
		{
			case 'amount':
				$orderby = 'amount';
				break;
			case 'transactionid':
				$orderby = 'transactionid';
				break;
			case 'username':
				$orderby = 'username';
				break;
			case 'paymentapiid':
				$orderby = 'paymenttransaction.paymentapiid';
				break;
			case 'dateline':
			default:
				$vbulletin->GPC['orderby'] = 'dateline';
				$orderby = 'dateline';
		}

		if (!$vbulletin->GPC['transactionid'])
		{
			print_form_header('subscriptions', 'transactions');
			print_table_header($vbphrase['transaction_log']);

			construct_hidden_code('type', 'log');
			construct_hidden_code('scope', 1);
			print_time_row($vbphrase['start_date'], 'start', $vbulletin->GPC['start'], false);
			print_time_row($vbphrase['end_date'], 'end', $vbulletin->GPC['end'], false);
			if (!empty($subobj->subscriptioncache))
			{
				print_select_row($vbphrase['subscription'], 'subscriptionid', $sublist, $vbulletin->GPC['subscriptionid']);
			}
			print_select_row($vbphrase['processor'], 'paymentapiid', $apicache, $vbulletin->GPC['paymentapiid']);
			print_select_row($vbphrase['currency'], 'currency', array(
				''    => $vbphrase['all_currency'],
				'usd' => $vbphrase['us_dollars'],
				'gbp' => $vbphrase['pounds_sterling'],
				'eur' => $vbphrase['euros'],
				'aud' => $vbphrase['aus_dollars'],
				'cad' => $vbphrase['cad_dollars'],
			), $vbulletin->GPC['currency']);
			print_select_row($vbphrase['type'], 'state', array(
				'-1'   => $vbphrase['all_types'],
				'0' => $vbphrase['failure'],
				'1'  => $vbphrase['charge'],
				'2'  => $vbphrase['reversal'],
			), $vbulletin->GPC['state']);
			print_input_row($vbphrase['username'], 'username', $userinfo['username'], false);
			print_select_row($vbphrase['order_by'], 'orderby', array(
				'dateline'       => $vbphrase['date'],
				'amount'         => $vbphrase['amount'],
				'transactionid'  => $vbphrase['transactionid'],
				'username'       => $vbphrase['username'],
				'paymentapiid'   => $vbphrase['processor'],
			), $vbulletin->GPC['orderby']);
			print_submit_row($vbphrase['go']);
		}

		if ($vbulletin->GPC['transactionid'] OR !$vbulletin->GPC['scope'])
		{
  			print_form_header('subscriptions', 'transactions');
  			construct_hidden_code('type', 'log');
  			construct_hidden_code('scope', 1);
  			print_table_header($vbphrase['transaction_lookup']);
  			print_input_row($vbphrase['transactionid'], 'transactionid', $vbulletin->GPC['transactionid']);
  			print_yes_no_row($vbphrase['exact_match'], 'exact', empty($vbulletin->GPC['transactionid']) ? true : $vbulletin->GPC['exact']);
  			print_submit_row($vbphrase['go']);
  		}
	}

	$condition = array();
	if (!$vbulletin->GPC['transactionid'])
	{
		$start_time = mktime(0, 0, 0, $vbulletin->GPC['start']['month'], $vbulletin->GPC['start']['day'], $vbulletin->GPC['start']['year']);
		$end_time = mktime(23, 59, 59, $vbulletin->GPC['end']['month'], $vbulletin->GPC['end']['day'], $vbulletin->GPC['end']['year']);
		if ($start_time > 0)
		{
			$condition[] = "dateline >= $start_time";
		}
		if ($end_time > 0)
		{
			$condition[] = "dateline <= $end_time";
		}
		$condition[] = $vbulletin->GPC['paymentapiid'] ? "paymenttransaction.paymentapiid = " . $vbulletin->GPC['paymentapiid'] : "1=1";
		$condition[] = $vbulletin->GPC['currency'] ? "paymenttransaction.currency = '" . $db->escape_string($vbulletin->GPC['currency']) . "'" : "1=1";
		$condition[] = $vbulletin->GPC['subscriptionid'] ? "paymentinfo.subscriptionid = " . $vbulletin->GPC['subscriptionid'] : "1=1";
		$condition[] = $userinfo['userid'] ? "paymentinfo.userid = " . $userinfo['userid'] : "1=1";
		if ($vbulletin->GPC['state'] >= 0)
		{
			$condition[] = "paymenttransaction.state = " . $vbulletin->GPC['state'];
		}
	}
	else
	{
		if ($vbulletin->GPC['exact'])
		{
			$condition[] = " transactionid = '" . $db->escape_string($vbulletin->GPC['transactionid']) . "'";
		}
		else
		{
			$condition[] = " transactionid LIKE '%" . $db->escape_string($vbulletin->GPC['transactionid']) . "%'";
		}
	}

	if ($vbulletin->GPC['type'] == 'stats')
	{
		if ($vbulletin->GPC['scope'])
		{
			require_once(DIR . '/includes/adminfunctions_stats.php');
			switch ($vbulletin->GPC['scope'])
			{
				case 'weekly':
					$sqlformat = '%U %Y';
					$phpformat = '# (! Y)';
					break;
				case 'monthly':
					$sqlformat = '%m %Y';
					$phpformat = '! Y';
					break;
				case 'daily':
					$sqlformat = '%w %U %m %Y';
					$phpformat = '! d, Y';
					break;
				default:
			}

			$statistics = $db->query_read("
				SELECT COUNT(*) AS total,
				DATE_FORMAT(from_unixtime(dateline), '$sqlformat') AS formatted_date,
				MAX(dateline) AS dateline
				FROM " . TABLE_PREFIX . "paymenttransaction AS paymenttransaction
				LEFT JOIN " . TABLE_PREFIX . "paymentinfo AS paymentinfo ON (paymenttransaction.paymentinfoid = paymentinfo.paymentinfoid)
				WHERE	" . implode(" AND ", $condition) . "
				GROUP BY formatted_date
				ORDER BY $orderby
			");

			$results = array();
			while ($stats = $db->fetch_array($statistics))
			{
				$month = strtolower(date('F', $stats['dateline']));
				$dates[] = str_replace(' ', '&nbsp;', str_replace('#', $vbphrase['week'] . '&nbsp;' . strftime('%U', $stats['dateline']), str_replace('!', $vbphrase["$month"], date($phpformat, $stats['dateline']))));
				$results[] = $stats['total'];
			}

			if (!sizeof($results))
			{
				//print_array($results);
				print_stop_message('no_matches_found');
			}

			// we'll need a poll image
			$style = $db->query_first("
				SELECT stylevars FROM " . TABLE_PREFIX . "style
				WHERE styleid = " . $vbulletin->options['styleid'] . "
				LIMIT 1
			");
			$vbulletin->stylevars = unserialize($style['newstylevars']);
			fetch_stylevars($style, $vbulletin->userinfo);

			print_form_header('');
			print_table_header($vbphrase['results'], 3);
			print_cells_row(array($vbphrase['date'], '&nbsp;', $vbphrase['total']), 1);
			$maxvalue = max($results);
			foreach ($results as $key => $value)
			{
				$i++;
				$bar = ($i % 6) + 1;
				if ($maxvalue == 0)
				{
					$percentage = 100;
				}
				else
				{
					$percentage = ceil(($value/$maxvalue) * 100);
				}
				print_statistic_result($dates["$key"], $bar, $value, $percentage);
			}
			print_table_footer(3);
		}
	}
	else
	{
		if ($vbulletin->GPC['scope'])
		{
			$searchquery = "
				SELECT paymenttransaction.*,
					paymentinfo.subscriptionid, paymentinfo.userid,
					paymentapi.title,
					user.username
				FROM " . TABLE_PREFIX . "paymenttransaction AS paymenttransaction
				LEFT JOIN " . TABLE_PREFIX . "paymentinfo AS paymentinfo ON (paymenttransaction.paymentinfoid = paymentinfo.paymentinfoid)
				LEFT JOIN " . TABLE_PREFIX . "paymentapi AS paymentapi ON (paymenttransaction.paymentapiid = paymentapi.paymentapiid)
				LEFT JOIN " . TABLE_PREFIX . "user AS user ON (paymentinfo.userid = user.userid)
				WHERE	" . implode(" AND ", $condition) . "
				ORDER BY $orderby
				LIMIT " . $vbulletin->GPC['limitstart'] . ", " . $vbulletin->GPC['limitnumber'] . "
			";

			$counttrans = $db->query_first("
				SELECT COUNT(*) AS trans
				FROM " . TABLE_PREFIX . "paymenttransaction AS paymenttransaction
				LEFT JOIN " . TABLE_PREFIX . "paymentinfo AS paymentinfo ON (paymenttransaction.paymentinfoid = paymentinfo.paymentinfoid)
				WHERE " . implode(" AND ", $condition) . "
			");

			$trans = $db->query_read($searchquery);

			if (!$counttrans['trans'])
			{
				print_stop_message('no_matches_found');
			}
			else
			{
				$limitfinish = $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'];

				print_form_header('subscriptions', 'transactions');
				print_table_header(
					construct_phrase(
						$vbphrase['showing_transactions_x_to_y_of_z'],
						($vbulletin->GPC['limitstart'] + 1),
						iif($limitfinish > $counttrans['trans'], $counttrans['trans'], $limitfinish),
						$counttrans[trans]
						), 7);

				$addon = '&amp;limitnumber=' . $vbulletin->GPC['limitnumber'];
				$addon .= $limitstart ? '&amp;limitstart=' . $vbulletin->GPC['limitstart'] : '';
				$addon .= '&amp;start[month]=' .  $vbulletin->GPC['start']['month'];
				$addon .= '&amp;start[day]=' . $vbulletin->GPC['start']['day'];
				$addon .= '&amp;start[year]=' . $vbulletin->GPC['start']['year'];
				$addon .= '&amp;end[month]=' . $vbulletin->GPC['end']['month'];
				$addon .= '&amp;end[day]=' . $vbulletin->GPC['end']['day'];
				$addon .= '&amp;end[year]=' . $vbulletin->GPC['end']['year'];
				$addon .= '&amp;scope=1';
				$addon .= $vbulletin->GPC['transactionid'] ? '&amp;transactionid=' . urlencode($vbulletin->GPC['transactionid']) : '';
				$addon .= $vbulletin->GPC['paymentapiid'] ? '&amp;paymentapiid=' . $vbulletin->GPC['paymentapiid'] : '';
				$addon .= $vbulletin->GPC['type'] ? '&amp;type=' . $vbulletin->GPC['type'] : '';
				$addon .= $vbulletin->GPC['currency'] ? '&amp;currency=' . $vbulletin->GPC['currency'] : '';
				$addon .= $vbulletin->GPC['subscriptionid'] ? '&amp;subscriptionid=' . $vbulletin->GPC['subscriptionid'] : '';
				$addon .= $vbulletin->GPC['state'] >= 0 ? '&amp;state=' . $vbulletin->GPC['state'] : '';
				$addon .= $userinfo['userid'] ? '&amp;userid=' . $userinfo['userid'] : '';

				$headings = array();
				#API
				if ($vbulletin->GPC['orderby'] == 'paymentapiid')
				{
					$headings[] = $vbphrase['processor'];
				}
				else
				{
					$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=transactions&amp;orderby=paymentapiid" . $addon . "\" title=\"" . $vbphrase['order_by_api'] . "\">" . $vbphrase['processor'] . "</a>";
				}
				#Date
				if ($vbulletin->GPC['orderby'] == 'dateline')
				{
					$headings[] = $vbphrase['date'];
				}
				else
				{
					$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=transactions&amp;orderby=dateline" . $addon . "\" title=\"" . $vbphrase['order_by_date'] . "\">" . $vbphrase['date'] . "</a>";
				}
				#Transactionid
				if ($vbulletin->GPC['orderby'] == 'transactionid')
				{
					$headings[] = $vbphrase['transactionid'];
				}
				else
				{
					$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=transactions&amp;orderby=transactionid" . $addon . "\" title=\"" . $vbphrase['order_by_transactionid'] . "\">" . $vbphrase['transactionid'] . "</a>";
				}
				#Amount
				if ($vbulletin->GPC['orderby'] == 'amount')
				{
					$headings[] = $vbphrase['amount'];
				}
				else
				{
					$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=transactions&amp;orderby=amount" . $addon . "\" title=\"" . $vbphrase['order_by_amount'] . "\">" . $vbphrase['amount'] . "</a>";
				}
				#Username
				if ($vbulletin->GPC['orderby'] == 'username')
				{
					$headings[] = $vbphrase['username'];
				}
				else
				{
					$headings[] = "<a href=\"subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=transactions&amp;orderby=username" . $addon . "\" title=\"" . $vbphrase['order_by_username'] . "\">" . $vbphrase['username'] . "</a>";
				}
				$headings[] = $vbphrase['subscription'];
				$headings[] = $vbphrase['type'];

				print_cells_row($headings, 1);
				// now display the results
				while ($tran = $db->fetch_array($trans))
				{
					$cell = array();
					$cell[] = $tran['title'] ? $tran['title'] : '-';
					$cell[] = vbdate($vbulletin->options['logdateformat'], $tran['dateline']);
					$cell[] = $tran['transactionid'] ? htmlspecialchars_uni($tran['transactionid']) : '-';
					$cell[] = $tran['state'] ? htmlspecialchars_uni(vb_number_format($tran['amount'], 2) . ' ' . strtoupper($tran['currency'])) : '-';
					$cell[] = $tran['username'] ? "<a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&u=$tran[userid]\"><b>$tran[username]</b></a>&nbsp;" : '-';
					$cell[] = $tran['subscriptionid'] ? $vbphrase['sub' . $tran['subscriptionid'] . '_title'] : '-';
					if ($tran['state'] == 0)
					{
						$cell[] = construct_link_code($vbphrase['failure'], "subscriptions.php?do=transdetails&amp;paymenttransactionid=$tran[paymenttransactionid]" . $vbulletin->session->vars['sessionurl'] . "do=edit");
					}
					else if ($tran['state'] == 1)
					{
						$cell[] = $vbphrase['charge'];
					}
					else if ($tran['state'] == 2)
					{
						$cell[] = $vbphrase['reversal'];
					}
					else
					{
						$cell[] = $vbphrase['n_a'];
					}
					print_cells_row($cell);
				}

				construct_hidden_code('paymentapiid', $vbulletin->GPC['paymentapiid']);
				construct_hidden_code('transactionid', $vbulletin->GPC['transactionid']);
				construct_hidden_code('limitnumber', $vbulletin->GPC['limitnumber']);
				construct_hidden_code('orderby', $vbulletin->GPC['orderby']);
				construct_hidden_code('start[month]', $vbulletin->GPC['start']['month']);
				construct_hidden_code('start[day]', $vbulletin->GPC['start']['day']);
				construct_hidden_code('start[year]', $vbulletin->GPC['start']['year']);
				construct_hidden_code('end[month]', $vbulletin->GPC['end']['month']);
				construct_hidden_code('end[day]', $vbulletin->GPC['end']['day']);
				construct_hidden_code('end[year]', $vbulletin->GPC['end']['year']);
				construct_hidden_code('currency', $vbulletin->GPC['currency']);
				construct_hidden_code('type', $vbulletin->GPC['type']);
				construct_hidden_code('subscriptionid', $vbulletin->GPC['subscriptionid']);
				construct_hidden_code('state', $vbulletin->GPC['state']);
				construct_hidden_code('userid', $userinfo['userid']);
				construct_hidden_code('scope', 1);

				if ($vbulletin->GPC['limitstart'] == 0 AND $counttrans['trans'] > $vbulletin->GPC['limitnumber'])
				{
					construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
					print_submit_row($vbphrase['next_page'], 0, 7);
				}
				else if ($limitfinish < $counttrans['trans'])
				{
					construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
					print_submit_row($vbphrase['next_page'], 0, 7, $vbphrase['prev_page'], '', true);
				}
				else if ($vbulletin->GPC['limitstart'] > 0 AND $limitfinish >= $counttrans['trans'])
				{
					print_submit_row($vbphrase['first_page'], 0, 7, $vbphrase['prev_page'], '', true);
				}
				else
				{
					print_table_footer();
				}
			}
		}
	}
}

print_cp_footer();

// ###################### Start toggle_subs #######################
// Function disables subs if there isn't an active API or active SUB (and vice versa)
function toggle_subs()
{
	global $vbulletin;

	// bit of a hack, will most likely change this to a datastore item in the future

	$setting = 0;
	if ($check = $vbulletin->db->query_first("
		SELECT paymentapiid
		FROM " . TABLE_PREFIX . "paymentapi
		WHERE active = 1
	"))
	{
		if ($check = $vbulletin->db->query_first("
			SELECT subscriptionid
			FROM " . TABLE_PREFIX . "subscription
			WHERE active = 1
		"))
		{
			$setting = 1;
		}
	}

	if ($setting != $vbulletin->options['subscriptionmethods'])
	{
		// update $vboptions
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "setting
			SET value = '$setting'
			WHERE varname = 'subscriptionmethods'
		");
		build_options();
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 48073 $
|| ####################################################################
\*======================================================================*/
?>
