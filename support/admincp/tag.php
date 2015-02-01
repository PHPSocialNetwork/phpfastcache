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
ignore_user_abort(true);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 28048 $');

// ################### DEFINE LOCAL SCRIPT CONSTANTS ######################

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('tagscategories');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_taggablecontent.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadmintags'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################


if (empty($_REQUEST['do']))
{
	$action = 'modify';
}
else
{
	$action = $_REQUEST['do'];
}

//I'm not sure how much we need this, but the old branch logic checks some
//actions against REQUEST and some against POST. This should maintain
//equivilent behavior (error instead of explicit fallthrough;
$post_only_actions = array('taginsert', 'tagclear', 'tagkill', 'tagmerge', 'tagdomerge');
if (in_array($action, $post_only_actions) AND empty($_POST['do']))
{
	exit;
}

$dispatch = array
(
	'taginsert' => 'taginsert',
	'tagclear' => 'tagclear',
	'tagmerge' => 'tagmerge',
	'tagdomerge' => 'tagdomerge',
	'tagdopromote' => 'tagdopromote',
	'tagkill' => 'tagkill',
	'tags' => 'displaytags', //legacy from when this was part of threads
	'modify' => 'displaytags',
);

if (array_key_exists($action, $dispatch))
{
	// these three actions need to set cookies, and will print the cp header themselves.
	if (!in_array($action, array('tagclear', 'tagdomerge', 'tagkill')))
	{
		print_cp_header($vbphrase['tag_manager']);
	}
	tagcp_init_tag_action();
	call_user_func($dispatch["$action"]);
	print_cp_footer();
}
else
{

}

// ########################################################################
// some utility function for the actions
function tagcp_init_tag_action()
{
	global $vbulletin;

	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber' => TYPE_UINT,
		'sort'       => TYPE_NOHTML
	));

	define('CP_REDIRECT', 'tag.php?do=tags&page=' . $vbulletin->GPC['pagenumber'] . '&sort=' . $vbulletin->GPC['sort']);
}


function tagcp_fetch_tag_list()
{
	global $vbulletin;

	$vbulletin->input->clean_array_gpc('p', array(
		'tag'               => TYPE_ARRAY_KEYS_INT
	));

	$vbulletin->input->clean_array_gpc('c', array(
		'vbulletin_inlinetag' => TYPE_STR,
	));

	$taglist = $vbulletin->GPC['tag'];

	if (!empty($vbulletin->GPC['vbulletin_inlinetag']))
	{
		$cookielist = explode('-', $vbulletin->GPC['vbulletin_inlinetag']);
		$cookielist = $vbulletin->input->clean($cookielist, TYPE_ARRAY_UINT);

		$taglist = array_unique(array_merge($taglist, $cookielist));
	}

	return $taglist;
}


// ########################################################################
// handled inserting a form
function taginsert()
{
	global $vbulletin, $vphrase;

	$vbulletin->input->clean_array_gpc('p', array(
		'tagtext' => TYPE_NOHTML
	));


	$tagdm = datamanager_init('tag', $vbulletin, ERRTYPE_ARRAY);
	if ($tagdm->fetch_by_tagtext($vbulletin->GPC['tagtext'])) {
		print_stop_message('tag_exists');
	}

	$errors = array();
	$valid = vB_Taggable_Content_Item::filter_tag_list(array($vbulletin->GPC['tagtext']), $errors);

	if ($errors)
	{
		print_stop_message('generic_error_x', implode('<br /><br />', $errors));
	}

	if (!empty($valid))
	{
		$tagdm->set('tagtext', $valid[0]);
		$tagdm->set('dateline', TIMENOW);

		if ($tagdm->errors)
		{
			print_stop_message('generic_error_x', implode('<br /><br />', $tagdm->errors));
		}

		$tagdm->save();
	}

	print_stop_message('tag_saved');
}

// ########################################################################
// clear the tag selection cookie
function tagclear()
{
	global $vbphrase;

	setcookie('vbulletin_inlinetag', '', TIMENOW - 3600, '/');

	print_cp_header($vbphrase['tag_manager']);
	displaytags();
}

// ########################################################################

