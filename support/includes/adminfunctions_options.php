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

error_reporting(E_ALL & ~E_NOTICE);

/**
* Prints a setting group for use in options.php?do=options
*
* @param	string	Settings group ID
* @param	boolean	Show advanced settings?
*/
function print_setting_group($dogroup, $advanced = 0)
{
	global $settingscache, $grouptitlecache, $vbulletin, $vbphrase, $bgcounter, $settingphrase;

	if (!is_array($settingscache["$dogroup"]))
	{
		return;
	}

	print_column_style_code(array('width:45%', 'width:55%'));

	echo "<thead>\r\n";

	print_table_header(
		$settingphrase["settinggroup_$grouptitlecache[$dogroup]"]
		 . iif($vbulletin->debug,
			'<span class="normal">' .
			construct_link_code($vbphrase['edit'], "options.php?" . $vbulletin->session->vars['sessionurl'] . "do=editgroup&amp;grouptitle=$dogroup") .
			construct_link_code($vbphrase['delete'], "options.php?" . $vbulletin->session->vars['sessionurl'] . "do=removegroup&amp;grouptitle=$dogroup") .
			construct_link_code($vbphrase['add_setting'], "options.php?" . $vbulletin->session->vars['sessionurl'] . "do=addsetting&amp;grouptitle=$dogroup") .
			'</span>'
		)
	);

	echo "</thead>\r\n";

	$bgcounter = 1;

	foreach ($settingscache["$dogroup"] AS $settingid => $setting)
	{
		if (($advanced OR !$setting['advanced']) AND !empty($setting['varname']))
		{
			print_setting_row($setting, $settingphrase);
		}
	}
}

