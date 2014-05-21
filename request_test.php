<?php
class RequestTest {
	
	
	
	
	public static $sRequestStoreKey = 'requests';
	public static $iMaxRequestsStored = 8;
	
	
	
	
	public static function main ($bRecordAll = true) {
		
		if (isset($_REQUEST['request_test'])) {
			echo self::sDashboard();
			exit();
		} else if (isset($_REQUEST['repeat_request'])) {
			self::vRepeatRequest($_REQUEST['request_id']);
		} else if ($bRecordAll || isset($_REQUEST['record_request'])) {
			if (!$_REQUEST['this_is_a_request_test']) {
				self::vStoreRequest();
			}
		}
		
	}
	
	
	
	
	public static function sDashboard () {
		
		$sHTML = '';
		ODT::dump(Store::get(self::$sRequestStoreKey));
		$aRequests = self::aGetRequests();
		foreach ($aRequests as $aRequest) {
			$sMethod = $aRequest['SERVER']['REQUEST_METHOD'];
			$sURI = $aRequest['SERVER']['REQUEST_URI'];
			$sArguments = $aRequest['REQUEST'];
			$sRequestInfo = $sMethod . ' ' . $sURI;
			$sHTML .= '<div><a href="/?repeat_request&request_id=' . $aRequest['sID'] . '">' . $sRequestInfo . '</a></div>';
		}
		return $sHTML;
		
	}
	
	
	
	
	public static function vRepeatRequest ($sID) {
		
		$aRequests = self::aGetRequests();
		foreach ($aRequests as $aOneRequest) {
			if ($aOneRequest['sID'] == $sID) {
				$aRequest = $aOneRequest;
				break;
			}
		}
		if (isset($aRequest)) {
			$sCurlURL = 'http://' . $_SERVER['HTTP_HOST'] . $aRequest['SERVER']['REQUEST_URI'];
			$aCurlOptions = array(
				 CURLOPT_TIMEOUT			=>	30
				,CURLOPT_CUSTOMREQUEST		=>	$aRequest['SERVER']['REQUEST_METHOD']
				,CURLOPT_COOKIE				=>	$aRequest['SERVER']['HTTP_COOKIE']
			);
			$aResponse = self::aCurl($sCurlURL, $aCurlOptions);
			header($aResponse['sHeader']);
			echo $aResponse['sBody'];
			exit();
		} else {
			exit('could not find request_id \'' . $sID . '\'');
		}
		
	}
	
	
	
	
	public static function aCurl ($sURL, $aOptions = array()) {
		
		$oCH = curl_init();
		
		curl_setopt($oCH, CURLOPT_REFERER, $sURL);
		curl_setopt_array($oCH, $aOptions);
		curl_setopt($oCH, CURLOPT_URL, $sURL);
		curl_setopt($oCH, CURLOPT_HEADER, 1);
		curl_setopt($oCH, CURLOPT_RETURNTRANSFER, 1);
		
		$sResponse = curl_exec($oCH);
		list($sResponseHeader, $sResponseBody) = explode("\r\n\r\n", $sResponse, 2);
		#$sResponseHeader = trim($sResponseHeader);
		
		curl_close($oCH);
		
		$aReturn = array(
			 'sHeader'	=>	$sResponseHeader
			,'sBody'	=>	$sResponseBody
		);
ODT::vExit($aReturn);
		
		return $aReturn;
		
	}
	
	
	
	
	public static function vStoreRequest () {
		
		self::aAddRequest(array(
			 'sID'		=>	'' . time() . ''
			,'SERVER'	=>	$_SERVER
			,'REQUEST'	=>	$_REQUEST
		));
		
	}
	
	
	
	
	public static function aGetRequests () {
		
		return Store::get(self::$sRequestStoreKey);
		
	}
	
	
	
	
	public static function aAddRequest ($aRequest) {
		
		$aRequests = Store::get(self::$sRequestStoreKey);
		if (!$aRequests) {
			$aRequests = array();
		}
		$aRequests []= $aRequest;
		if (count($aRequests) > self::$iMaxRequestsStored) {
			array_splice($aRequests, 0, count($aRequests) - self::$iMaxRequestsStored);
		}
		Store::set(self::$sRequestStoreKey, $aRequests);
		return $aRequests;
		
	}
	
	
	
	
}