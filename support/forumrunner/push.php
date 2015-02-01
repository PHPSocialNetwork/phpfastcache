<?php
/*
 * Forum Runner
 *
 * Copyright (c) 2010-2011 to End of Time Studios, LLC
 *
 * This file may not be redistributed in whole or significant part.
 *
 * http://www.forumrunner.com
 */

if (!is_object($vbulletin->db)) {
    exit;
}

define(MCWD, DIR . '/forumrunner');

require_once(DIR . '/forumrunner/support/Snoopy.class.php');
if (file_exists(DIR . '/forumrunner/sitekey.php')) {
    require_once(DIR . '/forumrunner/sitekey.php');
} else if (file_exists(DIR . '/forumrunner/vb_sitekey.php')) {
    require_once(DIR . '/forumrunner/vb_sitekey.php');
}
require_once(DIR . '/forumrunner/version.php');
require_once(DIR . '/forumrunner/support/utils.php');

// You must have your valid Forum Runner forum site key.  This can be
// obtained from http://www.forumrunner.com in the Forum Manager.
if (!$mykey || $mykey == '') {
    exit;
}

function
sortbydateline ($a, $b)
{
    if ($a['dateline'] == $b['dateline']) {
	return 0;
    }
    return ($a['dateline'] < $b['dateline']) ? -1 : 1;
}

