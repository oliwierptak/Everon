<?php
namespace Everon\Test;

class ConfigManagerTest extends \Everon\TestCase
{
    protected function setUp()
    {
        if (is_dir($this->getTempDirectory()) === false) {
            @mkdir($this->getTempDirectory(), 0775, true);
        }
    }
    
    public function testConstructor()
    {
        $Matcher = new \Everon\Config\ExpressionMatcher();
        $Manager = new \Everon\Config\Manager($Matcher, $this->getConfigDirectory(), $this->getTempDirectory().'configmanager'.ev_DS);
        $this->assertInstanceOf('\Everon\Interfaces\ConfigManager', $Manager);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testRegister(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $ConfigManager->register($Expected);

        $ConfigTwo = new \Everon\Config('test', 'test.ini', ['test_two'=>true]);
        $ConfigTwo->setName('test_two');
        $ConfigTwo->setFilename('test_two.ini');
        $ConfigManager->register($ConfigTwo);

        $this->assertCount(2, $ConfigManager->getConfigs());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testUnRegister(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $ConfigManager->register($Expected);
        $ConfigManager->unRegister($Expected->getName());

        $this->assertCount(0, $ConfigManager->getConfigs());
    }

    /**
     * @dataProvider dataProvider
     * @expectedException \Everon\Exception\Config
     * @expectedExceptionMessage Config with name: "test" already registered
     */
    public function testRegisterShouldThrowExceptionWhenConfigAlreadyExists(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $ConfigManager->register($Expected);
        $ConfigManager->register($Expected);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testLoadAndRegisterConfigs(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $Config = $ConfigManager->getRouterConfig();
        $this->assertInstanceOf('\Everon\Interfaces\RouterConfig', $Config);

        $Config = $ConfigManager->getApplicationConfig();
        $this->assertInstanceOf('\Everon\Interfaces\Config', $Config);
        
        $Config = $ConfigManager->getConfigByName('test');
        $this->assertInstanceOf('\Everon\Interfaces\Config', $Config);
        $this->assertEquals($Expected->toArray(), $Config->toArray());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSettersAndGetters(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $Config = $ConfigManager->getApplicationConfig();
        $this->assertInstanceOf('Everon\Config', $Config);
        
        $Config = $ConfigManager->getRouterConfig();
        $this->assertInstanceOf('Everon\Config\Router', $Config);
        
        $Config = $ConfigManager->getConfigByName('test');
        $this->assertInstanceOf('Everon\Config', $Config);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testCachingShouldCreateCacheDirectoriesWhenNotExist(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $ConfigManager->enableCache();
        $Config = $ConfigManager->getConfigByName('test');

        $ConfigManager->disableCache();
        $Config = $ConfigManager->getConfigByName('test');        

        $dir = $this->getTempDirectory().'configmanager';
        $this->removeDirectoryRecursive($dir);

        $ConfigManager->unRegister('test');
        $ConfigManager->register($Expected);
        $Config = $ConfigManager->getConfigByName('test');
        $this->assertInstanceOf('Everon\Config', $Config);
    }
  
    /**
     * @dataProvider dataProvider
     */
    public function testCachingEnabled(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $dir = $this->getTempDirectory().'configmanager';
        $this->removeDirectoryRecursive($dir);
        
        $ConfigManager->enableCache();
        
        $method = $this->getProtectedMethod('Everon\Config\Manager', 'saveConfigToCache');
        $method->invoke($ConfigManager, $Expected);
        
        $Config = $ConfigManager->getConfigByName('test');
        $ConfigManager->unRegister('test');
        $ConfigManager->register($Config);
        
        $this->assertEquals($Expected->toArray(), $Config->toArray());
    }
    
    /**
     * @dataProvider dataProvider
     */
    public function testCachingDisabled(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $ConfigManager->disableCache();
        $Config = $ConfigManager->getConfigByName('test');
       
        $ConfigManager->unRegister('test');
        $ConfigManager->register($Config);
        
        $this->assertEquals($Expected->toArray(), $Config->toArray());
    }

    /**
     * @dataProvider dataProvider
     * @expectedException \Everon\Exception\Config
     * @expectedExceptionMessage Invalid config name: wrong
     */
    public function testGetConfigByNameShouldThrowExceptionWhenConfigFileNotFound(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $ConfigManager->enableCache();
        $Config = $ConfigManager->getConfigByName('wrong');
    }
    
    /**
     * @dataProvider dataProvider
     * @expectedException \Everon\Exception\Config
     * @expectedExceptionMessage Unable to save config cache file: "test.ini"
     */
    public function testSaveToCacheShouldThrowException(\Everon\Interfaces\ConfigManager $ConfigManager, \Everon\Interfaces\Config $Expected)
    {
        $ConfigMock = $this->getMockBuilder('Everon\Config')
            ->disableOriginalConstructor()
            ->setMethods(['getFilename'])
            ->getMock();
           
        $ConfigMock->expects($this->exactly(2))
            ->method('getFilename')
            ->will($this->throwException(
                new \Everon\Exception\Config('Unable to save config cache file: "test.ini"')
            ));
        
        $ConfigManager->enableCache();
        $method = $this->getProtectedMethod('Everon\Config\Manager', 'saveConfigToCache');
        $method->invoke($ConfigManager, $ConfigMock);
    }
    
    public function dataProvider()
    {
        $dc = new \Everon\Dependency\Container();
        $Factory = new \Everon\Factory($dc);
        $Matcher = $Factory->buildConfigExpressionMatcher();
        $ConfigManager = $Factory->buildConfigManager($Matcher, $this->getConfigDirectory(), $this->getConfigManagerTempDirectory());
        $dc->register('ConfigManager', function() use ($ConfigManager) {
            return $ConfigManager;
        });

        $Expected = new \Everon\Config(
            'test',
            $this->getConfigDirectory().'test.ini',
            ['test'=>1]
        );

        return [
            [$ConfigManager, $Expected]
        ];
    }

}
