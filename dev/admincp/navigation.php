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
@set_time_limit(0);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 58699 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cpuser');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminforums')
OR !can_administer('canadminstyles'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################

$vbulletin->input->clean_array_gpc('r', array(
	'navid'	=> TYPE_UINT,
	'tabid'	=> TYPE_UINT
));

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'list';
}

log_admin_action(
	'navid = ' . $vbulletin->GPC['navid'] . ', tabid = ' . $vbulletin->GPC['tabid']
);

require_once(DIR . '/includes/adminfunctions_language.php');

if ($_REQUEST['do'] == 'list')
{
	$navlist = build_navigation_list(true, $vbulletin->GPC['tabid']);

	if ($vbulletin->GPC_exists['tabid'])
	{
		if (!$navlist[$vbulletin->GPC['tabid']])
		{
			print_stop_message('invalid_tabid');
		}
	}
}
else
{
	$navlist = build_navigation_list(false, $vbulletin->GPC['tabid']);

	if ($vbulletin->GPC_exists['navid'])
	{
		$navelement = $navlist[$vbulletin->GPC['navid']];

		if (!$navelement['navid'])
		{
			print_stop_message('invalid_navid');
		}
		else
		{
			expand_navigation_state($navelement);
		}
	}
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['navigation_manager']);

($hook = vBulletinHook::fetch_hook('navigation_admin_start')) ? eval($hook) : false;

