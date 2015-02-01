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
define('CVS_REVISION', '$RCSfile$ - $Revision: 49279 $');
define('NOZIP', 1);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('thread', 'threadmanage', 'prefix');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_databuild.php');
require_once(DIR . '/includes/adminfunctions_prefix.php');

@set_time_limit(0);

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminthreads'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'forumid' => TYPE_INT,
	'pollid'  => TYPE_INT,
));

// ############################# LOG ACTION ###############################
log_admin_action(iif(!empty($vbulletin->GPC['forumid']), "forum id = " . $vbulletin->GPC['forumid'], iif(!empty($vbulletin->GPC['pollid']), "poll id = " . $vbulletin->GPC['pollid'])));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if ($_POST['do'] != 'tagclear' AND $_POST['do'] != 'tagkill')
{
	print_cp_header($vbphrase['thread_manager']);
}

// ###################### Do who voted ####################
if ($_POST['do'] == 'dovotes')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'pollid' => TYPE_UINT
	));

	$poll = $db->query_first("
		SELECT poll.*, thread.threadid, thread.title
		FROM " . TABLE_PREFIX . "poll AS poll
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING(pollid)
		WHERE poll.pollid = " . $vbulletin->GPC['pollid'] . "
	");

	$votes = $db->query_read("
		SELECT pollvote.*, user.username
		FROM " . TABLE_PREFIX . "pollvote AS pollvote
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid=pollvote.userid)
		WHERE pollid = " . $vbulletin->GPC['pollid'] . "
		ORDER BY username ASC
	");

	$options = explode('|||', $poll['options']);
	$options = array_map('rtrim', $options);

	$lastoption = 0;
	$users = '';

	print_form_header('', '');
	$poll_link = fetch_seo_url('poll|bburl', $poll, array('do' => 'showresults'));
	print_description_row(construct_phrase($vbphrase['poll_x_in_thread_y'], 
		"<a href=\"" . $poll_link . "\" target=\"_blank\">$poll[question]</a>", 
		"<a href=\"" . fetch_seo_url('thread|bburl', $poll) . "\" target=\"_blank\">$poll[title]</a>"), 0, 2, 'thead');
	print_table_header($poll['question'], 2, 0);

	while ($vote = $db->fetch_array($votes))
	{
		if (empty($vote['username']))
		{
			$username = '<span class="smallfont">' . $vbphrase['guest'] . '</span>';
		}
		else
		{
			$username = "<a href=\"" . fetch_seo_url('member|bburl', $vote) . "\" target=\"_blank\">$vote[username]</a>";
		}

		$votelist["{$vote['voteoption']}"] .= "$username &nbsp;";
	}

	if (is_array($votelist))
	{
		foreach ($votelist AS $optionid => $usernamelist)
		{
			$option = $options[($optionid - 1)];
			print_label_row("<b>$option</b>", $usernamelist);
		}
	}

	print_table_footer();
}

