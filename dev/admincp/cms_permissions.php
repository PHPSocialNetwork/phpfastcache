<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin Blog 4.2.1 - Licence Number VBF02D260D
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
define('CVS_REVISION', '$RCSfile$ - $Revision: 27874 $');
define('NOZIP', 1);

define('VBCMS_PERM_READ',       1);
define('VBCMS_PERM_CREATE',     2);
define('VBCMS_PERM_EDITOTHERS', 4);
define('VBCMS_PERM_PUBLISH',    8);
define('VBCMS_PERM_HTML',       16);
define('VBCMS_PERM_DOWNLOAD',   32);

// #################### PRE-CACHE TEMPLATES AND DATA ######################

if (!count($phrasegroups))
{
	$phrasegroups = array('vbcms', 'global', 'cpcms', 'cphome');
	$globaltemplates = array(
		'pagenav_curpage', 'pagenav_pagelinkrel', 'pagenav_pagelink','pagenav'
	);
}

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_cms_layout.php');
require_once(DIR . '/packages/vbcms/contentmanager.php');
require_once(DIR . '/includes/functions_databuild.php');

vB_Router::setRelativePath('../'); // Needed ?

if (!isset($vbulletin->userinfo['permissions']['cms']))
{
	vBCMS_Permissions::getUserPerms();
}

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!($vbulletin->userinfo['permissions']['cms']['admin'] & 2 ))
{
	print_cp_no_permission();
}

fetch_phrase_group('cpcms');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

//If we get an ajax request, we return the XML and exit
if ($_REQUEST['do'] == 'perms_section')
{
	require_once DIR . '/includes/functions_misc.php';
	require_once DIR . '/includes/adminfunctions_misc.php';
	$vbulletin->input->clean_array_gpc('r', array(
		'sectionid' => TYPE_UINT,
	));

	if ($vbulletin->GPC_exists['sectionid'])
	{
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('root');

		$xml->add_tag('html', ($vbulletin->GPC['sectionid'] ? getSectionTable($vbulletin->GPC['sectionid'])
			: showDefault()) );

		$xml->close_group();
		$xml->print_xml();
		return;
	}
}
else if ($_REQUEST['do'] == 'save')
{
	saveData();
}
else if ($_REQUEST['do'] == 'remove_perms')
{
	removePerms();
}

print_cp_header($vbphrase['permissions_manager']);

?>
<script type="text/javascript" src="../clientscript/vbulletin_ajax_htmlloader.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
<script type="text/javascript" src="../clientscript/vbulletin_cms_management.js?v=<?php echo $vbulletin->options['simpleversion']; ?>"></script>
<script type="text/javascript"><!--

var showRemove = false;

function setSection(sectionid)
{
	load_html('edit_block',
				'../ajax.php?do=perms_section&sectionid=' + sectionid, '', null, null);

	if (document.getElementById('sel_node_0') != undefined)
	{
		document.getElementById('sel_node_0').style.display='none';
	}

	if (showRemove)
	{
		document.getElementById('delete_button').style.display="inline";
	}
	else
	{
		document.getElementById('delete_button').style.display="none";
	}
}

function clearSection(sectionid)
{
	load_html('edit_block',
				'../ajax.php?do=del_perms&sectionid=' + sectionid, '', null, resetPage);
	if (showRemove)
	{
		document.getElementById('delete_button').style.display="block";
	}
	else
	{
		document.getElementById('delete_button').style.display="none";
	}
}

//-->
</script>
<form name="cms_perm_data" id="cms_perm_data" method="post" target="_self" action="cms_permissions.php">
<table class="tcat" width="90%" cellpadding="4" cellspacing="0" style="margin:auto;">
<tr>
<td>
<div style="width:100%;position:relative;text-align:<?php echo vB_Template_Runtime::fetchStyleVar('right'); ?>;">
		<input id="button_perms_save" type="submit" value="<?php echo $vbphrase['save_permission_changes']; ?>" />
		<input id="button_perms_reset" type="reset" value="<?php echo $vbphrase['reset']; ?>" />
		<input		type="button"
					id="delete_button"
					value="<?php echo $vbphrase['remove_perms']; ?>"
					onclick="document.getElementById('do').value='remove_perms'; document.getElementById('cms_perm_data').submit()"
					style="display:none;" />
