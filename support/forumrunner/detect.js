var androidBranded = false;
var iphoneBranded = false;
var forumName = '';

function
forumRunnerCookie ()
{
    var expires = new Date();
    expires.setTime(expires.getTime() + (90 * 1000 * 60 * 60 * 24));
    document.cookie = 'skip_fr_detect=false;expires=' + expires.toGMTString() + ';path=/';
}

function
forumRunnerAndroid (opera)
{
    var msg;
    var operaMsg;
    if (androidBranded && forumName != '') {
        msg = 'Get our Android app for easier viewing and posting on this forum, optional push notifications and more!';
        operaMsg = 'Get our Android app for easier viewing and posting on this forum!  Search for "' + forumName + '" in the Market.  Reload this page to load the normal website.';
    } else {
        msg = 'Get our Android app for easier viewing and posting on this forum, optional push notifications and more!';
        operaMsg = 'Get our Android app for easier viewing and posting on this forum!  Search for "Forum Runner" in the Market.  Reload the page to load the normal website.';
    }
    
    if (opera) {
	forumRunnerCookie();
	alert(operaMsg);
	return;
    }
	
    if (confirm(msg)) {
	window.location = 'market://details?id=net.endoftime.android.forumrunner';
    } else {
	forumRunnerCookie();
    }
}

function 
iOSVersion ()
{
    if (/iP(hone|od|ad)/.test(navigator.platform)) {
        var v = (navigator.appVersion).match(/OS (\d+)_(\d+)_?(\d+)?/);
        return [parseInt(v[1], 10), parseInt(v[2], 10), parseInt(v[3] || 0, 10)];
    }
}

function
forumRunnerIphone (type, opera)
{
    var operaMsg;
    var safariMsg;

	// If we are on iOS 6 or later, send the meta tag.
	var ver = iOSVersion();
	if (ver[0] >= 6) {
		var meta = document.createElement('meta');
		meta.name = 'apple-itunes-app';
		meta.content = 'app-id=XXXITUNES_APP_IDXXX';
		document.getElementsByTagName('head')[0].appendChild(meta);
		return;
	}

    if (iphoneBranded) {
        operaMsg = 'Get our ' + type + ' app for easier viewing and posting on this forum!  Search for "' + forumName + '" in the App Store.';
        safariMsg = 'Get our ' + type + ' app for easier viewing and posting on this forum, optional push notifications and more!';

    } else {
        operaMsg = 'Get our ' + type + ' app for easier viewing and posting on this forum!  Search for "Forum Runner" in the App Store.';
        safariMsg = 'Get our ' + type + ' app for easier viewing and posting on this forum, optional push notifications and more!';
    }

    if (opera) {
	forumRunnerCookie();
	alert(operaMsg);
	return;
    }

    if (confirm(safariMsg)) {
	window.location = 'http://itunes.apple.com/us/app/forum-runner-vbulletin/id362527234?mt=8';
    } else {
	forumRunnerCookie();
    }
}

function
forumRunnerDetect ()
{
    if (document.cookie.indexOf('skip_fr_detect=false') == -1) {
	var agent = navigator.userAgent.toLowerCase();
	var type;
	var opera = (agent.indexOf('opera') != -1);
	var android = iphone = false;

	if (agent.indexOf('iphone') != -1) {
	    type = 'iPhone';
	    iphone = true;
	} else if (agent.indexOf('ipod') != -1) {
	    type = 'iPod Touch';
	    iphone = true;
	} else if (agent.indexOf('ipad') != -1) {
	    type = 'iPad';
	    iphone = true;
	} else if (agent.indexOf('android') != -1) {
	    android = true;
	} else {
	    return;
	}

	if (android) {
	    forumRunnerAndroid(opera);
	} else if (iphone) {
	    forumRunnerIphone(type, opera)
	}
    }
}

forumRunnerDetect();
