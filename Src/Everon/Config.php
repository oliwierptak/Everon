<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon;

use Everon\Helper;
use Everon\Exception;


class Config implements Interfaces\Config, Interfaces\Arrayable
{
    use Helper\ArrayMergeDefault;
    use Helper\ToArray;
    
    protected $name = null;

    protected $filename = '';

    /**
     * @var array
     */
    protected $go_path = [];
    
    protected $data_processed = false;


    /**
     * @param $name
     * @param $filename
     * @param \Closure|\Array $data
     */
    public function __construct($name, $filename, $data)
    {
        if (is_array($data) === false && is_callable($data) === false) {
            throw new Exception\Config('Invalid data type for: "%s@%s"', [$name, $filename]);
        }
        
        $this->name = $name;
        $this->filename = $filename;
        $this->data = $data;
    }

    /**
     * @return array
     */
    protected function getData()
    {
        if ($this->data instanceof \Closure) {
            $this->data = $this->data->__invoke();
        }

        $this->processData();
        return $this->data;
    }
    
    protected function processData()
    {
        if ($this->data_processed === true) {
            return;
        }

        $this->data_processed = true;
        $HasInheritance = function($value) {
            return strpos($value, '<') !== false;
        };

        $use_inheritance = false;
        foreach ($this->data as $name => $data) {
            if ($HasInheritance($name) === true) {
                $use_inheritance = true;
            }
        }
        
        if ($use_inheritance === false) {
            return;
        }

        $inheritance_list = [];
        $data_processed = [];
        foreach ($this->data as $name => $data) {
            if ($HasInheritance($name) === true) {
                list($for, $from) = explode('<', $name);
                $for = trim($for);
                $from = trim($from);
                $inheritance_list[$for] = $from;
                $data_processed[$for] = $data;
            }
            else {
                $data_processed[$name] = $data;
            }
        }

        if (empty($inheritance_list) === false) {
            foreach ($inheritance_list as $for => $from) {
                $data_processed[$for] = $this->arrayMergeDefault($data_processed[$from], $data_processed[$for]);
            }

            //make sure everything has default
            $default = reset($data_processed);
            foreach ($data_processed as $name => $data) {
                $data_processed[$name] = $this->arrayMergeDefault($default, $data_processed[$name]);
            }
        }
        
        $this->data = $data_processed;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function get($name, $default=null)
    {
        $data = $this->getData();
        
        if (empty($this->go_path) === false) {
            foreach ($this->go_path as $index) {
                if (isset($data[$index]) === false) {
                    $this->go_path = [];
                    return $default;
                }
                else {
                    $data = $data[$index];
                }
            }
        }
        
        $this->go_path = [];
        return isset($data[$name]) ? $data[$name] : $default;
    }

    /**
     * @param $where
     * @return Interfaces\Config
     */
    public function go($where)
    {
        $this->go_path[] = $where; 
        return $this;
    }
    
}