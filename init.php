<?php

class OlliInit {
	public static function init ($sBasePath, $sLogDir = null, $sODTStoreDir = null) {
		if (!class_exists('ODT')) {
			require_once('odt.php');
			require_once('store.php');
			require_once('request_test.php');
			require_once('wiretapper.php');
			require_once('profiler.php');
			ODT::init($sBasePath, $sLogDir);
			ODTStore::init($sODTStoreDir);
		}
	}
}
