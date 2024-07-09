<?php

namespace PureDevLabs\Misc;

abstract class BaseHandler
{
	const _NETWORK_INTERFACE_PATTERN = '/^\d+: ([^:]+):/';
	const _NETWORK_IPV6_PATTERN = '/inet6 ([a-fA-F0-9:]+)\/(\d+) scope global/';
	const _VALID_IPV6_64_PATTERN = '/^[a-fA-F0-9:]+\/64$/';
	const _VALID_IPV6_PATTERN = '/^([a-fA-F0-9:]+)$/';

	protected function getNetworkInfo()
	{
		$networkName = '';
		$globalIPv6 = '';
		$netmask = '';
		$output = [];
		exec('ip addr', $output);
		
		if (!empty($output))
		{
			foreach ($output as $line)
			{
				if (preg_match(self::_NETWORK_INTERFACE_PATTERN, $line, $matches) == 1)
				{
					$networkName = $matches[1];
				}
				if (preg_match(self::_NETWORK_IPV6_PATTERN, $line, $ipv6Matches) == 1)
				{
					$globalIPv6 = $ipv6Matches[1];
					$netmask = $ipv6Matches[2];
				}
				if (!empty($networkName) && !empty($globalIPv6) && !empty($netmask)) break;
			}
		}
		return [
			'networkName' => $networkName,
			'currentIp' => $globalIPv6,
			'netmask' => $netmask
		];
	}

	protected function generateRandomIPv6($prefix)
	{
		$randomIPv6 = '';
		// Ensure the prefix is a valid /64 block
		if (preg_match(self::_VALID_IPV6_64_PATTERN, $prefix) == 1)
		{
			// Extract the network prefix (without CIDR)
			$networkPrefix = explode('/', $prefix)[0];
			// Generate four random 16-bit hexadecimal blocks
			$randomSuffix = [];
			for ($i = 0; $i < 4; $i++)
			{
				$randomBlock = bin2hex(random_bytes(2)); // Create a 16-bit block
				$randomSuffix[] = $randomBlock; // Add each block to the suffix
			}
			// Join the prefix and suffix to construct the full IPv6 address
			$randomIPv6 = rtrim($networkPrefix, ':') . ':' . implode(':', $randomSuffix); // Ensure no extra colons
		}
		return $randomIPv6; // Return the generated IPv6 address
	}

	protected function getIPv6Prefix($ipv6Address)
	{
		$ipv6Prefix = '';
		// Ensure the IPv6 address is valid
		if (preg_match(self::_VALID_IPV6_PATTERN, $ipv6Address) == 1)
		{
			$segments = explode(':', $ipv6Address);
			// Check if there are at least four segments to create a /64 prefix
			if (count($segments) >= 4)
			{
				// Extract the first four blocks for the /64 prefix
				$prefixSegments = array_slice($segments, 0, 4); // Take the first 4 segments
				$ipv6Prefix = implode(':', $prefixSegments) . '::/64'; // Reconstruct the /64 prefix
			}
		}
		return $ipv6Prefix; // Return the /64 prefix
	}
}