// ###################### Start who voted ####################
if ($_REQUEST['do'] == 'votes')
{

// JAVASCRIPT CODE
?>
<script type="text/javascript">
function js_fetch_thread_title(formid,threadid)
{
	if (threadid)
	{
		formid.threadtitle.value = t[threadid];
	}
}
t = new Array();
<?php
// END JAVASCRIPT CODE

	$polloptions = '';
	$polls = $db->query_read("
		SELECT thread.title, poll.pollid, poll.question
		FROM " . TABLE_PREFIX . "thread AS thread
		INNER JOIN " . TABLE_PREFIX . "poll AS poll ON (thread.pollid=poll.pollid)
		WHERE thread.open <> 10 AND thread.pollid <> 0
		ORDER BY thread.dateline DESC
	");
	while ($poll = $db->fetch_array($polls))
	{
		if (empty($poll['pollid']))
		{
			continue;
		}
		if (empty($firsttitle))
		{
			$firsttitle = $poll['title'];
		}
		$polloptions .= "<option value=\"$poll[pollid]\">[$poll[pollid]] $poll[question]</option>\n";
		echo "t[" . intval($poll['pollid']) . "] = \"$poll[title]\";\n";
	}

	echo "</script>\n\n";

	if (!$polloptions)
	{
		print_stop_message('no_polls_found');
	}

	print_form_header('thread', 'dovotes');
	print_table_header($vbphrase['who_voted']);
	print_label_row($vbphrase['poll'], "<select name=\"pollid\" class=\"bginput\" tabindex=\"1\" onchange=\"js_fetch_thread_title(this.form,this.options[this.selectedIndex].value)\">$polloptions</select>", '', 'top', 'pollid');
	print_label_row($vbphrase['thread'], "<input type=\"text\" tabindex=\"1\" class=\"bginput\" size=\"50\" name=\"threadtitle\" value=\"$firsttitle\" readonly=\"readonly\" disabled=\"disabled\" />", '', 'top', 'threadtitle');
	print_submit_row($vbphrase['who_voted'], 0);
}

// ###################### Start Prune by user #######################
if ($_REQUEST['do'] == 'pruneuser')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'username'  => TYPE_NOHTML,
		'forumid'   => TYPE_INT,
		'subforums' => TYPE_BOOL,
		'userid'    => TYPE_UINT
	));

	// we only ever submit this via post
	$vbulletin->input->clean_array_gpc('p', array(
		'confirm'   => TYPE_BOOL,
	));

	if (!$vbulletin->GPC['confirm'])
	{

		if (empty($vbulletin->GPC['username']) AND !$vbulletin->GPC['userid'])
		{
			print_stop_message('invalid_user_specified');
		}
		else if (!$vbulletin->GPC['forumid'])
		{
			print_stop_message('invalid_forum_specified');
		}

		if ($vbulletin->GPC['forumid'] == -1)
		{
			$forumtitle = $vbphrase['all_forums'];
		}
		else
		{
			$forum = $db->query_first("SELECT title FROM " . TABLE_PREFIX . "forum WHERE forumid = " . $vbulletin->GPC['forumid']);
			$forumtitle = $forum['title'] . ($vbulletin->GPC['subforums'] ? ' (' . $vbphrase['include_child_forums'] . ')' : '');
		}

		$users = $db->query_read("
			SELECT userid,username
			FROM " . TABLE_PREFIX . "user
			WHERE " . ($vbulletin->GPC['username'] ?
				"username LIKE '%" . $db->escape_string_like($vbulletin->GPC['username']) . "%'" :
				'userid = ' . $vbulletin->GPC['userid']) . "
			ORDER BY username
		");

		if (!$db->num_rows($users))
		{
			print_stop_message('invalid_user_specified');
		}
		else
		{
			echo '<p>' . construct_phrase($vbphrase['about_to_delete_posts_in_forum_x_by_users'], $forumtitle) . '</p>';
		}

		while ($user = $db->fetch_array($users))
		{

			print_form_header('thread', 'pruneuser');
			print_table_header(construct_phrase($vbphrase['prune_all_x_posts_automatically'], $user['username']), 2, 0);
			construct_hidden_code('forumid', $vbulletin->GPC['forumid']);
			construct_hidden_code('userid', $user['userid']);
			construct_hidden_code('subforums', $vbulletin->GPC['subforums']);
			construct_hidden_code('confirm', 1);
			print_submit_row(construct_phrase($vbphrase['prune_all_x_posts_automatically'], $user['username']), '', 2);

			print_form_header('thread', 'pruneusersel');
			print_table_header(construct_phrase($vbphrase['prune_x_posts_selectively'], $user['username']), 2, 0);
			construct_hidden_code('forumid', $vbulletin->GPC['forumid']);
			construct_hidden_code('userid', $user['userid']);
			construct_hidden_code('subforums', $vbulletin->GPC['subforums']);
			print_submit_row(construct_phrase($vbphrase['prune_x_posts_selectively'], $user['username']), '', 2);

			echo "\n<hr />\n";
		}
		exit;
	}

	if ($vbulletin->GPC['forumid'] != -1)
	{
		if ($vbulletin->GPC['subforums'])
		{
			$forumcheck = "(thread.forumid=" . $vbulletin->GPC['forumid'] . " OR parentlist LIKE '%," . $vbulletin->GPC['forumid'] . ",%') AND ";
		}
		else
		{
			$forumcheck = "thread.forumid=" . $vbulletin->GPC['forumid'] . " AND ";
		}
	}
	else
	{
		$forumcheck = '';
	}

	$usernames = $db->query_first("SELECT username FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC['userid']);
	$username = $usernames['username'];

	require_once(DIR . '/includes/functions_log_error.php');

	echo '<p><b>' . $vbphrase['deleting_threads'] . '</b>';
	$threads = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "thread AS thread
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum USING(forumid)
		WHERE $forumcheck postusername = '" . $db->escape_string($username) . "'
	");
	while ($thread = $db->fetch_array($threads))
	{
		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$threadman->set_existing($thread);
		$threadman->delete(0);
		unset($threadman);

		echo ". \n";
		vbflush();
	}
	echo ' ' .$vbphrase['done'] . '</p><p><b>' . $vbphrase['deleting_posts'] . '</b>';
	$posts = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "post AS post,
			" . TABLE_PREFIX . "thread AS thread
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum USING(forumid)
		WHERE $forumcheck
			post.threadid = thread.threadid AND
			post.userid = " . $vbulletin->GPC['userid'] . "
	");

	while ($post = $db->fetch_array($posts))
	{
		$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
		$postman->set_existing($post);
		$postman->delete();
		unset($postman);

		echo ". \n";
		vbflush();
	}
	echo ' ' . $vbphrase['done'] . '</p>';

	//define('CP_REDIRECT', 'thread.php?do=prune');
	define('CP_BACKURL', '');
	print_stop_message('pruned_threads_successfully');
}