</div>
</td>
</tr>
</table>
<br />
<div  align="center" style="width:90%;margin:auto;overflow:auto;height:auto;" id="cmspermissions_edit">
	<input type="hidden" name="s" value="<?php echo htmlspecialchars_uni($vbulletin->session->vars['sessionhash']); ?>"/>
	<input type="hidden" name="do" id="do" value="save" />
	<input type="hidden" name="adminhash" value="<?php echo ADMINHASH; ?>" />
	<input type="hidden" name="securitytoken" value="<?php $vbulletin->userinfo['securitytoken']; ?>" />
	<div id="edit_block">
		<?php
		$vbulletin->input->clean_array_gpc('r', array(
			'do'        => TYPE_STR,
			'sectionid' => TYPE_INT,
			'count'     => TYPE_INT,
		));

		if (!$vbulletin->GPC_exists['sectionid'] OR !$vbulletin->GPC['sectionid'])
		{
			echo showDefault();
		}
		else
		{
			echo getSectionTable($vbulletin->GPC['sectionid']);
		}
		?>
</div><!-- -->
</div>
</form>
<br />
<div  style="width:90%;text-align:<?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>;margin:auto;" id="sections">
	<div>
		<strong><?php echo $vbphrase['click_on_section']; ?></strong><br />
		<?php echo listSections(); ?>
		<?php echo getNodePanel('sel_node_0'); ?>
	</div>
</div>

<?php
print_cp_footer();



