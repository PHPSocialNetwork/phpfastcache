<?php

/**
 * Skimlinks vBulletin Plugin
 *
 * @author Skimlinks
 * @version 2.0.7
 * @copyright © 2011 Skimbit Ltd.
 */

error_reporting(E_ALL & ~E_NOTICE);

function is_forum($forum) {
	global $vbulletin;
	if (!empty($forum['forumid']))
	{
	if ($forum['options'] & $vbulletin->bf_misc_forumoptions['cancontainthreads'])
	{
	if (empty($forum['link']))
	{
	return true;
	}
	}
	}
	return false;
}

$phrasegroups = array('cpoption', 'cphome');
$specialtemplates = array();

require_once('./global.php');

log_admin_action();
define("SKIMLINKS_SALT", $vbulletin->options['skimlinks_salt']);
print_cp_header($vbphrase['skimlinks_plugin']);
?>
<style type="text/css">
.skimLinksInfo {
	font-size: 11px;
}
input[type='submit'][disabled], input[type='reset'][disabled] {
	color: #999;
}
</style>
<?php

if (empty($_REQUEST['do']) OR $_REQUEST['do'] == 'global')
{
	print_form_header('skimlinks', 'update');
	construct_hidden_code('option_type', 'global');

	print_table_header('Skimlinks - '.$vbphrase['skimlinks_global_options']);
	print_description_row($vbphrase['skimlinks_plugin_description'], false, 2, 'alt1 skimLinksInfo');

	/* skimlinks api */
	$skimlinks_pub_id = isset($vbulletin->options['skimlinks_pub_id']) ? $vbulletin->options['skimlinks_pub_id'] : '';
	$jquerypath = vB_Template_Runtime::fetchStyleVar('jquerymain');
	if (!$show['remotejquery'])
	{
		$jquerypath = '../' . $jquerypath;
	}	
	?>

	<script type="text/javascript" src="<?php echo $jquerypath; ?>"></script>
	<script type="text/javascript">
	<!--
		if (typeof jQuery === "undefined")
		{
			document.write('<script type="text/javascript" src="../clientscript/jquery/jquery-<?php echo JQUERY_VERSION; ?>.min.js">');
		}
	// -->
	</script>

	<script type="text/javascript">
	var skim_cert = '';
	$(function() {
		var ni = document.getElementsByTagName('body')[0];
		var newdiv = document.createElement('script');
		newdiv.setAttribute('src', 'http://api-accounts.skimlinks.com/jsonp/<?php echo urlencode(@SKIMLINKS_SALT); ?>/cert/');
		ni.appendChild(newdiv);
	});
	function skimBack(id) {
		document.getElementById('skim_setup_head').innerHTML = '';
		$('#'+id).fadeOut('fast', function() {
			$('#skim_buttons').fadeIn('fast');
		});
	}
	function skimShow(id) {
		skimClearErrors();
		document.getElementById('skim_setup_head').innerHTML = id == 'skim_form_register' ? " - <?php echo addslashes($vbphrase['skimlinks_global_create_acc']); ?>" : " - <?php echo addslashes($vbphrase['skimlinks_global_ass_acc']); ?>";
		$('#skim_buttons').fadeOut('fast', function() {
			$('#'+id).fadeIn('fast');
		});
	}
	function skimClearErrors() {
		document.getElementById('skimr_name_error').style.display = 'none';
		document.getElementById('skimr_name_error_icon').style.display = 'none';
		document.getElementById('skimr_email_error').style.display = 'none';
		document.getElementById('skimr_email_error_icon').style.display = 'none';
		document.getElementById('skimr_domain_error').style.display = 'none';
		document.getElementById('skimr_domain_error_icon').style.display = 'none';
		document.getElementById('skima_email_error').style.display = 'none';
		document.getElementById('skima_email_error_icon').style.display = 'none';
		document.getElementById('skima_domain_error').style.display = 'none';
		document.getElementById('skima_domain_error_icon').style.display = 'none';
		document.getElementById('skim_form_associate_check_error').style.display = 'none';
		document.getElementById('skim_form_register_check_error').style.display = 'none';
				document.getElementById('assoc_to_reg_btn').style.display = 'block';
		document.getElementById('assoc_to_reg_back').style.display = 'none';
				document.getElementById('reg_to_assoc_btn').style.display = 'block';
		document.getElementById('reg_to_assoc_back').style.display = 'none';
	}
	function skimValidateEmail(email) {
		var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
		return email.match(re)
	}
	var promptchange = false;
	var didask = false;
	function skimEnableSubmit() {
		$('input[type=submit], input[type=reset]').removeAttr('disabled');
		promptchange = true;
	}
	window.onbeforeunload = function(e){
		if (! didask) {
			didask = true;
			e = e || self.window;
			if (promptchange) {
				if (e) {
					e.returnValue = "<?php echo addslashes($vbphrase['unsaved_changes_may_be_lost']); ?>";
				}
				return "<?php echo addslashes($vbphrase['unsaved_changes_may_be_lost']); ?>";
			}
		}
	};
	function skimGetParam(field, call) {
		var param = '';
		if (call == 'register') {
			if (field == 'name') {
				param = $.trim(document.getElementById('skimr_name').value);
			} else if (field == 'email') {
				param = $.trim(document.getElementById('skimr_email').value);
				if (! skimValidateEmail(param)) param = '';
			} else if (field == 'domain') {
				param = $.trim(document.getElementById('skimr_domain').value);
			}
		} else {
			if (field == 'email') {
				param = $.trim(document.getElementById('skima_email').value);
				if (! skimValidateEmail(param)) param = '';
			} else if (field == 'domain') {
				param = $.trim(document.getElementById('skima_domain').value);
			}
		}
		return param;
	}
	function skimGetParams(call) {
		var params = '';
		if (call == 'register') {
			var name = skimGetParam('name', 'register');
			var email = skimGetParam('email', 'register');
			var domain = skimGetParam('domain', 'register');
			if (name != '' && email != '' && domain != '') {
				params = 'do=getid&name='+name+'&email='+email+'&domain='+domain;
			}
		} else {
			var email = skimGetParam('email', 'associate');
			var domain = skimGetParam('domain', 'associate');
			if (name != '' && email != '' && domain != '') {
				params = 'do=getid&email='+email+'&domain='+domain;
			}
		}
		return params;
	}
	// Better URL encoding function
	function skimurlencode(s){
		return encodeURIComponent( s.replace( /\//g, '[skimfs]' ) ).replace( /\%20/g, '+' ).replace( /!/g, '%21' ).replace( /'/g, '%27' ).replace( /\(/g, '%28' ).replace( /\)/g, '%29' ).replace( /\*/g, '%2A' ).replace( /\~/g, '%7E' );
	}
	
	function skimApiCall(call) {
		document.getElementById(call == 'register' ? 'skimr_load' : 'skima_load').style.display = 'inline';
		var params = '';
		if (call == 'register') {
			params = skimurlencode(skimGetParam('email', 'register'))+'/'+skimurlencode(skimGetParam('domain', 'register'))+'/'+skimurlencode(skimGetParam('name', 'register'));
		} else {
			params = skimurlencode(skimGetParam('email', 'associate'))+'/'+skimurlencode(skimGetParam('domain', 'associate'));
		}
		var ni = document.getElementsByTagName('body')[0];
		var newdiv = document.createElement('script');
		newdiv.setAttribute('src', 'http://api-accounts.skimlinks.com/jsonp/<?php echo urlencode(@SKIMLINKS_SALT); ?>/'+call+'/'+skim_cert+'/'+params);
		ni.appendChild(newdiv);
	}
	function skimlinks_callback(data) {
		var call = data.call;
		if (call == 'cert') {
			skim_cert = data.id;
		} else {
			document.getElementById(call == 'register' ? 'skimr_load' : 'skima_load').style.display = 'none';
			if (data.success == 1) {
				document.getElementById('skimlinks_pub_id').value = data.id;
				document.getElementById(call == 'register' ? 'skim_form_register_check' : 'skim_form_associate_check').style.display = 'none';
				document.getElementById(call == 'register' ? 'skim_form_register_successful' : 'skim_form_associate_successful').style.display = 'block';
				skimEnableSubmit();
			} else {
				document.getElementById(call == 'register' ? 'skimr_error' : 'skima_error').innerHTML = data.error;
				document.getElementById(call == 'register' ? 'skim_form_register_check' : 'skim_form_associate_check').style.display = 'none';
				document.getElementById(call == 'register' ? 'skim_form_register_check_error' : 'skim_form_associate_check_error').style.display = 'block';
				if (data.success == 0) {
					document.getElementById(call == 'register' ? 'reg_to_assoc_btn' : 'assoc_to_reg_btn').style.display = 'none';
					document.getElementById(call == 'register' ? 'reg_to_assoc_back' : 'assoc_to_reg_back').style.display = 'block';
				}
			}
		}
	}
	function skimCheck(call) {
		if (call == 'register') {
			var name = skimGetParam('name', 'register');
			var email = skimGetParam('email', 'register');
			var domain = skimGetParam('domain', 'register');
			if (name != '' && email != '' && domain != '') {
				document.getElementById('skimr_name_val').innerHTML = name;
				document.getElementById('skimr_name_val_e').innerHTML = name;
				document.getElementById('skimr_email_val').innerHTML = email;
				document.getElementById('skimr_email_val_e').innerHTML = email;
				document.getElementById('skimr_domain_val').innerHTML = domain;
				document.getElementById('skimr_domain_val_e').innerHTML = domain;
				document.getElementById('skim_form_register_form').style.display = 'none';
				document.getElementById('skim_form_register_check').style.display = 'block';
			} else {
				if (name == '') {
					document.getElementById('skimr_name_error').style.display = 'table-row';
					document.getElementById('skimr_name_error_icon').style.display = 'inline';
				}
				if (email == '') {
					document.getElementById('skimr_email_error').style.display = 'table-row';
					document.getElementById('skimr_email_error_icon').style.display = 'inline';
				}
				if (domain == '') {
					document.getElementById('skimr_domain_error').style.display = 'table-row';
					document.getElementById('skimr_domain_error_icon').style.display = 'inline';
				}
			}
		} else {
			var email = skimGetParam('email', 'associate');
			var domain = skimGetParam('domain', 'associate');
			if (email != '' && domain != '') {
				document.getElementById('skima_email_val').innerHTML = email;
				document.getElementById('skima_domain_val').innerHTML = domain;
				document.getElementById('skima_email_val_e').innerHTML = email;
				document.getElementById('skima_domain_val_e').innerHTML = domain;
				document.getElementById('skim_form_associate_form').style.display = 'none';
				document.getElementById('skim_form_associate_check').style.display = 'block';
			} else {
				if (email == '') {
					document.getElementById('skima_email_error').style.display = 'table-row';
					document.getElementById('skima_email_error_icon').style.display = 'inline';
				}
				if (domain == '') {
					document.getElementById('skima_domain_error').style.display = 'table-row';
					document.getElementById('skima_domain_error_icon').style.display = 'inline';
				}
			}
		}
	}
	function skimEdit(call) {
		skimClearErrors();
		if (call == 'register') {
			document.getElementById('skim_form_register_check').style.display = 'none';
			document.getElementById('skim_form_register_form').style.display = 'block';
		} else {
			document.getElementById('skim_form_associate_check').style.display = 'none';
			document.getElementById('skim_form_associate_form').style.display = 'block';
		}
	}
	function skimDissociate() {
		if (confirm("<?php echo $vbphrase['skimlinks_global_confirm_js']; ?>")) {
			document.getElementById('skimlinks_pub_id').value = '';
			document.getElementById('skim_dissociate_link').style.display = 'none';
			document.getElementById('skimlinks_setup_head').style.display = 'table-row';
			document.getElementById('skimlinks_setup').style.display = 'table-row';
			skimEnableSubmit();
			$('.skimdisabledalert').hide();
			alert("<?php echo $vbphrase['skimlinks_global_alert_js']; ?>");
		}
	}
	function skimChange(call) {
		if (call == 'register') {
			document.getElementById('skima_email').value = document.getElementById('skimr_email').value;
			document.getElementById('skima_domain').value = document.getElementById('skimr_domain').value;
			document.getElementById('skim_setup_head').innerHTML = " - <?php echo addslashes($vbphrase['skimlinks_global_ass_acc']); ?>";
			document.getElementById('skim_form_register').style.display = 'none';
			document.getElementById('skim_form_register_form').style.display = 'block';
			document.getElementById('skim_form_associate').style.display = 'block';
			document.getElementById('skim_form_associate').style.opacity = 1;
			skimEdit('associate');
		} else {
			document.getElementById('skimr_email').value = document.getElementById('skima_email').value;
			document.getElementById('skimr_domain').value = document.getElementById('skima_domain').value;
			document.getElementById('skim_setup_head').innerHTML = " - <?php echo addslashes($vbphrase['skimlinks_global_create_acc']); ?>";
			document.getElementById('skim_form_associate').style.display = 'none';
			document.getElementById('skim_form_associate_form').style.display = 'block';
			document.getElementById('skim_form_register').style.display = 'block';
			document.getElementById('skim_form_register').style.opacity = 1;
			skimEdit('register');
		}
	}
	</script>

<?php

$skimlinks_forum_url = str_replace('http://', '', $vbulletin->options['bburl']);

?>	
	
	<tr valign="top" id="skimlinks_setup_head"<?php echo ! empty($skimlinks_pub_id) ? ' style="display: none;"' : ''; ?>><td class="thead" colspan="2"><?php echo $vbphrase['skimlinks_global_setup']; ?><span id="skim_setup_head"></span></td></tr>

	<tr valign="top" id="skimlinks_setup"<?php echo ! empty($skimlinks_pub_id) ? ' style="display: none;"' : ''; ?>><td class="alt1 skimLinksInfo" colspan="2">

		<div id="skim_buttons">
			<?php echo $vbphrase['skimlinks_global_new_setup']; ?>
			<div style="text-align: center; margin: 25px 0;">
				<input type="button" value="<?php echo $vbphrase['skimlinks_global_active_new_acc']; ?>" onclick="skimShow('skim_form_register');" class="button" style="margin-right: 70px;" />
				<input type="button" value="<?php echo $vbphrase['skimlinks_global_ass_ext_acc']; ?>" onclick="skimShow('skim_form_associate');" class="button" style="margin-left: 70px;" />
			</div>
		</div>

		<div id="skim_form_register" style="display: none;">
			<div id="skim_form_register_form">
				&lt;<a href="javascript:void();" onclick="skimBack('skim_form_register');" style="color: #001F30;"><?php echo $vbphrase['skimlinks_global_back']; ?></a><br /><br />
				<table border="0" cellpadding="0" cellspacing="0" width="460" align="center">
					<tr>
						<td colspan="3"><div style="font-size: 11px; margin-bottom: 15px;">
						<strong><?php echo $vbphrase['skimlinks_global_enter']." ".$vbphrase['skimlinks_global_website_info']; ?></strong><br /><br />
						<?php echo $vbphrase['skimlinks_global_is_free']; ?></div></td>
					</tr>
					<tr>
						<td colspan="2" valign="bottom"><strong><?php echo $vbphrase['skimlinks_global_website_info']; ?></strong></td>
						<td align="right" valign="bottom"><small><?php echo $vbphrase['skimlinks_global_all_req']; ?></small></td>
					</tr>
					<tr>
						<td colspan="3"><div style="height: 1px; background-color: #001F30; margin-bottom: 8px;"></div></td>
					</tr>
					<tr id="skimr_name_error" style="display: none;"><td colspan="2"></td><td style="color: #ff0000; font-size: 10px;"><?php echo $vbphrase['skimlinks_global_ness_acc']; ?></td></tr>
					<tr>
						<td width="165"><?php echo $vbphrase['skimlinks_global_name']; ?>:</td>
						<td width="15"><span id="skimr_name_error_icon" style="display: none;"><img src="../images/misc/colorpicker_close.gif" style="border: 0;" alt="" /></span></td>
						<td align="right" width="280" style="height: 26px;"><input type="text" id="skimr_name" value="" class="bginput" style="width: 280px;" /></td>
					</tr>
					<tr id="skimr_email_error" style="display: none;"><td colspan="2"></td><td style="color: #ff0000; font-size: 10px;"><?php echo $vbphrase['skimlinks_global_valid_email']; ?></td></tr>
					<tr>
						<td><?php echo $vbphrase['skimlinks_global_email']; ?>:</td>
						<td><span id="skimr_email_error_icon" style="display: none;"><img src="../images/misc/colorpicker_close.gif" style="border: 0;" alt="" /></span></td>
						<td align="right" style="height: 26px;"><input type="text" id="skimr_email" value="<?php echo $vbulletin->userinfo['email']; ?>" class="bginput" style="width: 280px;" /></td>
					</tr>
					<tr id="skimr_domain_error" style="display: none;"><td colspan="2"></td><td style="color: #ff0000; font-size: 10px;"><?php echo $vbphrase['skimlinks_global_ness_acc']; ?></td></tr>
					<tr>
						<td><?php echo $vbphrase['skimlinks_global_domain']; ?>:</td>
						<td><span id="skimr_domain_error_icon" style="display: none;"><img src="../images/misc/colorpicker_close.gif" style="border: 0;" alt="" /></span></td>
						<td align="right" style="height: 26px;"><input type="text" id="skimr_domain" value="<?php echo $skimlinks_forum_url; ?>" class="bginput" style="width: 280px;" /></td>
					</tr>
					<tr>
						<td colspan="3"><?php echo $vbphrase['skimlinks_global_tos_note']; ?></td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
						<td>
							<input type="button" value="<?php echo $vbphrase['skimlinks_global_next_step']; ?>" onclick="skimCheck('register');" class="button" />
							<a href="javascript:void();" onclick="skimBack('skim_form_register');" style="margin-left: 8px;"><small><?php echo $vbphrase['skimlinks_global_cancel']; ?></small></a>
						</td>
					</tr>
				</table>
			</div>
			<div id="skim_form_register_check" style="display: none">
				&lt;<a href="javascript:void();" onclick="skimBack('skim_form_register');" style="color: #001F30;"><?php echo $vbphrase['skimlinks_global_back']; ?></a><br /><br />
				<table border="0" cellpadding="0" cellspacing="0" width="460" align="center">
					<tr>
						<td colspan="2"><div style="font-size: 11px; margin-bottom: 15px;">
						<strong><?php echo $vbphrase['skimlinks_global_confirm']." ".$vbphrase['skimlinks_global_website_info']; ?></strong><br /><br />
						<?php echo $vbphrase['skimlinks_global_confirm_note']; ?></div></td>
					</tr>
					<tr>
						<td colspan="2"><strong><?php echo $vbphrase['skimlinks_global_website_info']; ?></strong></td>
					</tr>
					<tr>
						<td colspan="2"><div style="height: 1px; background-color: #001F30; margin-bottom: 8px;"></div></td>
					</tr>
					<tr>
						<td width="180"><?php echo $vbphrase['skimlinks_global_name']; ?>:</td>
						<td width="280" style="height: 26px;"><span id="skimr_name_val"></span></td>
					</tr>
					<tr>
						<td><?php echo $vbphrase['skimlinks_global_email']; ?>:</td>
						<td style="height: 26px;"><span id="skimr_email_val"></span></td>
					</tr>
					<tr>
						<td><?php echo $vbphrase['skimlinks_global_domain']; ?>:</td>
						<td style="height: 26px;"><span id="skimr_domain_val"></span></td>
					</tr>
					<tr>
						<td colspan="2"><?php echo $vbphrase['skimlinks_global_tos_note']; ?></td>
					</tr>
					<tr>
						<td align="right"><span id="skimr_load" style="display: none; margin-right: 8px;"><img src="../images/misc/progress.gif" style="border: 0;" alt="" /></span></td>
						<td>
							<input type="button" value="<?php echo $vbphrase['skimlinks_global_active_acc']; ?>" onclick="skimApiCall('register');" class="button" />
							<a href="javascript:void();" onclick="skimEdit('register');" style="margin-left: 8px;"><small><?php echo $vbphrase['skimlinks_global_edit_info']; ?></small></a>
						</td>
					</tr>
				</table>
			</div>
			<div id="skim_form_register_check_error" style="display: none">
				&lt;<a href="javascript:void();" onclick="skimBack('skim_form_register');" style="color: #001F30;"><?php echo $vbphrase['skimlinks_global_back']; ?></a><br /><br />
				<table border="0" cellpadding="0" cellspacing="0" width="460" align="center">
					<tr>
						<td colspan="2"><div style="font-size: 11px; margin-bottom: 15px;"><img src="../images/misc/colorpicker_close.gif" style="border: 0;" alt="" /><?php echo $vbphrase['skimlinks_global_active_fail']; ?></div></td>
					</tr>
					<tr>
						<td colspan="2"><strong><?php echo $vbphrase['skimlinks_global_website_info']; ?></strong></td>
					</tr>
					<tr>
						<td colspan="2"><div style="height: 1px; background-color: #001F30; margin-bottom: 8px;"></div></td>
					</tr>
					<tr>
						<td width="180"><?php echo $vbphrase['skimlinks_global_name']; ?>:</td>
						<td width="280" style="height: 26px;"><span id="skimr_name_val_e"></span></td>
					</tr>
					<tr>
						<td><?php echo $vbphrase['skimlinks_global_email']; ?>:</td>
						<td style="height: 26px;"><span id="skimr_email_val_e"></span></td>
					</tr>
					<tr>
						<td><?php echo $vbphrase['skimlinks_global_domain']; ?>:</td>
						<td style="height: 26px;"><span id="skimr_domain_val_e"></span></td>
					</tr>
					<tr><td colspan="3">
						<div style="float: left; width: 15px; margin-top: 10px;"><img src="../images/misc/colorpicker_close.gif" style="border: 0;" alt="" /></div>
						<small style="display: block; margin: 8px 0; float: right; width: 445px;" id="skimr_error"></small>
						<div style="clear: both; height: 0; line-height: 0;"></div>
					</td></tr>
					<tr>
						<td align="right"><span id="skimr_load" style="display: none; margin-right: 8px;"><img src="../images/misc/progress.gif" style="border: 0;" alt="" /></span></td>
						<td>
							<div id="reg_to_assoc_btn">
								<input type="button" value="<?php echo $vbphrase['skimlinks_global_ass_acc']; ?>" onclick="skimChange('register');" class="button" />
								<a href="javascript:void();" onclick="skimEdit('register');" style="margin-left: 8px;"><small><?php echo $vbphrase['skimlinks_global_edit_info']; ?></small></a>
							</div>
							<div id="reg_to_assoc_back"><a href="javascript:void();" onclick="skimBack('skim_form_register');"><small><?php echo $vbphrase['skimlinks_global_return_to']; ?></small></a><br /><br /></div>
						</td>
					</tr>
				</table>
			</div>
			<div id="skim_form_register_successful" style="display: none;">
				<table border="0" cellpadding="0" cellspacing="0" width="460" align="center"><tr><td style="font-size: 11px;">
					<?php echo $vbphrase['skimlinks_global_active_succ']; ?>
					<div style="text-align: center"><input type="submit" class="button" value="<?php echo $vbphrase['skimlinks_global_finish']; ?>" /></div>
				</td></tr></table>
			</div>
		</div>

		<div id="skim_form_associate" style="display: none;">
			<div id="skim_form_associate_form">
				&lt;<a href="javascript:void();" onclick="skimBack('skim_form_associate');" style="color: #001F30;"><?php echo $vbphrase['skimlinks_global_back']; ?></a><br /><br />
				<table border="0" cellpadding="0" cellspacing="0" width="460" align="center">
					<tr>
						<td colspan="3"><div style="font-size: 11px; margin-bottom: 15px;">
						<strong><?php echo $vbphrase['skimlinks_global_enter']." ".$vbphrase['skimlinks_global_acc_details']; ?></strong><br /><br />
						<?php echo $vbphrase['skimlinks_global_ass_note']; ?></div></td>
					</tr>
					<tr>
						<td colspan="3"><strong>Skimlinks <?php echo $vbphrase['skimlinks_global_acc_info']; ?></strong></td>
					</tr>
					<tr>
						<td colspan="3"><div style="height: 1px; background-color: #001F30; margin-bottom: 8px;"></div></td>
					</tr>
					<tr id="skima_email_error" style="display: none;"><td colspan="2"></td><td style="color: #ff0000; font-size: 10px;"><?php echo $vbphrase['skimlinks_global_valid_email']; ?></td></tr>
					<tr>
						<td width="165"><?php echo $vbphrase['skimlinks_global_email']; ?>:</td>
						<td width="15"><span id="skima_email_error_icon" style="display: none;"><img src="../images/misc/colorpicker_close.gif" style="border: 0;" alt="" /></span></td>
						<td width="280" align="right" style="height: 26px;"><input type="text" id="skima_email" value="<?php echo $vbulletin->userinfo['email']; ?>" class="bginput" style="width: 280px;" /></td>
					</tr>
					<tr id="skima_domain_error" style="display: none;"><td colspan="2"></td><td style="color: #ff0000; font-size: 10px;"><?php echo $vbphrase['skimlinks_global_ness_ass']; ?></td></tr>
					<tr>
						<td><?php echo $vbphrase['skimlinks_global_domain']; ?>:</td>
						<td><span id="skima_domain_error_icon" style="display: none;"><img src="../images/misc/colorpicker_close.gif" style="border: 0;" alt="" /></span></td>
						<td align="right" style="height: 26px;"><input type="text" id="skima_domain" value="<?php echo $skimlinks_forum_url; ?>" class="bginput" style="width: 280px;" /></td>
					</tr>
					<tr><td colspan="3"><div style="height: 8px;"></div></td></tr>
					<tr>
						<td colspan="2">&nbsp;</td>
						<td>
							<input type="button" value="<?php echo $vbphrase['skimlinks_global_next_step']; ?>" onclick="skimCheck('associate');" class="button" />
							<a href="javascript:void();" onclick="skimBack('skim_form_associate');" style="margin-left: 8px;"><small><?php echo $vbphrase['skimlinks_global_cancel']; ?></small></a>
						</td>
					</tr>
				</table>
			</div>
			<div id="skim_form_associate_check" style="display: none">
				&lt;<a href="javascript:void();" onclick="skimBack('skim_form_associate');" style="color: #001F30;"><?php echo $vbphrase['skimlinks_global_back']; ?></a><br /><br />
				<table border="0" cellpadding="0" cellspacing="0" width="460" align="center">
					<tr>
						<td colspan="2"><div style="font-size: 11px; margin-bottom: 15px;">
						<strong><?php echo $vbphrase['skimlinks_global_confirm']." ".$vbphrase['skimlinks_global_acc_details']; ?></strong><br /><br />
						<?php echo $vbphrase['skimlinks_global_confirm_ass']; ?></div></td>
					</tr>
					<tr>
						<td colspan="2"><strong>Skimlinks <?php echo $vbphrase['skimlinks_global_acc_info']; ?></strong></td>
					</tr>
					<tr>
						<td colspan="2"><div style="height: 1px; background-color: #001F30; margin-bottom: 8px;"></div></td>
					</tr>
					<tr>
						<td width="180"><?php echo $vbphrase['skimlinks_global_email']; ?>:</td>
						<td width="280" style="height: 26px;"><span id="skima_email_val"></span></td>
					</tr>
					<tr>
						<td><?php echo $vbphrase['skimlinks_global_domain']; ?>:</td>
						<td style="height: 26px;"><span id="skima_domain_val"></span></td>
					</tr>
					<tr><td colspan="2"><div style="height: 8px;"></div></td></tr>
					<tr>
						<td align="right"><span id="skima_load" style="display: none; margin-right: 8px;"><img src="../images/misc/progress.gif" style="border: 0;" alt="" /></span></td>
						<td>
							<input type="button" value="<?php echo $vbphrase['skimlinks_global_ass_acc']; ?>" onclick="skimApiCall('associate');" class="button" />
							<a href="javascript:void();" onclick="skimEdit('associate');" style="margin-left: 8px;"><small><?php echo $vbphrase['skimlinks_global_edit_info']; ?></small></a>
						</td>
					</tr>
				</table>
			</div>
			<div id="skim_form_associate_check_error" style="display: none">
				&lt;<a href="javascript:void();" onclick="skimBack('skim_form_associate');" style="color: #001F30;"><?php echo $vbphrase['skimlinks_global_back']; ?></a><br /><br />
				<table border="0" cellpadding="0" cellspacing="0" width="460" align="center">
					<tr>
						<td colspan="2"><div style="font-size: 11px; margin-bottom: 15px;"><img src="../images/misc/colorpicker_close.gif" style="border: 0;" alt="" /><?php echo $vbphrase['skimlinks_global_active_fail']; ?></div></td>
					</tr>
					<tr>
						<td colspan="2" style="color: #999;"><strong>Skimlinks <?php echo $vbphrase['skimlinks_global_acc_info']; ?></strong></td>
					</tr>
					<tr>
						<td colspan="2"><div style="height: 1px; background-color: #999; margin-bottom: 8px;"></div></td>
					</tr>
					<tr>
						<td width="180" style="color: #999;"><?php echo $vbphrase['skimlinks_global_email']; ?>:</td>
						<td width="280" style="color: #999; height: 26px;"><span id="skima_email_val_e"></span></td>
					</tr>
					<tr>
						<td style="color: #999;"><?php echo $vbphrase['skimlinks_global_domain']; ?>:</td>
						<td style="height: 26px; color: #999;"><span id="skima_domain_val_e"></span></td>
					</tr>
					<tr>
						<td colspan="3">
							<div style="float: left; width: 15px; margin-top: 10px;"><img src="../images/misc/colorpicker_close.gif" style="border: 0;" alt="" /></div>
							<small style="display: block; margin: 8px 0; float: right; width: 445px;" id="skima_error"></small>
							<div style="clear: both; height: 0; line-height: 0;"></div>
						</td>
					</tr>
					<tr>
						<td align="right"><span id="skima_load" style="display: none; margin-right: 8px;"><img src="../images/misc/progress.gif" style="border: 0;" alt="" /></span></td>
						<td>
							<div id="assoc_to_reg_btn">
								<input type="button" value="<?php echo $vbphrase['skimlinks_global_create_acc']; ?>" onclick="skimChange('associate');" class="button" />
								<a href="javascript:void();" onclick="skimEdit('associate');" style="margin-left: 8px;"><small><?php echo $vbphrase['skimlinks_global_edit_info']; ?></small></a>
							</div>
							<div id="assoc_to_reg_back"><a href="javascript:void();" onclick="skimBack('skim_form_associate');"><small><?php echo $vbphrase['skimlinks_global_return_to']; ?></small></a><br /><br /></div>
						</td>
					</tr>
				</table>
			</div>
			<div id="skim_form_associate_successful" style="display: none;">
				<table border="0" cellpadding="0" cellspacing="0" width="460" align="center"><tr><td><div style="font-size: 11px; margin: 15px 0;">
					<?php echo $vbphrase['skimlinks_global_ass_succ']; ?>
					<div style="text-align: center;"><input type="submit" class="button" value="<?php echo $vbphrase['skimlinks_global_finish']; ?>" /></div>
				</div></td></tr></table>
			</div>
		</div>

		<br />&nbsp;
	</td></tr>

	<?php
	if (! $vbulletin->options['skimlinks_enabled'] && ! empty($skimlinks_pub_id)) {
		print_description_row($vbphrase['skimlinks_global_status'], false, 2, 'thead skimdisabledalert');
		print_description_row($vbphrase['skimlinks_gloabal_is_off'], false, 2, 'alt1 skimLinksInfo skimdisabledalert');
	}

	print_description_row($vbphrase['skimlinks_global_options'], false, 2, 'thead');

	print_description_row($vbphrase['skimlinks_global_enable'], false, 2, fetch_row_bgclass().' skimLinksInfo');

	print_yes_no_row($vbphrase['skimlinks_enabled'], 'skimlinks_enabled', $vbulletin->options['skimlinks_enabled'], 'skimEnableSubmit();');

	print_description_row($vbphrase['skimlinks_to_enable_products'],
		false, 2, fetch_row_bgclass().' skimLinksInfo');
	print_label_row($vbphrase['skimlinks_pub_id'],
		"<div id=\"ctrl_skimlinks_pub_id\" style=\"float: left;\"><input type=\"text\" class=\"bginput\" name=\"skimlinks_pub_id\" id=\"skimlinks_pub_id\" value=\"" . htmlspecialchars_uni($skimlinks_pub_id) . "\" readonly=\"readonly\" size=\"25\" style=\"background-color: #f6f6f6; border-color: #f6f6f6;\" /></div>" .
		(! empty($skimlinks_pub_id) ? '<div style="float: right; margin-right: 8px;"><a href="javascript:void(0);" onclick="skimDissociate();" id="skim_dissociate_link"><small>'.$vbphrase['skimlinks_dissociate_account'].'</small></a></div><div style="clear: both; heigh: 0; line-height: 0;"></div>' : ''),
		'', 'top', skimlinks_pub_id);

	print_description_row($vbphrase['skimlinks_global_settings_description'], false, 2, 'alt1 skimLinksInfo');

	print_description_row($vbphrase['skimlinks_product_level_options'], false, 2, 'thead');
	print_description_row('<div style="font-size: 11px; margin: 4px 0;">'.$vbphrase['skimlinks_product_level_options_description'].'</div>', false, 2, fetch_row_bgclass().' skimLinksInfo');

	print print_submit_row();
	?>
	<script type="text/javascript">
	document.getElementById('cpform').addEventListener("submit", function (e) { promptchange = false; },false);
	$(function() {
		$('input[type=submit], input[type=reset]').attr('disabled', 'disabled');
	});
	</script>
	<?php
}

function getCheckbox($name, $value = 1, $checked = true, array $attributes = null)
{
	$attributeString = '';

	if (is_array($attributes))
	{
		foreach ($attributes AS $attribute => $value)
		{
			$attributeString .= " $attribute=\"" . htmlspecialchars($value) . "\"";
		}
	}

	return '<input type="checkbox" class="' . $name . '" value="' . $value . '"' . $attributeString . ($checked ? ' checked="checked"' : '') . ' />';
}

function print_colspan_fix()
{
	echo "</table><table cellpadding=\"4\" cellspacing=\"0\" border=\"0\" align=\"center\" width=\"100%\" class=\"tborder\">\n";
}

if ($_REQUEST['do'] == 'advanced')
{
	?>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
	<script type="text/javascript">

		$(function()
		{
			$('form[name=SkimLinksForm] > table').wrapAll('<div class="tborder" />');

			$('form[name=SkimLinksForm]').bind('submit', function(e)
			{
				var i, j, value, $inputs, inputNames = new Array(
					'skimlinks_disable_groups',
					'skimwords_disable_groups',
					'skimwords_disable_groups_parse',
					'skimlinks_disable_groups_parse',
					'skimlinks_disable_forums',
					'skimwords_disable_forums'
				);

				for (i = 0; i < inputNames.length; i++)
				{
					value = [];

					$inputs = $('input:checkbox.' + inputNames[i]).each(function()
					{
						if (this.checked == false)
						{
							value.push(this.value);
						}
					});

					$('input[name=' + inputNames[i] + ']').val(value.join(','));
				}
			});

		});

	</script>
	<style type="text/css">

		table.tborder     { border: none; }
		div.tborder       { margin: 0px auto; width: 90%; }
		th                { border-bottom: 1px solid silver; }
		.alt2border .alt2 { border-left: 1px solid silver; }
		dd                { margin-top: 4px; margin-left: 25px; }
		input.number      { text-align: right; width: 100px; }

	</style>
	<?php

	$slUgDisable = json_decode($vbulletin->options['skimlinks_disable_groups']);
	$slUgDisableParse = json_decode($vbulletin->options['skimlinks_disable_groups_parse']);
	$slForumDisable = json_decode($vbulletin->options['skimlinks_disable_forums']);

	$swUgDisable = json_decode($vbulletin->options['skimwords_disable_groups']);
	$swUgDisableParse = json_decode($vbulletin->options['skimwords_disable_groups_parse']);
	$swForumDisable = json_decode($vbulletin->options['skimwords_disable_forums']);

	$slAgeLimitChecked = array();
	if ($vbulletin->options['skimlinks_thread_age_limit'] > 0)
	{
		$slAgeLimitChecked[1] = ' checked="checked"';
		$slThreadDays = $vbulletin->options['skimlinks_thread_age_limit'];
	}
	else
	{
		$slAgeLimitChecked[0] = ' checked="checked"';
		$slThreadDays = 1;
	}

	$swAgeLimitChecked = array();
	if ($vbulletin->options['skimwords_thread_age_limit'] > 0)
	{
		$swAgeLimitChecked[1] = ' checked="checked"';
		$swThreadDays = $vbulletin->options['skimwords_thread_age_limit'];
	}
	else
	{
		$swAgeLimitChecked[0] = ' checked="checked"';
		$swThreadDays = 1;
	}

	print_form_header('skimlinks', 'update', false, true, 'SkimLinksForm', '100%');
	construct_hidden_code('option_type', 'advanced');

	print_table_header('Skimlinks - ' . $vbphrase['skimlinks_advanced_options'], 5);
	print_description_row('<div class="smallfont">' . $vbphrase['skimlinks_advanced_options_help'] . '</div>', false, 5);

	print_table_header($vbphrase['usergroups'], 5);
	?>
	<tr>
		<td <?php if (!version_compare(@$vbulletin->versionnumber, '3.8.0', '>=')) { ?>colspan="3"<?php } ?> class="thead"><?php echo $vbphrase['skimlinks_activate_products'] ?></td>
		<td class="thead" colspan="2"><span class="normal"><?php echo $vbphrase['skimlinks_for_visitors_in_these_usergroups'] ?></span></td>
		<?php if (version_compare(@$vbulletin->versionnumber, '3.8.0', '>=')) { ?>
		<td class="thead" colspan="2"><span class="normal"><?php echo $vbphrase['skimlinks_in_posts_by_these_usergroups'] ?></span></td>
		<?php } ?>
	</tr>
	<tr align="center" class="alt2border">
		<th <?php if (!version_compare(@$vbulletin->versionnumber, '3.8.0', '>=')) { ?>colspan="3"<?php } ?> class="alt1" align="left"><?php echo $vbphrase['usergroup'] ?></th>
		<th class="alt2">SkimLinks</th>
		<th class="alt1">SkimWords</th>
		<?php if (version_compare(@$vbulletin->versionnumber, '3.8.0', '>=')) { ?>
		<th class="alt2">SkimLinks</th>
		<th class="alt1">SkimWords</th>
		<?php } ?>
	</tr>
	<?php
	construct_hidden_code('skimlinks_disable_groups', '');
	construct_hidden_code('skimwords_disable_groups', '');
	construct_hidden_code('skimlinks_disable_groups_parse', '');
	construct_hidden_code('skimwords_disable_groups_parse', '');
	foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
	{
		echo '<tr align="center" class="alt2border">';
		if(version_compare(@$vbulletin->versionnumber, '3.8.0', '>=')){
			echo '<td class="alt1" align="left">' . $usergroup['title'] .'</td>';
		} else {
			echo '<td colspan="3" class="alt1" align="left">' . $usergroup['title'] .'</td>';
		}
		echo '<td class="alt2">' . getCheckbox('skimlinks_disable_groups', $usergroupid, !in_array($usergroupid, $slUgDisable)) . '</td>
			<td class="alt1">' . getCheckbox('skimwords_disable_groups', $usergroupid, !in_array($usergroupid, $swUgDisable)) . '</td>';

		if (version_compare(@$vbulletin->versionnumber, '3.8.0', '>=')){
			echo '<td class="alt2">' . getCheckbox('skimlinks_disable_groups_parse', $usergroupid, !in_array($usergroupid, $slUgDisableParse)) . '</td>
				<td class="alt1">' . getCheckbox('skimwords_disable_groups_parse', $usergroupid, !in_array($usergroupid, $swUgDisableParse)) . '</td>';
		}

		echo '</tr>';
	}

	print_table_header($vbphrase['forums'], 5);
	print_description_row('<span class="normal">' . $vbphrase['skimlinks_activate_products_within_these_forums'] . '</span>', false, 5, 'thead');
	?>
	<tr align="center" class="alt2border">
		<th class="alt1" align="left" colspan="3"><?php echo $vbphrase['forum'] ?></th>
		<th class="alt2">SkimLinks</th>
		<th class="alt1">SkimWords</th>
	</tr>
	<?php
	construct_hidden_code('skimlinks_disable_forums', '');
	construct_hidden_code('skimwords_disable_forums', '');
	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		echo '<tr align="center" class="alt2border">
			<td class="alt1" align="left" colspan="3">' . str_repeat('--', $forum['depth']) . ' ' . $forum['title'] . '</td>
			<td class="alt2">' . (is_forum($forum) ? getCheckbox('skimlinks_disable_forums', $forumid, !in_array($forumid, $slForumDisable)) : '-') . '</td>
			<td class="alt1">' . (is_forum($forum) ? getCheckbox('skimwords_disable_forums', $forumid, !in_array($forumid, $swForumDisable)) : '-') . '</td>
			</tr>';
	}
	print_colspan_fix();

	print_table_header($vbphrase['threads']);
	print_description_row('<span class="normal">' . $vbphrase['skimlinks_product_activation_based_on_age_of_thread'] . '</span>', false, 5, 'thead');
	?>
	<tr align="left">
		<th class="alt1" width="50%"><?php echo $vbphrase['skimlinks_activate_skimlinks_in'] ?></th>
		<th class="alt2" width="50%"><?php echo $vbphrase['skimlinks_activate_skimwords_in'] ?></th>
	</tr>
	<tr>
		<td class="alt1">
			<label><input type="radio" name="skimlinks_thread_age_limit_enable" value="0" <?php echo $slAgeLimitChecked[0] ?> /> <?php echo $vbphrase['skimlinks_activate_in_all_threads']?></label>
			<dl class="dep_group">
				<dt><label>
					<input type="radio" name="skimlinks_thread_age_limit_enable" value="1" id="timeLimitLinks" class="dep_ctrl" <?php echo $slAgeLimitChecked[1] ?> />
					<?php echo $vbphrase['skimlinks_activate_in_only_threads_last_post_older_than'] ?>
				</label></dt>
				<dd id="timeLimitLinks_deps"><label><input type="text" name="skimlinks_thread_age_limit" class="bginput number" value="<?php echo $slThreadDays ?>" /> <?php  echo $vbphrase['skimlinks_days'] ?></label></dd>
			</dl>
		</td>
		<td class="alt2">
			<label><input type="radio" name="skimwords_thread_age_limit_enable" value="0" <?php echo $swAgeLimitChecked[0] ?> /> <?php echo $vbphrase['skimlinks_activate_in_all_threads']?></label>
			<dl class="dep_group">
				<dt><label>
					<input type="radio" name="skimwords_thread_age_limit_enable" value="1" id="timeLimitWords" class="dep_ctrl" <?php echo $swAgeLimitChecked[1] ?> />
					<?php echo $vbphrase['skimlinks_activate_in_only_threads_last_post_older_than'] ?>
				</label></dt>
				<dd id="timeLimitWords_deps"><label><input type="text" name="skimwords_thread_age_limit" class="bginput number" value="<?php echo $swThreadDays ?>" /> <?php  echo $vbphrase['skimlinks_days'] ?></label></dd>
			</dl>
		</td>
	</tr>
	<?php
	print_description_row('<span class="smallfont">' . $vbphrase['skimlinks_time_limit_note'] . '</span>', false);

	if (version_compare(@$vbulletin->versionnumber, '3.8.0', '>=')) {
		print_table_header($vbphrase['users']);
		print_description_row('<span class="normal">' . $vbphrase['allow_users_to_disable_skimlinks'] . '</span>', false, 5, 'thead');
		print_yes_no_row($vbphrase['users_can_disable_skimlinks'], 'skimlinks_allow_user_disable', $vbulletin->options['skimlinks_allow_user_disable']);
		print_textarea_row($vbphrase['skimlinks_user_disable_explain_text_label'] . ' <dfn>' . $vbphrase['skimlinks_user_disable_explain_text_dfn'] . '</dfn>', 'skimlinks_user_disable_description', $vbulletin->options['skimlinks_user_disable_description'], 4, 40, true, false);
	}

	print_submit_row();
}

if ($_REQUEST['do'] == 'skimlinks')
{
	$usergroupDisable = json_decode($vbulletin->options['skimlinks_disable_groups']);
	$usergroupDisableParse = json_decode($vbulletin->options['skimlinks_disable_groups_parse']);
	$forumDisable = json_decode($vbulletin->options['skimlinks_disable_forums']);

	print_form_header('skimlinks', 'update');
	construct_hidden_code('option_type', 'skimlinks');

	print_table_header($vbphrase['skimlinks_options']);
	print_description_row($vbphrase['skimlinks_options_description'], false, 2, 'alt1 skimLinksInfo');
	print_description_row('&nbsp;');

	print_table_header($vbphrase['usergroups']);
	print_description_row($vbphrase['skimlinks_active_usergroups'], false, 2, 'thead');

	foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
	{
		$left = '<label title="ID: ' . $usergroupid . '"><input type="checkbox" name="usergroupDisplay[' . $usergroupid . ']" value="1" ' .
			(!in_array($usergroupid, $usergroupDisable) ? 'checked="checked"' : '') . ' /> ' . $usergroup['title'] . '
			</label>';

		$right = '<!--<label style="font-size: 11px"><input type="checkbox" name="usergroupParse[' . $usergroupid . ']" value="1" ' .
			(!in_array($usergroupid, $usergroupDisableParse) ? 'checked="checked"' : '') . ' /> Process posted links
			</label>-->';

		print_label_row($left, $right);
	}
	print_description_row('&nbsp;');

	print_table_header($vbphrase['forums']);
	print_description_row($vbphrase['skimlinks_active_in_forums'], false, 2, 'thead');

	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		print_description_row(
			'<label title="ID: ' . $forumid . '"><input type="checkbox" name="forumDisplay[' . $forumid . ']" value="1" ' .
			(!in_array($forumid, $forumDisable) ? 'checked="checked"' : '') . ' /> ' .
			str_repeat('--', $forum['depth']) . ' ' . $forum['title'] . '
			</label>'
		);
	}

	print_description_row($vbphrase['allow_users_to_disable_skimlinks'], false, 2, 'thead');
	print_yes_no_row($vbphrase['users_can_disable_skimlinks'], 'skimlinks_allow_user_disable', $vbulletin->options['skimlinks_allow_user_disable']);
	print_textarea_row($vbphrase['skimlinks_user_disable_explain_text_label'], 'skimlinks_user_disable_description', $vbulletin->options['skimlinks_user_disable_description']);

	print_submit_row();
}