// ###################### Start prune by user selector #######################
if ($_REQUEST['do'] == 'pruneusersel')
{

	$vbulletin->input->clean_array_gpc('r', array(
		'forumid'   => TYPE_INT,
		'subforums' => TYPE_BOOL,
		'userid'    => TYPE_UINT
	));

	$usernames = $db->query_first("SELECT username FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC['userid']);
	$username = $usernames['username'];

	if ($vbulletin->GPC['forumid'] != -1)
	{
		if ($vbulletin->GPC['subforums'])
		{
			$forumcheck = "(thread.forumid = " . $vbulletin->GPC['forumid'] . " OR parentlist LIKE '%," . $vbulletin->GPC['forumid'] . ",%') AND ";
		}
		else
		{
			$forumcheck = "thread.forumid = " . $vbulletin->GPC['forumid'] . " AND ";
		}
	}
	else
	{
		$forumcheck = '';
	}

?>
	<script type="text/javascript">
	function js_check_all_posts()
	{
		for (var i=0; i < document.cpform.elements.length; i++)
		{
			var e = document.cpform.elements[i];
			if (e.name != 'allboxposts' && e.name != 'allboxthreads' && e.type=='checkbox' && e.name.substring(0, 10) == 'deletepost')
			{
				e.checked = document.cpform.allboxposts.checked;
			}
		}
	}

	function js_check_all_threads()
	{
		for (var i=0;i < document.cpform.elements.length;i++)
		{
			var e = document.cpform.elements[i];
			if (e.name != 'allboxposts' && e.name != 'allboxthreads' && e.type=='checkbox' && e.name.substring(0, 12) == 'deletethread')
			{
				e.checked = document.cpform.allboxthreads.checked;
			}
		}
	}
	</script>
<?php

	print_form_header('thread', 'dopruneuser');
	print_table_header($vbphrase['prune_threads']);
	print_label_row($vbphrase['title'], '<label for="cb_allthreads">' . $vbphrase['delete'] . ' <input type="checkbox" name="allboxthreads" title="' . $vbphrase['check_all'] . '" onClick="js_check_all_threads();" checked="checked" /></label>', 'thead');

	$threads = $db->query_read("
		SELECT threadid,thread.title
		FROM " . TABLE_PREFIX . "thread AS thread
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum USING(forumid)
		WHERE $forumcheck postusername = '" . $db->escape_string($username) . "'
		ORDER BY thread.lastpost DESC
	");
	while ($thread = $db->fetch_array($threads))
	{
			print_checkbox_row("<a href=\"" .	fetch_seo_url('thread|bburl', $thread) . "\" target=\"_blank\">$thread[title]</a>", 
				"deletethread[$thread[threadid]]", 1, 1);
	}

	print_table_break();
	print_table_header($vbphrase['prune_posts']);
	print_label_row($vbphrase['title'], '<label for="cb_allposts">' . $vbphrase['delete'] . ' <input type="checkbox" name="allboxposts" tabindex="1" title="' . $vbphrase['check_all'] . '" onClick="js_check_all_posts();" checked="checked" /></label>', 'thead');

	$threads = $db->query_read("
		SELECT post.postid,thread.threadid,thread.title
		FROM " . TABLE_PREFIX . "post AS post, " . TABLE_PREFIX . "thread AS thread
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum USING (forumid)
		WHERE thread.threadid = post.threadid
			AND thread.firstpostid <> post.postid
			AND $forumcheck post.userid=" . $vbulletin->GPC['userid'] . "
		ORDER BY post.threadid DESC, post.dateline DESC
	");
	while ($thread = $db->fetch_array($threads))
	{
		print_checkbox_row("<a href=\"" . fetch_seo_url('thread|bburl', $thread, array('p' => $thread['postid'])) . "#post$thread[postid]" . 
			"\" target=\"_blank\">$thread[title]</a> (postid $thread[postid])", "deletepost[$thread[postid]]", 1, 1);
	}

	print_table_break();

	print_submit_row($vbphrase['submit']);
}

// ###################### Start Prune by user selected #######################
if ($_POST['do'] == 'dopruneuser')
{

	require_once(DIR . '/includes/functions_log_error.php');

	$vbulletin->input->clean_array_gpc('p', array(
		'deletethread' => TYPE_ARRAY_BOOL,
		'deletepost'   => TYPE_ARRAY_BOOL,
	));

	$deletethread = array_keys($vbulletin->GPC['deletethread']);
	$deletepost = array_keys($vbulletin->GPC['deletepost']);

	if (empty($deletethread) AND empty($deletepost))
	{
		print_stop_message('no_matches_found');
	}

	if (!empty($deletethread))
	{
		echo '<p><b>' . $vbphrase['deleting_threads'] . '</b>';
		foreach ($deletethread AS $threadid)
		{
			$threadinfo = fetch_threadinfo($threadid);

			// 3.5.1 Bug 1803: Make sure we have smth. to delete
			if (!is_array($threadinfo))
			{
				continue;
			}

			$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$threadman->set_existing($threadinfo);
			$threadman->delete(0);
			unset($threadman);

			echo ". \n";
			vbflush();
		}
		echo ' ' . $vbphrase['done'] . '</p>';
	}

	if (!empty($deletepost))
	{
		echo '<p><b>' . $vbphrase['deleting_posts'] . '</b>';
		foreach ($deletepost AS $postid)
		{
			$postinfo = fetch_postinfo($postid);

			// 3.5.1 Bug 1803: Make sure we have smth. to delete
			if (!is_array($postinfo))
			{
				continue;
			}

			$postman =& datamanager_init('Post', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$postman->set_existing($postinfo);
			$postman->delete();
			unset($postman);

			echo ". \n";
			vbflush();
		}
		echo ' ' . $vbphrase['done'] . '</p>';
	}

	//define('CP_REDIRECT', 'thread.php?do=prune');
	define('CP_BACKURL', '');
	print_stop_message('pruned_threads_successfully');
}

// ###################### Start Prune #######################
if ($_REQUEST['do'] == 'prune')
{
	print_form_header('', '');
	print_table_header($vbphrase['prune_threads_manager']);
	print_description_row($vbphrase['pruning_many_threads_is_a_server_intensive_process']);
	print_table_footer();

	print_form_header('thread', 'dothreads');
	construct_hidden_code('type', 'prune');
	print_move_prune_rows();
	print_submit_row($vbphrase['prune_threads']);

	print_form_header('thread', 'pruneuser');
	print_table_header($vbphrase['prune_by_username']);
	print_input_row($vbphrase['username'], 'username');
	print_forum_chooser($vbphrase['forum'], 'forumid', -1, $vbphrase['all_forums'], true);

	print_yes_no_row($vbphrase['include_child_forums'], 'subforums');
	print_submit_row($vbphrase['prune_threads']);
}

// ###################### Start Move #######################
if ($_REQUEST['do'] == 'move')
{
	print_form_header('thread', 'dothreads');
	construct_hidden_code('type', 'move');
	print_table_header($vbphrase['move_threads']);
	print_forum_chooser($vbphrase['destination_forum'], 'destforumid', -1, '', true);
	print_move_prune_rows();
	print_submit_row($vbphrase['move_threads']);
}

// ###################### Start Prune Post Edit History #######################
if ($_REQUEST['do'] == 'pruneedit')
{
	print_form_header('', '');
	print_table_header($vbphrase['prune_post_edit_history_manager']);
	print_description_row($vbphrase['pruning_many_histories_is_a_server_intensive_process']);
	print_table_footer();

	print_form_header('thread', 'doposthistories');
	construct_hidden_code('type', 'prune');
	print_prune_edit_history_rows();
	print_submit_row($vbphrase['prune_post_edit_history']);
}

// ###################### Start pruneedithistoryrows #######################
function print_prune_edit_history_rows()
{
	global $vbphrase;
	print_description_row($vbphrase['date_options'], 0, 2, 'thead', 'center');
		print_input_row($vbphrase['last_post_edit_date_is_at_least_xx_days_ago'], 'postedit[originaldaysolder]', 0, 1, 5);
		print_input_row($vbphrase['last_post_edit_date_is_at_most_xx_days_ago'] . '<dfn>' . construct_phrase($vbphrase['note_leave_x_specify_no_limit'], '0') . '</dfn>', 'postedit[originaldaysnewer]', 0, 1, 5);
		print_input_row($vbphrase['last_post_in_thread_is_at_least_xx_days_ago'], 'thread[lastdaysolder]', 0, 1, 5);
		print_input_row($vbphrase['last_post_in_thread_is_at_most_xx_days_ago'] . '<dfn>' . construct_phrase($vbphrase['note_leave_x_specify_no_limit'], '0') . '</dfn>', 'thread[lastdaysnewer]', 0, 1, 5);

	print_description_row($vbphrase['other_options'], 0, 2, 'thead', 'center');
		print_input_row($vbphrase['thread_title'], 'thread[titlecontains]');
		print_forum_chooser($vbphrase['forum'], 'thread[forumid]', -1, $vbphrase['all_forums'], true);
		print_yes_no_row($vbphrase['include_child_forums'], 'thread[subforums]');
}

/************ GENERAL MOVE/PRUNE HANDLING CODE ******************/

// ###################### Helper function to prune post edit histories and update them #######################
function do_prune_post_edit_histories($histories)
{
	global $vbphrase, $db;
	$postids = array();
	echo '<p><b>' . $vbphrase['deleting_post_edit_histories'] . '</b>';
	while ($history = $db->fetch_array($histories))
	{
		$postids[] = $history['postid'];
	}
	while(count($postids))
	{
		// work in batches of 1000 at a time
		$to_delete = array_slice($postids, 0, 1000);
		$delete_query = "
			DELETE FROM " . TABLE_PREFIX . "postedithistory
			WHERE postid IN ( " . implode(',', $to_delete) . " )
		";
		$db->query_write($delete_query);

		// Remove the history links
		$update_query = "
			UPDATE " . TABLE_PREFIX . "editlog
			SET hashistory = 0
			WHERE hashistory != 0
			AND postid IN ( " . implode(',', $to_delete) . " )
		";
		$db->query_write($update_query);
		echo ".";
		vbflush();

		// remove the 1000 from our $postids array
		$postids = array_splice($postids, 1000);
	}
}

// ###################### Start makeprunemoveboxes #######################
function print_move_prune_rows()
{
	global $vbphrase;
	print_description_row($vbphrase['date_options'], 0, 2, 'thead', 'center');
		print_input_row($vbphrase['original_post_date_is_at_least_xx_days_ago'], 'thread[originaldaysolder]', 0, 1, 5);
		print_input_row($vbphrase['original_post_date_is_at_most_xx_days_ago'] . '<dfn>' . construct_phrase($vbphrase['note_leave_x_specify_no_limit'], '0') . '</dfn>', 'thread[originaldaysnewer]', 0, 1, 5);
		print_input_row($vbphrase['last_post_date_is_at_least_xx_days_ago'], 'thread[lastdaysolder]', 0, 1, 5);
		print_input_row($vbphrase['last_post_date_is_at_most_xx_days_ago'] . '<dfn>' . construct_phrase($vbphrase['note_leave_x_specify_no_limit'], '0') . '</dfn>', 'thread[lastdaysnewer]', 0, 1, 5);

	print_description_row($vbphrase['view_options'], 0, 2, 'thead', 'center');
		print_input_row($vbphrase['thread_has_at_least_xx_replies'], 'thread[repliesleast]', 0, 1, 5);
		print_input_row($vbphrase['thread_has_at_most_xx_replies'] . '<dfn>' . construct_phrase($vbphrase['note_leave_x_specify_no_limit'], '-1') . '</dfn>', 'thread[repliesmost]', -1, 1, 5);
		print_input_row($vbphrase['thread_has_at_least_xx_views'], 'thread[viewsleast]', 0, 1, 5);
		print_input_row($vbphrase['thread_has_at_most_xx_views'] . '<dfn>' . construct_phrase($vbphrase['note_leave_x_specify_no_limit'], '-1') . '</dfn>', 'thread[viewsmost]', -1, 1, 5);

	print_description_row($vbphrase['status_options'], 0, 2, 'thead', 'center');
		print_yes_no_other_row($vbphrase['thread_is_sticky'], 'thread[issticky]', $vbphrase['either'], 0);

		$state = array(
			'visible' => $vbphrase['visible'],
			'moderation' => $vbphrase['awaiting_moderation'],
			'deleted' => $vbphrase['deleted'],
			'any' => $vbphrase['any']
		);
		print_radio_row($vbphrase['thread_state'], 'thread[state]', $state, 'any');

		$status = array(
			'open' => $vbphrase['thread_open'],
			'closed' => $vbphrase['thread_closed'],
			'redirect' => $vbphrase['redirect'],
			'not_redirect' => $vbphrase['not_redirect'],
			'any' => $vbphrase['any']
		);
		print_radio_row($vbphrase['thread_status'], 'thread[status]', $status, 'not_redirect');

	print_description_row($vbphrase['other_options'], 0, 2, 'thead', 'center');
		print_input_row($vbphrase['username'], 'thread[posteduser]');
		print_input_row($vbphrase['title'], 'thread[titlecontains]');
		print_forum_chooser($vbphrase['forum'], 'thread[forumid]', -1, $vbphrase['all_forums'], true);
		print_yes_no_row($vbphrase['include_child_forums'], 'thread[subforums]');

		if ($prefix_options = construct_prefix_options(0, '', true, true))
		{
			print_label_row($vbphrase['prefix'], '<select name="thread[prefixid]" class="bginput">' . $prefix_options . '</select>', '', 'top', 'prefixid');
		}
}

// ###################### Start genpruneedithistoryquery #######################
function fetch_post_history_prune_sql($thread, $postedit)
{
	global $db, $vbphrase;

	$thread['forumid'] = intval($thread['forumid']);
	$query = '1=1';

	// original post
	if (intval($postedit['originaldaysolder']))
	{
		$query .= ' AND editlog.dateline <= ' . (TIMENOW - ($postedit['originaldaysolder'] * 86400));
	}
	if (intval($postedit['originaldaysnewer']))
	{
		$query .= ' AND editlog.dateline >= ' . (TIMENOW - ($postedit['originaldaysnewer'] * 86400));
	}

	// last post
	if (intval($thread['lastdaysolder']))
	{
		$query .= ' AND thread.lastpost <= ' . (TIMENOW - ($thread['lastdaysolder'] * 86400));
	}
	if (intval($thread['lastdaysnewer']))
	{
		$query .= ' AND thread.lastpost >= ' . (TIMENOW - ($thread['lastdaysnewer'] * 86400));
	}

	// title contains
	if ($thread['titlecontains'])
	{
		$query .= " AND thread.title LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($thread['titlecontains'])) . "%'";
	}

	// forum
	$thread['forumid'] = intval($thread['forumid']);

	if ($thread['forumid'] != -1)
	{
		if ($thread['subforums'])
		{
			$query .= " AND (thread.forumid = $thread[forumid] OR forum.parentlist LIKE '%,$thread[forumid],%')";
		}
		else
		{
			$query .= " AND thread.forumid = $thread[forumid]";
		}
	}

	return $query;
}

// ###################### Start genmoveprunequery #######################
function fetch_thread_move_prune_sql($thread)
{
	global $db, $vbphrase;

	$thread['forumid'] = intval($thread['forumid']);
	$query = '1=1';

	// original post
	if (intval($thread['originaldaysolder']))
	{
		$query .= ' AND thread.dateline <= ' . (TIMENOW - ($thread['originaldaysolder'] * 86400));
	}
	if (intval($thread['originaldaysnewer']))
	{
		$query .= ' AND thread.dateline >= ' . (TIMENOW - ($thread['originaldaysnewer'] * 86400));
	}

	// last post
	if (intval($thread['lastdaysolder']))
	{
		$query .= ' AND thread.lastpost <= ' . (TIMENOW - ($thread['lastdaysolder'] * 86400));
	}
	if (intval($thread['lastdaysnewer']))
	{
		$query .= ' AND thread.lastpost >= ' . (TIMENOW - ($thread['lastdaysnewer'] * 86400));
	}

	// replies
	if (intval($thread['repliesleast']) > 0)
	{
		$query .= ' AND thread.replycount >= ' . intval($thread['repliesleast']);
	}
	if (intval($thread['repliesmost']) > -1)
	{
		$query .= ' AND thread.replycount <= ' . intval($thread['repliesmost']);
	}

	// views
	if (intval($thread['viewsleast']) > 0)
	{
		$query .= ' AND thread.views >= ' . intval($thread['viewsleast']);
	}
	if (intval($thread['viewsmost']) > -1)
	{
		$query .= ' AND thread.views <= ' . intval($thread['viewsmost']);
	}

	// sticky
	if ($thread['issticky'] == 1)
	{
		$query .= ' AND thread.sticky = 1';
	}
	else if ($thread['issticky'] == 0)

	{
		$query .= ' AND thread.sticky = 0';
	}

	// state
	switch ($thread['state'])
	{
		case 'visible':
			$query .= ' AND thread.visible = 1';
			break;

		case 'moderation':
			$query .= ' AND thread.visible = 0';
			break;

		case 'deleted':
			$query .= ' AND thread.visible = 2';
			break;
	}

	//status
	switch ($thread['status'])
	{
		case 'open':
			$query .= ' AND thread.open = 1';
			break;

		case 'closed':
			$query .= ' AND thread.open = 0';
			break;

		case 'redirect':
			$query .= ' AND thread.open = 10';
			break;

		case 'not_redirect':
			$query .= ' AND thread.open <> 10';
			break;
	}

	// posted by
	if ($thread['posteduser'])
	{
		$user = $db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "user
			WHERE username = '" . $db->escape_string(htmlspecialchars_uni($thread['posteduser'])) . "'
		");
		if (!$user)
		{
			print_stop_message('invalid_username_specified');
		}
		$query .= " AND thread.postuserid = $user[userid]";
	}

	// title contains
	if ($thread['titlecontains'])
	{
		$query .= " AND thread.title LIKE '%" . $db->escape_string_like(htmlspecialchars_uni($thread['titlecontains'])) . "%'";
	}

	// forum
	$thread['forumid'] = intval($thread['forumid']);

	if ($thread['forumid'] != -1)
	{
		if ($thread['subforums'])
		{
			$query .= " AND (thread.forumid = $thread[forumid] OR forum.parentlist LIKE '%,$thread[forumid],%')";
		}
		else
		{
			$query .= " AND thread.forumid = $thread[forumid]";
		}
	}

	// prefixid
	switch ($thread['prefixid'])
	{
		case '': // any prefix, no limit
			break;

		case '-1': // none
			$query .= " AND thread.prefixid = ''";
			break;

		default: // a prefix
			$query .= " AND thread.prefixid = '" . $db->escape_string($thread['prefixid']) . "'";
			break;
	}

	return $query;
}

// ###################### Start post edit history prune by options #######################
if ($_POST['do'] == 'doposthistories')
{
	// While we are only having one type right now -- prune -- the type parameter is passed and kept for future purposes
	$vbulletin->input->clean_array_gpc('p', array(
		'type'		=> TYPE_NOHTML,
		'thread'	=> TYPE_ARRAY,
		'postedit'	=> TYPE_ARRAY,
	));

	$whereclause = fetch_post_history_prune_sql($vbulletin->GPC['thread'], $vbulletin->GPC['postedit']);

	if ($vbulletin->GPC['thread']['forumid'] == 0)
	{
		print_stop_message('please_complete_required_fields');
	}

	$fullquery = "
		SELECT COUNT(*) AS count
		FROM " . TABLE_PREFIX . "editlog AS editlog
		LEFT JOIN " . TABLE_PREFIX . "post AS post ON (post.postid = editlog.postid)
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = post.threadid)
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum ON (forum.forumid = thread.forumid)
		WHERE $whereclause
		AND editlog.hashistory = 1
	";
	$count = $db->query_first($fullquery);

	if (!$count['count'])
	{
		print_stop_message('no_post_edit_histories_matched_your_query');
	}

	print_form_header('thread', 'dopostedithistoriesall');
	construct_hidden_code('type', $vbulletin->GPC['type']);
	construct_hidden_code('thread', sign_client_string(serialize($vbulletin->GPC['thread'])));
	construct_hidden_code('postedit', sign_client_string(serialize($vbulletin->GPC['postedit'])));

	print_table_header(construct_phrase($vbphrase['x_post_with_edit_history_matches_found'], $count['count']));
	print_submit_row($vbphrase['prune_all_post_edit_histories'], '');
}

// ###################### Start thread move/prune by options #######################
if ($_POST['do'] == 'dothreads')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'type'        => TYPE_NOHTML,
		'thread'      => TYPE_ARRAY,
		'destforumid' => TYPE_INT,
	));

	$whereclause = fetch_thread_move_prune_sql($vbulletin->GPC['thread']);

	if ($vbulletin->GPC['thread']['forumid'] == 0)
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($vbulletin->GPC['type'] == 'move')
	{
		$foruminfo = fetch_foruminfo($vbulletin->GPC['destforumid']);
		if (!$foruminfo)
		{
			print_stop_message('invalid_destination_forum_specified');
		}
		if (!$foruminfo['cancontainthreads'] OR $foruminfo['link'])
		{
			print_stop_message('destination_forum_cant_contain_threads');
		}
	}

	$fullquery = "
		SELECT COUNT(*) AS count
		FROM " . TABLE_PREFIX . "thread AS thread
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum ON (forum.forumid = thread.forumid)
		WHERE $whereclause
	";
	$count = $db->query_first($fullquery);

	if (!$count['count'])
	{
		print_stop_message('no_threads_matched_your_query');
	}

	print_form_header('thread', 'dothreadsall');
	construct_hidden_code('type', $vbulletin->GPC['type']);
	construct_hidden_code('criteria', sign_client_string(serialize($vbulletin->GPC['thread'])));

	print_table_header(construct_phrase($vbphrase['x_thread_matches_found'], $count['count']));
	if ($vbulletin->GPC['type'] == 'prune')
	{
		print_submit_row($vbphrase['prune_all_threads'], '');
	}
	else
	{
		construct_hidden_code('destforumid', $vbulletin->GPC['destforumid']);
		print_submit_row($vbphrase['move_all_threads'], '');
	}

	print_form_header('thread', 'dothreadssel');
	construct_hidden_code('type', $vbulletin->GPC['type']);
	construct_hidden_code('criteria', sign_client_string(serialize($vbulletin->GPC['thread'])));
	print_table_header(construct_phrase($vbphrase['x_thread_matches_found'], $count['count']));
	if ($vbulletin->GPC['type'] == 'prune')
	{
		print_submit_row($vbphrase['prune_threads_selectively'], '');
	}
	else
	{
		construct_hidden_code('destforumid', $vbulletin->GPC['destforumid']);
		print_submit_row($vbphrase['move_threads_selectively'], '');
	}
}

