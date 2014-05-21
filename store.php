<?php
class Store {
	
	
	
	
	private static $sStoreDir;
	private static $aCache = array();
	
	
	
	
	public static function init ($sStoreDir = null) {
		
		if (!$sStoreDir) {
			$sStoreDir = ODT::$sLogBasePath;
		} else {
			if (substr($sStoreDir, 0, 1) == '/') {
				self::$sStoreDir = $sStoreDir . '/';
			} else {
				self::$sStoreDir = ODT::$sBasePath . '/' . $sStoreDir . '/';
			}
		}
		
	}
	
	
	
	
	public static function set ($sKey, $mValue) {
		
		$sFile = self::sFile($sKey);
		$sValue = json_encode($mValue);
		file_put_contents($sFile, $sValue);
		
	}
	
	
	
	
	public static function get ($sKey) {
		
		if (isset(self::$aCache[$sKey])) {
			return self::$aCache[$sKey];
		} else {
			$sFile = self::sFile($sKey);
			if (!file_exists($sFile)) {
				return null;
			}
			$sValue = file_get_contents($sFile);
			$mValue = json_decode($sValue, true);
			self::$aCache[$sKey] = $mValue;
			return $mValue;
		}
		
	}
	
	
	
	
	public static function sFile ($sKey) {
		
		return self::$sStoreDir . $sKey . '.store';
		
	}
	
	
	
	
}