if ($_REQUEST['do'] == 'list')
{
	?>
	<script type="text/javascript">
	<!--
	function process_action(navid)
	{
		if (navid == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['error']); ?>');
			return;
		}
		else
		{
			action = eval("document.cpform.action_" + navid + ".options[document.cpform.action_" + navid + ".selectedIndex].value");
			document.cpform.reset();
		}

		switch (action)
		{
			case '':
				return false;
				break;
			case 'addm':
				window.location = "navigation.php?<?php echo ($vbulletin->session->vars['sessionhash'] ? $vbulletin->session->vars['sessionhash'].'&' : ''); ?>do=add&type=menu&navid=" + navid;
				break;
			case 'addl':
				window.location = "navigation.php?<?php echo ($vbulletin->session->vars['sessionhash'] ? $vbulletin->session->vars['sessionhash'].'&' : ''); ?>do=add&type=link&navid=" + navid;
				break;
			case 'edit':
				window.location = "navigation.php?<?php echo ($vbulletin->session->vars['sessionhash'] ? $vbulletin->session->vars['sessionhash'].'&' : ''); ?>do=edit&navid=" + navid;
				break;
			case 'move':
				window.location = "navigation.php?<?php echo ($vbulletin->session->vars['sessionhash'] ? $vbulletin->session->vars['sessionhash'].'&' : ''); ?>do=move&navid=" + navid;
				break;
			case 'delete':
				window.location = "navigation.php?<?php echo ($vbulletin->session->vars['sessionhash'] ? $vbulletin->session->vars['sessionhash'].'&' : ''); ?>do=delete&navid=" + navid;
				break;
			case 'default':
				window.location = "navigation.php?<?php echo ($vbulletin->session->vars['sessionhash'] ? $vbulletin->session->vars['sessionhash'].'&' : ''); ?>do=default&navid=" + navid;
				break;
			default:
				alert('<?php echo addslashes_js($vbphrase['error']); ?>');
				break;
		}
	}

	function setCheckBox(id, prefix, checked)
	{
		element = document.getElementById(id);
		if (element)
		{
			element.value = checked ? 1 : 2 ;
		}

		if (prefix)
		{
			setCheckBoxes(prefix, checked);
		}
	}

	function setCheckBoxes(prefix, checked)
	{
		plen = prefix.length;
		elements = document.getElementsByTagName('input');
		for (i = 0; i < elements.length; i++)
		{
			box = elements[i];
			if (box && box.id)
			{
				check = box.id.substring(0,plen);
				if (prefix == check)
				{
					vid = 'v' + box.id;
					box.disabled = !checked;

					element = document.getElementById(vid);

					if (element)
					{
						element.value = 3 ;
					}
				}
			}
		}
	}

	//-->
	</script>
	<?php

	$limit = 10;
	$productlist = fetch_product_list();
	$counts = get_navigation_counts($navlist, false);

	print_form_header('navigation', 'update');
	print_table_header($vbphrase['navigation_manager'], 6);
	print_description_row($vbphrase['if_you_make_changes'], 0, 6);
	construct_hidden_code('tabid', $vbulletin->GPC['tabid']);

	foreach ($counts AS $key => $value)
	{
		if ($value <= $limit)
		{
			continue;
		}

		if ($key == '#')
		{
			print_description_row(construct_phrase($vbphrase['too_many_active_tabs'], $value), 0, 6);
		}
		else
		{
			print_description_row(construct_phrase($vbphrase['element_x_y_too_many_active'], $vbphrase[$navlist[$key]['navtype']], $navlist[$key]['text'], $limit), 0, 6);
		}

		$counts[$id]++;
	}

	$uid = array();
	$tabstate = 0; // Tab Disabled state.
	$menustate = 0; // Menu Disabled state.
	$default = get_navigation_default($navlist);

	foreach($navlist AS $key => $navelement)
	{
		$cell = array();

		$navoptions = array(
			'edit'  => $vbphrase['edit'],
		);

		if ($navelement['navtype'] == 'tab')
		{
			$navoptions['addl'] = $vbphrase['add_new_link'];
			$navoptions['addm'] = $vbphrase['add_new_menu'];
			if ($navelement['name'] != $default)
			{
				$navoptions['default'] = $vbphrase['set_tab_default'];
			}

			$xkey = '0_' . $navelement['parentid'];
			$cbkey = 'active' . '_' . $navelement['root'];
		}
		else if ($navelement['navtype'] == 'menu')
		{
			$navoptions['addl'] = $vbphrase['add_new_link'];
			$navoptions['move'] = $vbphrase['move'];

			$xkey = $navelement['root'] . '_' . $navelement['parentid'];
			$cbkey = 'active' . '_' . $navelement['root'] . '_' . $navelement['navid'];
		}
		else // Link
		{
			$navoptions['move'] = $vbphrase['move'];
			if ($navelement['name'] != $default)
			{
				$navoptions['default'] = $vbphrase['set_tab_default'];
			}

			$cbkey = '';
			$xkey = $navelement['root'] . '_' . $navelement['parentid'];
		}

		$uid[$xkey]++;
		$boxkey = 'active' . '_' . $xkey . '_' . $uid[$xkey];
		$vboxkey = 'vactive' . '_' . $xkey . '_' . $uid[$xkey];

		if (!$navelement['protected'] AND $navelement['name'] != $default)
		{
			$navoptions['delete'] = $vbphrase['delete'];
		}

		if ($navelement['navtype'] == 'tab')
		{
			$tabstate = 0;
			$menustate = 0;
		}
		else if ($navelement['navtype'] == 'menu')
		{
			$menustate = 0;
		}

		$cell[] =  build_element_cell(($vbulletin->debug ? $navelement['name'] : ''), $navelement['text'], $navelement['level'],
				true, $vbphrase[$navelement['navtype']], ($navelement['navtype'] == 'tab' ? 'navigation.php' : ''), $navelement['url'],
				$navelement['navid'] != $vbulletin->GPC['tabid'] ? 'list&amp;tabid='.$navelement['navid'] : '', $vbulletin->session->vars['sessionurl']);
		$cell[] =  build_checkbox_cell('active['.$key.']', 1, $boxkey, $navelement['active'], $tabstate OR $menustate, "setCheckBox('$vboxkey','$cbkey',this.checked)");
		$cell[] =  build_text_input_cell('order['.$key.']', $navelement['displayorder'], $size = 3, $vbphrase['edit_display_order']);
		$cell[] =  build_display_cell(($navelement['navtype'] != 'menu' ? ($navelement['default'] ? $vbphrase['yes'] : '--') : $vbphrase['n_a']), $navelement['default']);
		$cell[] =  build_display_cell($productlist[$navelement['productid']], false, false, $vbulletin->products[$navelement['productid']] != 1);
		$cell[] =  build_action_cell('action_'.$key, $navoptions, 'process_action('.$key.')', $vbphrase['go'], true, true);

		if ($navelement['navtype'] == 'tab')
		{
			print_cells_row(array(
				$vbphrase['tab'],
				$vbphrase['active'],
				$vbphrase['display_order'],
				$vbphrase['default'],
				$vbphrase['product'],
				$vbphrase['controls'],
			), 1, 'tcat');
		}

		// Output the line.
		print_cells_row($cell, false, false, 0, 'top', false, false, $navelement['navtype'].'row');

		if ($navelement['navtype'] == 'tab' AND $navelement['navid'] == $vbulletin->GPC['tabid'] AND !$navelement['links'])
		{
			print_description_row($vbphrase['tab_has_no_elements'], 0, 6);
		}

		if ($navelement['active'] != 1 OR $vbulletin->products[$navelement['productid']] != 1)
		{
			if ($navelement['navtype'] == 'tab')
			{
				$tabstate = 1;
			}
			else if ($navelement['navtype'] == 'menu')
			{
				$menustate = 1;
			}
		}
	}

	print_table_footer(6, "<input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['save'] . "\" accesskey=\"s\" />"
		. construct_button_code($vbphrase['add_new_tab'], "navigation.php?" . $vbulletin->session->vars['sessionurl'] . "do=add&type=tab"));

	($hook = vBulletinHook::fetch_hook('navigation_admin_list')) ? eval($hook) : false;
}