// ###################### Start move/prune all matching post edit histories #######################
if ($_POST['do'] == 'dopostedithistoriesall')
{
	require_once(DIR . '/includes/functions_log_error.php');
	$vbulletin->input->clean_array_gpc('p', array(
		'type'		=> TYPE_NOHTML,
		'thread'	=> TYPE_STR,
		'postedit'	=> TYPE_STR,
	));

	$thread = @unserialize(verify_client_string($vbulletin->GPC['thread']));
	$postedit = @unserialize(verify_client_string($vbulletin->GPC['postedit']));

	$whereclause = fetch_post_history_prune_sql($thread, $postedit);

	$fullquery = "
		SELECT editlog.postid AS postid
		FROM " . TABLE_PREFIX . "editlog AS editlog
		LEFT JOIN " . TABLE_PREFIX . "post AS post ON (post.postid = editlog.postid)
		LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = post.threadid)
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum ON (forum.forumid = thread.forumid)
		WHERE $whereclause
	";

	$histories = $db->query_read($fullquery);

	if ($vbulletin->GPC['type'] == 'prune')
	{
		do_prune_post_edit_histories($histories);

		define('CP_BACKURL', '');
		print_stop_message('pruned_post_edit_history_successfully');
	}
}

// ###################### Start move/prune all matching #######################
if ($_POST['do'] == 'dothreadsall')
{
	require_once(DIR . '/includes/functions_log_error.php');

	$vbulletin->input->clean_array_gpc('p', array(
		'type'        => TYPE_NOHTML,
		'criteria'    => TYPE_STR,
		'destforumid' => TYPE_INT,
	));

	$thread = @unserialize(verify_client_string($vbulletin->GPC['criteria']));
	$whereclause = fetch_thread_move_prune_sql($thread);

	$fullquery = "
		SELECT *
		FROM " . TABLE_PREFIX . "thread AS thread
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum ON (forum.forumid = thread.forumid)
		WHERE $whereclause
	";
	$threads = $db->query_read($fullquery);

	if ($vbulletin->GPC['type'] == 'prune')
	{
		echo '<p><b>' . $vbphrase['deleting_threads'] . '</b>';
		while ($thread = $db->fetch_array($threads))
		{
			$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
			$threadman->set_existing($thread);
			$threadman->delete(0);
			unset($threadman);

			echo ". \n";
			vbflush();
		}
		echo ' ' . $vbphrase['done'] . '</p>';

		//define('CP_REDIRECT', 'thread.php?do=prune');
		define('CP_BACKURL', '');
		print_stop_message('pruned_threads_successfully');
	}
	else if ($vbulletin->GPC['type'] == 'move')
	{
		$threadslist = '0';
		while ($thread = $db->fetch_array($threads))
		{
			$threadslist .= ",$thread[threadid]";
		}

		$db->query_write("
			UPDATE " . TABLE_PREFIX . "thread SET
				forumid = " . $vbulletin->GPC['destforumid'] . "
			WHERE threadid IN ($threadslist)
		");

		$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "postparsed");

		require_once(DIR . '/includes/functions_prefix.php');
		remove_invalid_prefixes($threadslist, $vbulletin->GPC['destforumid']);

		require_once(DIR . '/includes/functions_databuild.php');
		build_forum_counters($vbulletin->GPC['destforumid']);

		//define('CP_REDIRECT', 'thread.php?do=move');
		define('CP_BACKURL', '');
		print_stop_message('moved_threads_successfully');
	}
}

