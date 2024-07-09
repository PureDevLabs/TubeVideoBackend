<?php

namespace PureDevLabs\Extractors;

use PureDevLabs\Core;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use PureDevLabs\Extractors\Youtube;

class YoutubeData extends Youtube
{
	public const _SEARCH_API_ROOT = "https://www.youtube.com/youtubei/v1/search";
	public const _RELATED_API_ROOT = "https://www.youtube.com/youtubei/v1/next";
	private $_recursionCount = 0;
	private $_renderers = [
		'search' => [
			'contentSrc' => [
				'videoRenderer'
			],
			'continueSrc' => [
				[
					'name' => 'continuationCommand',
					'token' => 'token'
				]
			]
		],
		'related' => [
			'contentSrc' => [
				'compactVideoRenderer'
			],
			'continueSrc' => [
				[
					'name' => 'results',
					'token' => '{key1}.continuationItemRenderer.continuationEndpoint.continuationCommand.token'
				]
			]
		]
	];

	public function GetResults($resultType, $term, $nextPageToken)
	{
		$page = (!empty($nextPageToken)) ? $nextPageToken : '1';
		if (config('app.search_cache'))
		{
			$cachedResults = Cache::get($resultType . ':' . $term . ':' . $page);
			if (isset($cachedResults))
			{
				return json_decode($cachedResults, FALSE);
			}
			else
			{
				$data = $this->ExtractResults($resultType, $term, $nextPageToken);
				(!isset($data['error'])) ? Cache::put($resultType . ':' . $term . ':' . $page, json_encode($data), config('app.search_cache_expires')) : '';
				return $data;
			}
		}
		else
		{
			return $this->ExtractResults($resultType, $term, $nextPageToken);
		}
	}

