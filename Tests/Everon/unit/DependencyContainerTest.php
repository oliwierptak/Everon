<?php
namespace Everon\Test;

class DependencyContainerTest extends \Everon\TestCase
{

    /**
     * @dataProvider dataProvider
     * @expectedException \Everon\Exception\DependencyContainer
     * @expectedExceptionMessage Container does not contain: "wrong name"
     */    
    public function testResolveShouldThrowExceptionWhenWrongName(\Everon\Interfaces\DependencyContainer $Container)
    {
        $Container->resolve('wrong name');
    }

    /**
     * @dataProvider dataProvider
     */    
    public function testRegisterAndResolve(\Everon\Interfaces\DependencyContainer $Container)
    {
        $Container->register('test', function() {
            return new \stdClass();
        });
        
        $Test = $Container->resolve('test');
        $this->assertInstanceOf('stdClass', $Test);
    }
    
    /**
     * @dataProvider dataProvider
     */    
    public function testResolveShouldUseServicesWhenAvailable(\Everon\Interfaces\DependencyContainer $Container)
    {
        $Test = $Container->resolve('test');
        $this->assertInstanceOf('stdClass', $Test);
        $Test = $Container->resolve('test');
        $this->assertInstanceOf('stdClass', $Test);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetDefinitionsAndServices(\Everon\Interfaces\DependencyContainer $Container)
    {
        $this->assertCount(1, $Container->getDefinitions());
        $this->assertCount(0, $Container->getServices());
        
        $Test = $Container->resolve('test');
        $this->assertInstanceOf('\stdClass', $Test);
        
        $this->assertCount(1, $Container->getServices());
    }
    
    public function dataProvider()
    {
        $Container = new \Everon\Dependency\Container();
        $Container->register('test', function() {
            return new \stdClass();
        });
        
        return [
            [$Container]
        ];
    }

}
