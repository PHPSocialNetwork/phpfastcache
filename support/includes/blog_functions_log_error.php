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

require_once(DIR . '/includes/functions_log_error.php');

/**
* Logs the moderation actions that are being performed on the blog
*
* @param	array	Array of information indicating on what data the action was performed
* @param	integer	This value corresponds to the action that was being performed
* @param	string	Other moderator parameters
*/
function blog_moderator_action(&$loginfo, $logtype, $action = '')
{
	global $vbulletin;

	$modlogsql = array();

	if ($result = fetch_modlogtypes($logtype))
	{
		$logtype =& $result;
	}

	($hook = vBulletinHook::fetch_hook('log_moderator_action')) ? eval($hook) : false;

	if (is_array($loginfo[0]))
	{
		foreach ($loginfo AS $index => $log)
		{
			if (is_array($action))
			{
				$action = serialize($action);
			}
			else if ($log['username'] OR $log['title'])
			{
				$action = serialize(array($log['title'], $log['username']));
			}
			$log['id1'] = $log['blog_userid'] ? $log['blog_userid'] : $log['id1'];
			$log['id2'] = $log['blogid'] ? $log['blogid'] : $log['id2'];
			$log['id3'] = $log['blogtextid'] ? $log['blogtextid'] : $log['id3'];
			$log['id4'] = $log['attachmentid'] ? $log['attachmentid'] : $log['id4'];
			$log['id5'] = $log['blogtracbackid'] ? $log['blogtrackbackid'] : $log['id5'];

			$modlogsql[] = "(" . intval($logtype) . ", " . intval($log['userid']) . ", " . TIMENOW . ", " . intval($log['id1']) . ", " . intval($log['id2']) . ", " . intval($log['id3']) . ", " . intval($log['id4']) . ", " . intval($log['id5']) . ", '" . $vbulletin->db->escape_string($action) . "', '" . $vbulletin->db->escape_string(IPADDRESS) . "', 'vbblog')";
		}

		$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "moderatorlog (type, userid, dateline, id1, id2, id3, id4, id5, action, ipaddress, product) VALUES " . implode(', ', $modlogsql));
	}
	else
	{
		$moderatorlog['userid'] =& $vbulletin->userinfo['userid'];
		$moderatorlog['dateline'] = TIMENOW;

		$moderatorlog['type'] = intval($logtype);

		$moderatorlog['id1'] = $loginfo['blog_userid'] ? $loginfo['blog_userid'] : ($loginfo['userid'] ? intval($loginfo['userid']) : intval($loginfo['id1']));
		$moderatorlog['id2'] = $loginfo['blogid'] ? intval($loginfo['blogid']) : intval($loginfo['id2']);
		$moderatorlog['id3'] = $loginfo['blogtextid'] ? intval($loginfo['blogtextid']) : intval($loginfo['id3']);
		$moderatorlog['id4'] = $loginfo['attachmentid'] ? intval($loginfo['attachmentid']) : intval($loginfo['id4']);
		$moderatorlog['id5'] = $loginfo['blogtrackbackid'] ? intval($loginfo['blogtrackbackid']) : intval($loginfo['id5']);
		$moderatorlog['product'] = 'vbblog';
		$moderatorlog['ipaddress'] = IPADDRESS;

		if (is_array($action))
		{
			$action = serialize($action);
		}
		$moderatorlog['action'] = $action;

		/*insert query*/
		$vbulletin->db->query_write(fetch_query_sql($moderatorlog, 'moderatorlog'));
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # SVN: $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>