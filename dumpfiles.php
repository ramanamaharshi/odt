<?php




class DumpFileNames {
	
	
	
	
	public static function wiretap ($sDirectory, $sInsert = null) {
		
		if ($sInsert === null) {
			$sInsert = self::$sTapping;
		}
		
		$aFiles = self::aListAllPhpFiles($sDirectory);
		
		foreach ($aFiles as $sFile) {
			
			$sFileContent = file_get_contents($sFile);
			$sNewFileContent = self::sFunctionInsert($sFileContent, "\n" . $sInsert);
			$oStream = fopen($sFile, 'w') or die('can not open file ' . $sFile);
			fwrite($oStream, $sNewFileContent);
			fclose($oStream);
			
			echo 'wiretapped ' . $sFile . '<br />';
			
		}
		
		die('done');
		
	}
	
	
	
	
	public static function unwiretap ($sDirectory, $sDelete = null) {
		
		if ($sDelete === null) {
			$sDelete = self::$sTapping;
		}
		
		$aFiles = self::aListAllPhpFiles($sDirectory);
		
		foreach ($aFiles as $sFile) {
			
			$sFileContent = file_get_contents($sFile);
			$sNewFileContent = str_replace("\n" . $sDelete, '', $sFileContent);
			$oStream = fopen($sFile, 'w') or die('can not open file ' . $sFile);
			fwrite($oStream, $sNewFileContent);
			fclose($oStream);
			
			echo 'unwiretapped ' . $sFile . '<br />';
			
		}
		
		die('done');
		
	}
	
	
	
	
	public static function sFunctionInsert ($sCode, $sAddThis) {
		
		return preg_replace('/^/i', $sAddThis, $sCode);
		
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
						$aDirectories []= $sContent;
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
			
			echo '<br />';
			var_dump($e);
			echo '<br />';
			
			return false;
			
		}
		
	}
	
	
	
	
}

#DumpFileNames::unwiretap('/zzz/projects/alt/proethiopia/hack_recovery/proethiopia.de/htdocs', '\<\?php var_dump(__FILE__);die; \?\>');





















