<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\Application;
use Inet\SugarCRM\EntryPoint;
use Psr\Log\NullLogger;

/**
 * @group sugarcrm
 */
class EntryPointTest extends SugarTestCase
{
    /** Define a wrong folder: exception thrown
     * @expectedException \Inet\SugarCRM\Exception\SugarException
     * @expectedExceptionMessageRegExp #Unable to find an installed instance of SugarCRM in :/foo#
     */
    public function testWrongInstanciationBadFolder()
    {
        EntryPoint::createInstance(new Application(new NullLogger(), '/foo'), '1');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp #You must first create the singleton instance with createInstance().#
     */
    public function testGetInstanceFailure()
    {
        EntryPoint::getInstance();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp #Unable to create another SugarCRM\\EntryPoint from another path.#
     */
    public function testCreateInstanceFailure()
    {
        $this->getEntryPointInstance();
        EntryPoint::createInstance(new Application(new NullLogger(), '/foo'), '1');
    }

    public function testCreateTwice()
    {
        $first = $this->getEntryPointInstance();
        $second = EntryPoint::createInstance(new Application(new NullLogger(), $first->getPath()), '1');
        $this->assertEquals($first, $second);
    }

    public function testGettersSetters()
    {
        $entryPoint = $this->getEntryPointInstance();
        $logger = $entryPoint->getLogger();
        $this->assertInstanceOf('PSR\Log\LoggerInterface', $logger);


        $expectedSugarDir = getenv('SUGARCRM_PATH');
        if ($expectedSugarDir[0] != '/') {
            $lastCwd = $entryPoint->getLastCwd();
            $expectedSugarDir = realpath($lastCwd . '/' . $expectedSugarDir);
        }
        $sugarDir = $entryPoint->getPath();
        $this->assertEquals($expectedSugarDir, $sugarDir);

        $sugarDB = $entryPoint->getSugarDb();
        $this->assertInstanceOf('\MysqliManager', $sugarDB);

        $this->assertInstanceOf('\Inet\SugarCRM\Application', $entryPoint->getApplication());

        $currentUser = $entryPoint->getCurrentUser();
        $this->assertInstanceOf('\User', $currentUser);

        $beansList = $entryPoint->getBeansList();
        $this->assertInternalType('array', $beansList);
        $this->assertArrayHasKey('Users', $beansList);
    }

    /** Define a wrong user: exception thrown
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Wrong User ID: foo/
     */
    public function testSetBadUser()
    {
        $entryPoint = $this->getEntryPointInstance();
        $entryPoint->setCurrentUser('foo');
    }

    public function testGetInstance()
    {
        chdir(__DIR__);
        $entryPoint = $this->getEntryPointInstance();
        $this->assertEquals($entryPoint->getPath(), getcwd());
        $this->assertEquals(__DIR__, $entryPoint->getLastCwd());
    }
}
