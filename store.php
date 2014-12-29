<?php
class ODTStore {
	
	
	
	
	private static $sODTStoreDir;
	private static $aCache = array();
	
	
	
	
	public static function init ($sODTStoreDir = null) {
		
		if (!$sODTStoreDir) {
			$sODTStoreDir = ODT::$sLogBasePath;
		} else {
			if (substr($sODTStoreDir, 0, 1) == '/') {
				self::$sODTStoreDir = $sODTStoreDir . '/';
			} else {
				self::$sODTStoreDir = ODT::$sBasePath . '/' . $sODTStoreDir . '/';
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
		
		return self::$sODTStoreDir . $sKey . '.ODTStore';
		
	}
	
	
	
	
}