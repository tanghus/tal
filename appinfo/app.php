<?php
OC::$CLASSPATH['OCA\TAL\Template'] =				'tal/lib/taltemplate.php';
OC::$CLASSPATH['OCA\TAL\L10N'] =					'tal/lib/tall10n.php';
OC::$CLASSPATH['PHPTAL'] =						'tal/3rdparty/PHPTAL/classes/PHPTAL.php';
OC::$CLASSPATH['PHPTAL_TranslationService'] =	'tal/3rdparty/PHPTAL/classes/PHPTAL/TranslationService.php';

OCP\App::registerAdmin('tal','settings');

function phptal_tales_remote($exp, $nothrow) {
	//$exp = trim($exp, ' \t\r\n/');
	//error_log(phptal_tales($exp, $nothrow));
	return "OCP\Util::linkToRemote(".phptal_tales($exp, $nothrow).")";
}

function phptal_tales_url($src, $nothrow) {
	//$exp = trim($exp, ' \t\r\n/');
	//error_log(phptal_tales($src, $nothrow));
	return "OCA\TAL\Template::linkToAbsolute(".phptal_tales($src, $nothrow).")";
}

function phptal_tales_linkto($src, $nothrow) {
	return "OCA\TAL\Template::linkTo(".phptal_tales($src, $nothrow).")";
}

function phptal_tales_image($src, $nothrow) {
	return "OCA\TAL\Template::imagePath(".phptal_tales($src, $nothrow).")";
}

function phptal_tales_config($src, $nothrow) {
	return "OCA\TAL\Template::config(".phptal_tales($src, $nothrow).")";
}