if ($_REQUEST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'order' 	=> TYPE_ARRAY_INT,
		'active'	=> TYPE_ARRAY_BOOL,
		'vactive'	=> TYPE_ARRAY,
	));

	$active = array();
	$display = array();

	foreach($vbulletin->GPC['order'] AS $key => $order)
	{
		$display[$order][] = $key;
		/* Deal with missing active values.
		This could be because the checkbox is unticked or
		disabled, we try and track this state in vactive */
		if (!isset($vbulletin->GPC['active'][$key]))
		{
			$vbulletin->GPC['active'][$key] = 0;

			switch ($vbulletin->GPC['vactive'][$key])
			{
				case 1: // Ticked
					$vbulletin->GPC['active'][$key] = 1;
					break;

				case 2: // Unticked
					$vbulletin->GPC['active'][$key] = 0;
					break;

				case 3: // Disabled
					unset($vbulletin->GPC['active'][$key]);
					break;

				default: // Unchanged.
					break;
			}
		}
	}

	foreach($vbulletin->GPC['active'] AS $key => $status)
	{
		$active[$status][] = $key;
	}

	foreach($display AS $value => $keys)
	{
		$sql = "
			UPDATE " . TABLE_PREFIX . "navigation
			SET displayorder = $value,
			state = state | " . $vbulletin->bf_misc_navstate['edited'] . "
			WHERE displayorder != $value
			AND navid IN (" . implode(',',$keys) .
		")";

		$db->query_write($sql);
	}

	foreach($active AS $value => $keys)
	{
		$sql = "
			UPDATE " . TABLE_PREFIX . "navigation
			SET state = state | " . $vbulletin->bf_misc_navstate['edited'] . ",
			state = state " . ($value ? '| ' : ' & ~') . $vbulletin->bf_misc_navstate['active'] . "
			WHERE (state & " . $vbulletin->bf_misc_navstate['active'] . ") != " .
			($value ? $vbulletin->bf_misc_navstate['active'] : 0) . "
			AND navid IN (" . implode(',',$keys) .
		")";

		$db->query_write($sql);
	}

	$taburl = $vbulletin->GPC['tabid'] ? '&amp;tabid=' . $vbulletin->GPC['tabid'] : '';

	($hook = vBulletinHook::fetch_hook('navigation_admin_update')) ? eval($hook) : false;

	build_navigation_datastore();
	define('CP_REDIRECT', 'navigation.php?do=list' . $taburl);
	print_stop_message('saved_settings_successfully');

}

if ($_REQUEST['do'] == 'edit')
{
	$products = fetch_product_list(false, true, false, $navelement['productid']);

	print_form_header('navigation', 'doedit');
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase[$navelement['navtype']], $navelement['text'], $navelement['navid']));
	construct_hidden_code('navid', $navelement['navid']);
	construct_hidden_code('tabid', $navelement['root']);

	print_label_row($vbphrase['identity'], $navelement['name']);
	print_yes_no_row($vbphrase['active'], 'active', $navelement['active']);
	if ($navelement['protected'] OR !$vbulletin->debug) // Should use 'move' function.
	{
		construct_hidden_code('product', $navelement['productid']);
		print_label_row($vbphrase['product'],$products[$navelement['productid']]);
	}
	else
	{
		print_select_row($vbphrase['product'], 'product', $products, $navelement['productid']);
	}
	print_input_row($vbphrase['title'], 'title', $navelement['text'], true, 50);
	if ($navelement['navtype'] != 'menu')
	{
		print_input_row($vbphrase['target'], 'url', $navelement['url'], true, 50, 500);
	}
	else
	{
		construct_hidden_code('url', '');
	}

	if ($navelement['navtype'] == 'tab')
	{
		$menulist = array('' => $vbphrase['use_target_url']);
		foreach($navelement['links'] AS $id => $element)
		{
			if ($element['navtype'] == 'menu' AND $element['active'])
			{
				$menulist[$element['name']] = $element['text'];
			}
		}
		if (count($menulist) == 1)
		{
			print_label_row($vbphrase['target_menu'], construct_phrase($vbphrase['create_nav_sub_menu'], $navelement['navid']));
		}
		else
		{
			print_select_row($vbphrase['target_menu'], 'menuid', $menulist, $navelement['menuid']);
		}
	}

	print_input_row($vbphrase['display_order'], 'order', $navelement['displayorder'], true, 5);
	print_input_row($vbphrase['showperm'], 'show', $navelement['showperm'], true, 30);
	if ($navelement['navtype'] == 'tab')
	{
		print_yes_no_row($vbphrase['usetabid'], 'usetabid', $navelement['usetabid']);
		print_input_row($vbphrase['scripts'], 'scripts', $navelement['scripts'], true, 30);
	}
	else
	{
		construct_hidden_code('usetabid', 0);
		construct_hidden_code('scripts', '');
	}
	print_yes_no_row($vbphrase['newpage'], 'newpage', $navelement['newpage']);

	($hook = vBulletinHook::fetch_hook('navigation_admin_edit')) ? eval($hook) : false;

	print_submit_row($vbphrase['save']);
}