// ###################### Start move/prune select #######################
if ($_POST['do'] == 'dothreadssel')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'type'        => TYPE_NOHTML,
		'criteria'    => TYPE_STR,
		'destforumid' => TYPE_INT,
	));

	$thread = @unserialize(verify_client_string($vbulletin->GPC['criteria']));
	$whereclause = fetch_thread_move_prune_sql($thread);

	$fullquery = "
		SELECT thread.*, forum.title AS forum_title
		FROM " . TABLE_PREFIX . "thread AS thread
		LEFT JOIN " . TABLE_PREFIX . "forum AS forum ON (forum.forumid = thread.forumid)
		WHERE $whereclause
	";
	$threads = $db->query_read($fullquery);

	print_form_header('thread', 'dothreadsselfinish');
	construct_hidden_code('type', $vbulletin->GPC['type']);
	construct_hidden_code('destforumid', $vbulletin->GPC['destforumid']);
	if ($vbulletin->GPC['type'] == 'prune')
	{
		print_table_header($vbphrase['prune_threads_selectively'], 5);
	}
	else if ($vbulletin->GPC['type'] == 'move')
	{
		print_table_header($vbphrase['move_threads_selectively'], 5);
	}
	print_cells_row(array(
		'<input type="checkbox" name="allbox" title="' . $vbphrase['check_all'] . '" onclick="js_check_all(this.form);" checked="checked" />',
		$vbphrase['title'],
		$vbphrase['user'],
		$vbphrase['replies'],
		$vbphrase['last_post']
	), 1);

	while ($thread = $db->fetch_array($threads))
	{
		$thread['prefix_plain_html'] = ($thread['prefixid'] ? htmlspecialchars_uni($vbphrase["prefix_$thread[prefixid]_title_plain"]) : '');

		$cells = array();
		$cells[] = "<input type=\"checkbox\" name=\"thread[$thread[threadid]]\" tabindex=\"1\" checked=\"checked\" />";
		$cells[] = $thread['prefix_plain_html'] . " <a href=\"" . fetch_seo_url('thread|bburl', $thread) . 
			"\" target=\"_blank\">$thread[title]</a>";
		if ($thread['postuserid'])
		{
			$cells[] = "<span class=\"smallfont\"><a href=\"" . fetch_seo_url('member|bburl', $thread, null, 'postuserid', 'postusername') . "\">$thread[postusername]</a></span>";
		}
		else
		{
			$cells[] = '<span class="smallfont">' . $thread['postusername'] . '</span>';
		}
		$cells[] = "<span class=\"smallfont\">$thread[replycount]</span>";
		$cells[] = '<span class="smallfont">' . vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'], $thread['lastpost']) . '</span>';
		print_cells_row($cells, 0, 0, -1);
	}
	print_submit_row($vbphrase['go'], NULL, 5);

}

