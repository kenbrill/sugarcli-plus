<?php
namespace Inet\SugarCRM\Tests;

use Psr\Log\NullLogger;
use Inet\SugarCRM\Application;

class ApplicationTest extends SugarTestCase
{
    public function testSugarPath()
    {
        $sugarDir = __DIR__ . '/fake_sugar';
        $app = new Application(new NullLogger(), $sugarDir);
        $this->assertInstanceOf('Inet\SugarCRM\Application', $app);
        $this->assertEquals(realpath($sugarDir), $app->getPath());
        $this->assertTrue($app->isValid());
        $this->assertTrue($app->isInstalled());

        $app = new Application(new NullLogger(), __DIR__ . '/invalid_sugar');
        $this->assertFalse($app->isInstalled());
    }

    /**
     * @expectedException \Inet\SugarCrm\Exception\SugarException
     */
    public function testFailSugarPath()
    {
        $sugar = new Application(new NullLogger(), __DIR__);
        $sugar->getSugarConfig();
    }

    public function testSugarConfig()
    {
        $sugar = new Application(new NullLogger(), __DIR__ . '/fake_sugar');
        $actual_config = $sugar->getSugarConfig(true);
        require(__DIR__ . '/fake_sugar/config.php');
        require(__DIR__ . '/fake_sugar/config_override.php');
        $sugar_config;
        $this->assertEquals($sugar_config, $actual_config);
        $conf = $sugar->getSugarConfig();
        $this->assertEquals('localhost', $conf['dbconfig']['db_host_name']);
        $this->assertEquals('debug', $conf['logger']['level']);
    }

    /**
     * @expectedException \Inet\SugarCrm\Exception\SugarException
     */
    public function testInvalidSugarConfig()
    {
        $sugar = new Application(new NullLogger(), __DIR__ . '/invalid_sugar');
        $sugar->getSugarConfig();
    }

    public function testGetVersion()
    {
        $sugar = new Application(new NullLogger(), __DIR__ . '/fake_sugar');
        $expected = array(
            'version' => '7.5.0.1',
            'db_version' => '7.5.0.1',
            'flavor' => 'PRO',
            'build' => '1006',
            'build_timestamp' => '2014-12-12 09:59am',
        );
        $this->assertEquals($expected, $sugar->getVersion());
    }

    /**
     * @expectedException \Inet\SugarCrm\Exception\SugarException
     */
    public function testInvalidVersion()
    {
        $sugar = new Application(new NullLogger(), __DIR__);
        $sugar->getVersion();
    }
}
