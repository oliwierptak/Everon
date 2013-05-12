<?php
namespace Everon\Test;

use Everon\Interfaces;

class RouterValidatorTest extends \Everon\TestCase
{

    public function testConstructor()
    {
        $Validator = new \Everon\RouterValidator();
        $this->assertInstanceOf('\Everon\Interfaces\RouterValidator', $Validator);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testValidate(Interfaces\RouterValidator $Validator, Interfaces\ConfigItemRouter $RouteItem, Interfaces\Request $Request)
    {
        $result = $Validator->validate($RouteItem, $Request);
        $this->assertInternalType('array', $result);
    }
    
    /**
     * @dataProvider dataProvider
     * @expectedException \Everon\Exception\InvalidRouterParameter
     * @expectedExceptionMessage Invalid required parameter: "password" for route: "test_complex"
     */
    public function testValidateShouldThrowExceptionWhenError(Interfaces\RouterValidator $Validator, Interfaces\ConfigItemRouter $RouteItem, Interfaces\Request $Request)
    {
        $post = $Request->getPostCollection();
        $post['password'] = '';
        $Request->setPostCollection($post);
        
        $result = $Validator->validate($RouteItem, $Request);
        $this->assertInternalType('array', $result);
    }
    
    /**
     * @dataProvider dataProvider
     * @expectedException \Everon\Exception\Router
     */
    public function testValidateQueryAndGetShouldThrowExceptionWhenError(Interfaces\RouterValidator $Validator, Interfaces\ConfigItemRouter $RouteItem, Interfaces\Request $Request)
    {
        $RouteItemMock = $this->getMock('Everon\Interfaces\ConfigItemRouter');
        $RouteItemMock->expects($this->once())
            ->method('filterQueryKeys')
            ->will($this->throwException(new \Exception('filterQueryKeys')));
        
        $result = $Validator->validate($RouteItemMock, $Request);
        $this->assertInternalType('array', $result);
    }
    
    /**
     * @dataProvider dataProvider
     * @expectedException \Everon\Exception\Router
     */
    public function testValidatePostShouldThrowExceptionWhenError(Interfaces\RouterValidator $Validator, Interfaces\ConfigItemRouter $RouteItem, Interfaces\Request $Request)
    {
        $RouteItemMock = $this->getMock('Everon\Interfaces\ConfigItemRouter');
        $RouteItemMock->expects($this->once())
            ->method('filterQueryKeys')
            ->will($this->returnValue([]));
        
        $RouteItemMock->expects($this->any())
            ->method('getPostRegex')
            ->will($this->throwException(new \Exception('getPostRegex')));
        
        $result = $Validator->validate($RouteItemMock, $Request);
        $this->assertInternalType('array', $result);
    }
    
    public function dataProvider()
    {
        /**
         * @var \Everon\Interfaces\DependencyContainer $Container
         * @var \Everon\Interfaces\Factory $Factory
         */
        list($Container, $Factory) = $this->getContainerAndFactory();

        $server_data = $this->getServerDataForRequest([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/login/submit/session/adf24ds34/redirect/account%5Csummary?and=something&else=2457',
            'QUERY_STRING' => 'and=something&else=2457',
        ]);
        $Request = $Factory->buildRequest(
            $server_data, [
                'and'=>'something',
                'else'=>2457
            ],[
                'token' => 3,
                'username' => 'test',
                'password' => 'aaa'
            ],
            []
        );
        
        $Router = $Container->resolve('Router');
        $Router->setRequest($Request);
        
        $Validator = new \Everon\RouterValidator();
        return [
            [$Validator, $Router->getCurrentRoute(), $Request]
        ];
    }

}
