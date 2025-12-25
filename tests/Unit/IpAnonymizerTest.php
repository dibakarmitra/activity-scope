<?php

namespace Dibakar\ActivityScope\Tests\Unit;

use Dibakar\ActivityScope\Support\IpAnonymizer;
use Dibakar\ActivityScope\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class IpAnonymizerTest extends TestCase
{
    /** @test */
    public function it_returns_null_for_invalid_ips()
    {
        $anonymizer = new IpAnonymizer();
        $this->assertNull($anonymizer->anonymize('not-an-ip'));
        $this->assertNull($anonymizer->anonymize(null));
    }

    /** @test */
    public function it_anonymizes_ipv4_with_default_mask()
    {
        Config::set('activityscope.ipv4_mask', 24);

        $anonymizer = new IpAnonymizer();
        $original = '192.168.1.150';
        $expected = '192.168.1.0';

        $this->assertEquals($expected, $anonymizer->anonymize($original));
    }

    /** @test */
    public function it_anonymizes_ipv4_with_custom_mask()
    {
        Config::set('activityscope.ipv4_mask', 16);

        $anonymizer = new IpAnonymizer();
        $original = '192.168.1.150';
        $expected = '192.168.0.0';

        $this->assertEquals($expected, $anonymizer->anonymize($original));
    }

    /** @test */
    public function it_anonymizes_ipv6_with_default_mask()
    {
        Config::set('activityscope.ipv6_mask', 48);

        $anonymizer = new IpAnonymizer();
        // 2001:0db8:85a3:0000:0000:8a2e:0370:7334
        $original = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        // Prefix /48: 2001:0db8:85a3::
        $expected = '2001:db8:85a3::';

        $this->assertEquals($expected, $anonymizer->anonymize($original));
    }

    /** @test */
    public function it_returns_original_ip_if_mask_too_large()
    {
        Config::set('activityscope.ipv4_mask', 32);

        $anonymizer = new IpAnonymizer();
        $original = '192.168.1.1';

        $this->assertEquals($original, $anonymizer->anonymize($original));
    }
}