/**
* Prints a setting row for use in options.php?do=options
*
* @param	array	Settings array
* @param	array	Phrases
*/
function print_setting_row($setting, $settingphrase, $option_config = true)
{
	global $vbulletin, $vbphrase, $bgcounter, $settingphrase;

	$settingid = $setting['varname'];

	echo '<tbody>';

	print_description_row(
		iif(($vbulletin->debug AND $option_config), '<div class="smallfont" style="float:' . vB_Template_Runtime::fetchStyleVar('right') . '">' . construct_link_code($vbphrase['edit'], "options.php?" . $vbulletin->session->vars['sessionurl'] . "do=editsetting&amp;varname=$setting[varname]") . construct_link_code($vbphrase['delete'], "options.php?" . $vbulletin->session->vars['sessionurl'] . "do=removesetting&amp;varname=$setting[varname]") . '</div>') .
		'<div>' . $settingphrase["setting_$setting[varname]_title"] . "<a name=\"$setting[varname]\"></a></div>",
		0, 2, 'optiontitle' . ($vbulletin->debug ? "\" title=\"\$vbulletin->options['" . $setting['varname'] . "']" : '')
	);
	echo "</tbody><tbody id=\"tbody_$settingid\">\r\n";

	// make sure all rows use the alt1 class
	$bgcounter--;

	$description = "<div class=\"smallfont\"" . ($vbulletin->debug ? "title=\"\$vbulletin->options['$setting[varname]']\"" : '') . ">" . $settingphrase["setting_$setting[varname]_desc"] . '</div>';
	$name = "setting[$setting[varname]]";
	$right = "<span class=\"smallfont\">$vbphrase[error]</span>";
	$width = 40;
	$rows = 8;

	if (preg_match('#^input:?(\d+)$#s', $setting['optioncode'], $matches))
	{
		$width = $matches[1];
		$setting['optioncode'] = '';
	}
	else if (preg_match('#^textarea:?(\d+)(,(\d+))?$#s', $setting['optioncode'], $matches))
	{
		$rows = $matches[1];
		if ($matches[2])
		{
			$width = $matches[3];
		}
		$setting['optioncode'] = 'textarea';
	}
	else if (preg_match('#^bitfield:(.*)$#siU', $setting['optioncode'], $matches))
	{
		$setting['optioncode'] = 'bitfield';
		$setting['bitfield'] =& fetch_bitfield_definitions($matches[1]);
	}
	else if (preg_match('#^(select|selectmulti|radio):(piped|eval)(\r\n|\n|\r)(.*)$#siU', $setting['optioncode'], $matches))
	{
		$setting['optioncode'] = "$matches[1]:$matches[2]";
		$setting['optiondata'] = trim($matches[4]);
	}
	else if (preg_match('#^usergroup:?(\d+)$#s', $setting['optioncode'], $matches))
	{
		$size = intval($matches[1]);
		$setting['optioncode'] = 'usergroup';
	}
	else if (preg_match('#^(usergroupextra)(\r\n|\n|\r)(.*)$#siU', $setting['optioncode'], $matches))
	{
		$setting['optioncode'] = 'usergroupextra';
		$setting['optiondata'] = trim($matches[3]);
	}
	else if (preg_match('#^profilefield:?([a-z0-9,;=]*)(?:\r\n|\n|\r)(.*)$#siU', $setting['optioncode'], $matches))
	{
		$setting['optioncode'] = 'profilefield';
		$setting['optiondata'] = array(
			'constraints'  => trim($matches[1]),
			'extraoptions' => trim($matches[2]),
		);
	}
	else if (preg_match('#^apipostidmanage(?:\r\n|\n|\r)(.*)$#siU', $setting['optioncode'], $matches))
	{
		$setting['optioncode'] = 'apipostidmanage';
		$setting['optiondata'] = preg_split("#(\r\n|\n|\r)#s", $matches[1], -1, PREG_SPLIT_NO_EMPTY);
	}

	switch ($setting['optioncode'])
	{
		// input type="text"
		case '':
		{
			print_input_row($description, $name, $setting['value'], 1, $width);
		}
		break;

		// input type="radio"
		case 'yesno':
		{
			print_yes_no_row($description, $name, $setting['value']);
		}
		break;

		// textarea
		case 'textarea':
		{
			print_textarea_row($description, $name, $setting['value'], $rows, "$width\" style=\"width:90%");
		}
		break;

		// bitfield
		case 'bitfield':
		{
			$setting['value'] = intval($setting['value']);
			$setting['html'] = '';

			if ($setting['bitfield'] === NULL)
			{
				print_label_row($description, construct_phrase("<strong>$vbphrase[settings_bitfield_error]</strong>", implode(',', vB_Bitfield_Builder::fetch_errors())), '', 'top', $name, 40);
			}
			else
			{
				#$setting['html'] .= "<fieldset><legend>$vbphrase[yes] / $vbphrase[no]</legend>";
				$setting['html'] .= "<div id=\"ctrl_setting[$setting[varname]]\" class=\"smallfont\">\r\n";
				$setting['html'] .= "<input type=\"hidden\" name=\"setting[$setting[varname]][0]\" value=\"0\" />\r\n";
				foreach ($setting['bitfield'] AS $key => $value)
				{
					$value = intval($value);
					$setting['html'] .= "<table style=\"width:175px; float:" . vB_Template_Runtime::fetchStyleVar('left') . "\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr valign=\"top\">
					<td><input type=\"checkbox\" name=\"setting[$setting[varname]][$value]\" id=\"setting[$setting[varname]]_$key\" value=\"$value\"" . (($setting['value'] & $value) ? ' checked="checked"' : '') . " /></td>
					<td width=\"100%\" style=\"padding-top:4px\"><label for=\"setting[$setting[varname]]_$key\" class=\"smallfont\">" . fetch_phrase_from_key($key) . "</label></td>\r\n</tr></table>\r\n";
				}

				$setting['html'] .= "</div>\r\n";
				#$setting['html'] .= "</fieldset>";
				print_label_row($description, $setting['html'], '', 'top', $name, 40);
			}
		}
		break;

		// select:piped
		case 'select:piped':
		{
			print_select_row($description, $name, fetch_piped_options($setting['optiondata']), $setting['value']);
		}
		break;

		// radio:piped
		case 'radio:piped':
		{
			print_radio_row($description, $name, fetch_piped_options($setting['optiondata']), $setting['value'], 'smallfont');
		}
		break;

		// select:eval
		case 'select:eval':
		{
			$options = null;

			eval($setting['optiondata']);

			if (is_array($options) AND !empty($options))
			{
				print_select_row($description, $name, $options, $setting['value']);
			}
			else
			{
				print_input_row($description, $name, $setting['value']);
			}
		}
		break;

		// select:eval
		case 'selectmulti:eval':
		{
			$options = null;

			eval($setting['optiondata']);

			if (is_array($options) AND !empty($options))
			{
				print_select_row($description, $name . '[]', $options, $setting['value'], false, 5, true);
			}
			else
			{
				print_input_row($description, $name, $setting['value']);
			}
		}
		break;

		// radio:eval
		case 'radio:eval':
		{
			$options = null;

			eval($setting['optiondata']);

			if (is_array($options) AND !empty($options))
			{
				print_radio_row($description, $name, $options, $setting['value'], 'smallfont');
			}
			else
			{
				print_input_row($description, $name, $setting['value']);
			}
		}
		break;

		case 'username':
		{
			if (intval($setting['value']) AND $userinfo = $vbulletin->db->query_first("SELECT username FROM " . TABLE_PREFIX . "user WHERE userid = " . intval($setting['value'])))
			{
				print_input_row($description, $name, $userinfo['username'], false);
			}
			else
			{
				print_input_row($description, $name);
			}
			break;
		}

		case 'usergroup':
		{
			$usergrouplist = array();
			foreach ($vbulletin->usergroupcache AS $usergroup)
			{
				$usergrouplist["$usergroup[usergroupid]"] = $usergroup['title'];
			}

			if ($size > 1)
			{
				print_select_row($description, $name . '[]', array(0 => '') + $usergrouplist, unserialize($setting['value']), false, $size, true);
			}
			else
			{
				print_select_row($description, $name, $usergrouplist, $setting['value']);
			}
			break;
		}

		case 'usergroupextra':
		{
			$usergrouplist = fetch_piped_options($setting['optiondata']);
			foreach ($vbulletin->usergroupcache AS $usergroup)
			{
				$usergrouplist["$usergroup[usergroupid]"] = $usergroup['title'];
			}

			print_select_row($description, $name, $usergrouplist, $setting['value']);
			break;
		}

		case 'profilefield':
		{
			static $profilefieldlistcache = array();
			$profilefieldlisthash = md5(serialize($setting['optiondata']));

			if (!isset($profilefieldlistcache[$profilefieldlisthash]))
			{
				$profilefieldlist = fetch_piped_options($setting['optiondata']['extraoptions']);

				$constraints = preg_split('#;#', $setting['optiondata']['constraints'], -1, PREG_SPLIT_NO_EMPTY);
				$where = array();
				foreach ($constraints AS $constraint)
				{
					$constraint = explode('=', $constraint);
					switch ($constraint[0])
					{
						case 'editablegt':
							$where[] = 'editable > ' . intval($constraint[1]);
							break;
						case 'types':
							$constraint[1] = preg_split('#,#', $constraint[1], -1, PREG_SPLIT_NO_EMPTY);
							if (!empty($constraint[1]))
							{
								$where[] = "type IN('" . implode("', '", array_map(array($vbulletin->db, 'escape_string'), $constraint[1])) . "')";
							}
							break;
					}
				}

				$profilefields = $vbulletin->db->query_read_slave("
					SELECT *
					FROM " . TABLE_PREFIX . "profilefield
					" . (!empty($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
					ORDER BY displayorder
				");

				while ($profilefield = $vbulletin->db->fetch_array($profilefields))
				{
					$fieldname = "field$profilefield[profilefieldid]";
					$profilefieldlist[$fieldname] = construct_phrase($vbphrase['profilefield_x_fieldid_y'], fetch_phrase_from_key("{$fieldname}_title"), $fieldname);
				}

				$profilefieldlistcache[$profilefieldlisthash] = $profilefieldlist;
				unset($profilefieldlist, $constraints, $constraint, $where, $profilefields, $profilefield, $fieldname);
			}

			print_select_row($description, $name, $profilefieldlistcache[$profilefieldlisthash], $setting['value']);
			break;
		}

		// arbitrary number of <input type="text" />
		case 'multiinput':
		{
			$setting['html'] = "<div id=\"ctrl_$setting[varname]\"><fieldset id=\"multi_input_fieldset_$setting[varname]\" style=\"padding:4px\">";

			$setting['values'] = unserialize($setting['value']);
			$setting['values'] = (is_array($setting['values']) ? $setting['values'] : array());
			$setting['values'][] = '';

			foreach ($setting['values'] AS $key => $value)
			{
				$setting['html'] .= "<div id=\"multi_input_container_$setting[varname]_$key\">" . ($key + 1) . " <input type=\"text\" class=\"bginput\" name=\"setting[$setting[varname]][$key]\" id=\"multi_input_$setting[varname]_$key\" size=\"40\" value=\"" . htmlspecialchars_uni($value) . "\" tabindex=\"1\" /></div>";
			}

			$i = sizeof($setting['values']);
			if ($i == 0)
			{
				$setting['html'] .= "<div><input type=\"text\" class=\"bginput\" name=\"setting[$setting[varname]][$i]\" size=\"40\" tabindex=\"1\" /></div>";
			}

			$setting['html'] .= "
				</fieldset>
				<div class=\"smallfont\"><a href=\"#\" onclick=\"return multi_input['$setting[varname]'].add()\">Add Another Option</a></div>
				<script type=\"text/javascript\">
				<!--
				multi_input['$setting[varname]'] = new vB_Multi_Input('$setting[varname]', $i, '" . $vbulletin->options['cpstylefolder'] . "');
				//-->
				</script>
			";

			print_label_row($description, $setting['html']);
			break;
		}

		// activity stream options
		case 'activitystream':
		{
			$options = array();
			$activities = $vbulletin->db->query_read("
				SELECT
					typeid, section, type, enabled
				FROM " . TABLE_PREFIX . "activitystreamtype AS a
				INNER JOIN " . TABLE_PREFIX . "package AS p ON (p.packageid = a.packageid)
				ORDER BY section, type
			");
			while ($activity = $vbulletin->db->fetch_array($activities))
			{
				$options["{$activity['section']}_{$activity['type']}"] = $activity;
			}

			$setting['html'] = '';
			$setting['html'] .= "<div id=\"ctrl_setting[$setting[varname]]\" class=\"smallfont\">\r\n";
			$setting['html'] .= "<input type=\"hidden\" name=\"setting[$setting[varname]][0]\" value=\"0\" />\r\n";
			foreach ($options AS $key => $activity)
			{
				$setting['html'] .= "<table style=\"width:175px; float:" . vB_Template_Runtime::fetchStyleVar('left') . "\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr valign=\"top\">
				<td><input type=\"checkbox\" name=\"setting[$setting[varname]][$activity[typeid]]\" id=\"setting[$setting[varname]]_$key\" value=\"1\"" . ($activity['enabled'] ? ' checked="checked"' : '') . " /></td>
				<td width=\"100%\" style=\"padding-top:4px\"><label for=\"setting[$setting[varname]]_$key\" class=\"smallfont\">" . fetch_phrase_from_key($key) . "</label></td>\r\n</tr></table>\r\n";
			}
			print_label_row($description, $setting['html'], '', 'top', $name, 40);
		}
		break;

		// default registration options
		case 'defaultregoptions':
		{
			$setting['value'] = intval($setting['value']);

			$checkbox_options = array(
				'receiveemail' => 'display_email',
				'adminemail' => 'receive_admin_emails',
				'invisiblemode' => 'invisible_mode',
				'vcard' => 'allow_vcard_download',
				'signature' => 'display_signatures',
				'avatar' => 'display_avatars',
				'image' => 'display_images',
				'showreputation' => 'display_reputation',
				'enablepm' => 'receive_private_messages',
				'emailonpm' => 'send_notification_email_when_a_private_message_is_received',
				'pmpopup' => 'pop_up_notification_box_when_a_private_message_is_received',
			);

			$setting['value'] = intval($setting['value']);

			$setting['html'] = '';
			#$setting['html'] .= "<fieldset><legend>$vbphrase[yes] / $vbphrase[no]</legend>";
			$setting['html'] .= "<div id=\"ctrl_setting[$setting[varname]]\" class=\"smallfont\">\r\n";
			$setting['html'] .= "<input type=\"hidden\" name=\"setting[$setting[varname]][0]\" value=\"0\" />\r\n";
			foreach ($checkbox_options AS $key => $phrase)
			{
				$value = $vbulletin->bf_misc_regoptions["$key"];

				$setting['html'] .= "<table style=\"width:175px; float:" . vB_Template_Runtime::fetchStyleVar('left') . "\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr valign=\"top\">
				<td><input type=\"checkbox\" name=\"setting[$setting[varname]][$value]\" id=\"setting[$setting[varname]]_$key\" value=\"$value\"" . (($setting['value'] & $value) ? ' checked="checked"' : '') . " /></td>
				<td width=\"100%\" style=\"padding-top:4px\"><label for=\"setting[$setting[varname]]_$key\" class=\"smallfont\">" . fetch_phrase_from_key($phrase) . "</label></td>\r\n</tr></table>\r\n";
			}
			#$setting['html'] .= "</fieldset>";
			print_label_row($description, $setting['html'], '', 'top', $name, 40);
		}
		break;

		// cp folder options
		case 'cpstylefolder':
		{
			if ($folders = fetch_cpcss_options() AND !empty($folders))
			{
				print_select_row($description, $name, $folders, $setting['value'], 1, 6);
			}
			else
			{
				print_input_row($description, $name, $setting['value'], 1, 40);
			}
		}
		break;

		case 'apipostidmanage':
		{
			$setting['html'] = "<div id=\"ctrl_apipostidmanage\"><fieldset id=\"multi_input_fieldset_apipostidmanage}\" style=\"padding:4px\">";

			$setting['values'] = unserialize($setting['value']);
			$setting['values'] = (is_array($setting['values']) ? $setting['values'] : array());

			$setting['html'] .= "
				<div style=\"padding:4px\">
					<span style=\"display:block\">{$vbphrase['apipostidmanage_enable']}</span>
					<label for=\"multi_input_apipostidmanage_enable1\" />
						<input type=\"radio\"" . ($setting['values']['enable'] ? ' checked="checked" ' : '') . "class=\"bginput\" name=\"setting[apipostidmanage][enable]\" id=\"multi_input_apipostidmanage_enable1\"  value=\"1\" tabindex=\"1\" />
						$vbphrase[yes]
					</label>
					<label for=\"multi_input_{$setting['varname']}_enable2\" />
						<input type=\"radio\"" . (!$setting['values']['enable'] ? ' checked="checked" ' : '') . "class=\"bginput\" name=\"setting[apipostidmanage][enable]\" id=\"multi_input_apipostidmanage_enable2\"  value=\"0\" tabindex=\"1\" />
						$vbphrase[no]
					</label>
				</div>";

			foreach ($setting['optiondata'] AS $device)
			{
				if (!$vbphrase['apipostidmanage_' . $device])
				{
					continue;
				}

				$setting['html'] .= "<div style=\"padding:4px\">
					<span style=\"display:block\">" . $vbphrase['apipostidmanage_' . $device] . "</span>
					<input type=\"text\" class=\"bginput\" name=\"setting[apipostidmanage][{$device}]\" id=\"multi_input_apipostidmanage_{$device}\" size=\"50\" value=\"" . htmlspecialchars_uni($setting['values'][$device]) . "\" tabindex=\"1\" />
				</div>";
			}

			$setting['html'] .= "</fieldset></div>";

			print_label_row($description, $setting['html'], '', 'top', 'apipostidmanage');
			break;
		}
		break;

		// cookiepath / cookiedomain options
		case 'cookiepath':
		case 'cookiedomain':
		{
			$func = 'fetch_valid_' . $setting['optioncode'] . 's';

			$cookiesettings = $func(($setting['optioncode'] == 'cookiepath' ? $vbulletin->script : $_SERVER['HTTP_HOST']), $vbphrase['blank']);

			$setting['found'] = in_array($setting['value'], array_keys($cookiesettings));

			$setting['html'] = "
			<div id=\"ctrl_$setting[varname]\">
			<fieldset>
				<legend>$vbphrase[suggested_settings]</legend>
				<div style=\"padding:4px\">
					<select name=\"setting[$setting[varname]]\" tabindex=\"1\" class=\"bginput\">" .
						construct_select_options($cookiesettings, $setting['value']) . "
					</select>
				</div>
			</fieldset>
			<br />
			<fieldset>
				<legend>$vbphrase[custom_setting]</legend>
				<div style=\"padding:4px\">
					<label for=\"{$settingid}o\"><input type=\"checkbox\" id=\"{$settingid}o\" name=\"setting[{$settingid}_other]\" tabindex=\"1\" value=\"1\"" . ($setting['found'] ? '' : ' checked="checked"') . " />$vbphrase[use_custom_setting]
					</label><br />
					<input type=\"text\" class=\"bginput\" size=\"25\" name=\"setting[{$settingid}_value]\" value=\"" . ($setting['found'] ? '' : $setting['value']) . "\" />
				</div>
			</fieldset>
			</div>";

			print_label_row($description, $setting['html'], '', 'top', $name, 50);
		}
		break;

		case 'facebooksslcheck':
		{
			require_once(DIR . '/includes/class_vurl.php');
			$vurl = new vB_vURL($vbulletin);
			$result = $vurl->test_ssl();

			print_label_row($description, $result ? $vbphrase['supported'] : $vbphrase['not_supported']);
		}
		break;

		case 'usergroups:none':
		{
			$array = build_usergroup_list($vbphrase['none'], 0);
			$size = sizeof($array);

			print_select_row($description, $name.'[]', $array, unserialize($setting['value']), false, ($size > 10 ? 10 : $size), true);
		}
		break;

		case 'usergroups:all':
		{
			$array = build_usergroup_list($vbphrase['all'], -1);
			$size = sizeof($array);

			print_select_row($description, $name.'[]', $array, unserialize($setting['value']), false, ($size > 10 ? 10 : $size), true);
		}
		break;

		case 'forums:all':
		{
			$array = construct_forum_chooser_options(-1,$vbphrase['all']);
			$size = sizeof($array);

			$vbphrase[forum_is_closed_for_posting] = $vbphrase[closed];
			print_select_row($description, $name.'[]', $array, unserialize($setting['value']), false, ($size > 10 ? 10 : $size), true);
		}
		break;

		case 'forums:none':
		{
			$array = construct_forum_chooser_options(0,$vbphrase['none']);
			$size = sizeof($array);

			$vbphrase[forum_is_closed_for_posting] = $vbphrase[closed];
			print_select_row($description, $name.'[]', $array, unserialize($setting['value']), false, ($size > 10 ? 10 : $size), true);
		}
		break;

		// just a label
		default:
		{
			$handled = false;
			($hook = vBulletinHook::fetch_hook('admin_options_print')) ? eval($hook) : false;
			if (!$handled)
			{
				eval("\$right = \"<div id=\\\"ctrl_setting[$setting[varname]]\\\">$setting[optioncode]</div>\";");
				print_label_row($description, $right, '', 'top', $name, 50);
			}
		}
		break;
	}

	echo "</tbody>\r\n";

	$valid = exec_setting_validation_code($setting['varname'], $setting['value'], $setting['validationcode']);

	echo "<tbody id=\"tbody_error_$settingid\" style=\"display:" . (($valid === 1 OR $valid === true) ? 'none' : '') . "\"><tr><td class=\"alt1 smallfont\" colspan=\"2\"><div style=\"padding:4px; border:solid 1px red; background-color:white; color:black\"><strong>$vbphrase[error]</strong>:<div id=\"span_error_$settingid\">$valid</div></div></td></tr></tbody>";
}

/**
* Returns a list of usergroups for selection
*
* @param	string	The text for option 0.
*/
function build_usergroup_list($option = '', $value = 0)
{
	global $vbulletin;

	if ($option)
	{
		$usergrouplist = array($value => $option);
	}
	else
	{
		$usergrouplist = array();
	}

	foreach ($vbulletin->usergroupcache AS $usergroup)
	{
		$usergrouplist["$usergroup[usergroupid]"] = $usergroup['title'];
	}

	return $usergrouplist;
}

/**
* Updates the setting table based on data passed in then rebuilds the datastore.
* Only entries in the array are updated (allows partial updates).
*
* @param	array	Array of settings. Format: [setting_name] = new_value
*/
function save_settings($settings)
{
	global $vbulletin, $vbphrase;

	$varnames = array();
	foreach(array_keys($settings) AS $varname)
	{
		$varnames[] = $vbulletin->db->escape_string($varname);
	}

	$rebuildstyle = false;
	$oldsettings = $vbulletin->db->query_read("
		SELECT value, varname, datatype, optioncode
		FROM " . TABLE_PREFIX . "setting
		WHERE varname IN ('" . implode("', '", $varnames) . "')
		ORDER BY varname
	");
	while ($oldsetting = $vbulletin->db->fetch_array($oldsettings))
	{
		switch ($oldsetting['varname'])
		{
			// **************************************************
			case 'bbcode_html_colors':
			{
				$settings['bbcode_html_colors'] = serialize($settings['bbcode_html_colors']);
			}
			break;

			// **************************************************
			case 'styleid':
			{
				$vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "style
					SET userselect = 1
					WHERE styleid = " . $settings['styleid'] . "
				");
			}
			break;

			case 'as_content':
			{
				$vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "activitystreamtype
					SET enabled = 0;
				");
				$vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "activitystreamtype
					SET enabled = 1
					WHERE typeid IN (" . implode(",", array_keys($vbulletin->GPC['setting']['as_content'])) . ")
				");
				build_activitystream_datastore();
				$settings['as_content'] = '';
			}
			break;

			// **************************************************
			case 'banemail':
			{
				build_datastore('banemail', $settings['banemail']);
				$settings['banemail'] = '';
			}
			break;

			// **************************************************
			case 'editormodes':
			{
				$vbulletin->input->clean_array_gpc('p', array('fe' => TYPE_UINT, 'qr' => TYPE_UINT, 'qe' => TYPE_UINT));

				$settings['editormodes'] = serialize(array(
					'fe' => $vbulletin->GPC['fe'],
					'qr' => $vbulletin->GPC['qr'],
					'qe' => $vbulletin->GPC['qe']
				));
			}
			break;

			// **************************************************
			case 'cookiepath':
			case 'cookiedomain':
			{
				if ($settings[$oldsetting['varname'] . '_other'] AND $settings[$oldsetting['varname'] . '_value'])
				{
					$settings[$oldsetting['varname']] = $settings[$oldsetting['varname'] . '_value'];
				}
			}
			break;

			case 'apipostidmanage':
			{
				$store = array(
					'enable'  => $settings['apipostidmanage']['enable'],
					'iphone'  => $settings['apipostidmanage']['iphone'],
					'android' => $settings['apipostidmanage']['android'],
					'facebook'=> $settings['apipostidmanage']['facebook'],
				);
				$settings["$oldsetting[varname]"] = serialize($store);
			}
			break;

			// **************************************************
			default:
			{
				($hook = vBulletinHook::fetch_hook('admin_options_processing')) ? eval($hook) : false;

				if ($oldsetting['optioncode'] == 'multiinput')
				{
					$store = array();
					foreach ($settings["$oldsetting[varname]"] AS $value)
					{
						if ($value != '')
						{
							$store[] = $value;
						}
					}
					$settings["$oldsetting[varname]"] = serialize($store);
				}
				else if (preg_match('#^(usergroup|forum)s?:([0-9]+|all|none)$#', $oldsetting['optioncode']))
				{
					// serialize the array of usergroup inputs
					if (!is_array($settings["$oldsetting[varname]"]))
					{
						 $settings["$oldsetting[varname]"] = array();
					}
					$settings["$oldsetting[varname]"] = array_map('intval', $settings["$oldsetting[varname]"]);
					$settings["$oldsetting[varname]"] = serialize($settings["$oldsetting[varname]"]);
				}
			}
		}

		$newvalue = validate_setting_value($settings["$oldsetting[varname]"], $oldsetting['datatype']);

		// this is a strict type check because we want '' to be different from 0
		// some special cases below only use != checks to see if the logical value has changed
		if (strval($oldsetting['value']) !== strval($newvalue))
		{
			switch ($oldsetting['varname'])
			{
				case 'activememberdays':
				case 'activememberoptions':
					if ($oldsetting['value'] != $newvalue)
					{
						$vbulletin->options["$oldsetting[varname]"] = $newvalue;

						require_once(DIR . '/includes/functions_databuild.php');
						build_birthdays();
					}
				break;

				case 'showevents':
				case 'showholidays':
					if ($oldsetting['value'] != $newvalue)
					{
						$vbulletin->options["$oldsetting[varname]"] = $newvalue;

						require_once(DIR . '/includes/functions_calendar.php');
						build_events();
					}
				break;

				case 'languageid':
				{
					if ($oldsetting['value'] != $newvalue)
					{
						$vbulletin->options['languageid'] = $newvalue;
						require_once(DIR . '/includes/adminfunctions_language.php');
						build_language($vbulletin->options['languageid']);
					}
				}
				break;

				case 'cpstylefolder':
				{
					$admindm =& datamanager_init('Admin', $vbulletin, ERRTYPE_CP);
					$admindm->set_existing($vbulletin->userinfo);
					$admindm->set('cssprefs', $newvalue);
					$admindm->save();
					unset($admindm);
				}
				break;

				case 'smcolumns':
				case 'attachthumbssize':
				{
					if ($oldsetting['value'] != $newvalue)
					{
						$rebuildstyle = true;
					}
				}

				case 'storecssasfile':
				{
					if (!is_demo_mode() AND $oldsetting['value'] != $newvalue)
					{
						$vbulletin->options['storecssasfile'] = $newvalue;
						$rebuildstyle = true;
					}
				}
				break;

				case 'loadlimit':
				{
					update_loadavg();
				}
				break;

				case 'tagcloud_usergroup':
				{
					build_datastore('tagcloud', serialize(''), 1);
				}
				break;

				case 'censorwords':
				case 'codemaxlines':
				case 'url_nofollow':
				case 'url_nofollow_whitelist':
				{
					if ($oldsetting['value'] != $newvalue)
					{
						$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "postparsed");
						if (is_newer_version($vbulletin->options['templateversion'], '3.6',true))
						{
							$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "sigparsed");
						}
					}

					($hook = vBulletinHook::fetch_hook('admin_options_processing_censorcode')) ? eval($hook) : false;
				}
				break;

				case 'album_recentalbumdays':
				{
					if ($oldsetting['value'] > $newvalue)
					{
						require_once(DIR . '/includes/functions_album.php');
						exec_rebuild_album_updates();
					}
				}
				default:
				{
					($hook = vBulletinHook::fetch_hook('admin_options_processing_build')) ? eval($hook) : false;
				}
			}

			if (is_demo_mode() AND in_array($oldsetting['varname'], array('storecssasfile', 'attachfile', 'usefileavatar', 'errorlogdatabase', 'errorlogsecurity', 'safeupload', 'tmppath')))
			{
				continue;
			}

			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "setting
				SET value = '" . $vbulletin->db->escape_string($newvalue) . "'
				WHERE varname = '" . $vbulletin->db->escape_string($oldsetting['varname']) . "'
			");
		}
	}
	build_options();

	if ($rebuildstyle)
	{
		require_once(DIR . '/includes/adminfunctions_template.php');
		print_rebuild_style(-1, '', 1, 0, 0, 0);
		print_rebuild_style(-2, '', 1, 0, 0, 0);
	}
}

/**
* Attempts to run validation code on a setting
*
* @param	string	Setting varname
* @param	mixed	Setting value
* @param	string	Setting validation code
*
* @return	mixed
*/
function exec_setting_validation_code($varname, $value, $validation_code, $raw_value = false)
{
	if ($raw_value === false)
	{
		$raw_value = $value;
	}

	if ($validation_code != '')
	{
		$validation_function = create_function('&$data, $raw_data', $validation_code);
		$validation_result = $validation_function($value, $raw_value);

		if ($validation_result === false OR $validation_result === null)
		{
			$valid = fetch_error("setting_validation_error_$varname");
			if (preg_match('#^Could#i', $valid) AND preg_match("#'" . preg_quote("setting_validation_error_$varname", '#') . "'#i", $valid))
			{
				$valid = fetch_error("you_did_not_enter_a_valid_value");
			}
			return $valid;
		}
		else
		{
			return $validation_result;
		}
	}

	return 1;
}

/**
* Validates the provided value of a setting against its datatype
*
* @param	mixed	(ref) Setting value
* @param	string	Setting datatype ('number', 'boolean' or other)
* @param	boolean	Represent boolean with 1/0 instead of true/false
* @param boolean  Query database for username type
*
* @return	mixed	Setting value
*/
function validate_setting_value(&$value, $datatype, $bool_as_int = true, $username_query = true)
{
	global $vbulletin;

	switch ($datatype)
	{
		case 'number':
			$value += 0;
			break;

		case 'integer':
			$value = intval($value);
			break;

		case 'arrayinteger':
			$key = array_keys($value);
			$size = sizeOf($key);
			for ($i = 0; $i < $size; $i++)
			{
				$value[$key[$i]] = intval($value[$key[$i]]);
			}
			break;

		case 'arrayfree':
			$key = array_keys($value);
			$size = sizeOf($key);
			for ($i = 0; $i < $size; $i++)
			{
				$value[$key[$i]] = trim($value[$key[$i]]);
			}
			break;

		case 'posint':
			$value = max(1, intval($value));
			break;

		case 'boolean':
			$value = ($bool_as_int ? ($value ? 1 : 0) : ($value ? true : false));
			break;

		case 'bitfield':
			if (is_array($value))
			{
				$bitfield = 0;
				foreach ($value AS $bitval)
				{
					$bitfield += $bitval;
				}
				$value = $bitfield;
			}
			else
			{
				$value += 0;
			}
			break;

		case 'username':
			$value = trim($value);
			if ($username_query)
			{
				if (empty($value))
				{
					$value =  0;
				}
				else if ($userinfo = $vbulletin->db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '" . $vbulletin->db->escape_string(htmlspecialchars_uni($value)) . "'"))
				{
					$value = $userinfo['userid'];
				}
				else
				{
					$value = false;
				}
			}
			break;

		default:
			$value = trim($value);
	}

	return $value;
}

/**
* Returns a list of valid settings for $vbulletin->options['cookiedomain'] based on $_SERVER['HTTP_HOST']
*
* @param	string	$_SERVER['HTTP_HOST']
* @param	string	Phrase to use for blank option
*
* @return	array
*/
function fetch_valid_cookiedomains($http_host, $blank_phrase)
{
	$cookiedomains = array('' => $blank_phrase);
	$domain = $http_host;

	while (substr_count($domain, '.') > 1)
	{
		$dotpos = strpos($domain, '.');
		$newdomain = substr($domain, $dotpos);
		$cookiedomains["$newdomain"] = $newdomain;
		$domain = substr($domain, $dotpos + 1);
	}

	return $cookiedomains;
}

/**
* Returns a list of valid settings for $vbulletin->options['cookiepath'] based on $vbulletin->script
*
* @param	string	$vbulletin->script
*
* @return	array
*/
function fetch_valid_cookiepaths($script)
{
	$cookiepaths = array('/' => '/');
	$curpath = '/';

	$path = preg_split('#/#', substr($script, 0, strrpos($script, '/')), -1, PREG_SPLIT_NO_EMPTY);

	for ($i = 0; $i < sizeof($path) - 1; $i++)
	{
		$curpath .= "$path[$i]/";
		$cookiepaths["$curpath"] = $curpath;
	}

	return $cookiepaths;
}


function get_settings_export_xml($product)
{
	global $vbulletin;
	$setting = array();
	$settinggroup = array();

	$groups = $vbulletin->db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "settinggroup
		WHERE volatile = 1
		ORDER BY displayorder, grouptitle
	");
	while ($group = $vbulletin->db->fetch_array($groups))
	{
		$settinggroup["$group[grouptitle]"] = $group;
	}

	$sets = $vbulletin->db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "setting
		WHERE volatile = 1
		AND (product = '" . $vbulletin->db->escape_string($product) . "'" .
			iif($product == 'vbulletin', " OR product = ''") . ")
		ORDER BY displayorder, varname
	");
	while ($set = $vbulletin->db->fetch_array($sets))
	{
		$setting["$set[grouptitle]"][] = $set;
	}
	unset($set);
	$vbulletin->db->free_result($sets);

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	$xml->add_group('settinggroups', array('product' => $product));

	foreach($settinggroup AS $grouptitle => $group)
	{
		if (!empty($setting["$grouptitle"]))
		{
			$group = $settinggroup["$grouptitle"];
			$xml->add_group('settinggroup',
				array(
					'name' => htmlspecialchars($group['grouptitle']),
					'displayorder' => $group['displayorder'],
					'product' => $group['product']
				)
			);
			foreach($setting["$grouptitle"] AS $set)
			{
				$arr = array('varname' => $set['varname'], 'displayorder' => $set['displayorder']);
				if ($set['advanced'])
				{
					$arr['advanced'] = 1;
				}

				$xml->add_group('setting', $arr);
				if ($set['datatype'])
				{
					$xml->add_tag('datatype', $set['datatype']);
				}
				if ($set['optioncode'] != '')
				{
					$xml->add_tag('optioncode', $set['optioncode']);
				}
				if ($set['validationcode'])
				{
					$xml->add_tag('validationcode', $set['validationcode']);
				}
				if ($set['defaultvalue'] != '')
				{
					$xml->add_tag('defaultvalue', iif($set['varname'] == 'templateversion', $vbulletin->options['templateversion'], $set['defaultvalue']));
				}
				if ($set['blacklist'])
				{
					$xml->add_tag('blacklist', 1);
				}
				$xml->close_group();
			}
			$xml->close_group();
		}
	}

	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n";

	$doc .= $xml->output();
	$xml = null;
	return $doc;
}

/**
* Imports settings from an XML settings file
*
* Call as follows:
* $path = './path/to/install/vbulletin-settings.xml';
* xml_import_settings($xml);
*
* @param	mixed	Either XML string or boolean false to use $path global variable
*/
function xml_import_settings($xml = false)
{
	global $vbulletin, $vbphrase;

	print_dots_start('<b>' . $vbphrase['importing_settings'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

	require_once(DIR . '/includes/class_xml.php');

	$xmlobj = new vB_XML_Parser($xml, $GLOBALS['path']);
	if ($xmlobj->error_no == 1)
	{
			print_dots_stop();
			print_stop_message('no_xml_and_no_path');
	}
	else if ($xmlobj->error_no == 2)
	{
			print_dots_stop();
			print_stop_message('please_ensure_x_file_is_located_at_y', 'vbulletin-settings.xml', $GLOBALS['path']);
	}

	if(!$arr = $xmlobj->parse())
	{
		print_dots_stop();
		print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
	}

	if (!$arr['settinggroup'])
	{
		print_dots_stop();
		print_stop_message('invalid_file_specified');
	}

	$product = (empty($arr['product']) ? 'vbulletin' : $arr['product']);

	// delete old volatile settings and settings that might conflict with new ones...
	$vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "settinggroup WHERE volatile = 1 AND (product = '" . $vbulletin->db->escape_string($product) . "'" . iif($product == 'vbulletin', " OR product = ''") . ')');
	$vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "setting WHERE volatile = 1 AND (product = '" . $vbulletin->db->escape_string($product) . "'" . iif($product == 'vbulletin', " OR product = ''") . ')');

	// run through imported array
	if (!is_array($arr['settinggroup'][0]))
	{
		$arr['settinggroup'] = array($arr['settinggroup']);
	}
	foreach($arr['settinggroup'] AS $group)
	{
		// need check to make sure group product== xml product before inserting new settinggroup
		if (empty($group['product']) OR $group['product'] == $product)
		{
			// insert setting group
			/*insert query*/
			$vbulletin->db->query_write("
				INSERT IGNORE INTO " . TABLE_PREFIX . "settinggroup
				(grouptitle, displayorder, volatile, product)
				VALUES
				('" . $vbulletin->db->escape_string($group['name']) . "', " . intval($group['displayorder']) . ", 1, '" . $vbulletin->db->escape_string($product) . "')
			");
		}

		// build insert query for this group's settings
		$qBits = array();
		if (!is_array($group['setting'][0]))
		{
			$group['setting'] = array($group['setting']);
		}
		foreach($group['setting'] AS $setting)
		{
			if (isset($vbulletin->options["$setting[varname]"]))
			{
				$newvalue = $vbulletin->options["$setting[varname]"];
			}
			else
			{
				$newvalue = $setting['defaultvalue'];
			}
			$qBits[] = "(
				'" . $vbulletin->db->escape_string($setting['varname']) . "',
				'" . $vbulletin->db->escape_string($group['name']) . "',
				'" . $vbulletin->db->escape_string(trim($newvalue)) . "',
				'" . $vbulletin->db->escape_string(trim($setting['defaultvalue'])) . "',
				'" . $vbulletin->db->escape_string(trim($setting['datatype'])) . "',
				'" . $vbulletin->db->escape_string($setting['optioncode']) . "',
				" . intval($setting['displayorder']) . ",
				" . intval($setting['advanced']) . ",
				1" . (!defined('UPGRADE_COMPAT') ? ",
					'" . $vbulletin->db->escape_string($setting['validationcode']) . "',
					" . intval($setting['blacklist']) . ",
					'" . $vbulletin->db->escape_string($product) . "'" : '') . "\n\t)";
		}
		// run settings insert query
		/*insert query*/
		$vbulletin->db->query_write("
			INSERT IGNORE INTO " . TABLE_PREFIX . "setting
			(varname, grouptitle, value, defaultvalue, datatype, optioncode, displayorder,
			advanced, volatile" . (!defined('UPGRADE_COMPAT') ? ', validationcode, blacklist, product' : '') . ")
			VALUES
			" . implode(",\n\t", $qBits));
	}

	// rebuild the $vbulletin->options array
	build_options();

	// stop the 'dots' counter feedback
	print_dots_stop();

}

/**
* Restores a settings backup from an XML file
*
* Call as follows:
* $path = './path/to/install/vbulletin-settings.xml';
* xml_import_settings($xml);
*
* @param	mixed	Either XML string or boolean false to use $path global variable
* @param bool	Ignore blacklisted settings
*/
function xml_restore_settings($xml = false, $blacklist = true)
{
	global $vbulletin, $vbphrase;
	$newsettings = array();

	print_dots_start('<b>' . $vbphrase['importing_settings'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

	require_once(DIR . '/includes/class_xml.php');

	$xmlobj = new vB_XML_Parser($xml, $GLOBALS['path']);
	if ($xmlobj->error_no == 1)
	{
			print_dots_stop();
			print_stop_message('no_xml_and_no_path');
	}
	else if ($xmlobj->error_no == 2)
	{
			print_dots_stop();
			print_stop_message('please_ensure_x_file_is_located_at_y', 'vbulletin-settings.xml', $GLOBALS['path']);
	}

	if(!$newsettings = $xmlobj->parse())
	{
		print_dots_stop();
		print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
	}

	if (!$newsettings['setting'])
	{
		print_dots_stop();
		print_stop_message('invalid_file_specified');
	}

	$product = (empty($newsettings['product']) ? 'vbulletin' : $newsettings['product']);

	foreach($newsettings['setting'] AS $setting)
	{
		// Loop to update all the settings
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "setting
			SET value='" . $vbulletin->db->escape_string($setting['value']) . "'
			WHERE varname ='" . $vbulletin->db->escape_string($setting['varname']) . "'
				AND product ='" . $vbulletin->db->escape_string($product) . "'
				" . ($blacklist ? "AND blacklist = 0" : "") . "
		");

	}

	unset($newsettings);

	// rebuild the $vbulletin->options array
	build_options();

	// stop the 'dots' counter feedback
	print_dots_stop();

}

/**
* Fetches an array of style titles for use in select menus
*
* @param	string	Prefix for titles
* @param	boolean	Display top level style?
* @param	string	'both', display both master styles, 'standard', the standard styles, 'mobile', the mobile styles
*
* @return	array
*/
function fetch_style_title_options_array($titleprefix = '', $displaytop = false, $type = 'both')
{
	require_once(DIR . '/includes/adminfunctions_template.php');
	global $stylecache, $vbphrase;

	cache_styles();
	$styles = array();

	foreach($stylecache AS $style)
	{
		$styles[$style['type']]["$style[styleid]"] = $titleprefix . construct_depth_mark($style['depth'], '--', iif($displaytop, '--', '')) . " $style[title]";
	}

	if ($type == 'both')
	{
		$out = array(
			$vbphrase['standard_styles'] => $styles['standard'],
			$vbphrase['mobile_styles']   => $styles['mobile'],
		);
		return $out;
	}
	else
	{
		$out = array(
			$vbphrase[$type . '_styles'] => $styles[$type],
		);
		return $out;
	}
}

/**
* Fetches information about GD
*
* @return	array
*/
function fetch_gdinfo()
{
	$gdinfo = array();

	if (function_exists('gd_info'))
	{
		$gdinfo = gd_info();
	}
	else if (function_exists('phpinfo') AND function_exists('ob_start'))
	{
		if (@ob_start())
		{
			eval('@phpinfo();');
			$info = @ob_get_contents();
			@ob_end_clean();
			preg_match('/GD Version[^<]*<\/td><td[^>]*>(.*?)<\/td><\/tr>/si', $info, $version);
			preg_match('/FreeType Linkage[^<]*<\/td><td[^>]*>(.*?)<\/td><\/tr>/si', $info, $freetype);
			$gdinfo = array(
				'GD Version'       => $version[1],
				'FreeType Linkage' => $freetype[1],
			);
		}
	}

	if (empty($gdinfo['GD Version']))
	{
		$gdinfo['GD Version'] = $vbphrase['n_a'];
	}
	else
	{
		$gdinfo['version'] = preg_replace('#[^\d\.]#', '', $gdinfo['GD Version']);
	}

	if (preg_match('#with (unknown|freetype|TTF)( library)?#si', trim($gdinfo['FreeType Linkage']), $freetype))
	{
		$gdinfo['freetype'] = $freetype[1];
	}

	return $gdinfo;
}

/**
* Fetches an array describing the bits in the requested bitfield
*
* @param	string	Represents the array key required... use x|y|z to fetch ['x']['y']['z']
*
* @return	array	Reference to the requested array from includes/xml/bitfield_{product}.xml
*/
function &fetch_bitfield_definitions($string)
{
	static $bitfields = null;

	if ($bitfields === null)
	{
		require_once(DIR . '/includes/class_bitfield_builder.php');
		$bitfields = vB_Bitfield_Builder::return_data();
	}

	$keys = "['" . implode("']['", preg_split('#\|#si', $string, -1, PREG_SPLIT_NO_EMPTY)) . "']";

	eval('$return =& $bitfields' . $keys . ';');

	return $return;
}

/**
* Attempts to fetch the text of a phrase from the given key.
* If the phrase is not found, the key is returned.
*
* @param	string	Phrase key
*
* @return	string
*/
function fetch_phrase_from_key($phrase_key)
{
	global $vbphrase;

	return (isset($vbphrase["$phrase_key"])) ? $vbphrase["$phrase_key"] : $phrase_key;
}

/**
* Returns an array of options and phrase values from a piped list
* such as 0|no\n1|yes\n2|maybe
*
* @param	string	Piped data
*
* @return	array
*/
function fetch_piped_options($piped_data)
{
	$options = array();

	$option_lines = preg_split("#(\r\n|\n|\r)#s", $piped_data, -1, PREG_SPLIT_NO_EMPTY);
	foreach ($option_lines AS $option)
	{
		if (preg_match('#^([^\|]+)\|(.+)$#siU', $option, $option_match))
		{
			$option_text = explode('(,)', $option_match[2]);
			foreach (array_keys($option_text) AS $idx)
			{
				$option_text["$idx"] = fetch_phrase_from_key(trim($option_text["$idx"]));
			}
			$options["$option_match[1]"] = implode(', ', $option_text);
		}
	}

	return $options;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 63231 $
|| ####################################################################
\*======================================================================*/
