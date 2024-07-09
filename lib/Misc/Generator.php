<?php

namespace PureDevLabs\Misc;

class Generator extends BaseHandler
{
	public function run()
	{
		$output = $this->getNetworkInfo();
		if (!empty($output['networkName']) && !empty($output['currentIp']))
		{
			$output['prefix'] = $this->getIPv6Prefix($output['currentIp']);
			if (!empty($output['prefix']))
			{
				$output['newIp'] = $this->generateRandomIPv6($output['prefix']);
				if (!empty($output['newIp']))
				{
					$configNewIP = 'ip -6 addr add ' . $output['newIp'] . '/64 dev ' . $output['networkName'];
					echo "\n\nRunning Command: " . $configNewIP . "\n";

					$result = [];
					exec($configNewIP, $result);
					print_r($result);

					$delOldIP = 'ip -6 addr del ' . $output['currentIp'] . '/64 dev ' . $output['networkName'];
					echo "\n\nRunning Command: " . $delOldIP . "\n";

					$result = [];
					exec($delOldIP, $result);
					print_r($result);
				}
			}
		}
		echo "\n\nOutput:\n";
		print_r($output);
	}
}
