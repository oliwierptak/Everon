<?php
namespace Everon\Helper;

use Everon\Helper;
use Everon\Interfaces;


class Collection implements
    \Countable, \ArrayAccess, \IteratorAggregate,
    Interfaces\Collection,
    Interfaces\Arrayable
{
    use Helper\ToArray;
    
    protected $position = 0;
    
    
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->position = 0;
    }

    public function count()
    {
        return count($this->data);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function has($name)
    {
        return $this->offsetExists($name);
    }

    public function remove($name)
    {
        unset($this->data[$name]);
    }

    public function set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function get($name)
    {
        return $this->data[$name];
    }
    
}