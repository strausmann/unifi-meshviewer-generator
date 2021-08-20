<?php

namespace ISPServerfarm\UnifiMeshviewer\Test;

use ISPServerfarm\UnifiMeshviewer\MeshviewerGenerator;
use PHPUnit\Framework\TestCase;

class MeshviewerGeneratorTest extends TestCase
{
    private $meshGenerator;

    protected $unifi_url = '';
    protected $unifi_user = '';
    protected $unifi_pass = '';
    protected $unifi_site = '';
    protected $client = null;
    protected $client_debug = null;
    protected $client_debug_mode = null;
    protected $client_loginresult = null;
    protected $alldevices = null;
    protected $allaccesspoints = [];

    private $nodelist = [];
    private $nodes_array = [];
    private $meshview = null;
    private $model = null;
    private $link = [];
    private $links = [];
    private $radio_stats = null;
    private $radio_stat0 = null;
    private $radio_stat1 = null;
    private $ap_metadata = null;
    private $firmware_base = 'Ubiquiti Networks';
    private $writeStatus = [];
    private $gateway_next_hop = null;
    private $gateway = null;

    /**
     * @use \ISPServerfarm\UnifiMeshviewer\MeshviewerGenerator
     */
    public function setup()
    {
        $this->meshGenerator = new MeshviewerGenerator();
        $this->unifi_pass = $_ENV['UNIFI_PASS'];
    }

    /**
     * @covers \ISPServerfarm\UnifiMeshviewer\MeshviewerGenerator::ping
     */
    public function testPing()
    {
        $this->assertSame('ping:pong', $this->meshGenerator->ping());
    }
}
