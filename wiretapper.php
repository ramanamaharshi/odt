<?php




class Wiretapper {
	
	
	
	
	public static $sMode = 'none';
	
	public static $sTapping = '\Wiretapper::tap(func_get_args());';
	
	public static $sWiretappingLogFile = 'odt.wiretapping.log';
	
	private static $aGlobalStack = array();
	
	
	
	
	public static function tap ($aFunctionArguments = array(), $iCutFunctions = 0) {
		switch (self::$sMode) {
			case 'none':
				return;
				break;
			case 'primitive':
				return self::primitiveTap($aFunctionArguments, $iCutFunctions + 1);
				break;
			case 'advanced':
				return self::advancedTap($aFunctionArguments, $iCutFunctions + 1);
				break;
			case 'performant':
				return self::performantTap($aFunctionArguments, $iCutFunctions + 1);
				break;
		}
	}
	
	
	
	
	public static function performantTap ($aFunctionArguments = array(), $iCutFunctions = 0) {
		
		$aBackTrace = debug_backtrace();
		$aRelevant = $aBackTrace[1 + $iCutFunctions];
		$aPrevious = $aBackTrace[1 + $iCutFunctions + 1];
		$aRelativePathFile = str_replace(ODT::$sBasePath, '', $aPrevious['file']);
		$sRelevant = $aRelativePathFile . ':' . $aPrevious['line'] . '  ' . $aRelevant['class'] . $aRelevant['type'] . $aRelevant['function'] . '()';
		ODT::log($sRelevant, self::$sWiretappingLogFile, -1, 0, $iCutFunctions + 1);
		
	}
	
	
	
	
	public static function advancedTap ($aFunctionArguments = array(), $iCutFunctions = 0) {
		
		$sFunctionArguments = '';
		foreach ($aFunctionArguments as $iKey => $mFunctionArgument) {
			if ($iKey != 0) {
				$sFunctionArguments .= ', ';
			}
			switch (gettype($mFunctionArgument)) {
				case 'boolean':
					$sFunctionArguments .= $mFunctionArgument ? 'true' : 'false';
					break;
				case 'integer':
					$sFunctionArguments .= $mFunctionArgument;
					break;
				case 'double':
					$sFunctionArguments .= $mFunctionArgument;
					break;
				case 'string':
					$sFunctionArguments .= '"' . $mFunctionArgument . '"';
					break;
				default:
					$sFunctionArguments .= '[' . gettype($mFunctionArgument) . ']';
					break;
			}
		}
				
		$aLocalStack = ODT::getStack($iCutFunctions + 1);
		
		$iGlobalStackSize = count(self::$aGlobalStack);
		$iLocalStackSize = count($aLocalStack);
		
		/// calculate which is the first new (to be written) stack entry BEGIN
		if ($iGlobalStackSize < $iLocalStackSize ) {
			$iNewStartsAt = $iGlobalStackSize;
		} else {
			$iNewStartsAt = $iLocalStackSize - 1;
		}
		$iMaybeStartsHere = $iNewStartsAt - 1;
		while (true) {
			if ($iMaybeStartsHere < 0) {
				break;
			}
			if (isset(self::$aGlobalStack[$iMaybeStartsHere]['bExplicitlyLogged']) && self::$aGlobalStack[$iMaybeStartsHere]['bExplicitlyLogged'] == true) {
				break;
			}
			$bEqual = true;
			$aMustBeSame = array('file', 'line', 'class', 'type', 'function');
			foreach ($aMustBeSame as $iKey => $sMustBeSame) {
				if (self::$aGlobalStack[$iMaybeStartsHere][$sMustBeSame] != $aLocalStack[$iMaybeStartsHere][$sMustBeSame]) {
					$bEqual = false;
					break;
				}
			}
			if (!$bEqual) {
				$iNewStartsAt = $iMaybeStartsHere;
			}
			$iMaybeStartsHere --;
		}
		/// calculate which is the first new (to be written) stack entry END
		
		for ($i = $iNewStartsAt; $i < $iLocalStackSize - 1; $i ++) {
			
			$aLayer = $aLocalStack[$i];
			if ($i == $iLocalStackSize - 2) {
				$aLocalStack[$i]['bExplicitlyLogged'] = true;
				$sLayerArguments = $sFunctionArguments;
			} else {
				$aLocalStack[$i]['bExplicitlyLogged'] = false;
				$sLayerArguments = '';
			}
			$sLayer = $aLayer['class'] . $aLayer['type'] . $aLayer['function'] . '(' . $sLayerArguments . ')';
			$iIndent = $i;
			$sIndent = str_pad('', $iIndent * 2);
			$sLoggingMark = ($aLocalStack[$i + 1]['function'] == '[log]' ? '✔ ' : '✖ '); //₰★✔✖♆
			
			ODT::log($sIndent . $sLoggingMark . $sLayer, self::$sWiretappingLogFile, -1, 0, $iCutFunctions + 1);
			
		}
		
		$iPopItems = $iGlobalStackSize - $iNewStartsAt - 1;
		for ($i = 0; $i < $iPopItems; $i ++) {
			array_pop(self::$aGlobalStack);
		}
		for ($i = $iNewStartsAt; $i < $iLocalStackSize; $i ++) {
			self::$aGlobalStack []= $aLocalStack[$i];
		}
		
	}
	
	
	
	
	public static function primitiveTap ($aFunctionArguments = array(), $iCutFunctions = 0) {
		
		ODT::log("\n" . '{{ctf}}()', self::$sWiretappingLogFile, -1, 0, $iCutFunctions + 1);
		ODT::logStack(self::$sWiretappingLogFile, $iCutFunctions + 1);
		
	}
	
	
	
	
	public static function wiretap ($sDirectory = '', $sInsert = null) {
		
		if ($sInsert === null) {
			$sInsert = self::$sTapping;
		}
		
        if (substr($sDirectory, 0, 1) != '/') {
            $sDirectory = ODT::$sBasePath . '/' . $sDirectory;
        }
        $sDirectory = preg_replace('/\/$/', '', $sDirectory);
        
		$aFiles = self::aListAllPhpFiles($sDirectory);
		
		foreach ($aFiles as $sFile) {
			
			$sFileContent = file_get_contents($sFile);
			$sNewFileContent = self::sFunctionInsert($sFileContent, "\n" . $sInsert);
			$oStream = fopen($sFile, 'w') or die('can not open file ' . $sFile);
			fwrite($oStream, $sNewFileContent);
			fclose($oStream);
			
			echo 'wiretapped ' . $sFile . '<br>';
			
		}
		
		echo 'wiretapped ' . count($aFiles) . ' files<br>';
		die('done');
		
	}
	
	
	
	
	public static function unwiretap ($sDirectory = '', $sDelete = null) {
		
		if ($sDelete === null) {
			$sDelete = self::$sTapping;
		}
		
        if (substr($sDirectory, 0, 1) != '/') {
            $sDirectory = ODT::$sBasePath . '/' . $sDirectory;
        }
        $sDirectory = preg_replace('/\/$/', '', $sDirectory);
        
		$aFiles = self::aListAllPhpFiles($sDirectory);
		
		foreach ($aFiles as $sFile) {
			
			$sFileContent = file_get_contents($sFile);
			$sNewFileContent = str_replace("\n" . $sDelete, '', $sFileContent);
			$oStream = fopen($sFile, 'w') or die('can not open file ' . $sFile);
			fwrite($oStream, $sNewFileContent);
			fclose($oStream);
			
			echo 'unwiretapped ' . $sFile . '<br>';
			
		}
		
		echo 'wiretapped ' . count($aFiles) . ' files<br>';
		die('done');
		
	}
	
	
	
	
	public static function sFunctionInsert ($sCode, $sAddThis) {
		
		return preg_replace('/\Wfunction\s+\w+[^\(]*\([^\)]*\)\s*\{/i', '$0' . $sAddThis, $sCode);
		
	}
	
	
	
	
	public static function aListAllPhpFiles ($sDirectory) {
		
		if (is_dir($sDirectory)) {
			
			$aReturn = array();
			
			$aDirectories = array($sDirectory);
			while (count($aDirectories) > 0) {
				$sCurrentDir = array_shift($aDirectories);
				$aContents = self::getContentsOfDirectory($sCurrentDir);
				foreach ($aContents as $sContent) {
					if (is_dir($sContent)) {
						if (!is_link($sContent)) {
							$aDirectories []= $sContent;
						}
					} else {
						if (preg_match('/\.php\z/', $sContent) > 0) {
							$aReturn [] = $sContent;
						}
					}
				}
				
			}
			
			return $aReturn;
			
		} else {
			
			return array($sDirectory);
			
		}
		
		
	}
	
	
	
	
	static function getContentsOfDirectory ($sDirectory) {
		
		try {
			
			$aReturn = array();
			
			$oHandler = opendir($sDirectory);
			
			if ($oHandler === false) {
				return false;
			}
			
			while (false != ($sFile = readdir($oHandler))) {
				if ($sFile != '.' && $sFile != '..') {
					$aReturn []= $sDirectory . '/' . $sFile;
				}
			}
			
			closedir($oHandler);
			
			return $aReturn;
			
		} catch (Exception $e) {
			
			echo '<br>';
			var_dump($e);
			echo '<br>';
			
			return false;
			
		}
		
	}
	
	
	
	
}



