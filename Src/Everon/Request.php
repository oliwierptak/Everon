<?php
namespace Everon;

//todo: rethink collections, hide them from the interface

class Request implements Interfaces\Request, Interfaces\Arrayable
{
    use Helper\ToArray;

    /**
     * @var Interfaces\Collection $_SERVER
     */
    protected $ServerCollection = null;

    /**
     * @var Interfaces\Collection $_POST
     */
    protected $PostCollection  = null;
    
    /**
     * @var Interfaces\Collection $_GET
     */
    protected $QueryCollection  = null;

    /**
     * @var Interfaces\Collection $_FILES
     */
    protected $FileCollection = null;
    
    /**
     * @var string REQUEST_METHOD
     */
    protected $method = null;

    /**
     * @var string REQUEST_URI
     */
    protected $url = null;

    /**
     * @var string Full url with hostname, path, protocol, etc. Eg. http://everon.nova:81/list?XDEBUG_PROFILE&param=1
     */
    protected $location = null;

    /**
     * @var string QUERY_STRING
     */
    protected $query_string = null;

    /**
     * @var string SERVER_PROTOCOL
     */
    protected $protocol = null;

    /**
     * @var integer SERVER_PORT
     */
    protected $port = null;

    /**
     * @var bool HTTPS
     */
    protected $secure = false;


    /**
     * @param array $server $_SERVER
     * @param array $get $_GET
     * @param array $post $_POST
     * @param array $files $_FILES
     */
    public function __construct(array $server, array $get, array $post, array $files)
    {
        $this->ServerCollection = new Helper\Collection($server);
        $this->PostCollection = new Helper\Collection($post);
        $this->QueryCollection = new Helper\Collection($get);
        $this->FileCollection = new Helper\Collection($files);
        
        $this->initRequest();
    }

    /**
     * @return array
     */
    public function getDataFromGlobals()
    {
        return [
            'method' => $this->ServerCollection['REQUEST_METHOD'],
            'url' => $this->ServerCollection['REQUEST_URI'],
            'query_string' => $this->ServerCollection['QUERY_STRING'],
            'location' => $this->getLocationFromGlobals(),
            'protocol' => $this->getProtocolFromGlobals(),
            'port' => $this->getPortFromGlobals(),
            'secure' => $this->getSecureFromGlobals()
        ];
    }

    protected function getLocationFromGlobals()
    {
        $host = $this->getHostNameFromGlobals();
        $port = $this->getPortFromGlobals();
        $protocol = $this->getProtocolFromGlobals();
        
        if ($protocol != '') {
            $protocol = strtolower(substr($protocol, 0, strpos($protocol, '/'))).'://';
        }

        $port_str = '';
        if ($port !== 0 && $port !== 80) {
            $port_str = ':'.$port;
        }
        
        return $protocol.$host.$port_str.@$this->ServerCollection['REQUEST_URI'];
    }
    
    protected function getHostNameFromGlobals()
    {
        if ($this->ServerCollection->has('SERVER_NAME')) {
            return $this->ServerCollection->get('SERVER_NAME');
        }
        
        if ($this->ServerCollection->has('HTTP_HOST')) {
            return $this->ServerCollection->get('HTTP_HOST');
        }
        
        return $this->ServerCollection->get('SERVER_ADDR');
    }
    
    protected function getProtocolFromGlobals()
    {
        $protocol = '';
        if ($this->ServerCollection->has('SERVER_PROTOCOL')) {
            $protocol = $this->ServerCollection->get('SERVER_PROTOCOL');
        }

        return $protocol;
    }

    protected function getPortFromGlobals()
    {
        $port = 0;
        if ($this->ServerCollection->has('SERVER_PORT')) {
            $port = (integer) $this->ServerCollection->get('SERVER_PORT');
        }

        return $port;
    }

    /**
     * @return bool
     */
    protected function getSecureFromGlobals()
    {
        if ($this->ServerCollection->has('HTTPS') && $this->ServerCollection->get('HTTPS') !== 'off') {
            return true;
        }

        if ($this->ServerCollection->has('SSL_HTTPS') && $this->ServerCollection->get('SSL_HTTPS') !== 'off') {
            return true;
        }
        
        if ($this->ServerCollection->has('SERVER_PORT') && $this->ServerCollection->get('SERVER_PORT') == 443) {
            return true;
        }

        return false;
    }

