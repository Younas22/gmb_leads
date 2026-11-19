<?php

namespace App\Services\Web;

/**
 * Decides whether an IP address is a public internet address we may connect to.
 * Blocks private, loopback, link-local (incl. cloud metadata 169.254.169.254),
 * CGNAT, documentation, multicast, reserved and IPv6 internal/translation ranges.
 */
class IpGuard
{
    private const BLOCKED_CIDRS = [
        // IPv4
        '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16',
        '172.16.0.0/12', '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '192.168.0.0/16',
        '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4',
        '255.255.255.255/32',
        // IPv6
        '::/128', '::1/128', '::ffff:0:0/96', '64:ff9b::/96', '64:ff9b:1::/48', '100::/64',
        '2001::/23', '2001:db8::/32', '2002::/16', 'fc00::/7', 'fe80::/10', 'fec0::/10', 'ff00::/8',
    ];

    public static function isPublic(string $ip): bool
    {
        $ip = trim($ip, '[]');

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // IPv4-mapped IPv6 (::ffff:10.0.0.1) is judged by its IPv4 part
        if (preg_match('/^::ffff:(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $m)) {
            $ip = $m[1];
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        foreach (self::BLOCKED_CIDRS as $cidr) {
            if (self::inCidr($ip, $cidr)) {
                return false;
            }
        }

        return true;
    }

    public static function inCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $remainder)) & 0xFF);

        return (($ipBin[$bytes] & $mask) === ($subnetBin[$bytes] & $mask));
    }
}
