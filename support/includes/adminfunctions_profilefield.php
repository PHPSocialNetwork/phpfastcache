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

// ###################### Store Hidden fields in cache ###############
function build_profilefield_cache()
{
	global $vbulletin;

	$fields = $vbulletin->db->query_read("
		SELECT profilefieldid, hidden, required, editable, form
		FROM " . TABLE_PREFIX . "profilefield AS profilefield
		WHERE hidden = 1
			OR (required = 3 AND editable IN (1,2) AND form = 0)
	");

	$hiddenfields = '';
	$requiredfields = array();
	while ($field = $vbulletin->db->fetch_array($fields))
	{
		if ($field['hidden'] == 1)
		{
			$hiddenfields .= ", '' AS field$field[profilefieldid]";
		}
		if ($field['form'] == 0 AND $field['required'] == 3 AND ($field['editable'] == 1 OR $field['editable'] == 2))
		{
			$requiredfields['field' . $field['profilefieldid']] = $field['profilefieldid'];
		}
	}

	$item = array(
		'hidden'   => $hiddenfields,
		'required' => $requiredfields,
	);

	build_datastore('profilefield', serialize($item), 1);
}

// ###################### Start bitwiserebuild #######################
function build_profilefield_bitfields($profilefieldid, $source, $dest = 0)
{

	global $vbulletin;
	static $erased;

	$sourcevalue = pow(2, $source - 1);
	$destvalue = pow(2, $dest - 1);

	// Empty out the Source values IF we haven't copied anything into them!
	$erased["$source"] = 1;
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "userfield
		SET temp = temp - $sourcevalue
		WHERE temp & $sourcevalue
	");

	if ($dest > 0)
	{
		if (!isset($erased["$source"]))
		{
			// Zero out the destination values
			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "userfield
				SET temp = temp - $destvalue
				WHERE temp & $destvalue
			");
			//echo "s:$sourcevalue ($source) d:$destvalue ($dest) $query<br />";
		}

		// Mark that we have written to this destination already so do not zero it if it becomes a source!

		// Copy the backup source values to the new destination location
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "userfield
			SET temp = temp + $destvalue
			WHERE field$profilefieldid & $sourcevalue
		");
		//echo "s:$sourcevalue ($source) d:$destvalue ($dest) $query<br />";
	}

}

// ###################### Start bitwiseswap #######################
// Swaps the locations of two bits in the checkbox bitwise data
function build_bitwise_swap($profilefieldid, $loc1, $loc2)
{

	global $vbulletin;

	$loc1value = pow(2, $loc1 - 1);
	$loc2value = pow(2, $loc2 - 1);

	// Zero loc1 in temp field
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "userfield
		SET temp = temp - $loc1value
		WHERE temp & $loc1value
	");
	// Copy loc2 to loc1
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "userfield
		SET temp = temp + $loc1value
		WHERE temp & $loc2value
	");
	// Zero loc2 in temp field
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "userfield
		SET temp = temp - $loc2value
		WHERE temp & $loc2value
	");
	// Copy loc1 from perm field to loc2 temp field
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "userfield
		SET temp = temp + $loc2value
		WHERE field$profilefieldid & $loc1value
	");

}

// ###################### Start outputprofilefield #######################
// Outputs a profilefield for creating & searching users
function print_profilefield_row($basename, $profilefield, $userfield = '', $searching = true)
{
	global $vbphrase;

	$data = unserialize($profilefield['data']);
	$fieldname = 'field' . $profilefield['profilefieldid'];
	$profilefieldname = $basename . '[field' . $profilefield['profilefieldid'] . ']';
	$optionalname = $basename . '[field' . $profilefield['profilefieldid'] . '_opt]';
	$output = '';

	$profilefield['title'] = htmlspecialchars_uni($vbphrase[$fieldname . '_title']);

	if (!is_array($userfield))
	{
		$use_default = ($searching ? false : true);
		$userfield = array($fieldname => '');
	}
	else
	{
		$use_default = false;
	}

	if ($profilefield['type'] == 'input')
	{
		print_input_row(
			$profilefield['title'],
			$profilefieldname,
			($use_default ? $profilefield['data'] : $userfield["$fieldname"]),
			0
		);
	}
	else if ($profilefield['type'] == 'textarea')
	{
		print_textarea_row(
			$profilefield['title'],
			$profilefieldname,
			($use_default ? $profilefield['data'] : $userfield["$fieldname"]),
			$profilefield['height'],
			40,
			0
		);
	}
	else if ($profilefield['type'] == 'select')
	{
		foreach ($data AS $key => $val)
		{
			$key++;
			$selected = '';
			if ($userfield["$fieldname"])
			{
				if (trim($val) == $userfield["$fieldname"])
				{
					$selected = 'selected="selected"';
					$foundselect = 1;
				}
			}
			else if ($use_default AND $profilefield['def'] == 1 AND $key == 1)
			{
				// select the first item after space when needed
				$selected = 'selected="selected"';
				$foundselect = 1;
			}
			else if ($key == 0)
			{
				$selected = 'selected="selected"';
				$foundselect = 1;
			}
			$selectbits .= "<option value=\"$key\" $selected>$val</option>";
		}
		if ($profilefield['optional'])
		{
			if (!$foundselect AND $userfield["$fieldname"])
			{
				$optional = $userfield["$fieldname"];
			}
			$optionalfield = "<dfn>$vbphrase[other_please_specify]:</dfn><input type=\"text\" name=\"$optionalname\" class=\"bginput\" value=\"$optional\" size=\"$profilefield[size]\" maxlength=\"$profilefield[maxlength]\" tabindex=\"1\" />";
		}


		if (!$foundselect)
		{
			$selected = 'selected="selected"';
		}
		else
		{
			$selected = '';
		}

		if ($searching OR $profilefield['def'] != 2)
		{
			$blankoption = "			<option value=\"0\" $selected></option>";
		}
		else
		{
			$blankoption = "";
		}
		$output = "<select name=\"$profilefieldname\" tabindex=\"1\" class=\"bginput\">
			$blankoption
			$selectbits
			</select>
			$optionalfield";
		print_label_row($profilefield['title'], $output);

	}
	else if ($profilefield['type'] == 'radio')
	{

		$radiobits = '';
		$foundfield = 0;
		$perline = 0;
		foreach ($data AS $key => $val)
		{
			$key++;
			$checked = '';
			if (!$userfield["$fieldname"] AND $key == 1 AND $profilefield['def'] == 1 AND $use_default)
			{
				$checked = 'checked="checked"';
			}
			else if (trim($val) == $userfield["$fieldname"])
			{
				$checked = 'checked="checked"';
				$foundfield = 1;
			}
			$radiobits .= "<label for=\"rb_{$key}_$profilefieldname\"><input type=\"radio\" name=\"$profilefieldname\" value=\"$key\" id=\"rb_{$key}_$profilefieldname\" tabindex=\"1\" $checked>$val</label>";
			$perline++;
			if ($profilefield['perline'] > 0 AND $perline >= $profilefield['perline'])
			{
				$radiobits .= '<br />';
				$perline = 0;
			}
		}
		if ($profilefield['optional'])
		{
			if (!$foundfield AND $userfield["$fieldname"])
			{
				$optional = $userfield["$fieldname"];
			}
			$optionalfield = "<dfn>$vbphrase[other_please_specify]:</dfn><input type=\"text\" name=\"$optionalname\" class=\"bginput\" value=\"$optional\" size=\"$profilefield[size]\" maxlength=\"$profilefield[maxlength]\" tabindex=\"1\" />";
		}
		print_label_row($profilefield['title'], "$radiobits$optionalfield");

	}
	else if ($profilefield['type'] == 'checkbox')
	{

		$checkboxbits = '';
		$perline = 0;
		foreach ($data AS $key => $val)
		{
			if ($userfield["$fieldname"] & pow(2, $key))
			{
				$checked = 'checked="checked"';
			}
			else

			{
				$checked = '';
			}
			$key++;
			$checkboxbits .= "<label for=\"cb_{$key}_$profilefieldname\"><input type=\"checkbox\" name=\"{$profilefieldname}[]\" value=\"$key\" id=\"cb_{$key}_$profilefieldname\" tabindex=\"1\" $checked>$val</label> ";
			$perline++;
			if ($profilefield['perline'] > 0 AND $perline >= $profilefield['perline'])
			{
				$checkboxbits .= '<br />';
				$perline = 0;
			}
		}
		print_label_row($profilefield['title'], $checkboxbits);

	}
	else if ($profilefield['type'] == 'select_multiple')
	{

		$selectbits = '';
		foreach ($data AS $key => $val)
		{
			if ($userfield["$fieldname"] & pow(2,$key))
			{
				$selected = 'selected="selected"';
			}
			else

			{
				$selected = '';
			}
			$key++;
			$selectbits .= "<option value=\"$key\" $selected>$val</option>";
		}
		$output = "<select name=\"{$profilefieldname}[]\" multiple=\"multiple\" size=\"$profilefield[height]\" tabindex=\"1\" class=\"bginput\">
			$selectbits
			</select>";
		print_label_row($profilefield['title'], $output);

	}
}

// ###################### Start checkprofilefield #######################
function fetch_profilefield_sql_condition($profilefield, &$profile)
{
	global $vbulletin;

	$varname = "field$profilefield[profilefieldid]";
	$optionalvar = $varname . '_opt';
	if (isset($profile["$varname"]))
	{
		$value = $profile["$varname"];
	}
	else
	{
		$value = '';
	}
	if (isset($profile["$optionalvar"]))
	{
		$optvalue = $profile["$optionalvar"];
	}
	else
	{
		$optvalue = '';
	}
	$bitwise = 0;
	$sql = '';
	if (empty($value) AND $optvalue === '')
	{
		return '';
	}
	if (($profilefield['type'] == 'input' OR $profilefield['type'] == 'textarea') AND $value !== '')
	{
		$condition = " AND $varname LIKE '%" . $vbulletin->db->escape_string_like(htmlspecialchars_uni(trim($value))) . '%\' ';
	}
	if ($profilefield['type'] == 'radio' OR $profilefield['type'] == 'select')
	{
		if ($value == 0 AND $optvalue === '')
		{ 	// The select field was left blank!
			// and the optional field is also empty
			return '';
		}
		$data = unserialize($profilefield['data']);
		foreach($data AS $key => $val)
		{
			$key++;
			if ($key == $value)
			{
				$value = trim($val);
				$sql = " AND $varname LIKE '" . $vbulletin->db->escape_string_like($value) . '\' ';
			 	break;
			}
		}
		if ($profilefield['optional'] AND !empty($optvalue))
		{
			$sql = " AND $varname LIKE '%" . $vbulletin->db->escape_string_like(htmlspecialchars_uni(trim($optvalue))) . '%\' ';
		}
		$condition = $sql;
	}
	if (($profilefield['type'] == 'checkbox' OR $profilefield['type'] == 'select_multiple') AND is_array($value))
	{
		foreach ($value AS $key => $val)
		{
			$condition .= " AND $varname & " . pow(2, $val - 1) . ' ';
		}
	}
	return $condition;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>