if ($_REQUEST['do'] == 'doedit')
{
	$products = fetch_product_list(false, true, false, $navelement['productid']);

	$vbulletin->input->clean_array_gpc('r', array(
		'order'    => TYPE_INT,
		'active'   => TYPE_BOOL,
		'product'  => TYPE_STR,
		'show'     => TYPE_STR,
		'url'      => TYPE_STR,
		'title'    => TYPE_STR,
		'usetabid' => TYPE_BOOL,
		'scripts'  => TYPE_STR,
		'newpage'  => TYPE_BOOL,
		'menuid'   => TYPE_STR,
	));

	if ($vbulletin->GPC['usetabid'] AND $navelement['navtype'] != 'tab')
	{
		print_stop_message('tabs_only');
	}

	$showvars = explode('.', $vbulletin->GPC['show']);

	foreach($showvars AS $showvar)
	{
		preg_match_all('#^[0-9].*|\W#i', $showvar, $matches);
		$check = trim(str_replace(' ', '#', implode('',$matches[0])));

		if ($check)
		{
			print_stop_message('invalid_showvar');
		}
	}

	if (strlen($vbulletin->GPC['show']) > 30)
	{
		print_stop_message('invalid_showvar');
	}

	if ($navelement['navtype'] == 'menu' AND $vbulletin->GPC['url'])
	{
		print_stop_message('menu_cannot_have_url');
	}

	$scripts = explode('.', $vbulletin->GPC['scripts']);

	foreach($scripts AS $script)
	{
		preg_match_all('#^[0-9].*|\W#i', $script, $matches);
		$check = trim(str_replace(' ','#',implode('',$matches[0])));

		if ($check)
		{
			print_stop_message('invalid_script');
		}
	}

	if (strlen($vbulletin->GPC['show']) > 30)
	{
		print_stop_message('invalid_script');
	}

	if (!$vbulletin->GPC['title'] OR vbstrlen($vbulletin->GPC['title']) > 50)
	{
		print_stop_message('invalid_title');
	}

	if ($vbulletin->GPC['product'] != $navelement['productid']
			AND (!in_array($vbulletin->GPC['product'], array_keys($products)) OR !$vbulletin->debug))
	{
		print_stop_message('invalid_productid');
	}

	$edited = 0;
	if ($vbulletin->GPC['order'] != $navelement['displayorder']
		OR $vbulletin->GPC['active'] != $navelement['active']
		OR $vbulletin->GPC['product'] != $navelement['productid']
		OR $vbulletin->GPC['show'] != $navelement['showperm']
		OR $vbulletin->GPC['url'] != $navelement['url']
		OR $vbulletin->GPC['usetabid'] != $navelement['usetabid']
		OR $vbulletin->GPC['scripts'] != $navelement['scripts']
		OR $vbulletin->GPC['newpage'] != $navelement['newpage']
		OR $vbulletin->GPC['menuid'] != $navelement['menuid']
	)
	{
		$edited = 1;
		$sqlset = '';
		$navelement['edited'] = $edited;
		$navelement['active'] = $vbulletin->GPC['active'];
		$navelement['usetabid'] = $vbulletin->GPC['usetabid'];
		$navelement['newpage'] = $vbulletin->GPC['newpage'];
	}

	($hook = vBulletinHook::fetch_hook('navigation_admin_doedit')) ? eval($hook) : false;

	collapse_navigation_state($navelement);

	if ($edited)
	{
		$sql = "
			UPDATE " . TABLE_PREFIX . "navigation
			SET state = ". $navelement['state'] . ",
			displayorder = ". $vbulletin->GPC['order'] . ",
			productid = '" . $db->escape_string($vbulletin->GPC['product']) . "',
			scripts = '" . $db->escape_string($vbulletin->GPC['scripts']) . "',
			showperm = '" . $db->escape_string($vbulletin->GPC['show']) . "',
			url = '" . $db->escape_string($vbulletin->GPC['url']) . "',
			menuid = '" . $db->escape_string($vbulletin->GPC['menuid']) . "'
			$sqlset
			WHERE navid = " . $navelement['navid'] . "
		";

		$db->query_write($sql);
	}

	if ($vbulletin->GPC['title'] != $navelement['text'])
	{
		$phrasename = 'vb_navigation_'.$navelement['navtype'].'_'.$navelement['name'].'_text';

		$sqldata = '(' .
			''	. '0,' .
			"'" . $db->escape_string($phrasename) . "'," .
			"'" . $db->escape_string($vbulletin->GPC['title']) . "'," .
			"'" . $db->escape_string($vbulletin->GPC['product']) . "'," .
			''	. "'global'," .
			"'" . $db->escape_string($vbulletin->options['templateversion']) . "'," .
			"'" . $db->escape_string($vbulletin->userinfo['username']) . "'," .
			''	. TIMENOW .
		')';

		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "phrase
			(languageid, varname, text, product, fieldname, version, username, dateline)
			VALUES $sqldata
		");

		build_language();
	}

	build_navigation_datastore();
	$taburl = $navelement['root'] ? '&amp;tabid=' . $navelement['root'] : '';
	define('CP_REDIRECT', 'navigation.php?do=list' . $taburl);
	print_stop_message('saved_settings_successfully');
}