    /**
     * @param $input
     * @return mixed
     */
    protected function sanitizeInput($input)
    {
        if (is_array($input) || $input instanceof \ArrayAccess) {
            array_walk_recursive($input, [$this,'sanitizeInputToken']);
            return $input;
        }

        $this->sanitizeInputToken($input, null);

        return $input;
    }

    /**
     * @param $value
     * @param $index
     */
    protected function sanitizeInputToken(&$value, $index)
    {
        $value = strip_tags($value);
    }

    protected function initRequest()
    {
        $data = $this->getDataFromGlobals();
        $this->validate($data);

        $data = $this->sanitizeInput($data);
        $this->data = $data;
        
        $this->method = $data['method'];
        $this->url = $data['url'];
        $this->query_string = $data['query_string'];
        $this->protocol = $data['protocol'];
        $this->port = (integer) $data['port'];
        $this->secure = (boolean) $data['secure'];
        $this->location = $data['location'];

        $this->FileCollection = $this->sanitizeInput($this->FileCollection);
    }

    /**
     * @param array $data
     * @throws Exception\Request
     */
    protected function validate(array $data)
    {
        $required = [
            'method',
            'url',
            'query_string',
            'location',
            'protocol',
            'port',
            'secure'            
        ];

        foreach ($required as $name) {
            if (!array_key_exists($name, $data)) {
                throw new Exception\Request('Missing required parameter: "%s"', $name);
            }
        }

        $method = strtolower($data['method']);
        $valid = ['post', 'get']; //todo: put into method or property

        if (!in_array($method, $valid)) {
            throw new Exception\Request('Unrecognized post method: "%s"', $method);
        }
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * @param $name
     * @param mixed $default
     * @return mixed
     */
    public function getPostParameter($name, $default=null)
    {
        if ($this->PostCollection->has($name)) {
            return $this->PostCollection->get($name);
        }

        return $default;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setPostParameter($name, $value)
    {
        $this->PostCollection->set($name, $value);
    }

    /**
     * @param $name
     * @param mixed $default
     * @return mixed
     */
    public function getQueryParameter($name, $default=null)
    {
        if ($this->QueryCollection->has($name)) {
            return $this->QueryCollection->get($name);
        }

        return $default;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setQueryParameter($name, $value)
    {
        $this->QueryCollection->set($name, $value);
    }

    /**
     * @param $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getUrlAsTokens()
    {
        $query_tokens = explode('?', $this->url);
        $url = current($query_tokens);
        
        return array_filter(explode('/', $url));
    }

    /**
     * @param $query_string
     */
    public function setQueryString($query_string)
    {
        $this->query_string = $query_string;
    }

    public function getQueryString()
    {
        return $this->query_string;
    }

    /**
     * @param $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param array $data
     */
    public function setQueryCollection(array $data)
    {
        $this->QueryCollection = new Helper\Collection($data);
        $this->initRequest();
    }

    /**
     * @return array
     */
    public function getQueryCollection()
    {
        return $this->QueryCollection->toArray();
    }

    /**
     * @param array $data
     */
    public function setPostCollection(array $data)
    {
        $this->PostCollection  = new Helper\Collection($data);
        $this->initRequest();
    }

    /**
     * @return array
     */
    public function getPostCollection()
    {
        return $this->PostCollection->toArray();
    }

    /**
     * @param array $data
     */
    public function setServerCollection(array $data)
    {
        $this->ServerCollection = new Helper\Collection($data);
        $this->initRequest();
    }

    /**
     * @return array
     */
    public function getServerCollection()
    {
        return $this->ServerCollection->toArray();
    }

    /**
     * @param array $files
     */
    public function setFileCollection(array $files)
    {
        $this->FileCollection = new Helper\Collection($files);
        $this->initRequest();
    }

    /**
     * @return array
     */
    public function getFileCollection()
    {
        return $this->FileCollection->toArray();
    }
    
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param $protocol
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }
    
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }
    
}