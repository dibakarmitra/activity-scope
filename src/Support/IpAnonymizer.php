<?php

namespace Dibakar\ActivityScope\Support;

class IpAnonymizer
{
    public function anonymize(?string $ip): ?string
    {
        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->anonymizeIpv4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->anonymizeIpv6($ip);
        }

        return null;
    }

    protected function anonymizeIpv4(string $ip): string
    {
        $mask = config('activityscope.ipv4_mask', 24);

        if ($mask >= 32) {
            return $ip;
        }

        $long = ip2long($ip);
        $netmask = -1 << (32 - $mask);

        return long2ip($long & $netmask);
    }

    protected function anonymizeIpv6(string $ip): string
    {
        $mask = config('activityscope.ipv6_mask', 48);

        if ($mask >= 128) {
            return $ip;
        }

        $packed = inet_pton($ip);
        if ($packed === false) {
            return $ip;
        }

        $bytes = intdiv($mask, 8);
        $bits = $mask % 8;

        $masked = substr($packed, 0, $bytes);

        if ($bits > 0) {
            $byte = ord($packed[$bytes]);
            $byte &= (0xFF << (8 - $bits));
            $masked .= chr($byte);
            $bytes++;
        }

        $masked .= str_repeat("\0", 16 - $bytes);

        return inet_ntop($masked);
    }
}