if ($_REQUEST['do'] == 'move')
{
	$tabs = get_navigation_parents($navlist, array('tab'));
	$parents = get_navigation_parents($navlist, array('tab','menu'));
	$products = fetch_product_list(false, true, false, $navelement['productid']);

	if ($navelement['navtype'] == 'tab')
	{
		print_stop_message('cannot_move_tabs');
	}

	print_form_header('navigation', 'domove');
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase[$navelement['navtype']], $navelement['text'], $navelement['navid']));
	construct_hidden_code('navid', $navelement['navid']);
	construct_hidden_code('tabid', $navelement['root']);

	print_label_row($vbphrase['identity'], $navelement['name']);
	if ($navelement['protected'])
	{
		construct_hidden_code('product', $navelement['productid']);
		print_label_row($vbphrase['product'],$products[$navelement['productid']]);
	}
	else
	{
		print_select_row($vbphrase['product'], 'product', $products, $navelement['productid']);
	}
	print_select_row($vbphrase['parent'], 'parent', ($navelement['navtype'] == 'link' ? $parents : $tabs), $navelement['parent']);
	print_input_row($vbphrase['display_order'], 'order', $navelement['displayorder'], true, 5);

	($hook = vBulletinHook::fetch_hook('navigation_admin_move')) ? eval($hook) : false;

	print_submit_row($vbphrase['save']);
}

if ($_REQUEST['do'] == 'domove')
{
	$roots = get_navigation_roots($navlist);
	$tabs = get_navigation_parents($navlist, array('tab'));
	$parents = get_navigation_parents($navlist, array('tab','menu'));
	$products = fetch_product_list(false, true, false, $navelement['productid']);

	$vbulletin->input->clean_array_gpc('r', array(
		'order'   => TYPE_INT,
		'parent'  => TYPE_STR,
		'product' => TYPE_STR,
	));

	if ($navelement['navtype'] == 'tab')
	{
		print_stop_message('cannot_move_tabs');
	}

	if ($vbulletin->GPC['parent'] != $navelement['parent']
	AND !in_array($vbulletin->GPC['parent'], ($navelement['navtype'] == 'link' ? array_keys($parents) : array_keys($tabs))))
	{
		print_stop_message('invalid_parent');
	}

	if ($vbulletin->GPC['product'] != $navelement['productid']
	AND !in_array($vbulletin->GPC['product'], array_keys($products)))
	{
		print_stop_message('invalid_productid');
	}

	$edited = 0;
	if ($vbulletin->GPC['order'] != $navelement['displayorder']
		OR $vbulletin->GPC['parent'] != $navelement['parent']
		OR $vbulletin->GPC['product'] != $navelement['productid']
	)
	{
		$edited = 1;
		$sqlset = '';
		$navelement['edited'] = $edited;
	}

	($hook = vBulletinHook::fetch_hook('navigation_admin_domove')) ? eval($hook) : false;

	collapse_navigation_state($navelement);

	if ($edited)
	{
		$sql = "
			UPDATE " . TABLE_PREFIX . "navigation
			SET state = ". $navelement['state'] . ",
			displayorder = ". $vbulletin->GPC['order'] . ",
			parent = '" . $db->escape_string($vbulletin->GPC['parent']) . "',
			productid = '" . $db->escape_string($vbulletin->GPC['product']) . "'
			$sqlset
			WHERE navid = " . $navelement['navid'] . "
		";

		$db->query_write($sql);
	}

	if ($vbulletin->GPC['parent'] != $navelement['parent'])
	{
		$navelement['root'] = $roots[$vbulletin->GPC['parent']]; // We have moved.
	}

	build_navigation_datastore();
	$taburl = $navelement['root'] ? '&amp;tabid=' . $navelement['root'] : '';
	define('CP_REDIRECT', 'navigation.php?do=list' . $taburl);
	print_stop_message('saved_settings_successfully');
}

