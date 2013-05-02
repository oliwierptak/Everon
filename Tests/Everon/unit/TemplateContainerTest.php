<?php
namespace Everon\Test;

class TemplateContainerTest extends \Everon\TestCase
{

    public function testConstructor()
    {
        $Container = new \Everon\View\Template\Container('Hello {world}!', ['world' => 'World']);
        $this->assertInstanceOf('\Everon\View\Template\Container', $Container);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testConstructFromData(\Everon\View\Template\Container $Container, $output)
    {
        $this->assertInstanceOf('\Everon\View\Template\Container', $Container);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSetAndGet(\Everon\View\Template\Container $Container, $output)
    {
        $Container->set('test', 'This is a test');
        $this->assertEquals('This is a test', $Container->get('test'));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSetAndGetData(\Everon\View\Template\Container $Container, $output)
    {
        $Container->setData(['test', 'This is a test']);
        $this->assertEquals(['test', 'This is a test'], $Container->getData());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetDefaultValue(\Everon\View\Template\Container $Container, $output)
    {
        $this->assertEquals('nono', $Container->get('NOT_ExiST', 'nono'));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testTemplateContent(\Everon\View\Template\Container $Container, $output)
    {
        $Container->setTemplateContent('template_string');
        $this->assertEquals('template_string', $Container->getTemplateContent());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetToString(\Everon\View\Template\Container $Container, $output)
    {
        $this->assertEquals($Container->getTemplateContent(), (string) $Container);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSetAndGetInclude(\Everon\View\Template\Container $Template, $output)
    {
        $Include = new \Everon\View\Template\Container('', []);
        $Template->setInclude('test', $Include);
        $this->assertEquals($Include, $Template->getInclude('test'));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSetAndGetAllIncludes(\Everon\View\Template\Container $Template, $output)
    {
        $Include = new \Everon\View\Template\Container('', []);
        $Template->setAllIncludes([
            'test' => $Include
        ]);
        $this->assertEquals($Include, $Template->getInclude('test'));
        $this->assertCount(1, $Template->getAllIncludes());
    }

    public function dataProvider()
    {
        return [
            [new \Everon\View\Template\Container('Hello {world}!', ['world' => 'World']),
                'Hello World!'],
            [new \Everon\View\Template\Container('My name is <b>{name}</b>.', ['name' => 'John Doe']),
                'My name is <b>John Doe</b>.'],
        ];
    }


}