function tagmerge()
{
	global $vbulletin, $vbphrase, $db;

	tagcp_init_tag_action();
	$taglist = tagcp_fetch_tag_list();
	if (!sizeof($taglist))
	{
		print_stop_message('no_tags_selected');
	}

	$tags = $db->query_read("
		SELECT tagid, tagtext
		FROM " . TABLE_PREFIX . "tag
		WHERE tagid IN (" . implode(',', $taglist) . ")
		ORDER BY tagtext ASC
	");

	if (!($tag_count = $db->num_rows($tags)))
	{
		print_stop_message('no_tags_selected');
	}

	print_form_header();
	print_table_header($vbphrase['merge_tags'], 3);

	$columns = array('','','');
	$counter = 0;
	while ($tag = $db->fetch_array($tags))
	{
		$column = floor($counter++ / ceil($tag_count / 3));
		$columns[$column] .= '<strong>' . $tag['tagtext'] . "</strong><br />\n";
	}

	print_description_row($vbphrase['tag_merge_description'], false, 3, '', vB_Template_Runtime::fetchStyleVar('left'));
	print_cells_row($columns, false, false, -3);
	print_table_footer();

	construct_hidden_code('page', $vbulletin->GPC['pagenumber']);
	construct_hidden_code('sort', $vbulletin->GPC['sort']);

	print_form_header('tag', 'tagdomerge');
	print_input_row($vbphrase['new_tag'], 'tagtext');
	print_submit_row($vbphrase['merge_tags'], false, 3, $vbphrase['go_back']);
}


// ########################################################################
function tagdomerge()
{
	global $vbulletin, $vbphrase, $db;

	$taglist = tagcp_fetch_tag_list();
	if (!sizeof($taglist))
	{
		print_cp_header($vbphrase['tag_manager']);
		print_stop_message('no_tags_selected');
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'tagtext' => TYPE_NOHTML
	));

	$tagtext = $vbulletin->GPC['tagtext'];

	$name_changed = false;
	$tagdm = datamanager_init('tag', $vbulletin, ERRTYPE_ARRAY);
	if (!$tagdm->fetch_by_tagtext($tagtext))
	{
		//bail on errors
		if ($tagdm->errors)
		{
			print_cp_header($vbphrase['tag_manager']);
			print_stop_message('generic_error_x', implode('<br /><br />', $tagdm->errors));
		}

		//otherwise create tag
		$errors = array();
		$valid = vB_Taggable_Content_Item::filter_tag_list(array($vbulletin->GPC['tagtext']), $errors);

		if ($errors)
		{
			print_cp_header($vbphrase['tag_manager']);
			print_stop_message('generic_error_x', implode('<br /><br />', $errors));
		}

		if (!empty($valid))
		{
			$tagdm->set('tagtext', $valid[0]);
			$tagdm->set('dateline', TIMENOW);

			if ($tagdm->errors)
			{
				print_cp_header($vbphrase['tag_manager']);
				print_stop_message('generic_error_x', implode('<br /><br />', $tagdm->errors));
			}

			$tagdm->save();
		}
	}
	else
	{
		//if the old tag and new differ only by case, then update
		if ($tagtext != $tagdm->fetch_field('tagtext') AND
			vbstrtolower($tagtext) == vbstrtolower($tagdm->fetch_field('tagtext'))
		)
		{
			$name_changed = true;
			$tagdm->set('tagtext', $tagtext);
			$tagdm->save();
		}
	}

	$targetid = $tagdm->fetch_field('tagid');
	if (!$targetid)
	{
		print_cp_header($vbphrase['tag_manager']);
		print_stop_message('no_changes_made');
	}

	// check if source and targed are the same
	if (sizeof($taglist) == 1 AND in_array($targetid, $taglist))
	{
		if ($name_changed)
		{
			print_cp_header($vbphrase['tag_manager']);
			print_stop_message('tags_edited_successfully');
		}
		else
		{
			print_cp_header($vbphrase['tag_manager']);
		 	print_stop_message('no_changes_made');
		}
	}

	if (false !== ($selected = array_search($targetid, $taglist)))
	{
		// ensure targetid is not in taglist
		unset($taglist[$selected]);
	}


	foreach ($taglist as $mergetagid)
	{
		if ($mergetagid != $targetid)
		{
			$mergetagdm = datamanager_init('tag', $vbulletin, ERRTYPE_ARRAY);
			if ($mergetagdm->fetch_by_id($mergetagid))
			{
				$mergetagdm->make_synonym($targetid);
			}
		}
	}

	// need to invalidate the search and tag cloud caches
	build_datastore('tagcloud', '', 1);
	build_datastore('searchcloud', '', 1);

	setcookie('vbulletin_inlinetag', '', TIMENOW - 3600, '/');
	print_cp_header($vbphrase['tag_manager']);
	print_stop_message('tags_edited_successfully');
}

// ########################################################################
function tagdopromote()
{
	global $vbulletin, $vbphrase, $db;

	$taglist = tagcp_fetch_tag_list();
	if (!sizeof($taglist))
	{
		print_stop_message('no_tags_selected');
	}

	foreach ($taglist as $tagid)
	{
			$tagdm = datamanager_init('tag', $vbulletin, ERRTYPE_ARRAY);
			if ($tagdm->fetch_by_id($tagid))
			{
				$tagdm->make_independent();
			}
	}

	print_stop_message('tags_edited_successfully');
}

