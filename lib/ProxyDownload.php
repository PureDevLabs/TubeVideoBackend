<?php

namespace PureDevLabs;

class ProxyDownload
{
    const _REQUEST_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36';

    public static function chunkedDownload($remoteURL, $outputInline=false)
	{
        $fsize = self::CheckDownloadUrl($remoteURL);
		$vidHost = 'googlevideo.com';
		if (preg_match('/(' . preg_quote($vidHost, '/') . ')$/', (string)parse_url($remoteURL, PHP_URL_HOST)) == 1)
	    {
			self::sendHeaders($outputInline, 'file.mkv', $fsize);

			// Activate flush
			if (function_exists('apache_setenv'))
			{
				apache_setenv('no-gzip', 1);
			}
			@ini_set('zlib.output_compression', false);
			ini_set('implicit_flush', true);
			ob_implicit_flush(true);

			// CURL Process
			$tryAgain = false;
			$ch = curl_init();
			$chunkEnd = $chunkSize = 1000000;  // 1 MB in bytes
			$tries = $count = $chunkStart = 0;
			while ($fsize >= $chunkStart)
			{
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_URL, $remoteURL);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_USERAGENT, self::_REQUEST_USER_AGENT);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_RANGE, $chunkStart.'-'.$chunkEnd);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_BUFFERSIZE, $chunkSize);

				curl_setopt($ch, CURLOPT_IPRESOLVE, constant("CURL_IPRESOLVE_V" . (string)env('APP_USE_IP_VERSION', 4)));
				$output = curl_exec($ch);
				$curlInfo = curl_getinfo($ch);
				if ($curlInfo['http_code'] != "206" && $tries < 10)
				{
					$tries++;
					continue;
				}
				else
				{
					$tries = 0;
					echo $output;
					flush();
					if (ob_get_length() > 0) ob_end_flush();
				}

				$chunkStart += $chunkSize;
				$chunkStart += ($count == 0) ? 1 : 0;
				$chunkEnd += $chunkSize;
				$count++;
			}
			curl_close($ch);
    	}
	}

	public static function CheckDownloadUrl($url)
    {
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::_REQUEST_USER_AGENT);
        curl_setopt($ch, CURLOPT_IPRESOLVE, constant("CURL_IPRESOLVE_V" . (string)env('APP_USE_IP_VERSION', 4)));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $headers = curl_exec($ch);
		if (curl_errno($ch) == 0)
		{
			$info = curl_getinfo($ch);
			//die(print_r($info));
			$filesize = (int)$info['download_content_length'];
		}
		curl_close($ch);
		return $filesize;
    }

    private static function sendHeaders($outputInline, $vidName, $fsize)
	{
		// Send some headers
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		if (!$outputInline)
		{
			header('Content-Disposition: attachment; filename="' . str_replace('"', '', htmlspecialchars_decode($vidName, ENT_QUOTES)) . '"');
		}
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		if ($fsize > 0)
		{
			header('Content-Length: ' . $fsize);
		}
		header('Connection: Close');
		flush();
	}
}