if ($_REQUEST['do'] == 'delete')
{
	($hook = vBulletinHook::fetch_hook('navigation_admin_delete')) ? eval($hook) : false;

	print_delete_confirmation('navigation', $vbulletin->GPC['navid'], 'navigation', 'dodelete', 'element', 0, '', 'name', 'navid');
}

if ($_REQUEST['do'] == 'dodelete')
{
	if ($navelement['protected'])
	{
		print_stop_message('cannot_delete_protected');
	}

	if ($navelement['deleted'])
	{
		print_stop_message('error_already_deleted');
	}

	$sqlset = '';

	$navelement['deleted'] = 1;

	($hook = vBulletinHook::fetch_hook('navigation_admin_dodelete')) ? eval($hook) : false;

	collapse_navigation_state($navelement);

	$sql = "
		UPDATE " . TABLE_PREFIX . "navigation
		SET state = ". $navelement['state'] . "
		$sqlset
		WHERE navid = " . $navelement['navid'] . "
	";

	$db->query_write($sql);

	if ($navelement['navid'] == $navelement['root'])
	{
		$navelement['root'] = 0; // cant redirect to what we just deleted.
	}

	build_navigation_datastore();
	$taburl = $navelement['root'] ? '&amp;tabid=' . $navelement['root'] : '';
	define('CP_REDIRECT', 'navigation.php?do=list' . $taburl);
	print_stop_message('saved_settings_successfully');
}

if ($_REQUEST['do'] == 'add')
{
	$roots = get_navigation_roots($navlist);
	$tabs = get_navigation_parents($navlist, array('tab'));
	$parents = get_navigation_parents($navlist, array('tab','menu'));
	$products = fetch_product_list(false, true, false);
	$ordermax = get_navigation_ordermax($navlist, $byname = true);

	$vbulletin->input->clean_array_gpc('r', array(
		'type'	=> TYPE_STR,
	));

	if ($vbulletin->GPC['type'] == 'tab')
	{
		$pshow = false;
		$tdesc = $vbphrase['add_new_tab'];
		$navelement['name'] = '#'; // For max display order array
	}
	else if ($vbulletin->GPC['type'] == 'menu')
	{
		$pshow = $tabs;
		$tdesc = $vbphrase['add_new_menu'];
	}
	else if ($vbulletin->GPC['type'] == 'link')
	{
		$pshow = $parents;
		$tdesc = $vbphrase['add_new_link'];
	}
	else
	{
		print_stop_message('invalid_type');
	}

	$count = 0;
	$name = $vbulletin->GPC['type'];
	$name .= '_' . strtolower(substr(vb_base64_encode(TIMENOW),4,4));

	do
	{
		$count++;
		if ($count > 100)
		{
			/* Something is very wrong */
			print_stop_message('internal_error','20');
		}

		$name .= '_' . rand(100,999);
	}
	while (isset($roots[$name]));

	print_form_header('navigation', 'doadd');
	print_table_header($tdesc);
	construct_hidden_code('type', $vbulletin->GPC['type']);
	construct_hidden_code('tabid', $navelement['root']);
	construct_hidden_code('identity', $name);
	print_label_row($vbphrase['identity'], $name);
	if (!$vbulletin->debug)
	{
		construct_hidden_code('product', 'vbulletin');
		print_label_row($vbphrase['product'], 'vbulletin');
	}
	else
	{
		print_select_row($vbphrase['product'], 'product', $products, $navelement['productid']);
	}
	if ($pshow)
	{
		print_select_row($vbphrase['parent'], 'parent', $pshow, $navelement['name']);
	}
	else
	{
		construct_hidden_code('parent', '');
	}
	print_input_row($vbphrase['title'], 'title', '', true, 50);
	if ($vbulletin->GPC['type'] != 'menu')
	{
		print_input_row($vbphrase['target'], 'url', '', true, 50, 500);
	}
	else
	{
		construct_hidden_code('url', '');
	}
	print_yes_no_row($vbphrase['active'], 'active', 0);
	print_input_row($vbphrase['display_order'], 'order', $ordermax[$navelement['name']] + 10, true, 5);
	print_input_row($vbphrase['showperm'], 'show', '', true, 30);
	if ($vbulletin->GPC['type'] == 'tab')
	{
		print_yes_no_row($vbphrase['usetabid'], 'usetabid', $navelement['usetabid']);
		print_input_row($vbphrase['scripts'], 'scripts', $navelement['scripts'], true, 30);
	}
	else
	{
		construct_hidden_code('usetabid', 0);
		construct_hidden_code('scripts', '');
	}
	print_yes_no_row($vbphrase['newpage'], 'newpage', $navelement['newpage']);

	($hook = vBulletinHook::fetch_hook('navigation_admin_add')) ? eval($hook) : false;

	print_submit_row($vbphrase['save']);
}