// ###################### Start move/prune select - finish! #######################
if ($_POST['do'] == 'dothreadsselfinish')
{

	require_once(DIR . '/includes/functions_log_error.php');

	$vbulletin->input->clean_array_gpc('p', array(
		'type'        => TYPE_NOHTML,
		'thread'      => TYPE_ARRAY_BOOL,
		'destforumid' => TYPE_INT,
	));

	$thread = array_keys($vbulletin->GPC['thread']);

	if (!empty($thread))
	{
		if ($vbulletin->GPC['type'] == 'prune')
		{
			echo '<p><b>' . $vbphrase['deleting_threads'] . '</b>';
			foreach ($thread AS $threadid)
			{
				$threadinfo = fetch_threadinfo($threadid);

				// 3.5.1 Bug 1803: Make sure we have smth. to delete
				if (!is_array($threadinfo))
				{
					continue;
				}

				$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_SILENT, 'threadpost');
				$threadman->set_existing($threadinfo);
				$threadman->delete(0);
				unset($threadman);

				echo ". \n";
				vbflush();
			}

			//define('CP_REDIRECT', 'thread.php?do=prune');
			define('CP_BACKURL', '');
			print_stop_message('pruned_threads_successfully');
		}
		else if ($vbulletin->GPC['type'] == 'move')
		{
			$threadslist = '0';
			foreach ($thread AS $threadid)
			{
				$threadslist .= ', ' . intval($threadid);
			}

			$db->query_write("
				UPDATE " . TABLE_PREFIX . "thread SET
					forumid = " . $vbulletin->GPC['destforumid'] . "
				WHERE threadid IN ($threadslist)
			");

			$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "postparsed");

			require_once(DIR . '/includes/functions_prefix.php');
			remove_invalid_prefixes($threadslist, $vbulletin->GPC['destforumid']);

			require_once(DIR . '/includes/functions_databuild.php');
			build_forum_counters($vbulletin->GPC['destforumid']);

			//define('CP_REDIRECT', 'thread.php?do=move');
			define('CP_BACKURL', '');
			print_stop_message('moved_threads_successfully');
		}
	}
}

