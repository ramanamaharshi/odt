<?php

$sOdtDir = '/zzz/tools/odt';
if (file_exists($sOdtDir . '/init.php') && !class_exists('OlliInit')) {
	require_once($sOdtDir . '/init.php');
	if (class_exists('OlliInit')) {
		OlliInit::init(dirname(__FILE__), $sOdtDir . '/log');
	}
}

