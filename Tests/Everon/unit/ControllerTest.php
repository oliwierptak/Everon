<?php
namespace Everon\Test;

class ControllerTest extends \Everon\TestCase
{

    /**
     * @dataProvider dataProvider
     * @expectedException \Everon\Exception\Factory
     * @expectedExceptionMessage Model: "\Everon\Test\wrong_model_name" initialization error. 
     * File for class: "Everon\Test\wrong_model_name" could not be found
     */
    public function testGetModelShouldThrowAnExceptionWhenInvalidModelName(\Everon\Interfaces\Controller $Controller)
    {
        $Controller->getModel('wrong_model_name');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetModelShouldReturnValidModel(\Everon\Interfaces\Controller $Controller)
    {
        $Model = $Controller->getModel('MyModel');
        $this->assertNotNull($Model);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGettersAndSetters(\Everon\Interfaces\Controller $Controller)
    {
        $this->assertEquals('Everon\Test\MyController', $Controller->getName());
        
        $Controller->setName('test');
        $this->assertEquals('test', $Controller->getName());

        $Controller->setOutput('test');
        $this->assertEquals('test', $Controller->getOutput());

        $this->assertInstanceOf('\Everon\Interfaces\Response', $Controller->getResponse());
        $this->assertInstanceOf('\Everon\Interfaces\Request', $Controller->getRequest());
        $this->assertInstanceOf('\Everon\Interfaces\Router', $Controller->getRouter());
        $this->assertInstanceOf('\Everon\Interfaces\ConfigManager', $Controller->getConfigManager());
        $this->assertInstanceOf('\Everon\Interfaces\Factory', $Controller->getFactory());
    }
    
    /**
     * @dataProvider dataProvider
     */
    public function testGetOutputShouldRunInitViewWhenViewWasNotSet(\Everon\Interfaces\Controller $Controller)
    {
        $Controller->setName('test');
        $this->assertEquals('test', $Controller->getName());
        
        $this->assertInstanceOf('\Everon\Interfaces\View', $Controller->getView());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testShouldReturnOutputWhenCastedToString(\Everon\Interfaces\Controller $Controller)
    {
        $Controller->setOutput('test');
        $this->assertEquals('test', (string) $Controller);
    }
    
    /**
     * @dataProvider dataProvider
     */
    public function testGetAllModelsShouldReturnArrayWithModels(\Everon\Interfaces\Controller $Controller)
    {
        $models = $Controller->getAllModels();
        $this->assertCount(1, $models);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetAllModelsShouldInitModels(\Everon\Interfaces\Controller $Controller)
    {
        $PropertyModels = $this->getProtectedProperty('\Everon\Controller', 'models');
        $PropertyModels->setValue($Controller, null);
        $models = $Controller->getAllModels();
        $this->assertCount(1, $models);
    }

    public function dataProvider()
    {
        $Logger = new \Everon\Logger($this->getLogDirectory());

        $DependencyContainer = new \Everon\Dependency\Container();
        $DependencyContainer->register('Logger', function() use ($Logger) {
            return $Logger;
        });

        $MyFactory = new MyFactory($DependencyContainer);

        $server = $this->getServerDataForRequest([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'QUERY_STRING' => '',
        ]);
        $Request = new \Everon\Request($server, [], [], []);
        $DependencyContainer->register('Request', function() use ($Request) {
            return $Request;
        });

        $Matcher = $MyFactory->buildConfigExpressionMatcher();
        $ConfigManager = $MyFactory->buildConfigManager($Matcher, $this->getConfigDirectory(), $this->getTempDirectory().'configmanager'.ev_DS);
        $DependencyContainer->register('ConfigManager', function() use ($ConfigManager) {
            return $ConfigManager;
        });

        $RouterConfig = $ConfigManager->getRouterConfig();
        $Router = $MyFactory->buildRouter($Request, $RouterConfig);
        $DependencyContainer->register('Router', function() use ($Router) {
            return $Router;
        });

        $DependencyContainer->register('Response', function() {
            return new \Everon\Response();
        });

        $View = $MyFactory->buildView('MyController', ['Curly']);
        $Controller = $MyFactory->buildController('MyController', $View);
        
        return [
            [$Controller]
        ];
    }

}