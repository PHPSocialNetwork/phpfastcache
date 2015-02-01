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
define('CVS_REVISION', '$RCSfile$ - $Revision: 32878 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cpuser', 'cpoption');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_options.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminsettings'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'questionid' => TYPE_UINT,
	'answerid'   => TYPE_UINT,
));
log_admin_action(!empty($vbulletin->GPC['questionid']) ? 'question id = ' . $vbulletin->GPC['questionid'] : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['human_verification_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'intro';
}

// ###################### Intro Screen #######################
if ($_REQUEST['do'] == 'intro')
{
		$getsettings = array(
			'hv_type',
			'regimagetype',
			'regimageoption',
			'hv_recaptcha_publickey',
			'hv_recaptcha_privatekey',
			'hv_recaptcha_theme',
		);

		$varnames = array();
		foreach ($getsettings AS $setting)
		{
				$varnames[] = 'setting_' . $setting . '_title';
				$varnames[] = 'setting_' . $setting . '_desc';
		}

		($hook = vBulletinHook::fetch_hook('admin_humanverify_intro_start')) ? eval($hook) : false;

		$settingphrase = array();
		$phrases = $db->query_read_slave("
			SELECT varname, text
			FROM " . TABLE_PREFIX . "phrase
			WHERE fieldname = 'vbsettings' AND
				languageid IN(-1, 0, " . LANGUAGEID . ") AND
				varname IN ('" . implode("', '", $varnames) . "')
			ORDER BY languageid ASC
		");
		while($phrase = $db->fetch_array($phrases))
		{
			$settingphrase["$phrase[varname]"] = $phrase['text'];
		}

		$cache = array();
		$settings = $db->query_read_slave("
			SELECT *
			FROM " . TABLE_PREFIX . "setting
			WHERE varname IN ('" . implode("', '", $getsettings) . "')
			ORDER BY displayorder
		");
		while ($setting = $db->fetch_array($settings))
		{
			if ($setting['varname'] == 'hv_type')
			{
				$thesetting = $setting;
			}
			else
			{
				$cache[] = $setting;
			}
		}

		($hook = vBulletinHook::fetch_hook('admin_humanverify_intro_setting')) ? eval($hook) : false;

		print_form_header('verify', 'updateoptions');
		print_column_style_code(array('width:60%', 'width:40%; white-space:nowrap'));
		print_table_header($vbphrase['human_verification_options']);

		print_setting_row($thesetting, $settingphrase);
		print_submit_row($vbphrase['save']);


		switch($vbulletin->options['hv_type'])
		{
			case 'Image':

				print_form_header('verify', 'updateoptions');
				print_column_style_code(array('width:60%', 'width:40%; white-space:nowrap'));
				print_table_header($vbphrase['image_verification_options']);

				foreach($cache AS $setting)
				{
					if ($setting['varname'] == 'regimagetype' OR $setting['varname'] == 'regimageoption')
					{
						print_setting_row($setting, $settingphrase);
					}
				}
				print_submit_row($vbphrase['save']);
				break;

			case 'Question':

				?>
				<script type="text/javascript">
				function js_jump(id, obj)
				{
					task = obj.options[obj.selectedIndex].value;
					switch (task)
					{
						case 'modifyquestion': window.location = "verify.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=modifyquestion&questionid=" + id; break;
						case 'killquestion': window.location = "verify.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=removequestion&questionid=" + id; break;
						default: return false; break;
					}
				}
				</script>
				<?php

				$options = array(
					'modifyquestion' => $vbphrase['edit'],
					'killquestion'   => $vbphrase['delete'],
				);

				$questions = $db->query_read_slave("
					SELECT question.questionid, question.regex, question.dateline, COUNT(*) AS answers, phrase.text, answer.answerid
					FROM " . TABLE_PREFIX . "hvquestion AS question
					LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase ON (phrase.varname = CONCAT('question', question.questionid) AND phrase.fieldname = 'hvquestion' and languageid = 0)
					LEFT JOIN " . TABLE_PREFIX . "hvanswer AS answer ON (question.questionid = answer.questionid)
					GROUP BY question.questionid
					ORDER BY dateline
				");

				print_form_header('verify', 'modifyquestion');
				print_table_header($vbphrase['question_verification_options'], 5);

				if ($db->num_rows($questions))
				{
					print_cells_row(array($vbphrase['question'], $vbphrase['answers'], $vbphrase['regex'], $vbphrase['date'], $vbphrase['controls']), 1);
				}
				else
				{
					print_description_row($vbphrase['not_specified_questions_no_validation'], false, 5);
				}

				while ($question = $db->fetch_array($questions))
				{
					print_cells_row(array(
						$question['text'],
						$question['answerid'] ? $question['answers'] : 0,
						$question['regex'] ? $vbphrase['yes'] : $vbphrase['no'],
						vbdate($vbulletin->options['logdateformat'], $question['dateline']),
						"<span style=\"white-space:nowrap\"><select name=\"q$question[questionid]\" onchange=\"js_jump($question[questionid], this);\" class=\"bginput\">" . construct_select_options($options) . "</select><input type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_jump($question[questionid], this.form.q$question[questionid]);\" class=\"button\" /></span>"
					));
				}
				print_submit_row($vbphrase['add_new_question'], 0, 5);

				break;

			case 'Recaptcha':

				print_form_header('verify', 'updateoptions');
				print_table_header($vbphrase['recaptcha_verification_options']);

				foreach($cache AS $setting)
				{
					if (preg_match('#^hv_recaptcha_#si', $setting['varname']))
					{
						print_setting_row($setting, $settingphrase);
					}
				}
				print_submit_row($vbphrase['save']);
				break;

			default:

				($hook = vBulletinHook::fetch_hook('admin_humanverify_intro_output')) ? eval($hook) : false;
		}

}

// ###################### Edit/Add Question #######################
if ($_REQUEST['do'] == 'modifyquestion')
{
	print_form_header('verify', 'updatequestion');
	if (empty($vbulletin->GPC['questionid']))
	{
		print_table_header($vbphrase['add_new_question']);
	}
	else
	{
		$question = $db->query_first_slave("
			SELECT question.questionid, question.regex, question.dateline, phrase.text
			FROM " . TABLE_PREFIX . "hvquestion AS question
			LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase ON (phrase.varname = CONCAT('question', question.questionid) AND phrase.fieldname = 'hvquestion' and languageid = 0)
			LEFT JOIN " . TABLE_PREFIX . "hvanswer AS answer ON (question.questionid = answer.questionid)
			WHERE question.questionid = " . $vbulletin->GPC['questionid'] . "
		");

		if (!$question)
		{
			print_stop_message('invalid_x_specified', $vbphrase['question']);
		}

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['question'], htmlspecialchars_uni($question['text']), $vbulletin->GPC['questionid']), 2, 0);
		construct_hidden_code('questionid', $vbulletin->GPC['questionid']);
	}

	if ($question['text'])
	{
		print_input_row($vbphrase['question'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=hvquestion&varname=question{$vbulletin->GPC['questionid']}&t=1", 1)  . '</dfn>', 'question', $question['text']);
	}
	else
	{
		print_input_row($vbphrase['question_dfn'], 'question');
	}
	print_input_row($vbphrase['regular_expression_require_match'], 'regex', $question['regex']);
	print_submit_row($vbphrase['save']);

	if (!empty($vbulletin->GPC['questionid']))
	{
		?>
		<script type="text/javascript">
		function js_jump(aid, qid, obj)
		{
			task = obj.options[obj.selectedIndex].value;
			switch (task)
			{
				case 'modifyanswer': window.location = "verify.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=modifyanswer&answerid=" + aid + "&questionid=" + qid; break;
				case 'killanswer': window.location = "verify.php?<?php echo $vbulletin->session->vars['sessionurl_js']; ?>do=removeanswer&answerid=" + aid + "&questionid=" + qid; break;
				default: return false; break;
			}
		}
		</script>
		<?php

		$answers = $db->query_read_slave("
			SELECT answer, answerid, questionid
			FROM " . TABLE_PREFIX . "hvanswer AS answer
			WHERE questionid = " . $vbulletin->GPC['questionid'] . "
			ORDER BY dateline
		");

		print_form_header('verify', 'modifyanswer');
		print_table_header($vbphrase['answers'], 2);
		construct_hidden_code('questionid', $vbulletin->GPC['questionid']);

		if ($db->num_rows($answers))
		{
			print_cells_row(array($vbphrase['answer'], $vbphrase['controls']), 1);
		}

		$options = array(
			'modifyanswer' => $vbphrase['edit'],
			'killanswer'   => $vbphrase['delete'],
		);

		while ($answer = $db->fetch_array($answers))
		{
			print_cells_row(array(
				$answer['answer'],
				"\n\t<select name=\"a$answer[answerid]\" onchange=\"js_jump($answer[answerid], $answer[questionid], this);\" class=\"bginput\">\n" . construct_select_options($options) . "\t</select>\n\t<input type=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_jump($answer[answerid], $answer[questionid], this.form.a$answer[answerid]);\" />\n\t"
			));
		}
		print_submit_row($vbphrase['add_new_answer'], 0, 2);
	}
}

// ###################### Save Question #######################
if ($_POST['do'] == 'updatequestion')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'question' => TYPE_STR,
		'regex'    => TYPE_STR,
	));
	if (empty($vbulletin->GPC['question']))
	{
		print_stop_message('please_complete_required_fields');
	}

	if (empty($vbulletin->GPC['questionid']))
	{
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "hvquestion
				(regex, dateline)
			VALUES
				('" . $vbulletin->db->escape_string($vbulletin->GPC['regex']) . "', " . TIMENOW . ")
		");
		$vbulletin->GPC['questionid'] = $db->insert_id();
	}
	else
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "hvquestion
			SET regex = '" . $db->escape_string($vbulletin->GPC['regex']) . "'
			WHERE questionid = " . $vbulletin->GPC['questionid']
		);
	}

	/*insert_query*/
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "phrase
			(languageid, fieldname, varname, text, product, username, dateline, version)
		VALUES
			(0,
			'hvquestion',
			'question" . $vbulletin->GPC['questionid'] . "',
			'" . $db->escape_string($vbulletin->GPC['question']) . "',
			'vbulletin',
			'" . $db->escape_string($vbulletin->userinfo['username']) . "',
			" . TIMENOW . ",
			'" . $db->escape_string($vbulletin->options['templateversion']) . "')
	");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	define('CP_REDIRECT', 'verify.php?do=modifyquestion&questionid=' . $vbulletin->GPC['questionid']);
	print_stop_message('updated_question_successfully');
}

// ###################### Edit/Add Answer #######################
if ($_REQUEST['do'] == 'modifyanswer')
{
	print_form_header('verify', 'updateanswer');
	$question = $db->query_first_slave("
		SELECT question.questionid, phrase.text
		FROM " . TABLE_PREFIX . "hvquestion AS question
		LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase ON (phrase.varname = CONCAT('question', question.questionid) AND phrase.fieldname = 'hvquestion' and languageid = 0)
		WHERE question.questionid = " . $vbulletin->GPC['questionid'] . "
	");

	if (!$question)
	{
		print_stop_message('invalid_x_specified', $vbphrase['question']);
	}

	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['question'], htmlspecialchars_uni($question['text']), $question['questionid']), 2, 0);

	if (empty($vbulletin->GPC['answerid']))
	{
		print_table_header($vbphrase['add_new_answer']);
		construct_hidden_code('questionid', $vbulletin->GPC['questionid']);
	}
	else
	{
		$answer = $db->query_first_slave("
			SELECT answer.answer, answer.questionid, answerid
			FROM " . TABLE_PREFIX . "hvanswer AS answer
			WHERE answer.answerid = " . $vbulletin->GPC['answerid'] . "
		");
		construct_hidden_code('answerid', $answer['answerid']);
		construct_hidden_code('questionid', $answer['questionid']);
	}

	print_input_row($vbphrase['answer'], 'answer', $answer['answer']);
	print_submit_row($vbphrase['save']);
}

// ###################### Save Question #######################
if ($_POST['do'] == 'updateanswer')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'answer' => TYPE_STR,
	));
	if ($vbulletin->GPC['answer'] === '')
	{
		print_stop_message('please_complete_required_fields');
	}

	if (!($db->query_first("SELECT questionid FROM " . TABLE_PREFIX . "hvquestion WHERE questionid = " . $vbulletin->GPC['questionid'])))
	{
		print_stop_message('invalid_x_specified', $vbphrase['question']);
	}

	if (empty($vbulletin->GPC['answerid']))
	{
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "hvanswer
				(questionid, answer, dateline)
			VALUES
				(" . $vbulletin->GPC['questionid'] . ",'" . $vbulletin->db->escape_string($vbulletin->GPC['answer']) . "', " . TIMENOW . ")
		");
		$vbulletin->GPC['answerid'] = $db->insert_id();
	}
	else
	{
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "hvanswer
			SET answer = '" . $db->escape_string($vbulletin->GPC['answer']) . "'
			WHERE answerid = " . $vbulletin->GPC['answerid']
		);
	}

	define('CP_REDIRECT', 'verify.php?do=modifyquestion&questionid=' . $vbulletin->GPC['questionid']);
	print_stop_message('updated_answer_successfully');
}