/*********************
* This function saves the data. It normally is called by ajax
*********************/
function saveData()
{
	global $vbulletin;
	//We have a series of records of the form id
	$vbulletin->input->clean_array_gpc('r', array(
		'sectionid' => TYPE_UINT,
		'count' => TYPE_UINT));

	if ($vbulletin->GPC_exists['count'] AND $vbulletin->GPC_exists['sectionid'])
	{
		$current = $vbulletin->db->query_first("
			SELECT nodeleft, noderight, permissionsfrom
			FROM " . TABLE_PREFIX . "cms_node
			WHERE nodeid = " . $vbulletin->GPC['sectionid'] . "
		");

		for ($i = 1; $i <= intval($vbulletin->GPC['count']) ; $i++)
		{
			$vbulletin->input->clean_array_gpc('p', array(
				"groupid_$i" => TYPE_INT,
				"permid_$i"  => TYPE_INT,
			));

			if ($vbulletin->GPC_exists["groupid_$i"])
			{
				$perm =
					  (isset($_POST["cb_1_$i"]) ? VBCMS_PERM_READ       : 0)
					+ (isset($_POST["cb_2_$i"]) ? VBCMS_PERM_DOWNLOAD   : 0)
					+ (isset($_POST["cb_3_$i"]) ? VBCMS_PERM_CREATE     : 0)
					+ (isset($_POST["cb_4_$i"]) ? VBCMS_PERM_EDITOTHERS : 0)
					+ (isset($_POST["cb_5_$i"]) ? VBCMS_PERM_PUBLISH    : 0)
					+ (isset($_POST["cb_6_$i"]) ? VBCMS_PERM_HTML       : 0);

				//Now at this point we do three things.
				//First, we get the current "permissionsfrom" field for this node.
				//Second, we set the new value for this permissions (insert or update).
				//Finally, we set the "permissionsfrom" to the new node for every
				//record below this section (nodeleft between this node's nodeleft and noderight,
				// and content type is not aggregator, and permissionsfrom is empty or same
				// as this node had
				if ($vbulletin->GPC["permid_$i"] )
				{
					$vbulletin->db->query_write($sql = "UPDATE " . TABLE_PREFIX . "cms_permissions
					SET permissions = $perm WHERE permissionid = " . $vbulletin->GPC["permid_$i"]);
				}
				else
				{
					$vbulletin->db->query_write($sql = "INSERT INTO " . TABLE_PREFIX . "cms_permissions
					(usergroupid, nodeid, permissions) values (" . $vbulletin->GPC["groupid_$i"] .
					", " . $vbulletin->GPC['sectionid'] . ", $perm)");
				}
			}
		}
		//Now compose the query that assigns to its children.
		$vbulletin->db->query_write($sql = "
			UPDATE " . TABLE_PREFIX . "cms_node
			SET permissionsfrom = " . $vbulletin->GPC['sectionid'] . "
			WHERE nodeleft BETWEEN "
				. $current['nodeleft'] . " AND " . $current['noderight'] .
				" AND (permissionsfrom IS NULL  " .
				(intval($current['permissionsfrom']) ? " OR permissionsfrom =" . $current['permissionsfrom'] : '') .
			")");

	}
	vB_Cache::instance()->event('cms_permissions_change');
	vB_Cache::instance()->cleanNow();

	return result;
}

/********************
* This function removes permissions from a node.
********************/
function removePerms()
{
	global $vbulletin;
	$vbulletin->input->clean_array_gpc('r', array(
		'sectionid' => TYPE_UINT,
		'count'     => TYPE_UINT,
	));

	if ($vbulletin->GPC_exists['count'] AND $vbulletin->GPC_exists['sectionid'])
	{
		//We have to do two things. First, we delete the permissions entry from this node.
		//Second, we get the permissions from this node's parent and assign that to its children

		//First get the parent's permissionsfrom;
		$permfrom = $vbulletin->db->query_first("
			SELECT n2.permissionsfrom, n.nodeleft, n.noderight, n.parentnode
		 	FROM " . TABLE_PREFIX . "cms_node AS n
		 	INNER JOIN " . TABLE_PREFIX . "cms_node AS n2
				ON n2.nodeid = n.parentnode
			WHERE n.nodeid = " . $vbulletin->GPC['sectionid'] . "
		");

		//Now a quick check. If parentnode is null, that means this is the root node and we can't delete it.
		if (!intval($permfrom['parentnode']))
		{
			return false;
		}

		//Now delete the existing record
		$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "cms_permissions
			WHERE nodeid = " . $vbulletin->GPC['sectionid'] . "
		");

		//Now compose the query that assigns to its children.
		$vbulletin->db->query_write($sql = "
			UPDATE " . TABLE_PREFIX . "cms_node
			SET permissionsfrom = " . $permfrom['permissionsfrom'] . "
			WHERE nodeleft BETWEEN "
				. $permfrom['nodeleft'] . " AND " . $permfrom['noderight'] .
				" AND (permissionsfrom IS NULL OR permissionsfrom = " . $vbulletin->GPC['sectionid'] . ")" );

		vB_Cache::instance()->event('cms_permissions_change');
		vB_Cache::instance()->cleanNow();
	}
}

/********************
* This function lists the usergroups and permisssions for the root node. This is the
* default
* @TODO this should use the getSectionTable() function, which should be refactored to handle the root node.
********************/
function showDefault()
{
	$section = vB::$db->query_first("
		SELECT info.title, node.nodeid FROM " . TABLE_PREFIX . "cms_nodeinfo AS info
		INNER JOIN " . TABLE_PREFIX . "cms_node AS node ON node.nodeid = info.nodeid
		WHERE node.parentnode IS NULL
	");
	$section['nodeid'] = intval($section['nodeid']);

	if ($section['nodeid'] < 1)
	{
		print_stop_message('missing_root_cms_node_explanation');
	}

	if ($recordset = vB::$db->query_read("
		SELECT u.usergroupid, u.title, p.permissions, p.permissionid
		FROM " . TABLE_PREFIX . "usergroup AS u
		LEFT JOIN " . TABLE_PREFIX . "cms_permissions AS p on p.usergroupid = u.usergroupid AND
			p.nodeid = " . $section['nodeid'] . "
		ORDER BY title
	"))
	{
		return makeTable($section, $recordset);
	}
	return '';

}


function makeTable($section, $recordset)
{
	require_once(DIR . '/includes/adminfunctions.php');
	global $vbphrase;
	$result = "
	<input type=\"hidden\" name=\"sectionid\" id=\"sectionid\" value=\"" . $section['nodeid'] . "\" />
	<table cellpadding=\"4\" cellspacing=\"0\" border=\"0\" align=\"center\" width=\"100%\" class=\"tborder\" style=\"border-collapse: separate;\">
	<tr class=\"thead\">
	<td colspan=\"6\"> " . $section['title'] . "
	<!-- <tbody> -->
	</td>
	</tr>
	<tr class=\"tcat\">
	<td class=\"feature_management_header\" style=\"padding:5px;\"> " .
		(
			intval($vbulletin->GPC['sectionid']) == intval($section['permissionsfrom']) ?
			construct_phrase($vbphrase['permissions_assigned_section_x'], $section['title']) :
			construct_phrase($vbphrase['permissions_not_assigned_section_x'], $section['title'])
		) . "
		<input type=\"button\" id=\"button_change_section\" onclick=\"showNodeWindow('filter_section')\" value=\"" . $vbphrase['navigate_to_section'] ."\" />
		</td>
		</tr>
		<tr><td><table class=\"tborder\" cellpadding=\"4\" border=\"0\" width=\"100%\" >
		<tr class=\"thead\">
			<td width=\"40%\">
				" . $vbphrase['user_group'] . "
			</td>";

	$table_columns = array(
		'can_read'            => array('groupid' => 1, 'permid' => VBCMS_PERM_READ),
		'can_download'        => array('groupid' => 2, 'permid' => VBCMS_PERM_DOWNLOAD),
		'create_content'      => array('groupid' => 3, 'permid' => VBCMS_PERM_CREATE),
		'edit_others_content' => array('groupid' => 4, 'permid' => VBCMS_PERM_EDITOTHERS),
		'can_publish'         => array('groupid' => 5, 'permid' => VBCMS_PERM_PUBLISH),
		'can_use_html'        => array('groupid' => 6, 'permid' => VBCMS_PERM_HTML),
	);
	foreach ($table_columns AS $table_column_phrase => $table_column_info)
	{
		$table_column_groupid = $table_column_info['groupid'];
		$table_column_permid = $table_column_info['permid'];

		$result .= "<td width=\"12%\" align=\"center\" valign=\"top\">
			" . $vbphrase[$table_column_phrase] . "
			<select id=\"change_selection_permid_" . $table_column_permid . "\" onchange=\"javascript: toggleCheckBox('cb', '" . $table_column_groupid . "', document.getElementById('count').value, this.value);\">
				<option></option>
				<option value=\"1\">" . $vbphrase['select_all'] . "</option>
				<option value=\"0\">" . $vbphrase['deselect_all'] . "</option>
				<option value=\"-1\">" . $vbphrase['invert_selection'] . "</option>
			</select>
		</td>
		";
	}
	$result .= "</tr>";

	$count = 0;
	while($row = vB::$db->fetch_array($recordset))
	{
		$count++;
		$bgclass = fetch_row_bgclass();
		$result .= "
	<tr id=\"usergroupid_$row[usergroupid]\" class=\"$bgclass\" >
		<td>" . $row['title'] . "<input type=\"hidden\" name=\"groupid_$count\" value=\"" . $row['usergroupid'] . "\" />
			<input type=\"hidden\" name=\"permid_$count\" value=\"" . $row['permissionid'] . "\" />
		</td>
		<td id=\"usergroupid_$row[usergroupid]_permissionid_" . VBCMS_PERM_READ .       "\" align=\"center\"><input type=\"checkbox\" name=\"cb_1_$count\" id=\"cb_1_$count\" " . (($row['permissions'] & VBCMS_PERM_READ)       ? 'checked="checked"' : '') . " /></td>
		<td id=\"usergroupid_$row[usergroupid]_permissionid_" . VBCMS_PERM_DOWNLOAD .   "\" align=\"center\"><input type=\"checkbox\" name=\"cb_2_$count\" id=\"cb_2_$count\" " . (($row['permissions'] & VBCMS_PERM_DOWNLOAD)   ? 'checked="checked"' : '') . " /></td>
		<td id=\"usergroupid_$row[usergroupid]_permissionid_" . VBCMS_PERM_CREATE .     "\" align=\"center\"><input type=\"checkbox\" name=\"cb_3_$count\" id=\"cb_3_$count\" " . (($row['permissions'] & VBCMS_PERM_CREATE )    ? 'checked="checked"' : '') . " /></td>
		<td id=\"usergroupid_$row[usergroupid]_permissionid_" . VBCMS_PERM_EDITOTHERS . "\" align=\"center\"><input type=\"checkbox\" name=\"cb_4_$count\" id=\"cb_4_$count\" " . (($row['permissions'] & VBCMS_PERM_EDITOTHERS) ? 'checked="checked"' : '') . " /></td>
		<td id=\"usergroupid_$row[usergroupid]_permissionid_" . VBCMS_PERM_PUBLISH .    "\" align=\"center\"><input type=\"checkbox\" name=\"cb_5_$count\" id=\"cb_5_$count\" " . (($row['permissions'] & VBCMS_PERM_PUBLISH)    ? 'checked="checked"' : '') . " /></td>
		<td id=\"usergroupid_$row[usergroupid]_permissionid_" . VBCMS_PERM_HTML .       "\" align=\"center\"><input type=\"checkbox\" name=\"cb_6_$count\" id=\"cb_6_$count\" " . (($row['permissions'] & VBCMS_PERM_HTML)       ? 'checked="checked"' : '') . " /></td>
	</tr>
		";
	}
	$result .= "</table>
		</td>
		</tr>
	<!-- </tbody> -->
</table>";
	$result .= "<input type=\"hidden\" name=\"count\" id=\"count\" value=\"$count\" />\n";

	return $result;
}

/********************
 * This function lists the usergroups and permisssions for a selected contentid.
 * We don't get called until we have verified a contentid
 *
 ********************/
function getSectionTable($sectionid)
{
	$sectionid = intval($sectionid);

	if ($recordset = vB::$db->query_read("
		SELECT u.usergroupid, u.title, p.permissions, p.permissionid
		FROM " . TABLE_PREFIX . "usergroup AS u
		LEFT JOIN " . TABLE_PREFIX . "cms_permissions AS p on p.usergroupid = u.usergroupid AND
			p.nodeid = " . $sectionid . "
		ORDER BY title
	"))
	{
		$section = vB::$db->query_first("
			SELECT info.title, node.permissionsfrom, node.nodeid
			FROM " . TABLE_PREFIX . "cms_nodeinfo AS info
			INNER JOIN " . TABLE_PREFIX . "cms_node AS node ON node.nodeid = info.nodeid
			WHERE node.nodeid = " . $sectionid . "
		");
		return makeTable($section, $recordset);
	}
	return '';
}

/*****************
* This function lists the section hierarchy. It allows the admin to select a
* section for editing above.
******************/
function listSections()
{
	//We make use of the preorder structure of the data. We compose a query where each
	//leat of the specific content type gets its complete parentage.
	global $vbulletin;
	global $vbphrase;
	$sql = "SELECT DISTINCT info.title AS section, info2.title,
			node2.nodeid, node.nodeid AS parentid, node.parentnode, node.permissionsfrom
			FROM " . TABLE_PREFIX . "cms_node AS node INNER JOIN " .
		TABLE_PREFIX . "cms_nodeinfo AS info ON info.nodeid = node.nodeid
			INNER JOIN " . TABLE_PREFIX .
		"cms_node AS node2 ON (node2.nodeleft >= node.nodeleft AND node2.nodeleft <= node.noderight)
			INNER JOIN " . TABLE_PREFIX . "cms_nodeinfo AS info2 ON info2.nodeid = node2.nodeid
			WHERE node2.contenttypeid = " .  vb_Types::instance()->getContentTypeID("vBCms_Section") .
		" ORDER BY node2.nodeleft, node.nodeleft;";

	if ($rst = $vbulletin->db->query_read($sql))
	{
		//Now it's simple. We walk down the list. We know we have reached a leaf when
		// nodeid = parentid
		$count = 0;
		$stack = array();
		while($row = $vbulletin->db->fetch_array($rst))
		{
			if (intval($row['nodeid']) == intval($row['parentid']) )
			{
				$stack[$count][] = "<img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] .  "/permission-"
				. (intval($row['nodeid']) == intval($row['permissionsfrom']) ?
				'set' : 'not-set')
				. "_small.png\" alt=\"permissions "
				. (intval($row['nodeid']) == intval($row['permissionsfrom']) ?
				'set' : 'not-set')
				. "\"/>";

				$stack[$count][] = "<a id=\"list_section_" . $row['nodeid'] . "\" href=\"javascript:" . (intval($row['parentnode']) ? 'showRemove=true;' : 'showRemove=false;')
					. " setSection(" . $row['nodeid'] . "); return false;\" onclick=\"javascript:"	. (intval($row['parentnode']) ? 'showRemove=true;' : 'showRemove=false;')
					. "setSection("	. $row['nodeid'] . ");return false;\" style=\"margin-" . vB_Template_Runtime::fetchStyleVar('right') . ":0.5em;\">"
					. htmlspecialchars($row['title'] ) . '</a>';
				$count++;
			}
			else
			{
				$stack[$count][] = "<span style=\"margin-" . vB_Template_Runtime::fetchStyleVar('left') . ":20px;margin-" . vB_Template_Runtime::fetchStyleVar('right') . ":0.5em;\">" . htmlspecialchars($row['section']) . "</span>"  ;
			}
		}
	}

	$result = '';
	$break = '';//'<li style="float:' . vB_Template_Runtime::fetchStyleVar('left') . '">&nbsp;&gt;&nbsp; </li>';
	foreach ($stack AS $values)
	{
		$result .= '<ul class="floatcontainer floatlist">';
			foreach ($values AS $value)
			{
				$result .= $break;
		 		$result .= '<li style="float:' . vB_Template_Runtime::fetchStyleVar('left') . '">' . $value . '</li>';
			}
		$result .= '</ul>';
	}
	return $result;
}

function getNodePanel($divId)
	{
		global $vbulletin;
		global $vbphrase;
		global $phrasegroups;
		global $sect_js_varname;
		fetch_phrase_group('cpcms');
	$result = "
<div id=\"$divId\" style=\"position: absolute;
	display: none;	width:600px;height:380px;background-color:white; text-align:" . vB_Template_Runtime::fetchStyleVar('left') . ";
	overflow: auto;" . vB_Template_Runtime::fetchStyleVar('left') . ":100px;top:100px; border:1px solid #000;clear:both;padding:0px 0px 0px 0px;\">
	<div class=\"tcat\" style=\"border:0;height:12px;top:0px;" . vB_Template_Runtime::fetchStyleVar('left') . ":0px;width:580px;position:relative;\" >
		<span style=\"" . vB_Template_Runtime::fetchStyleVar('left') . ":0px;top:0px;position:relative;text-align:" . vB_Template_Runtime::fetchStyleVar('left') . ";font-size:120%;width:50%;float:" . vB_Template_Runtime::fetchStyleVar('left') . ";\"><strong>" .
			$vbphrase['section_navigator'] . "</strong>
		</span>
		<div style=\"" . vB_Template_Runtime::fetchStyleVar('left') . ":400px;border:0;top:-20px;text-align:" . vB_Template_Runtime::fetchStyleVar('right') . ";padding:0px 10px 0px 0px;\">
			<input type=\"button\" id=\"button_selectsection_close\" value=\"" . $vbphrase['close'] . "\"
			onclick=\"document.getElementById('$divId').style.display='none'\" />
		</div>
	</div>
	<div class=\"picker_overlay\" style=\"border:0;height:343px;width:100%;top:0px;padding:0;
		overflow:auto;position:relative;display:block;\">
		<div class=\"tcat\" style=\"border:0;position:relative;" . vB_Template_Runtime::fetchStyleVar('left') . ":0px;width:561px;padding:0;
			font-size:14px;font-weight:bold;padding:2px;color:black;float:" . vB_Template_Runtime::fetchStyleVar('left') . ";padding:10px;
			border-style:solid;border-width:1px 1px 0 1px;border-color:#000000;\">" . $vbphrase['choose_a_section'] . "
		</div>
		<div style=\"border:0;padding-" . vB_Template_Runtime::fetchStyleVar('left') . ":5px\">
	" ;
	$result .= getSectionList();
	$result .= "
	</div>
	</div>
</div>\n";
		return $result;
	}

function getSectionList($orderby = 1)
{
	//We make use of the preorder structure of the data. We compose a query where each
	//leat of the specific content type gets its complete parentage.
	global $vbulletin;
	global $vbphrase;

	$sql = "SELECT DISTINCT info.title AS section, info2.title,
		node2.nodeid, node.nodeid AS parentid
		FROM " . TABLE_PREFIX . "cms_node node INNER JOIN " .
		TABLE_PREFIX . "cms_nodeinfo info ON info.nodeid = node.nodeid
		INNER JOIN " . TABLE_PREFIX .
		"cms_node AS node2 ON (node2.nodeleft >= node.nodeleft AND node2.nodeleft <= node.noderight)
		INNER JOIN " . TABLE_PREFIX . "cms_nodeinfo AS info2 ON info2.nodeid = node2.nodeid
		WHERE node2.contenttypeid = " .  vb_Types::instance()->getContentTypeID("vBCms_Section") .
		" ORDER BY " . ($orderby == 0 ? "info2.title" : "node2.nodeleft") .
		" , node.nodeleft;";

	if ($rst = $vbulletin->db->query_read($sql))
	{
		//Now it's simple. We walk down the list. We know we have reached a leaf when
		// nodeid = parentid
		$stack = array();
		$count = 0;
		while($row = $vbulletin->db->fetch_array($rst))
		{

			if (intval($row['nodeid']) == intval($row['parentid']) )
			{
				$stack[$count][] = '<a id="set_section_' . $row['nodeid'] . '" href="javascript: setSection(' . $row['nodeid'] . '); return false;" onclick="javascript:void setSection('
					. $row['nodeid'] . '); return false;" style="font-weight:bold;">'
					. htmlspecialchars_uni($row['title'] ) . '</a>';
				$count++;
			}
			else
			{
				$stack[$count][] = htmlspecialchars_uni($row['section']);
			}
		}
	}
	$result = '';
	$break = '<li style="float:' . vB_Template_Runtime::fetchStyleVar('left') . '">&nbsp;&gt;&nbsp; </li>';
	foreach ($stack AS $values)
	{
		$result .= '<ul class="floatcontainer floatlist">';
			foreach ($values AS $value)
			{
				$result .= $break;
		 		$result .= '<li style="float:' . vB_Template_Runtime::fetchStyleVar('left') . '">' . $value . '</li>';
			}
		$result .= '</ul>';
	}
	return $result;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:01, Mon May 13th 2013
|| # SVN: $Revision: 27874 $
|| ####################################################################
\*======================================================================*/