// **********************************************************************
// *** POLL STRIPPING SYSTEM - removes a poll from a thread *************
// **********************************************************************

// ###################### Start confirm kill poll #######################
if ($_POST['do'] == 'removepoll')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'threadid' => TYPE_UINT,
	));

	if (empty($vbulletin->GPC['threadid']))
	{
		print_stop_message('invalid_x_specified', 'threadid');
	}
	else
	{
		$thread = $db->query_first("
			SELECT thread.threadid, thread.title, thread.postusername, thread.pollid, poll.question
			FROM " . TABLE_PREFIX . "thread AS thread
			LEFT JOIN " . TABLE_PREFIX . "poll AS poll USING (pollid)
			WHERE threadid = " . $vbulletin->GPC['threadid'] . "
				AND open <> 10
		");
		if (!$thread['threadid'])
		{
			print_stop_message('invalid_x_specified', 'threadid');
		}
		else if (!$thread['pollid'])
		{
			print_stop_message('invalid_x_specified', 'pollid');
		}
		else
		{
			print_form_header('thread', 'doremovepoll');
			construct_hidden_code('threadid', $thread['threadid']);
			construct_hidden_code('pollid', $thread['pollid']);
			print_table_header($vbphrase['delete_poll']);
			print_label_row($vbphrase['posted_by'], "<i>$thread[postusername]</i>");
			print_label_row($vbphrase['title'], "<i>$thread[title]</i>");
			print_label_row($vbphrase['question'], "<i>$thread[question]</i>");
			print_submit_row($vbphrase['delete'], 0);
		}
	}
}

// ###################### Start do kill poll #######################
if ($_POST['do'] == 'doremovepoll')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'threadid' => TYPE_UINT,
		'pollid'   => TYPE_UINT
	));

	// check valid thread + poll
	$thread = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE threadid = " . $vbulletin->GPC['threadid'] . " AND pollid = " . $vbulletin->GPC['pollid']);
	if ($thread)
	{
		$pollman =& datamanager_init('Poll', $vbulletin, ERRTYPE_CP);
		$pollman->set_existing($thread);
		$pollman->delete();

		$threadman =& datamanager_init('Thread', $vbulletin, ERRTYPE_CP, 'threadpost');
		$threadman->set_existing($thread);
		$threadman->set('pollid', 0);
		$threadman->save();

		require_once(DIR . '/includes/functions_databuild.php');
		build_thread_counters($thread['threadid']);
		build_forum_counters($thread['forumid']);

		define('CP_REDIRECT', 'thread.php?do=killpoll');
		print_stop_message('deleted_poll_successfully');
	}
	else
	{
		print_stop_message('invalid_poll_specified');
	}

}