// ###################### Remove Answer #######################
if ($_REQUEST['do'] == 'removeanswer')
{
	$answer = $db->query_first_slave("
		SELECT answer, questionid, answerid
		FROM " . TABLE_PREFIX . "hvanswer
		WHERE answerid = " . $vbulletin->GPC['answerid'] . "
	");

	if (!$answer)
	{
		print_stop_message('invalid_x_specified', $vbphrase['answer']);
	}

	print_form_header('verify', 'killanswer');
	construct_hidden_code('answerid', $answer['answerid']);
	print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], htmlspecialchars_uni($answer['answer'])));
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_answer']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Kill Answer #######################
if ($_POST['do'] == 'killanswer')
{
	$answer = $db->query_first_slave("
		SELECT answer, questionid, answerid
		FROM " . TABLE_PREFIX . "hvanswer
		WHERE answerid = " . $vbulletin->GPC['answerid'] . "
	");

	if (!$answer)
	{
		print_stop_message('invalid_x_specified', $vbphrase['answer']);
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "hvanswer
		WHERE answerid = $answer[answerid]
	");

	define('CP_REDIRECT', 'verify.php?do=modifyquestion&questionid=' . $answer['questionid']);
	print_stop_message('deleted_answer_successfully');
}

// ###################### Remove Question #######################
if ($_REQUEST['do'] == 'removequestion')
{
	$question = $db->query_first_slave("
		SELECT questionid, phrase.text
		FROM " . TABLE_PREFIX . "hvquestion AS question
		LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase ON (phrase.varname = CONCAT('question', question.questionid) AND phrase.fieldname = 'hvquestion' and languageid = 0)
		WHERE questionid = " . $vbulletin->GPC['questionid'] . "
	");

	if (!$question)
	{
		print_stop_message('invalid_x_specified', $vbphrase['question']);
	}

	print_form_header('verify', 'killquestion');
	construct_hidden_code('questionid', $question['questionid']);
	print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], htmlspecialchars_uni($question['text'])));
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_question']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Kill Answer #######################
if ($_POST['do'] == 'killquestion')
{
	$question = $db->query_first_slave("
		SELECT questionid
		FROM " . TABLE_PREFIX . "hvquestion AS question
		LEFT JOIN " . TABLE_PREFIX . "phrase AS phrase ON (phrase.varname = CONCAT('question', question.questionid) AND phrase.fieldname = 'hvquestion' and languageid = 0)
		WHERE questionid = " . $vbulletin->GPC['questionid'] . "
	");

	if (!$question)
	{
		print_stop_message('invalid_x_specified', $vbphrase['question']);
	}

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "hvanswer
		WHERE questionid = $question[questionid]
	");

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "hvquestion
		WHERE questionid = $question[questionid]
	");

	$db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phrase
		WHERE fieldname = 'hvquestion' AND
			varname = 'question" . $question[questionid] . "'
	");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	define('CP_REDIRECT', 'verify.php');
	print_stop_message('deleted_question_successfully');
}

// ###################### Intro Screen #######################
if ($_POST['do'] == 'updateoptions')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'setting'  => TYPE_ARRAY,
	));

	save_settings($vbulletin->GPC['setting']);

	define('CP_REDIRECT', 'verify.php');
	print_stop_message('saved_settings_successfully');
}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 16:55, Fri Jul 19th 2013
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>