if ($_REQUEST['do'] == 'skimwords')
{
	$usergroupDisable = json_decode($vbulletin->options['skimwords_disable_groups']);
	$usergroupDisableParse = json_decode($vbulletin->options['skimwords_disable_groups_parse']);
	$forumDisable = json_decode($vbulletin->options['skimwords_disable_forums']);

	print_form_header('skimlinks', 'update');
	construct_hidden_code('option_type', 'skimwords');

	print_table_header($vbphrase['skimwords_options']);
	print_description_row($vbphrase['skimwords_options_description'], false, 2, 'alt1 skimLinksInfo');
	print_description_row('&nbsp;');

	print_table_header($vbphrase['usergroups']);
	print_description_row($vbphrase['skimwords_active_usergroups'], false, 2, 'thead');

	foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
	{
		$left = '<label title="ID: ' . $usergroupid . '"><input type="checkbox" name="usergroupDisplay[' . $usergroupid . ']" value="1" ' .
			(!in_array($usergroupid, $usergroupDisable) ? 'checked="checked"' : '') . ' /> ' . $usergroup['title'] . '
			</label>';

		$right = '<!--<label style="font-size: 11px"><input type="checkbox" name="usergroupParse[' . $usergroupid . ']" value="1" ' .
			(!in_array($usergroupid, $usergroupDisableParse) ? 'checked="checked"' : '') . ' /> Process posts
			</label>-->';

		print_label_row($left, $right);
	}
	print_description_row('&nbsp;');

	print_table_header($vbphrase['forums']);
	print_description_row($vbphrase['skimwords_active_in_forums'], false, 2, 'thead');

	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		print_description_row(
			'<label title="ID: ' . $forumid . '"><input type="checkbox" name="forumDisplay[' . $forumid . ']" value="1" ' .
			(!in_array($forumid, $forumDisable) ? 'checked="checked"' : '') . ' /> ' .
			str_repeat('--', $forum['depth']) . ' ' . $forum['title'] . '
			</label>'
		);
	}

	print_submit_row();
}