// ###################### Start kill poll #######################
if ($_REQUEST['do'] == 'killpoll')
{

	print_form_header('thread', 'removepoll');
	print_table_header($vbphrase['delete_poll']);
	print_input_row($vbphrase['enter_the_threadid_of_the_thread'], 'threadid', '', 0, 10);
	print_submit_row($vbphrase['continue'], 0);

	echo "\n\n<!-- the pun is intended ;o) -->\n\n";
}

// **********************************************************************
// *** UNSUBSCRIPTION SYSTEM - unsubscribe users from thread(s) *********
// **********************************************************************

// ############### generate id list for specified threads ####################
if ($_POST['do'] == 'dospecificunsubscribe')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'ids' => TYPE_NOHTML,
	));

	if (empty($vbulletin->GPC['ids']))
	{
		print_stop_message('please_complete_required_fields');
	}
	else
	{
		$ids = '';
		$threadids = preg_split('/( )+/', trim($vbulletin->GPC['ids']), -1, PREG_SPLIT_NO_EMPTY);
		foreach ($threadids AS $threadid)
		{
			$ids .= intval($threadid) . ' ';
		}
		$threadids = str_replace(' ', ',', trim($ids));

		$_REQUEST['do'] = 'confirmunsubscribe';
	}

}

// ############### generate id list for mass-selected threads ####################
if ($_POST['do'] == 'domassunsubscribe')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'forumid'   => TYPE_INT,
		'daysprune' => TYPE_UINT,
		'username'  => TYPE_NOHTML,
	));

	if ($vbulletin->GPC['forumid'] == -1)
	{
		unset($vbulletin->GPC['forumid']);
	}

	$datecut = TIMENOW - (86400 * $vbulletin->GPC['daysprune']);

	if (!empty($vbulletin->GPC['username']))
	{
		if (!($userexist = $db->query_first("
			SELECT userid
			FROM " . TABLE_PREFIX . "user
			WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'")))
		{
			print_stop_message('invalid_user_specified');
		}
	}

	if ($vbulletin->GPC['forumid'])
	{
		$sqlconds .= "\n" . iif(empty($sqlconds), 'WHERE', 'AND') . " forumid = " . $vbulletin->GPC['forumid'];
	}
	if ($datecut)
	{
		$sqlconds .= "\n" . iif(empty($sqlconds), 'WHERE', 'AND') . " lastpost < $datecut";
	}

	$threads = $db->query_read("SELECT threadid FROM " . TABLE_PREFIX . "thread $sqlconds");
	if ($db->num_rows($threads))
	{
		$ids = '';
		while ($thread = $db->fetch_array($threads))
		{
			$ids .= "$thread[threadid] ";
		}
		$threadids = str_replace(' ', ',', trim($ids));
		$_REQUEST['do'] = 'confirmunsubscribe';
	}
	else
	{
		print_stop_message('no_threads_matched_your_query');
	}

}

// ############### generate id list for mass-selected threads ####################
if ($_REQUEST['do'] == 'confirmunsubscribe')
{

	if (!isset($threadids))
	{
		print_stop_message('please_complete_required_fields');
	}

	$sub = $db->query_first("SELECT COUNT(*) AS threads
				FROM " . TABLE_PREFIX . "subscribethread
				WHERE threadid IN (" . $db->escape_string($threadids) . ") AND
					emailupdate <> 0
				" . iif($userexist['userid'], " AND userid = $userexist[userid]") . "
				");
	if ($sub['threads'] > 0)
	{
		$idarray = array('threadids' => $threadids);
		print_form_header('thread', 'killsubscription');
		print_table_header($vbphrase['confirm_deletion']);
		if ($userexist['userid'])
		{
			$idarray['userid'] = $userexist['userid'];
			$name = $vbulletin->GPC['username'];
		}
		else
		{
			$name = $vbphrase['all_users'];
		}
		print_description_row(construct_phrase($vbphrase['x_subscriptions_matches_found'], vb_number_format($sub['threads'])) . '<br /><br />' . $vbphrase['are_you_sure_you_want_to_delete_these_subscriptions']);
		print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);

		build_adminutil_text('subscribe', serialize($idarray));
	}
	else
	{
		print_stop_message('no_threads_matched_your_query');
	}

}

// ############### do unsubscribe threads ####################
if ($_POST['do'] == 'killsubscription')
{
	$idarray = unserialize(fetch_adminutil_text('subscribe'));
	$threadids = trim($idarray['threadids']);
	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "subscribethread
		WHERE threadid IN ($threadids) AND
			emailupdate <> 0
		" . iif($idarray['userid'], " AND userid = $idarray[userid]") . "
	");

	define('CP_REDIRECT', 'thread.php?do=unsubscribe');
	print_stop_message('deleted_subscriptions_successfully');
}

// ############### unsubscribe threads ####################
if ($_REQUEST['do'] == 'unsubscribe')
{

	print_form_header('thread', 'dospecificunsubscribe');
	print_table_header($vbphrase['unsubsribe_all_users_from_specific_threads']);
	print_textarea_row($vbphrase['enter_the_threadids_of_the_threads'], 'ids');
	print_submit_row($vbphrase['go']);

	print_form_header('thread', 'domassunsubscribe');
	print_table_header($vbphrase['unsubsribe_all_threads_from_specific_users']);
	print_input_row($vbphrase['username_leave_blank_to_remove_all'], 'username');
	print_input_row($vbphrase['find_all_threads_older_than_days'], 'daysprune', 30);
	print_forum_chooser($vbphrase['forum'], 'forumid', -1, $vbphrase['all_forums']);
	print_submit_row($vbphrase['go']);

}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 49279 $
|| ####################################################################
\*======================================================================*/
?>
