<?php


namespace TierTest\Integration;

use Tier\InjectionParams;
use Tier\TierHTTPApp;
use Room11\HTTP\Request\CLIRequest;
use Tier\TierApp;
use TierTest\BaseTestCase;
use TierTest\BuiltInServer;

/**
 * @requires extension pcntl
 * @requires extension posix
 */
class ServerTest extends BaseTestCase
{
    /** @var  InjectionParams */
    private $injectionParams;
    
    /** @var  BuiltInServer */
    private static $server = null;
    
    public function setup()
    {
        parent::setup();
    }

    public static function setUpBeforeClass()
    {
        // TODO - test this is set appropriatel.
        // opcache.save_comments
        
        $path = realpath(__DIR__."/../../../test/app/public");

        // PHPUnit calls this function even if the tests aren't
        // going to be run.
        if (extension_loaded('pcntl') === false) {
            //echo "pcntl extension is not loaded\n";
            return;
        }
        
        if (extension_loaded('posix') === false) {
            //echo "posix extension is not loaded\n";
            return;
        }

        self::$server = new BuiltInServer(8000, $path);
        self::$server->startServer(8000);
    }
    
    public static function tearDownAfterClass()
    {
        if (self::$server === null) {
            return;
        }

        self::$server->removeLockFile();
        self::$server->waitForChildToClose();
    }
    
    public function getURL($url)
    {
        $statusCode = 0;
        $contents = '';
        
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, "localhost:8000".$url);
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // $output contains the output string
        $output = curl_exec($ch);
        if ($output !== false) {
            $contents = $output;
        }
        
        $info = curl_getinfo($ch);
        if (array_key_exists('http_code', $info) === true) {
            $statusCode = $info['http_code'];
        }

        // close curl resource to free up system resources
        curl_close($ch);
        
        return [$statusCode, $contents];
    }
    
    public static function serverTests()
    {
        return array(
            array('/', 'Hello world', 200),
            array('/throwException', 'Exception: \'Testing exception handler\'', 500),
            array('/unknownDependency', 'Injection definition required for interface Fixtures\UnknownInterface', 500),
            array('/instantiateUnknownClass', 'Class \'TierTest\Controller\ThisClassDoesNotExist\' not found', 500),
        );
    }

    /**
    * @dataProvider serverTests
    */
    public function testBuiltinServer($path, $expectedText, $expectedStatus)
    {
        list($status, $contents) = $this->getURL($path);

        $this->assertEquals($expectedStatus, $status);
        $this->assertContains($expectedText, $contents);
    }
}
