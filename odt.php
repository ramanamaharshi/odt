<?php




class ODT {
	
	
	
	
	public static $aActivationRequest = array();
	public static $aFindDumpsRequest = array('odt_find' => 1);
	
	public static $bFindDumpsAndLogs = false;
	
	public static $bLog = true;
	public static $iDumpFontSize = 13;
	public static $sDumpColor = '#000000';
	public static $sDumpBackgroundColor = '#FFFFFF';
	
	public static $bInitialized = false;
	
	public static $sLogBasePath = '/odt/log';
	public static $sQuickLogFile = 'odt.quick.log';
	public static $sPersistentLogFile = 'odt.persistent.log';
	
    private static $bLogCleared = false;
	private static $oLogStream = null;
	private static $oPersistentLogStream = null;
	private static $iJavascriptIdCounter = 0;
	
	public static $sBasePath = null;
	
	public static $aDebugCodes = array();
	
	public static $bPrintXmlFilePaths = false;
	
	public static $aDateTagged = array();
	
	public static $aGeanyColors = array(
		 'default'	=>	'#000000'
		,'comment'	=>	'#808080'
		,'keyword'	=>	'#000099'
		,'operator'	=>	'#102060'
		,'variable'	=>	'#7F0000'
		,'string_a'	=>	'#FF901E'
		,'string_b'	=>	'#008000'
		,'number'	=>	'#606000'
	);
    
	
	
	
	public static function init ($sBasePath, $sLogDir = null) {
		
		if (self::$bInitialized) {
			return;
		}
		self::$bInitialized = true;
		
		self::$sBasePath = $sBasePath;
		if (!$sLogDir) {
			$sLogDir = $sBasePath;
		} else {
			if (substr($sLogDir, 0, 1) == '/') {
				self::$sLogBasePath = $sLogDir;
			} else {
				self::$sLogBasePath = $sBasePath . '/' . $sLogDir;
			}
		}
		self::$sLogBasePath = realpath(self::$sLogBasePath);
		self::$sBasePath = realpath(self::$sBasePath);
        
		if (isset(self::$aActivationRequest) && count(self::$aActivationRequest)) {
			foreach (self::$aActivationRequest as $sKey => $sValue) {
				if (!isset($_REQUEST[$sKey]) || $_REQUEST[$sKey] != $sValue) {
					self::$bLog = false;
				}
			}
		}
		
		if (isset(self::$aFindDumpsRequest) && count(self::$aFindDumpsRequest)) {
			self::$bFindDumpsAndLogs = true;
			foreach (self::$aFindDumpsRequest as $sKey => $sValue) {
				if (!isset($_REQUEST[$sKey]) || $_REQUEST[$sKey] != $sValue) {
					self::$bFindDumpsAndLogs = false;
				}
			}
		}
		
	}
	
	
	
	
	public static function close () {}
	
	
	
	
	public static function logStart ($sDebugCode, $iMaxLogs = -1) {
		if (!isset(self::$aDebugCodes[$sDebugCode])) {
			self::$aDebugCodes[$sDebugCode] = $iMaxLogs;
		}
	}
	public static function logEnd ($sDebugCode) {
		self::$aDebugCodes[$sDebugCode] = 0;
	}
	public static function logCodeActive ($sDebugCode) {
		return isset(self::$aDebugCodes[$sDebugCode]) && self::$aDebugCodes[$sDebugCode] !== 0;
	}
	
	
	
	
	public static function persLogStack () {
		return self::logStack(self::$sPersistentLogFile, 1);
	}
	public static function quickLogStack () {
		return self::logStack(self::$sQuickLogFile, 1);
	}
	public static function logStack ($sFile = null, $iCutFunctions = 0) {
		
		if ($sFile == null) {
			$sFile = self::$sQuickLogFile;
		}
		
		self::beforeLog($sFile);
		
		$aStackAsStringArray = self::aGetStackAsStringArray($iCutFunctions + 1);
		
		foreach ($aStackAsStringArray as $sString) {
			self::log($sString, $sFile, -1, null, $iCutFunctions + 1);
		}
		
		/*$aStack = self::getStack($iCutFunctions + 1);
		
		$iMaxLength = 0;
		foreach ($aStack as $iKey => $aLayer) {
			if ($iMaxLength < strlen($aLayer['sPartA'])) {
				$iMaxLength = strlen($aLayer['sPartA']);
			}
		}
		
		foreach ($aStack as $iKey => $aLayer) {
			$sMessage = '#' . str_pad($iKey, 2, '0', STR_PAD_LEFT) . ' ' . str_pad($aLayer['sPartA'], $iMaxLength, ' ') . '  ' . $aLayer['class'] . $aLayer['type'] . $aLayer['function'] . '()';
			self::log($sMessage, $sFile, -1, null, $iCutFunctions + 1);
		}*/
		
	}
	
	
	
	
	public static function aGetStackAsStringArray ($iCutFunctions = 0) {
		
		$aStack = self::getStack($iCutFunctions + 1);
		
		$iMaxLength = 0;
		foreach ($aStack as $iKey => $aLayer) {
			if ($iMaxLength < strlen($aLayer['sPartA'])) {
				$iMaxLength = strlen($aLayer['sPartA']);
			}
		}
		
		$aReturn = array();
		
		foreach ($aStack as $iKey => $aLayer) {
			$sMessage = '#' . str_pad($iKey, 2, '0', STR_PAD_LEFT) . ' ' . str_pad($aLayer['sPartA'], $iMaxLength, ' ') . '  ' . $aLayer['class'] . $aLayer['type'] . $aLayer['function'] . '()';
			$aReturn []= $sMessage;
		}
		
		return $aReturn;
		
	}
	
	
	
	
	public static function getStack ($iCutFunctions = 0) {
		
		$aStack = debug_backtrace();
		for ($i = 0; $i < $iCutFunctions; $i ++) {
			array_shift($aStack);
		}
		
		foreach ($aStack as $iKey => $aLayer) {
			if (!isset($aLayer['file'])) {
				$aLayer['file'] = '?';
			}
			if (!isset($aLayer['line'])) {
				$aLayer['line'] = '?';
			}
			$aStack[$iKey]['file'] = self::removeBasePath($aLayer['file']);
			$aStack[$iKey]['sPartA'] = $aStack[$iKey]['file'] . ':' . $aLayer['line'];
			$aStack[$iKey]['function'] = $aStack[$iKey]['function'];
		}
		
		$aStack[0]['class'] = '';
		$aStack[0]['type'] = '';
		$aStack[0]['function'] = '[log]';
		
		$aStack = array_reverse($aStack, false);
		
		foreach ($aStack as $iKey => $aLayer) {
			$aSubstitute = array(
				 'sPartA' => '?'
				,'class' => ''
				,'type' => ''
				,'function' => ''
			);
			foreach ($aLayer as $sKey => $mLayer) {
				$aSubstitute[$sKey] = $aLayer[$sKey];
			}
			$aStack[$iKey] = $aSubstitute;
		}
		
		return $aStack;
		
	}
	
	
	
	
	public static function persLogCTF ($aDebugParam = null, $iCutFunctions = 0) {
		return self::logCTF($aDebugParam, self::$sPersistentLogFile, $iCutFunctions + 1);
	}
	public static function quickLogCTF ($aDebugParam = null, $iCutFunctions = 0) {
		return self::logCTF($aDebugParam, self::$sQuickLogFile, $iCutFunctions + 1);
	}
	public static function logCTF ($aDebugParam = null, $sFile = null, $iCutFunctions = 0) {
		return self::log('{{ctf}}()', $sFile, -1, $aDebugParam, $iCutFunctions + 1);
	}
	
	
	
	
	public static function ctf ($sSpecial = '{{ctf}}') {
		
		$sParsed = self::sParseSpecial($sSpecial, 1);
		self::ec($sParsed);
		
	}
	
	
	
	
	public static function persLog ($mSubject, $iDepth = -1, $aDebugParam = null, $iCutFunctions = 0) {
		return self::persistentLog($mSubject, $iDepth, $aDebugParam, $iCutFunctions + 1);
	}
	public static function persistentLog ($mSubject, $iDepth = -1, $aDebugParam = null, $iCutFunctions = 0) {
		return self::log($mSubject, self::$sPersistentLogFile, $iDepth, $aDebugParam, $iCutFunctions + 1);
	}
	public static function quickLog ($mSubject, $iDepth = -1, $aDebugParam = null, $iCutFunctions = 0) {
		return self::log($mSubject, self::$sQuickLogFile, $iDepth, $aDebugParam, $iCutFunctions + 1);
	}
	public static function log ($mSubject, $sFile = null, $iDepth = -1, $aDebugParam = null, $iCutFunctions = 0) {
		
		if (self::$bFindDumpsAndLogs) {
			self::$bFindDumpsAndLogs = false;
			self::log(self::toString('{{file}} : {{line}}', 1, array(), $iTabs, $bHtml, $iCutFunctions));
			self::$bFindDumpsAndLogs = true;
		}
		
		if (!self::$bLogCleared) {
			self::$bLogCleared = true;
			self::logClear();
		}
		
		$bFindLogs = false;
		
		if ($sFile == null) {
			$sFile = self::$sQuickLogFile;
		}
		
		$sWriteFile = self::$sLogBasePath . '/' . $sFile;
		
		/// debugcodes BEGIN
		if ($aDebugParam !== null) {
			if (!is_array($aDebugParam)) {
				if ($aDebugParam == '') {
					$aDebugParam = array();
				} else {
					$aDebugParam = array($aDebugParam);
				}
			}
			$bAllDebugCodesTrue = true;
			foreach ($aDebugParam as $sDebugCode) {
				if (self::$aDebugCodes[$sDebugCode] == 0) {
					$bAllDebugCodesTrue = false;
				}
			}
			foreach ($aDebugParam as $sDebugCode) {
				if (self::logCodeActive($sDebugCode)) {
					self::$aDebugCodes[$sDebugCode] --;
				}
			}
			if (!$bAllDebugCodesTrue) {
				return;
			}
		}
		/// debugcodes END
		
		self::beforeLog($sFile);
		
		if ($bFindLogs) {
			$mSubject = $sMessage = '{{fl}}';
		}
		
		if (is_string($mSubject)) {
			
			$sMessage = $mSubject . "\n";
			
			$sMessage = self::sParseSpecial($sMessage, $iCutFunctions + 1);
			
		} else {
			
			$sMessage = self::toString($mSubject, $iDepth) . "\n";
			
		}
		
		$oStream = fopen($sWriteFile, 'a') or die('O SHIT');
		fwrite($oStream, $sMessage);
		fclose($oStream);
		chmod($sWriteFile, 0777);
		
	}
	
	
	
	
	public static function sParseSpecial ($sMessage, $iCutFunctions) {
		
		$aBacktrace = debug_backtrace();
		$sTraceFile = $aBacktrace[$iCutFunctions]['file'];
		$sTraceFile = self::removeBasePath($sTraceFile);
		$sLine = $aBacktrace[$iCutFunctions]['line'];
		if (
			array_key_exists($iCutFunctions + 1, $aBacktrace) 
			&& isset($aBacktrace[$iCutFunctions + 1])
			&& isset($aBacktrace[$iCutFunctions + 1]['class'])
			&& isset($aBacktrace[$iCutFunctions + 1]['type'])
			&& isset($aBacktrace[$iCutFunctions + 1]['function'])
		) {
			$sClass = $aBacktrace[$iCutFunctions + 1]['class'];
			$sType = $aBacktrace[$iCutFunctions + 1]['type'];
			$sFunction = $aBacktrace[$iCutFunctions + 1]['function'] . '()';
		} else {
			$sClass = '';
			$sType = '';
			$sFunction = '';
		}
		$sMessage = str_replace('{{fl}}', '{{f}}:{{l}}', $sMessage);
		$sMessage = str_replace('{{f}}', '{{file}}', $sMessage);
		$sMessage = str_replace('{{file}}', $sTraceFile, $sMessage);
		$sMessage = str_replace('{{l}}', '{{line}}', $sMessage);
		$sMessage = str_replace('{{line}}', $sLine, $sMessage);
		$sMessage = str_replace('{{ctf}}', '{{c}}{{t}}{{f}}', $sMessage);
		$sMessage = str_replace('{{c}}', '{{class}}', $sMessage);
		$sMessage = str_replace('{{class}}', $sClass, $sMessage);
		$sMessage = str_replace('{{t}}', '{{type}}', $sMessage);
		$sMessage = str_replace('{{type}}', $sType, $sMessage);
		$sMessage = str_replace('{{f}}', '{{function}}', $sMessage);
		$sMessage = str_replace('{{function}}', $sFunction, $sMessage);
		
		return $sMessage;
		
	}
	
	
	
	
	public static function logClear () {
		
		$oStream = fopen(self::$sLogBasePath . '/' . self::$sQuickLogFile, 'w') or die('could not open ' . self::$sLogBasePath . '/' . self::$sQuickLogFile);
		fclose($oStream);
		
	}
	
	
	
	
	public static function beforeLog ($sFile) {
		
		if (!isset(self::$aDateTagged[$sFile])) {
			self::$aDateTagged[$sFile] = true;
            $sDateString = date('y-m-d  H:i:s');
			$sDateTag = "\n\n" . $sDateString . "\n";
			self::log($sDateTag, $sFile);
		}
		
	}
	
	
	
	
	public static function vEcho ($sString, $sAdditionalStyles = '') {
		
		return self::ec($sString, $sAdditionalStyles);
		
	}
	
	
	
	
	public static function ec ($sString, $sAdditionalStyles = '', $bPlainToHtml = true, $iCutFunctions = 0) {
		
		if (!self::$bLog) return;
		if (self::$bFindDumpsAndLogs) {
			self::$bFindDumpsAndLogs = false;
			self::ec(self::sParseSpecial('{{file}}:{{line}}', $iCutFunctions + 1), 'font-weight:bold;font-size:10px;padding-bottom:0;margin-bottom:0;text-decoration:underline;color: #aaa;');
			self::$bFindDumpsAndLogs = true;
		}
		if ($bPlainToHtml) {
			self::sHTMLify($sString);
		}
		
		echo "\n" . '<div data-fl="' . htmlentities(self::sParseSpecial('{{fl}}', $iCutFunctions + 1)) . '">';
		echo "\n" . '<div style="overflow-x:auto;overflow-y:hidden;clear:both;font-family:monospace;text-transform:none;background-color:' . self::$sDumpBackgroundColor . ';color:' . self::$sDumpColor . ';padding:3px;margin:3px 0;font-size:' . self::$iDumpFontSize . 'px;' . $sAdditionalStyles . '">' . $sString . '</div>' . "\n";
		echo "\n" . '</div>';
		
	}
	
	
	
	
	public static function get_class ($oObject) {
		
		self::ec(get_class($oObject));
		
	}
	
	
	
	
	public static function dumpStack ($iCutFunctions = 0) {
		
		$aStackAsStringArray = self::aGetStackAsStringArray($iCutFunctions + 1);
		$sStack = implode('<br />', $aStackAsStringArray);
		self::ec($sStack, '', true, $iCutFunctions + 1);
		
	}
	
	
	
	
	public static function vExitFixed ($mValue, $iDepth = -1) {
		self::vExit($mValue, $iDepth, array('aAdditionalStyles' => array(
			'position' => 'fixed',
			'opacity' => '0.66',
			'max-height' => '100%',
			'max-width' => '100%',
			'left' => 0,
			'top' => 0,
		)));
	}
	
	
	
	
	public static function vExit ($mValue = '[nonce_HFxT2kjM8CRwNCqN]', $iDepth = -1, $aExtraParams = array(), $iTabs = 0, $bHtml = true, $iCutFunctions = 0) {
		
		if (!self::$bLog) return;
		if ($mValue !== '[nonce_HFxT2kjM8CRwNCqN]') {
			self::dump($mValue, $iDepth, $aExtraParams, $iTabs, $bHtml, $iCutFunctions + 1);
		}
		exit();
		
	}
	
	
	
	
	public static function dump ($mValue, $iDepth = -1, $aExtraParams = array(), $iTabs = 0, $bHtml = true, $iCutFunctions = 0) {
		
		if (!self::$bLog) return;
		$sString = self::toString($mValue, $iDepth, $aExtraParams, $iTabs, $bHtml, $iCutFunctions + 1, 0);
		$sAdditionalStyles = '';
		if (isset($aExtraParams['aAdditionalStyles'])) {
			foreach ($aExtraParams['aAdditionalStyles'] as $sKey => $sValue) {
				$sAdditionalStyles .= $sKey . ':' . $sValue . ';';
			}
		}
		self::ec($sString, $sAdditionalStyles, false, $iCutFunctions + 1);
		
	}
	
	
	
	
	public static function toString ($mThing, $iDepth = -1, $aExtraParams = array(), $iTabs = 0, $bHtml = false, $iCutFunctions = 0, $iDown = 0) {
		
		$bFindValue = isset($aExtraParams['sFindValue']);
		$aExtraParamsWithoutFindValue = $aExtraParams;
		unset($aExtraParamsWithoutFindValue['sFindValue']);
		
		if ($iDepth == 0) {
			$sReturn = '[end]';
			if ($bFindValue) {
				$sReturn = null;
			}
			return $sReturn;
		}
		
		$sType = null;
		
		if ($mThing === null) {
			$sString = 'NULL';
			$sReturn = $sString;
			$sColorWrap = 'keyword';
		}
		
		if (is_bool($mThing)) {
			$sString = ($mThing === true) ? 'true' : 'false';
			$sReturn = $sString;
			$sColorWrap = 'keyword';
		}
		
		if (is_int($mThing)) {
			$sString = '' . $mThing . '';
			$sReturn = $sString;
			$sColorWrap = 'number';
		}
		
		if (is_float($mThing)) {
			$sString = '' . $mThing . '';
			$sReturn = $sString;
			$sColorWrap = 'number';
		}
		
		if (is_string($mThing)) {
			$sString = $mThing;
            if (!mb_check_encoding($sString, 'UTF-8')) {
                $sString = utf8_encode($sString);
            }
			$sReturn = $sString;
			if ($iDown == 0) {
				$sReturn = self::sParseSpecial($sReturn, $iCutFunctions + 1);
			}
			if ($bHtml) {
				$sReturn = self::sHTMLify($sReturn);
			}
			$sReturn = '\'' . str_replace('\'', '\\\'', str_replace('\\', '\\\\', $sReturn)) . '\'';
			$sColorWrap = 'string_a';
		}
		
		if ($mThing === null || is_bool($mThing) || is_int($mThing) || is_float($mThing) || is_string($mThing)) {
			if ($bHtml) {
				$sReturn = self::sColorWrap($sReturn, $sColorWrap);
			}
			if ($bFindValue) {
                $sFindValuePreg = $aExtraParams['sFindValue'];
                $sFindValuePreg = '/' . trim($sFindValuePreg, '/') . '/';
				if (preg_match($sFindValuePreg, $sString) !== 1) {
					$sReturn = null;
				}
			}
			return $sReturn;
		}
		
		$bObject = is_object($mThing);
		
		if (is_object($mThing)) {
			
			$sType = 'object (' . get_class($mThing) . ') : {';
			$aProperties = (array)$mThing;
			$aFunctions = get_class_methods(get_class($mThing));
			if ($aProperties === null) {
				$aProperties = array();
			}
			if ($aFunctions === null) {
				$aFunctions = array();
			}
			$aArray = array();
			$iCounter = 0;
			foreach ($aProperties as $sKey => $mValue) {
				$sEscapedKey = preg_replace('/[^0-9a-zA-Z\_\ \*\+\-]/', '', $sKey);
				$aArray[$sEscapedKey] = $mValue;
			}
			//$aArray = $aProperties;
			/*foreach ($aFunctions as $sFunction) {
				$aArray[$sFunction . '()'] = null;
			}*/
			$mThing = $aArray;
			
			if (count($aArray) === 0) {
				$sType .= '}';
			}
			
		}
		
		if (is_array($mThing)) {
			
			$bEmpty = count($mThing) === 0;
			
			if ($sType === null) {
				if ($bHtml) {
					$sType = self::sColorWrap('array', 'keyword') . ' ';
					if ($bEmpty) {
						$sType .= self::sColorWrap('()', 'operator');
					} else {
						$sType .= self::sColorWrap('(', 'operator') . self::sColorWrap(' # (' . count($mThing) . ')', 'comment');
					}
				} else {
					$sType = 'array (' . ($bEmpty ? '' : ' # (' . count($mThing)) . ')';
				}
			}
			
			$sReturn = $sType;
			
			if ($bEmpty) return $sReturn;
			
			if ($bHtml) {
				$sSpace = '&nbsp;';
				$sTab = $sSpace . $sSpace . $sSpace . $sSpace . $sSpace;
				$sLnBr = '<br />';
			} else {
				$sSpace = " ";
				$sTab = "\t";
				$sLnBr = "\n";
			}
			
			$sComma = ',';
			$sArrow = '=>';
			if ($bHtml) {
				$sComma = self::sColorWrap($sComma, 'operator');
				$sArrow = self::sColorWrap($sArrow, 'operator');
			}
			
			/// klappen BEGIN
			self::$iJavascriptIdCounter ++;
			if ($bHtml) {
				$sToggleJS = 'var oNode = document.getElementById(\'js_toggle_' . self::$iJavascriptIdCounter . '\'); if (oNode.style.display == \'none\') { oNode.style.display = \'block\'; } else { oNode.style.display = \'none\'; }';
				$sReturn .= ' <span style="cursor: pointer; color: #484;" onclick="javascript:' . $sToggleJS . '">&curren;</span><div id="js_toggle_' . self::$iJavascriptIdCounter . '">';
			}
			/// klappen END
			
			$bFirst = true;
			if ($bFindValue) {
				$bAllValuesNull = true;
			}
			foreach ($mThing as $sKey => $mVal) {
				$bNumericKey = ('' . intval($sKey) === $sKey);
				if ($bNumericKey) {
					$sKey = intval($sKey);
				}
				$sKey = self::toString($sKey, -1, $aExtraParamsWithoutFindValue, 0, $bHtml, $iCutFunctions + 1, $iDown + 1);
				$sValue = self::toString($mVal, $iDepth -1, $aExtraParams, $iTabs + 1, $bHtml, $iCutFunctions + 1, $iDown + 1);
				$sAddToReturn = (($bHtml && $bFirst) ? '' : $sLnBr) . self::sMakeTabs($iTabs + 1, $sTab) . ($bFirst ? $sSpace : $sComma) . $sKey . $sSpace . $sArrow . $sSpace . $sValue;
				if ($bFindValue) {
					if ($sValue === null) {
						$sAddToReturn = '';
					} else {
						$bAllValuesNull = false;
					}
				}
				$sReturn .= $sAddToReturn;
				$bFirst = false;
			}
			/// klappen BEGIN
			if ($bHtml) {
				$sReturn .= '</div>';
			}
			/// klappen END
			$sClosingBracked = $bObject ? '}' : ')';
			$sReturn .= ($bHtml ? '' : $sLnBr) . self::sMakeTabs($iTabs, $sTab) . ($bHtml ? self::sColorWrap($sClosingBracked, 'operator') : $sClosingBracked);
			
			if ($bFindValue) {
				if ($bAllValuesNull) {
					$sReturn = null;
				}
			}
			
			return $sReturn;
			
		}
		
		
	}
	
	
	
	
	public static function sFormatXml ($sInput, $bRemoveAttributes = false, $bShortenTextNodes = false) {
		
		$sOutput = '';
		
		$iTabs = 0;
		$bFirst = true;
		preg_match_all('/(<[^>]*>)|([^<>]+)/', $sInput, $aMatches);
		$aInput = $aMatches[0];
		
		for ($i = 0; $i < count($aInput); $i ++) {
			$aInput[$i] = array(
				'sContent' => $aInput[$i],
				'sType' => self::sFormatXmlNicelyPartType($aInput[$i]),
			);
		}
		for ($i = 0; $i < count($aInput); $i ++) {
			
			$sContent = $aInput[$i]['sContent'];
			$sType = $aInput[$i]['sType'];
			
			if ($sType == 'textnode') {
				$sContent = preg_replace('/^\s+/', ' ', $sContent);
				$sContent = preg_replace('/\s+$/', ' ', $sContent);
				if ($bShortenTextNodes) {
					$sContent = preg_replace('/^( ?).*\S+.*?( ?)$/', '\1...\2', $sContent);
				}
			} else if ($sType != 'closing') {
				if ($bRemoveAttributes) {
					$sContent = preg_replace('/^<(\S+)(\s.*)?(\/?)>$/', '<\1\3>', $sContent);
				}
			}
			
			if ($i != 0) {
				$sOutput .= $sFormatting;
			}
			
			$sOutput .= $sContent;
			
			$bThisOpening = $sType == 'opening';
			$bNextClosing = false;
			if ($i + 1 != count($aInput)) {
				if ($aInput[$i + 1]['sType'] == 'closing') {
					$bNextClosing = true;
				}
			}
			
			if ($bThisOpening) {
				$iTabs ++;
			}
			if ($bNextClosing) {
				$iTabs --;
			}
			
			$sTabs = '';
			for ($i2 = 0; $i2 < $iTabs; $i2 ++) {
				$sTabs .= "\t";
			}
			
			if ($bThisOpening && $bNextClosing) {
				$sFormatting = '';
			} else {
				$sFormatting = '' . "\n" . $sTabs . '';
			}
			
		}
		
		return $sOutput;
		
	}
	
	
	
	
	private static function sFormatXmlNicelyPartType ($sPart) {
		
		$aSpecial = array('br', 'img');
		
		if (preg_match('/^<[^>]*>$/', $sPart)) {
			if (preg_match('/^<\//', $sPart)) {
				$sType = 'closing';
			} else if (preg_match('/^<[^>]*\/>$/', $sPart)) {
				$sType = 'empty';
			} else {
				preg_match_all('/^<(\S+)(\s.*)?>$/', $sPart, $aMatches);
				$sTag = $aMatches[1][0];
				if (in_array($sTag, $aSpecial)) {
					$sType = 'empty';
				} else {
					$sType = 'opening';
				}
			}
		} else {
			$sType = 'textnode';
		}
		
		return $sType;
		
	}
	
	
	
	
	public static function sHTMLify ($sInput) {
		
		$sOutput = $sInput;
		$sOutput = htmlentities($sOutput, ENT_NOQUOTES, 'UTF-8');
		$sOutput = str_replace("\t", '    ', $sOutput);
		$sOutput = str_replace(' ', '&nbsp;', $sOutput);
		$sOutput = str_replace("\n", '<br />', $sOutput);
		return $sOutput;
		
	}
	
	
	
	
	public static function sColorWrap ($sContent, $sColorTitle) {
		
		return self::sStyleWrap($sContent, array('color' => self::$aGeanyColors[$sColorTitle]));
		
	}
	
	
	
	public static function sStyleWrap ($sContent, $aStyle) {
		
		$sStyle = '';
		foreach ($aStyle as $sKey => $sValue) {
			$sStyle .= $sKey . ':' . $sValue . ';';
		}
		$sReturn = '<span style="' . $sStyle . '">' . $sContent . '</span>';
		return $sReturn;
		
	}
	
	
	
	
	public static function sMakeTabs ($iTabs, $sTab = "\t") {
		
		$sReturn = '';
		
		for ($i = 0; $i < $iTabs; $i ++) {
			$sReturn .= $sTab;
		}
		
		return $sReturn;
		
	}
	
	
	
	
	
	public static function removeBasePath ($sPath) {
		
		return str_replace(self::$sBasePath, '', $sPath);
		
	}
	
	
	
	
}