// First of all, expire all users who have not logged in for 2 weeks, so
// we don't keep spamming the server with their entries.
$vbulletin->db->query_write("
    DELETE FROM " . TABLE_PREFIX . "forumrunner_push_users
    WHERE last_login < DATE_SUB(NOW(), INTERVAL 14 DAY)
");

// Get list of users to check for updates to push
$userids = $vbulletin->db->query_read_slave("
    SELECT vb_userid, fr_username, b
    FROM " . TABLE_PREFIX . "forumrunner_push_users
");

$out_msg = array();

while ($user = $vbulletin->db->fetch_array($userids)) {
    $pms = array();
    $subs = array();

    // Check for new PMs for this user
    $unreadpms = $vbulletin->db->query_read_slave("
	SELECT pm.pmid AS pmid, pmtext.dateline AS dateline, pmtext.fromusername AS fromusername, pmtext.title AS title
	FROM " . TABLE_PREFIX . "pm AS pm
	LEFT JOIN " . TABLE_PREFIX . "pmtext AS pmtext ON pm.pmtextid = pmtext.pmtextid
	WHERE pm.userid = " . $user['vb_userid'] . " AND pm.messageread = 0
    ");

    // Have some PMs.  Check em out.
    if ($vbulletin->db->num_rows($unreadpms)) {
	$pmids = array();
	while ($pm = $vbulletin->db->fetch_array($unreadpms)) {
	    $pms[$pm['pmid']] = $pm;
	    $pmids[] = $pm['pmid'];
	}

	// We have our PM list.  Now lets see which ones we've already sent
	// and eliminate them.
	$sentpms = $vbulletin->db->query_read_slave("
	    SELECT vb_pmid
	    FROM " . TABLE_PREFIX . "forumrunner_push_data
	    WHERE vb_userid = " . $user['vb_userid'] . " AND vb_pmid IN (" . implode(',', $pmids) . ")
	");

	while ($sentpm = $vbulletin->db->fetch_array($sentpms)) {
	    unset($pms[$sentpm['vb_pmid']]);
	}

	unset($sentpms);

	usort($pms, 'sortbydateline');

	// Save that we sent PM notices
	foreach ($pms as $pm) {
	    $vbulletin->db->query_write("
		INSERT INTO " . TABLE_PREFIX . "forumrunner_push_data
		(vb_userid, vb_pmid)
		VALUES
		({$user['vb_userid']}, {$pm['pmid']})
	    ");
	}
    }

    unset($unreadpms);

    if (!$vbulletin->options['threadmarking']) {
	$lastvisit = $vbulletin->db->query_first_slave("
	    SELECT lastvisit FROM " . TABLE_PREFIX . "user WHERE userid = {$user['vb_userid']}
	");
	if ($lastvisit['lastvisit']) {
	    $lastvisit = intval($lastvisit['lastvisit']);
	} else {
	    $lastvisit = 0;
	}
	if ($user['vb_userid'] AND in_coventry($user['vb_userid'], true)) {
	    $lastpost_info = ", IF(tachythreadpost.userid IS NULL, thread.lastpost, tachythreadpost.lastpost) AS lastposts";

	    $tachyjoin = "LEFT JOIN " . TABLE_PREFIX . "tachythreadpost AS tachythreadpost ON " .
		"(tachythreadpost.threadid = subscribethread.threadid AND tachythreadpost.userid = " . $user['vb_userid'] . ')';
	    $lastpost_having = "HAVING lastposts > " . $lastvisit;
	} else {
	    $lastpost_info = ', thread.lastpost AS lastposts';
	    $tachyjoin = '';
	    $lastpost_having = "AND thread.lastpost > " . $lastvisit;
	}

	$subquery = $vbulletin->db->query_read_slave("
		SELECT thread.threadid, thread.title, thread.forumid, thread.postuserid, subscribethread.subscribethreadid
		$lastpost_info
		FROM " . TABLE_PREFIX . "subscribethread AS subscribethread
		INNER JOIN " . TABLE_PREFIX . "thread AS thread USING (threadid)
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = subscribethread.userid)
		$tachyjoin
		WHERE subscribethread.threadid = thread.threadid
			AND subscribethread.userid = " . $user['vb_userid'] . "
			AND thread.visible = 1
			AND subscribethread.canview = 1
			AND thread.lastposter != user.username
		$lastpost_having
	");
    } else {
	$readtimeout = TIMENOW - ($vbulletin->options['markinglimit'] * 86400);

	if ($user['vb_userid'] AND in_coventry($user['vb_userid'], true)) {
	    $lastpost_info = ", IF(tachythreadpost.userid IS NULL, thread.lastpost, tachythreadpost.lastpost) AS lastposts";

	    $tachyjoin = "LEFT JOIN " . TABLE_PREFIX . "tachythreadpost AS tachythreadpost ON " .
		"(tachythreadpost.threadid = subscribethread.threadid AND tachythreadpost.userid = " . $user['vb_userid'] . ')';
	} else {
	    $lastpost_info = ', thread.lastpost AS lastposts';
	    $tachyjoin = '';
	}

	$subquery = $vbulletin->db->query_read_slave("
		SELECT thread.threadid, thread.title, thread.forumid, thread.postuserid,
			IF(threadread.readtime IS NULL, $readtimeout, IF(threadread.readtime < $readtimeout, $readtimeout, threadread.readtime)) AS threadread,
			IF(forumread.readtime IS NULL, $readtimeout, IF(forumread.readtime < $readtimeout, $readtimeout, forumread.readtime)) AS forumread,
			subscribethread.subscribethreadid
			$lastpost_info
		FROM " . TABLE_PREFIX . "subscribethread AS subscribethread
		INNER JOIN " . TABLE_PREFIX . "thread AS thread ON (subscribethread.threadid = thread.threadid)
		LEFT JOIN " . TABLE_PREFIX . "threadread AS threadread ON (threadread.threadid = thread.threadid AND threadread.userid = " . $user['vb_userid'] . ")
		LEFT JOIN " . TABLE_PREFIX . "forumread AS forumread ON (forumread.forumid = thread.forumid AND forumread.userid = " . $user['vb_userid'] . ")
		$tachyjoin
		WHERE subscribethread.userid = " . $user['vb_userid'] . "
			AND thread.visible = 1
			AND subscribethread.canview = 1
		HAVING lastposts > IF(threadread > forumread, threadread, forumread)
	");
    }

    $subs = array();

    while ($thread = $vbulletin->db->fetch_array($subquery)) {
	if ($vbulletin->options['threadmarking'] AND $thread['threadread']) {
	    $threadview = intval($thread['threadread']);
	} else {
	    // Not using thread marking - use user's last visit date.
	    $threadview = $lastvisit;
	}
	if (!$threadview) {
	    continue;
	}
	if ($thread['lastposts'] > $threadview) {
	    // This is an updated thread since last time they were on the forum
	    // Let's see if we sent this already

	    $push_threaddata = $vbulletin->db->query_first_slave("
		SELECT * FROM " . TABLE_PREFIX . "forumrunner_push_data
		WHERE vb_threadid = {$thread['threadid']} AND vb_userid = {$user['vb_userid']}
	    ");
	    if ($push_threaddata) {
		// We have sent a notice about this thread at some point, lets see if
		// our update is newer.
		if ($push_threaddata['vb_threadread'] < $thread['lastposts']) {
		    // Yup.  Send a notice and update table.
		    if ($push_threaddata['vb_subsent']) {
			continue;
		    }

		    $vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "forumrunner_push_data
			SET vb_threadread = {$thread['lastposts']}, vb_subsent = 1
			WHERE id = {$push_threaddata['id']}
		    ");

		    $subs[] = array(
			'threadid' => $thread['threadid'],
			'title' => $thread['title'],
		    );

		} // Already sent update
	    } else {
		// Nope, send an update and insert new
		$subs[] = array(
		    'threadid' => $thread['threadid'],
		    'title' => $thread['title'],
		);

		$vbulletin->db->query_write("
		    INSERT INTO " . TABLE_PREFIX . "forumrunner_push_data
		    (vb_userid, vb_threadid, vb_threadread, vb_subsent)
		    VALUES ({$user['vb_userid']}, {$thread['threadid']}, {$thread['lastposts']}, 1)
		");
	    }
	    unset($push_threaddata);
	}
    }
    unset($subquery);

    $total = count($pms) + count($subs);

    // Nothing to see here... move along....
    $haspm = (count($pms) > 0);
    $hassub = (count($subs) > 0);
    if (!$haspm && !$hassub) {
	continue;
    }

    // Forum name is always first arg.
    $msgargs = array(base64_encode(prepare_utf8_string($vbulletin->options['bbtitle'])));

    $pmpart = 0;
    if ($haspm) {
	if (count($pms) > 1) {
	    $msgargs[] = base64_encode(count($pms));
	    $pmpart = 2;
	} else {
	    $first_pm = array_shift($pms);
	    $msgargs[] = base64_encode(prepare_utf8_string($first_pm['fromusername']));
	    $pmpart = 1;
	}
    }

    $subpart = 0;
    if ($hassub) {
	if (count($subs) > 1) {
	    $msgargs[] = base64_encode(count($subs));
	    $subpart = 2;
	} else {
	    $first_sub = array_shift($subs);
	    $msgargs[] = base64_encode(prepare_utf8_string($first_sub['title']));
	    $subpart = 1;
	}
    }

    $out_msg[] = array(
	'u' => $user['fr_username'],
	'b' => $user['b'],
	'pm' => $haspm,
	'subs' => $hassub,
	'm' => "__FR_PUSH_{$pmpart}PM_{$subpart}SUB",
	'a' => $msgargs,
	't' => $total,
    );
}

// Send our update to Forum Runner central push server.  Silently fail if
// necessary.
if (count($out_msg) > 0) {
    $snoopy = new snoopy();
    $snoopy->submit('http://push.forumrunner.com/push.php',
	array(
	    'k' => $mykey,
	    'm' => serialize($out_msg),
	    'v' => $fr_version,
	    'p' => $fr_platform,
	)
    );
}

?>