if ($_REQUEST['do'] == 'doadd')
{
	$tabs = get_navigation_parents($navlist, array('tab'));
	$parents = get_navigation_parents($navlist, array('tab','menu'));
	$products = fetch_product_list(false, true, false);

	$vbulletin->input->clean_array_gpc('r', array(
		'order' 	=> TYPE_INT,
		'parent'	=> TYPE_STR,
		'product'	=> TYPE_STR,
		'type' 		=> TYPE_STR,
		'active'	=> TYPE_BOOL,
		'identity'	=> TYPE_STR,
		'show'		=> TYPE_STR,
		'url'		=> TYPE_STR,
		'title'		=> TYPE_STR,
		'usetabid'	=> TYPE_BOOL,
		'scripts'	=> TYPE_STR,
		'newpage'	=> TYPE_BOOL,
	));

	//-- checks --//

	if ($vbulletin->GPC['type'] == 'tab' AND $vbulletin->GPC['parent'])
	{
		print_stop_message('tabs_cannot_have_parent');
	}

	if ($vbulletin->GPC['type'] == 'menu' AND $vbulletin->GPC['url'])
	{
		print_stop_message('menu_cannot_have_url');
	}

	if (!in_array($vbulletin->GPC['type'], array('tab','menu','link')))
	{
		print_stop_message('invalid_type');
	}

	if ($vbulletin->GPC['usetabid'] AND $vbulletin->GPC['type'] != 'tab')
	{
		print_stop_message('tabs_only');
	}

	$showvars = explode('.', $vbulletin->GPC['show']);

	foreach($showvars AS $showvar)
	{
		preg_match_all('#^[0-9].*|\W#i', $showvar, $matches);
		$check = trim(str_replace(' ','#',implode('',$matches[0])));

		if ($check)
		{
			print_stop_message('invalid_showvar');
		}
	}

	if (strlen($vbulletin->GPC['show']) > 30)
	{
		print_stop_message('invalid_showvar');
	}

	$scripts = explode('.', $vbulletin->GPC['scripts']);

	foreach($scripts AS $script)
	{
		preg_match_all('#^[0-9].*|\W#i', $script, $matches);
		$check = trim(str_replace(' ','#',implode('',$matches[0])));

		if ($check)
		{
			print_stop_message('invalid_script');
		}
	}

	if (strlen($vbulletin->GPC['show']) > 30)
	{
		print_stop_message('invalid_script');
	}

	preg_match_all('#^[0-9].*|\W#i', $vbulletin->GPC['identity'], $matches);
	$check = trim(str_replace(' ','#',implode('',$matches[0])));

	if ($check OR strlen($vbulletin->GPC['identity']) > 20)
	{
		print_stop_message('invalid_identity');
	}

	if (!$vbulletin->GPC['title'] OR vbstrlen($vbulletin->GPC['title']) > 50)
	{
		print_stop_message('invalid_title');
	}

	if ($vbulletin->GPC['type'] != 'menu' AND
	(!$vbulletin->GPC['url'] OR strlen($vbulletin->GPC['url']) > 500))
	{
		print_stop_message('invalid_url');
	}

	if ($vbulletin->GPC['type'] != 'tab' AND !in_array($vbulletin->GPC['parent'],
	($vbulletin->GPC['type'] == 'link' ? array_keys($parents) : array_keys($tabs))))
	{
		print_stop_message('invalid_parent');
	}

	if (!in_array($vbulletin->GPC['product'], array_keys($products)))
	{
		print_stop_message('invalid_productid');
	}

	//-- end checks --//

	$sqlset = $sqlfields = '';

	($hook = vBulletinHook::fetch_hook('navigation_admin_doadd')) ? eval($hook) : false;

	collapse_navigation_state($vbulletin->GPC);

	$sqldata = '(' .
		"'" . $db->escape_string($vbulletin->GPC['identity']) . "'," .
		"'" . $db->escape_string($vbulletin->GPC['product']) . "'," .
		"'" . $db->escape_string($vbulletin->GPC['type']) . "'," .
		''	. $vbulletin->GPC['state'] .',' .
		''	. $vbulletin->GPC['order'] .',' .
		"'" . $db->escape_string($vbulletin->GPC['parent']) . "'," .
		"'" . $db->escape_string($vbulletin->GPC['scripts']) . "'," .
		"'" . $db->escape_string($vbulletin->GPC['show']) . "'," .
		"'" . $db->escape_string($vbulletin->GPC['url']) . "'," .
		"'" . $db->escape_string($vbulletin->options['templateversion']) . "'," .
		"'" . $db->escape_string($vbulletin->userinfo['username']) . "'," .
		''	. TIMENOW . $sqlset .
	')';

	$ok = $db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "navigation
		(name, productid, navtype, state, displayorder, parent,
		scripts, showperm, url, version, username, dateline $sqlfields)
		VALUES $sqldata
	");

	if ($ok)
	{
		$phrasename = 'vb_navigation_'.$vbulletin->GPC['type'].'_'.$vbulletin->GPC['identity'].'_text';

		$sqldata = '(' .
			''	. '0,' .
			"'" . $db->escape_string($phrasename) . "'," .
			"'" . $db->escape_string($vbulletin->GPC['title']) . "'," .
			"'" . $db->escape_string($vbulletin->GPC['product']) . "'," .
			''	. "'global'," .
			"'" . $db->escape_string($vbulletin->options['templateversion']) . "'," .
			"'" . $db->escape_string($vbulletin->userinfo['username']) . "'," .
			''	. TIMENOW .
		')';

		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "phrase
			(languageid, varname, text, product, fieldname, version, username, dateline)
			VALUES $sqldata
		");

		build_language();
	}
	else
	{
		print_stop_message('add_failed');
	}

	build_navigation_datastore();
	$taburl = $vbulletin->GPC['tabid'] ? '&amp;tabid=' . $vbulletin->GPC['tabid'] : '';
	define('CP_REDIRECT', 'navigation.php?do=list' . $taburl);
	print_stop_message('saved_settings_successfully');
}