if ($_POST['do'] == 'update')
{
	$option_type = $vbulletin->input->clean_gpc('r', 'option_type', TYPE_STR);

	switch ($option_type)
	{
		case 'global':
		{
			$vbulletin->input->clean_array_gpc('p', array(
				'skimlinks_enabled' => TYPE_UINT,
				'skimlinks_pub_id' => TYPE_NOHTML
			));

			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "setting
				SET value = CASE varname
					WHEN 'skimlinks_enabled'
						THEN '" . $vbulletin->db->escape_string($vbulletin->GPC['skimlinks_enabled']) ."'
					WHEN 'skimlinks_pub_id'
						THEN '" . $vbulletin->db->escape_string($vbulletin->GPC['skimlinks_pub_id']) . "'
					ELSE value END
				WHERE varname IN
					('skimlinks_enabled', 'skimlinks_pub_id')
			");

			break;
		}

		case advanced:
		{
			$inputArray = array(
				'skimlinks_disable_groups'           => TYPE_STRING,
				'skimwords_disable_groups'           => TYPE_STRING,
				'skimlinks_disable_groups_parse'     => TYPE_STRING,
				'skimwords_disable_groups_parse'     => TYPE_STRING,
				'skimlinks_disable_forums'           => TYPE_STRING,
				'skimwords_disable_forums'           => TYPE_STRING,
				'skimlinks_thread_age_limit_enable'  => TYPE_UINT,
				'skimwords_thread_age_limit_enable'  => TYPE_UINT,
				'skimlinks_thread_age_limit'         => TYPE_UINT,
				'skimwords_thread_age_limit'         => TYPE_UINT,
				'skimlinks_allow_user_disable'       => TYPE_UINT,
				'skimlinks_user_disable_description' => TYPE_STR,
			);

			$vbulletin->input->clean_array_gpc('p', $inputArray);

			$vbulletin->db->query_write("
				UPDATE " . TABLE_PREFIX . "setting
				SET value = CASE varname

					WHEN 'skimlinks_disable_groups'
						THEN '" . $vbulletin->db->escape_string(json_encode(explode(',', $vbulletin->GPC['skimlinks_disable_groups']))) . "'
					WHEN 'skimwords_disable_groups'
						THEN '" . $vbulletin->db->escape_string(json_encode(explode(',', $vbulletin->GPC['skimwords_disable_groups']))) . "'

					WHEN 'skimlinks_disable_groups_parse'
						THEN '" . $vbulletin->db->escape_string(json_encode(explode(',', $vbulletin->GPC['skimlinks_disable_groups_parse']))) . "'
					WHEN 'skimwords_disable_groups_parse'
						THEN '" . $vbulletin->db->escape_string(json_encode(explode(',', $vbulletin->GPC['skimwords_disable_groups_parse']))) . "'

					WHEN 'skimlinks_disable_forums'
						THEN '" . $vbulletin->db->escape_string(json_encode(explode(',', $vbulletin->GPC['skimlinks_disable_forums']))) . "'
					WHEN 'skimwords_disable_forums'
						THEN '" . $vbulletin->db->escape_string(json_encode(explode(',', $vbulletin->GPC['skimwords_disable_forums']))) . "'

					WHEN 'skimlinks_thread_age_limit'
						THEN '" . ($vbulletin->GPC['skimlinks_thread_age_limit_enable'] ? $vbulletin->GPC['skimlinks_thread_age_limit'] : 0) . "'
					WHEN 'skimwords_thread_age_limit'
						THEN '" . ($vbulletin->GPC['skimwords_thread_age_limit_enable'] ? $vbulletin->GPC['skimwords_thread_age_limit'] : 0) . "'

					WHEN 'skimlinks_allow_user_disable'
						THEN '" . $vbulletin->db->escape_string($vbulletin->GPC['skimlinks_allow_user_disable']) . "'
					WHEN 'skimlinks_user_disable_description'
						THEN '" . $vbulletin->db->escape_string($vbulletin->GPC['skimlinks_user_disable_description']) . "'

				ELSE value END
				WHERE varname IN
					('" . implode("', '", array_keys($inputArray)) . "')
			");

			break;
		}

		case 'skimlinks':
		case 'skimwords':
		{
			$vbulletin->input->clean_array_gpc('p', array(
				'usergroupDisplay' => TYPE_ARRAY_UINT,
				'usergroupParse' => TYPE_ARRAY_UINT,
				'forumDisplay' => TYPE_ARRAY_UINT
			));

			$map = array(
				'skimlinks' => array(
					'usergroupDisplay' => 'skimlinks_disable_groups',
					'usergroupParse' => 'skimlinks_disable_groups_parse',
					'forumDisplay' => 'skimlinks_disable_forums',
				),
				'skimwords' => array(
					'usergroupDisplay' => 'skimwords_disable_groups',
					'usergroupParse' => 'skimwords_disable_groups_parse',
					'forumDisplay' => 'skimwords_disable_forums',
				),
			);

			$ugViewDisable = array();
			$ugParseDisable = array();
			$forumDisable = array();

			foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
			{
				if (empty($vbulletin->GPC['usergroupDisplay'][$usergroupid]))
				{
					$ugViewDisable[] = $usergroupid;
				}

				if (empty($vbulletin->GPC['usergroupParse'][$usergroupid]))
				{
					$ugParseDisable[] = $usergroupid;
				}
			}

			foreach ($vbulletin->forumcache AS $forumid => $forum)
			{
				if (empty($vbulletin->GPC['forumDisplay'][$forumid]))
				{
					$forumDisable[] = $forumid;
				}
			}

			$sql = "
				UPDATE " . TABLE_PREFIX . "setting
				SET value = CASE varname
					WHEN '" . $map[$option_type]['usergroupDisplay'] . "'
						THEN '" . $vbulletin->db->escape_string(json_encode($ugViewDisable)) . "'
					WHEN '" . $map[$option_type]['usergroupParse'] . "'
						THEN '" . $vbulletin->db->escape_string(json_encode($ugParseDisable)) . "'
					WHEN '" . $map[$option_type]['forumDisplay'] . "'
						THEN '" . $vbulletin->db->escape_string(json_encode($forumDisable)) . "'
					ELSE value END
				WHERE varname IN (
					'" . $map[$option_type]['usergroupDisplay'] . "',
					'" . $map[$option_type]['usergroupParse'] . "',
					'" . $map[$option_type]['forumDisplay'] . "'
				);
			";

			$vbulletin->db->query_write($sql);

			break;
		}
	}

	build_options();

	define('CP_REDIRECT', 'skimlinks.php?do=' . $option_type);
	print_stop_message('saved_settings_successfully');
}

print_cp_footer();