// ########################################################################

function tagkill()
{
	global $vbulletin, $vbphrase;

	$taglist = tagcp_fetch_tag_list();
	if (sizeof($taglist))
	{
		foreach ($taglist as $killtagid)
		{
			$killtagdm = datamanager_init('tag', $vbulletin, ERRTYPE_ARRAY);
			if ($killtagdm->fetch_by_id($killtagid))
			{
				$killtagdm->delete();
			}
		}

		// need to invalidate the search and tag cloud caches
		build_datastore('tagcloud', '', 1);
		build_datastore('searchcloud', '', 1);
	}

	setcookie('vbulletin_inlinetag', '', TIMENOW - 3600, '/');
	print_cp_header($vbphrase['tag_manager']);
	print_stop_message('tags_deleted_successfully');
}


// ########################################################################

function displaytags()
{
	global $vbulletin, $vbphrase, $db;

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}

	if ($vbulletin->GPC['sort'] == 'dateline')
	{
		$where = 'WHERE canonicaltagid = 0';
		$order = 'dateline DESC';
		$synonyms_in_list = false;
	}
	else if ($vbulletin->GPC['sort'] == 'alphaall')
	{
		$where = '';
		$order = 'tagtext ASC';
		$synonyms_in_list = true;
	}
	else
	{
		$where = 'WHERE canonicaltagid = 0';
		$order = 'tagtext ASC';
		$synonyms_in_list = false;
	}

	$column_count = 3;
	$max_per_column = 15;
	$perpage = $column_count * $max_per_column;

	list($tag_count) = $db->query_first(
		"SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "tag $where",
		DBARRAY_NUM
	);

	$start = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
	if ($start >= $tag_count)
	{
		$start = max(0, $tag_count - $perpage);
	}

	$tags = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "tag
		$where
		ORDER BY $order
		LIMIT $start, $perpage
	");

	print_form_header('tag', '', false, true, 'tagsform');
	print_table_header($vbphrase['tag_list'], 3);
	if ($db->num_rows($tags))
	{
		$columns = array();
		$counter = 0;

		// build page navigation
		$pagenav = tagcp_build_page_nav($vbulletin->GPC['pagenumber'], ceil($tag_count / $perpage),
			$vbulletin->GPC['sort']);
		$sort_links[''] =  '<a href="tag.php?do=tags">' . $vbphrase['display_alphabetically'] . '</a>';
		$sort_links['dateline'] = '<a href="tag.php?do=tags&amp;sort=dateline">' . $vbphrase['display_newest'] . '</a>';
		$sort_links['alphaall'] = '<a href="tag.php?do=tags&amp;sort=alphaall">' . $vbphrase['display_alphabetically_all'] . '</a>';

		//dont show the current sort
		unset($sort_links[$vbulletin->GPC['sort']]);

		print_description_row(
			"<div style=\"float: " . vB_Template_Runtime::fetchStyleVar('left') . "\">" . implode("&nbsp;&nbsp;" , $sort_links) . "</div>$pagenav",
			false, 3, 'thead', 'right'
		);

		// build columns
		while ($tag = $db->fetch_array($tags))
		{
			$columnid = floor($counter++ / $max_per_column);
			$columns["$columnid"][] = tagcp_format_tag_entry($tag, $synonyms_in_list);
		}

		// make column values printable
		$cells = array();
		for ($i = 0; $i < $column_count; $i++)
		{
			if ($columns["$i"])
			{
				$cells[] = implode("<br />\n", $columns["$i"]);
			}
			else
			{
				$cells[] = '&nbsp;';
			}
		}

		print_column_style_code(array(
			'width: 33%',
			'width: 33%',
			'width: 34%'
		));
		print_cells_row($cells, false, false, -3);

		?>
		<tr>
			<td colspan="<?php echo $column_count; ?>" align="center" class="tfoot">
				<select id="select_tags" name="do">
					<option value="tagmerge" id="select_tags_merge"><?php echo $vbphrase['merge_selected_synonym']; ?></option>
					<option value="tagdopromote" id="select_tags_delete"><?php echo $vbphrase['promote_synonyms_selected']; ?></option>
					<option value="tagkill" id="select_tags_delete"><?php echo $vbphrase['delete_selected']; ?></option>
					<optgroup label="____________________">
						<option value="tagclear"><?php echo $vbphrase[deselect_all_tags]; ?></option>
					</optgroup>
				</select>
				<input type="hidden" name="page" value="<?php echo $vbulletin->GPC['pagenumber']; ?>" />
				<input type="hidden" name="sort" value="<?php echo $vbulletin->GPC['sort']; ?>" />
				<input type="submit" value="<?php echo $vbphrase[go]; ?>" id="tag_inlinego" class="button" />
			</td>
		</tr>
		</table>

		<script type="text/javascript" src="../clientscript/vbulletin_inlinemod.js?v=<?php echo $vboptions[simpleversion]; ?>"></script>
		<script type="text/javascript">
			<!--
			inlineMod_tags = new vB_Inline_Mod('inlineMod_tags', 'tag', 'tagsform', '<?php echo $vbphrase[go_x]; ?>', 'vbulletin_inline', 'tag');
			/* vBmenu.register("inlinemodsel"); */
			//-->

			function js_show_synlist(trigger, listid)
			{
				list = document.getElementById(listid);
				list.style.display = 'block';
				trigger.onclick = function() {return js_hide_synlist(trigger, listid)};
				trigger.getElementsByTagName('img')[0].src = '../cpstyles/<?php echo $vbulletin->options['cpstylefolder']  ?>/collapse_generic.gif';
				return false;
			}

			function js_hide_synlist(trigger, listid)
			{
				list = document.getElementById(listid);
				list.style.display = 'none';
				trigger.onclick = function() {return js_show_synlist(trigger, listid)};
				trigger.getElementsByTagName('img')[0].src = '../cpstyles/<?php echo $vbulletin->options['cpstylefolder']  ?>/collapse_generic_collapsed.gif';
				return false;
			}
		</script>
		</form>
		<?php
	}
	else
	{
		print_description_row($vbphrase['no_tags_defined'], false, 3, '', 'center');
		print_table_footer();
	}

	construct_hidden_code('page', $vbulletin->GPC['pagenumber']);
	construct_hidden_code('sort', $vbulletin->GPC['sort']);

	print_form_header('tag', 'taginsert');
	print_input_row($vbphrase['add_tag'], 'tagtext');
	print_submit_row();
}

