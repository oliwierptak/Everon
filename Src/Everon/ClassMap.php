<?php
namespace Everon;

use Everon\Interfaces;

class ClassMap implements Interfaces\ClassMap
{
    protected $class_map = [];

    protected $class_map_filename = null;


    public function __construct($filename)
    {
        $this->class_map_filename = $filename;
    }

    public function addToMap($class, $file)
    {
        if (isset($this->class_map[$class]) === false) {
            $this->class_map[$class] = $file;
            $this->saveMap();
        }
    }

    public function getFilenameFromMap($class)
    {
        if (isset($this->class_map[$class])) {
            return $this->class_map[$class];
        }

        return null;
    }

    protected function getCacheFilename()
    {
        return $this->class_map_filename;
    }

    public function loadMap()
    {
        $filename = $this->getCacheFilename();
        if (is_file($filename)) {
            $this->class_map = include($filename);
        }
    }

    public function saveMap()
    {
        $data = var_export($this->class_map, 1);
        $filename = $this->getCacheFilename();
        $h = fopen($filename, 'w+');
        fwrite($h, "<?php return $data; ");
        fclose($h);
    }

}