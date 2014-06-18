<?php



class Profiler {
	
	
	
	
	private static $sTimeKey = '[nTime]';
	private static $aExecutionTimes = array();
	private static $aExecutionStack = array();
	private static $aExecutionStackTimes = array();
	
	
	
	
	public static function sGetExecutionTimeStackHtml () {
		
		return self::sGetExecutionTimeStackHtmlRec(null, '');
		
	}
	
	
	
	
	private static function sGetExecutionTimeStackHtmlRec ($aTimes = null, $sTabs = '') {
		
		if ($aTimes === null) $aTimes = self::$aExecutionTimes;
		
		$sHtml = '';
		
		foreach ($aTimes as $sKey => $aData) {
			if ($sKey != self::$sTimeKey) {
				$sTime = number_format($aData[self::$sTimeKey], 8, '.', '');
				$sDelimiter = '&nbsp;&nbsp;=&gt;&nbsp;&nbsp;';
				$sHtml .= '<tr><td>' . $sTabs . $sKey . '</td><td>' . $sDelimiter . '' . $sTime . '</td></tr>' . "\n";
				$sHtml .= '<tr><td></td><td>' . self::sGetExecutionTimeStackHtmlRec($aData, $sTabs . "\t") . '</td></tr>';
			}
		}
		
		$sHtml = '<table style="position: relative; background-color: white; color: black; padding: 0 5px; border: 1px solid #444;">' . $sHtml . '</table>';
		
		return $sHtml;
		
	}
	
	
	
	
	public static function aGetExecutionTimes () {
		
		return self::aCleanExecutionTimes(self::$aExecutionTimes);
		
	}
	
	
	
	
	private static function aCleanExecutionTimes ($aExecutionTimesPart) {
		
		foreach ($aExecutionTimesPart as $sKey => $aValues) {
			if ($sKey === self::$sTimeKey) continue;
			if (count($aValues) == 1) {
				$aExecutionTimesPart[$sKey] = $aValues[self::$sTimeKey];
			} else {
				$aExecutionTimesPart[$sKey] = self::aCleanExecutionTimes($aValues);
			}
		}
		
		return $aExecutionTimesPart;
		
	}
	
	
	
	
	public static function vStartMeasurement ($sMeasurementKey) {
		
		if ($sMeasurementKey === self::$sTimeKey) $sMeasurementKey = '_';
		
		$aParent = &self::$aExecutionTimes;
		foreach (self::$aExecutionStack as $sExecutionStackItem) {
			$aParent = &$aParent[$sExecutionStackItem];
		}
		$aParent[$sMeasurementKey] = array();
		$aParent[$sMeasurementKey][self::$sTimeKey] = -1;
		
		array_push(self::$aExecutionStack, $sMeasurementKey);
		self::$aExecutionStackTimes[$sMeasurementKey] = microtime(true);
		
	}
	
	
	
	
	public static function vStopMeasurement ($sMeasurementKey) {
		
		if ($sMeasurementKey === self::$sTimeKey) $sMeasurementKey = '_';
		
		$sLastStackItem = array_pop(self::$aExecutionStack);
		if ($sLastStackItem != $sMeasurementKey) {
			exit('error in vStopMeasurement: ' . $sLastStackItem . ' != ' . $sMeasurementKey);
		}
		$nStackTimeItem = self::$aExecutionStackTimes[$sLastStackItem];
		unset(self::$aExecutionStackTimes[$sLastStackItem]);
		
		$aParent = &self::$aExecutionTimes;
		foreach (self::$aExecutionStack as $sExecutionStackItem) {
			$aParent = &$aParent[$sExecutionStackItem];
		}
		$aParent[$sMeasurementKey][self::$sTimeKey] = microtime(true) - $nStackTimeItem;
		
	}
	
	
	
	
}



