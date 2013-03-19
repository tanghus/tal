<?php
OC::$CLASSPATH['OC_TALTemplate'] =				'tal/lib/taltemplate.php';
OC::$CLASSPATH['OC_TALL10N'] =					'tal/lib/tall10n.php';
OC::$CLASSPATH['PHPTAL'] =						'tal/3rdparty/PHPTAL/classes/PHPTAL.php';
OC::$CLASSPATH['PHPTAL_TranslationService'] =	'tal/3rdparty/PHPTAL/classes/PHPTAL/TranslationService.php';

OCP\App::registerPersonal('tal','settings');