	private function ExtractResults($resultType, $term, $nextPageToken)
	{
		$data = $this->GetYouTubeData($resultType, $term, $nextPageToken);

		if (!isset($data['error']))
		{
			$items = ['nextPageToken' => '', 'results' => []];
			$iterator = new \RecursiveArrayIterator($data);
			$recursive = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);

			$renderers = $this->_renderers[$resultType]['contentSrc'];
			$continueSrcs = $this->_renderers[$resultType]['continueSrc'];
			$continueSrcNames = array_column($continueSrcs, 'name');
			foreach ($recursive as $key => $value)
			{
				if (in_array($key, $continueSrcNames, true))
				{
					$continueSrcArrNo = (int)array_search($key, $continueSrcNames, true);
					$items['nextPageToken'] = (empty($items['nextPageToken'])) ? $this->FindNextPageToken($value, $continueSrcs[$continueSrcArrNo]['token']) : $items['nextPageToken'];
					continue;
				}
				if (in_array($key, $renderers, true))
				{
					$buildResultsFunc = "Build" . ucfirst($resultType) . "Results";
					$items['results'][] = $this->$buildResultsFunc($value);
					continue;
				}
			}
			if (empty($items['results']) && !empty($items['nextPageToken']) && ++$this->_recursionCount < 20)
			{
				return $this->ExtractResults($resultType, $term, $items['nextPageToken']);
			}
			return $items;
		}
		else
		{
			return $data;
		}
	}

	private function FindNextPageToken($value, $tokenIndex)
	{
		$indexArr = explode(".", $tokenIndex);
		foreach ($indexArr as $key => $index)
		{
			if (preg_match('/^(\{key\d+\})$/', $index, $match) == 1)
			{
				for ($i = 0; $i < count($value); $i++)
				{
					if (isset($value[$i]))
					{
						$token = $this->FindNextPageToken($value[$i], trim(preg_split('/' . preg_quote($match[1], "/") . '/', $tokenIndex)[1], "."));
						if (!empty($token)) return $token;
					}
				}
				break;
			}
			elseif (isset($value[$index]))
			{
				if ($key == count($indexArr) - 1)
				{
					return $value[$index];
				}
				else
				{
					$value = $value[$index];
				}
			}
			else
			{
				break;
			}
		}
		return '';
	}

	private function BuildSearchResults($item)
	{
		$data = array(
			'videoId' => (isset($item['videoId'])) ? $item['videoId'] : '',
			'title' => (isset($item['title']['runs'][0]['text'])) ? $item['title']['runs'][0]['text'] : '',
			'thumbnail' => (isset($item['thumbnail']['thumbnails'])) ? $item['thumbnail']['thumbnails'] : '',
			'thumb_webp' => (isset($item['videoId'])) ? 'https://i.ytimg.com/vi_webp/' . $item['videoId'] . '/hqdefault.webp' : '',
			'publishedAt' => (isset($item['publishedTimeText']['simpleText'])) ? $item['publishedTimeText']['simpleText'] : '',
			'duration' => (isset($item['lengthText']['simpleText'])) ? $item['lengthText']['simpleText'] : '',
			'viewCount' => (isset($item['viewCountText']['simpleText'])) ? preg_replace('/[^0-9]/', '', $item['viewCountText']['simpleText']) : '',
			'shortViewCount' => (isset($item['shortViewCountText']['simpleText'])) ? $item['shortViewCountText']['simpleText'] : '',
			'channelName' => (isset($item['ownerText']['runs'][0]['text'])) ? $item['ownerText']['runs'][0]['text'] : '',
			'channelId' => (isset($item['ownerText']['runs'][0]['navigationEndpoint']['browseEndpoint']['browseId'])) ? $item['ownerText']['runs'][0]['navigationEndpoint']['browseEndpoint']['browseId'] : '',
		);
		return $data;
	}

	private function BuildRelatedResults($item)
	{
		$data = array(
			'videoId' => (isset($item['videoId'])) ? $item['videoId'] : '',
			'title' => (isset($item['title']['simpleText'])) ? $item['title']['simpleText'] : '',
			'thumbnail' => (isset($item['thumbnail']['thumbnails'])) ? $item['thumbnail']['thumbnails'] : '',
			'thumb_webp' => (isset($item['videoId'])) ? 'https://i.ytimg.com/vi_webp/' . $item['videoId'] . '/hqdefault.webp' : '',
			'publishedAt' => (isset($item['publishedTimeText']['simpleText'])) ? $item['publishedTimeText']['simpleText'] : '',
			'duration' => (isset($item['lengthText']['simpleText'])) ? $item['lengthText']['simpleText'] : '',
			'viewCount' => (isset($item['viewCountText']['simpleText'])) ? preg_replace('/[^0-9]/', '', $item['viewCountText']['simpleText']) : '',
			'shortViewCount' => (isset($item['shortViewCountText']['simpleText'])) ? $item['shortViewCountText']['simpleText'] : '',
			'channelName' => (isset($item['shortBylineText']['runs'][0]['text'])) ? $item['shortBylineText']['runs'][0]['text'] : '',
			'channelId' => (isset($item['shortBylineText']['runs'][0]['navigationEndpoint']['browseEndpoint']['browseId'])) ? $item['shortBylineText']['runs'][0]['navigationEndpoint']['browseEndpoint']['browseId'] : '',
		);
		return $data;
	}

	private function GetYouTubeData($resultType, $term, $nextPageToken)
	{
		if (Storage::disk('local')->exists('YouTube/software.json'))
		{
			$softwareJson = Storage::get('YouTube/software.json');
			$data = json_decode($softwareJson, true);
			if (json_last_error() == JSON_ERROR_NONE)
			{
				$reqHeaders = $this->GeneratePostRequestHeaders();
				$postData = [
					'context' => [
						'client' => [
							'clientName' => $data['reqParams']['webParams']['clientName'] ?? 'WEB',
							'clientVersion' => $data['reqParams']['webParams']['clientVersion'] ?? '2.20211025.01.00'
						]
					],
					'contentCheckOk' => true,
					'racyCheckOk' => true
				];
				switch ($resultType)
				{
					case "search":
						$postData['query'] = $term;
						break;
					case "related":
						$postData['videoId'] = $term;
						unset($reqHeaders["Cookie"], $reqHeaders["Authorization"]);
						break;
				}
				if (!empty($nextPageToken))
				{
					$postData['continuation'] = $nextPageToken;
				}
			}

			try
			{
				$response = Http::withOptions(['force_ip_resolve' => 'v' . env('APP_USE_IP_VERSION', 4)])->timeout(4)->withHeaders($reqHeaders)->post(constant("self::_" . strtoupper($resultType) . "_API_ROOT"), $postData);
				if ($response->status() == 200)
				{
					$json = json_decode($response->body(), true);
					if (json_last_error() == JSON_ERROR_NONE)
					{
						return $json;
					}
				}
				else
				{
					$json = json_decode($response->body(), true);
					if (json_last_error() == JSON_ERROR_NONE)
					{
						if (strpos($json['error']['message'], 'API key not valid. Please pass a valid API key.') !== false)
						{
							return $this->UpdateSoftware();
						}
						else
						{
							return array(
								'error' => true,
								'httpCode' => $response->status(),
								'errorMessage' => $json['error']['message'],
								'errorCode' => $json['error']['code']
							);
						}
					}
				}
			}
			catch (\Throwable $th)
			{
				// dd($th->getMessage());
				return array(
					'error' => true,
					'httpCode' => 503,
					"errorMsg" => "Connection Error",
					"message" => "Can't connect to YouTube."
				);
			}
		}
		else
		{
			return $this->UpdateSoftware();
		}
	}
}
