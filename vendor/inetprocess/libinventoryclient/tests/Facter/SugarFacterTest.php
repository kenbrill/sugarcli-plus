<?php

namespace Inet\Inventory\Tests\Facter;

use Psr\Log\NullLogger;
use Inet\SugarCRM\Application;
use Inet\SugarCRM\Database\SugarPDO;

use Inet\Inventory\Facter\ArrayFacter;
use Inet\Inventory\Facter\SugarFacter;
use Inet\Inventory\Facter\SugarProvider\Version;
use Inet\Inventory\Tests\MockPDO;

class SugarFacterTest extends \PHPUnit_Framework_TestCase
{
    public function testVersionProvider()
    {
        $provider = new Version(new Application(new NullLogger(), __DIR__ . '/../fake_sugar'), new MockPDO());
        $facts = $provider->getFacts();
        $this->assertEquals(array(
            'version' => '7.5.0.1',
            'db_version' => '7.5.0.1',
            'flavor' => 'PRO',
            'build' => '1006',
            'build_timestamp' => '2014-12-12 09:59am',
        ), $facts);
    }

    public function testInstanceIdProvider()
    {
        $provider = new \Inet\Inventory\Facter\SugarProvider\InstanceId(
            new Application(new NullLogger(), __DIR__ . '/../fake_sugar'),
            new MockPDO()
        );
        $facts = $provider->getFacts();
        $this->assertArrayHasKey('instance_id', $facts);
        $this->assertRegExp('/\w+@' . gethostname() . '/', $facts['instance_id']);
    }

    public function testConfigProvider()
    {
        $provider = new \Inet\Inventory\Facter\SugarProvider\Config(
            new Application(new NullLogger(), __DIR__ . '/../fake_sugar'),
            new MockPDO()
        );
        $facts = $provider->getFacts();
        $this->assertEquals(array(
            'url' => 'XXXXXXXXXXXXXXX',
            'unique_key' => '9b4af07fd8b49289db29eacb326d8766',
            'log_level' => 'fatal',
        ), $facts);
    }

    public function testGitProvider()
    {
        $provider = new \Inet\Inventory\Facter\SugarProvider\Git(
            new Application(new NullLogger(), __DIR__),
            new MockPDO()
        );
        $facts = $provider->getFacts();
        $this->assertArrayHasKey('git', $facts);
        $this->assertArrayHasKey('tag', $facts['git']);
        $this->assertArrayHasKey('branch', $facts['git']);
        $this->assertArrayHasKey('origin', $facts['git']);
        $this->assertArrayHasKey('modified_files', $facts['git']);
        $this->assertInternalType('integer', $facts['git']['modified_files']);
    }

    public function testGitProviderFailures()
    {
        $provider = new \Inet\Inventory\Facter\SugarProvider\Git(
            new Application(new NullLogger(), __DIR__ .'/../../../..'),
            new MockPDO()
        );
        $this->assertNull($provider->getModifiedFiles());
        $reflex = new \ReflectionClass($provider);
        $method = $reflex->getMethod('execOrNull');
        $method->setAccessible(true);
        $this->assertNull($method->invoke($provider, 'git status'));
        $this->assertEquals(array(), $provider->getFacts());
    }

    public function cronDataProvider()
    {
        return array(
            //Valid
            array(true, '* * * * * cd test_path; php cron.php'),
            array(true, '*   *   *  * * cd test_path; php cron.php'),
            array(true, '* * * * * php test_path/cron.php'),
            array(true, '* * * * * randcron; cd test_path && php -f cron.php > /dev/null 2>&1'),
            array(true, "test\n* * * * * php test_path/cron.php"),
            // Invalid
            array(false, '* * * 4 * php test_path/cron.php'),
            array(false, '5 * * * * cd test_path; php cron.php'),
            array(false, '* * * * * cd test_; php cron.php'),
        );
    }

    /**
     * @dataProvider cronDataProvider
     */
    public function testCronProvider($expected, $crontabs)
    {
        $stub = $this->getMock(
            'Inet\Inventory\Facter\SugarProvider\Cron',
            array('exec'),
            array(
                new Application(new NullLogger(), 'test_path'),
                new MockPDO()
            )
        );
        $stub->method('exec')
            ->willReturn($crontabs);
        $reflex = new \ReflectionClass($stub);
        $method = $reflex->getMethod('isCronInstalled');
        $method->setAccessible(true);
        $this->assertEquals($expected, $method->invoke($stub));
    }

    public function testMultiFacter()
    {
        $multi = new \Inet\Inventory\Facter\MultiFacterFacter(
            array(new ArrayFacter(array(
                'foo' => 'bar',
                'baz' => 'baz'
            )))
        );
        $multi->addFacter(new ArrayFacter(array('baz' => 'test')));
        $this->assertEquals(array(
            'foo' => 'bar',
            'baz' => 'test',
        ), $multi->getFacts());

    }

    public function testSpaceUsageFail()
    {
        $stub = $this->getMock(
            'Inet\Inventory\Facter\SugarProvider\SpaceUsage',
            array('exec'),
            array(
                new Application(new NullLogger(), 'test_path'),
                new MockPDO()
            )
        );
        $stub->method('realExec')
            ->willReturn(null);
        $reflex = new \ReflectionClass($stub);
        $method = $reflex->getMethod('getDiskSpaceUsage');
        $method->setAccessible(true);
        $this->assertEquals(null, $method->invoke($stub));
    }

    /**
     * @group sugarcrm-path
     */
    public function testUserInfo()
    {
        $app = new Application(new NullLogger(), getenv('SUGARCLI_SUGAR_PATH'));
        $facter = new \Inet\Inventory\Facter\SugarProvider\UsersInfo($app, new SugarPDO($app));
        $facts = $facter->getFacts();
        $this->assertArrayHasKey('active', $facts['users']);
        $this->assertGreaterThan(0, $facts['users']['active']);
        $this->assertArrayHasKey('admin', $facts['users']);
        $this->assertGreaterThan(0, $facts['users']['admin']);
        $this->assertArrayHasKey('last_session', $facts['users']);
    }

    /**
     * @group sugarcrm-path
     */
    public function testLicense()
    {
        $app = new Application(new NullLogger(), getenv('SUGARCLI_SUGAR_PATH'));
        $facter = new \Inet\Inventory\Facter\SugarProvider\License($app, new SugarPDO($app));
        $facts = $facter->getFacts();
        $this->assertArrayHasKey('license', $facts);
        $this->assertArrayHasKey('expire', $facts['license']);
        $this->assertArrayHasKey('last_validation', $facts['license']);
        $this->assertArrayHasKey('last_validation_success', $facts['license']);
        $this->assertArrayHasKey('users', $facts['license']);
        $this->assertInternalType('integer', $facts['license']['users']);
        $this->assertArrayHasKey('validation_key_expire', $facts['license']);
    }

    /**
     * @group sugarcrm-path
     */
    public function testSugarFacter()
    {
        $app = new Application(new NullLogger(), getenv('SUGARCLI_SUGAR_PATH'));
        $facter = new SugarFacter($app, new SugarPDO($app));
        $facts = $facter->getFacts();
        $this->assertArrayHasKey('instance_id', $facts);
        $this->assertArrayHasKey('version', $facts);
    }
}