if ($_REQUEST['do'] == 'default')
{
	$current = $navlist[get_navigation_default($navlist, false)];
	$choices = get_navigation_parents($navlist, array('tab','link'), false);

	print_form_header('navigation', 'dodefault');
	print_table_header(construct_phrase($vbphrase['nav_default_change']));
	construct_hidden_code('oldid', $current['navid']);

	print_label_row($vbphrase['nav_default_old'],$current['text']);
	if ($vbulletin->debug)
	{
		print_select_row($vbphrase['nav_default_new'], 'newid', $choices, $navelement['navid']);
	}
	else
	{
		construct_hidden_code('newid', $navelement['navid']);
		print_label_row($vbphrase['nav_default_new'],$navelement['text'], '', 'top', null, 40);
	}
	print_yes_no_row($vbphrase['nav_default_set'], 'confirm', 1);

	($hook = vBulletinHook::fetch_hook('navigation_admin_default')) ? eval($hook) : false;

	print_submit_row($vbphrase['save']);
}

if ($_REQUEST['do'] == 'dodefault')
{
	$roots = get_navigation_roots($navlist);

	$vbulletin->input->clean_array_gpc('r', array(
		'oldid' 	=> TYPE_INT,
		'newid' 	=> TYPE_INT,
		'confirm' 	=> TYPE_INT,
	));

	$existing = get_navigation_default($navlist, false);

	$current = $navlist[$vbulletin->GPC['oldid']];
	$proposed = $navlist[$vbulletin->GPC['newid']];

	expand_navigation_state($current);
	expand_navigation_state($proposed);

	if ($vbulletin->GPC['confirm'])
	{
		if ($current['navid'] != $existing)
		{
			print_stop_message('invalid_current_default');
		}

		if ($current['navid'] != $vbulletin->GPC['oldid'])
		{
			print_stop_message('invalid_current_default');
		}

		if ($proposed['navid'] != $vbulletin->GPC['newid'])
		{
			print_stop_message('invalid_new_default');
		}

		$sqlset1 = $sqlset2 = '';

		$current['edited'] = 1;
		$proposed['edited'] = 1;

		$current['default'] = 0;
		$proposed['default'] = 1;

		($hook = vBulletinHook::fetch_hook('navigation_admin_dodefault')) ? eval($hook) : false;

		collapse_navigation_state($current);
		collapse_navigation_state($proposed);

		if ($current['navid'])
		{
			$sql = "
				UPDATE " . TABLE_PREFIX . "navigation
				SET state = ". $current['state'] . "
				$sqlset1
				WHERE navid = " . $current['navid'] . "
			";
			$db->query_write($sql);
		}

		$sql = "
			UPDATE " . TABLE_PREFIX . "navigation
			SET state = ". $proposed['state'] . "
			$sqlset2
			WHERE navid = " . $proposed['navid'] . "
		";

		$db->query_write($sql);
	}

	$parent = $roots[$proposed['name']];

	build_navigation_datastore();
	$taburl = $parent ? '&amp;tabid=' . $parent : '';
	define('CP_REDIRECT', 'navigation.php?do=list' . $taburl);
	print_stop_message('saved_settings_successfully');
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # CVS: $RCSfile$ - $Revision: 58699 $
|| ####################################################################
\*======================================================================*/
?>