function format_tag_list_item($id, $text)
{
	return '<label for="taglist_' . $id . '"><input type="checkbox" ' .
		'name="tag[' . $id . ']" id="taglist_' . $id . '" ' .
		'value="1" tabindex="1" /> ' . $text . '</label>';
}

function tagcp_build_page_nav($page, $total_pages, $sort)
{
	global $vbphrase;

	if ($total_pages > 1)
	{
		$pagenav = '<strong>' . $vbphrase['go_to_page'] . '</strong>';
		for ($thispage = 1; $thispage <= $total_pages; $thispage++)
		{
			if ($page == $thispage)
			{
				$pagenav .= " <strong>[$thispage]</strong> ";
			}
			else
			{
				$pagenav .= " <a href=\"tag.php?$session[sessionurl]do=tags&amp;page=$thispage&amp;sort=$sort\" class=\"normal\">$thispage</a> ";
			}
		}

	}
	else
	{
		$pagenav = '';
	}
	return $pagenav;
}

function tagcp_format_tag_entry($tag, $synonyms_in_list)
{
	global $vbulletin;

	$tagdm = datamanager_init('tag', $vbulletin, ERRTYPE_ARRAY);
	$tagdm->set_existing($tag);

	if (!$synonyms_in_list)
	{
		$label = $tag['tagtext'];

		$synonyms = $tagdm->fetch_synonyms();
		if (count($synonyms))
		{
			$list_id = 'synlist_' . $tag['tagid'];
			$synonym_list = '<span class="cbsubgroup-trigger" onclick="return js_show_synlist(this, \'' . $list_id . '\')">' .
			'<img src="../cpstyles/' . $vbulletin->options['cpstylefolder']  . '/collapse_generic_collapsed.gif" />'.
			'</span>';

			$synonym_list .= '<ul class="cbsubgroup" id="' . $list_id . '" style="display:none">';
			foreach ($synonyms as $synonym)
			{
				$synonym_list .= '<li>' .
					format_tag_list_item($synonym->fetch_field('tagid'), $synonym->fetch_field('tagtext')) .
				'</li>';
			}
			$synonym_list .= '</ul>';
		}
	}
	else
	{
	 	$canonical = $tagdm->fetch_canonical_tag();
		if ($canonical)
		{
			$label = '<i>' . $tag['tagtext'] . '</i> (' . $canonical->fetch_field('tagtext') . ')';
		}
		else
		{
			$label = $tag['tagtext'];
		}


		$synonym_list = '';
	}

	$tag_item_text = format_tag_list_item($tag['tagid'], $label);

	return '<div id="tag' . $tag['tagid'] . '" class="alt1" style="float:' .
		vB_Template_Runtime::fetchStyleVar('left') . ';clear:' . vB_Template_Runtime::fetchStyleVar('left') . '">' . "\n" .
		$tag_item_text . "\n" . $synonym_list . "\n" .
	'</div>';
}