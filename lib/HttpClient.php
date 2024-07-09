<?php

namespace PureDevLabs;

class HttpClient
{
	// Constants
	const _REQ_ENCODING = "gzip, deflate";
	const _USERAGENT = "Mozilla/5.0 (iPhone; CPU iPhone OS 15_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 244.0.0.12.112 (iPhone13,2; iOS 15_5; en_US; en-US; scale=3.00; 1170x2532; 383361019)";

	// Fields
	public array $_headers = [];
	private $_ch = NULL;
	private array $_additionalHeaders = [
		"Accept-Encoding: gzip, deflate",
		"Accept-Language: en-US,en;q=0.5",
		'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
		'Accept' => '*/*',
		'User-Agent' => self::_USERAGENT
	];

	#region Public Methods
	public function Get($url, $headers = '', $cookieFile = ''): string
	{
        $headers = (empty($headers)) ? $this->_additionalHeaders : $headers;
		$this->CurlInit($url, $headers);
		$ch = $this->_ch;
		if(!empty($cookieFile))
		{
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
		}
		$output = curl_exec($ch);
		if (curl_errno($ch) != 0)
		{
			echo "\nCURL error: " . curl_error($ch) . "\n";
			return "";
		}
		curl_close($ch);
		return $output;
	}
	#endregion

	#region Private Methods
	private function CurlInit($url, $headers): void
	{
		$this->_ch = $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_ENCODING, self::_REQ_ENCODING);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_USERAGENT, self::_USERAGENT);
		curl_setopt($ch, CURLOPT_REFERER, 'https://www.instagram.com');
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	}
	#endregion
}
