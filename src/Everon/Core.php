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


abstract class Core implements Interfaces\Core
{
    use Dependency\Injection\ConfigManager;
    use Dependency\Injection\Logger;
    use Dependency\Injection\Factory;
    use Dependency\Injection\Response;
    use Dependency\Injection\Request;
    use Dependency\Injection\Router;
    use Module\Dependency\Injection\ModuleManager;

    use Helper\GetUrl;
    use Helper\IsCallable;

    /**
     * @var Interfaces\Controller
     */
    protected $Controller = null;

    /**
     * @var Module\Interfaces\Module
     */
    protected $Module = null;

    /**
     * @var Config\Interfaces\ItemRouter
     */
    protected $Route = null;

    /**
     * @var RequestIdentifier
     */
    protected $RequestIdentifier = null;

    protected $previous_exception_handler = null;


    /**
     * @param RequestIdentifier $RequestIdentifier
     * @throws Exception\Core
     */
    protected function runOnce(RequestIdentifier $RequestIdentifier)
    {
        if ($this->RequestIdentifier !== null) {
            return;
        }

        register_shutdown_function([$this, 'shutdown']);

        $this->RequestIdentifier = $RequestIdentifier;
        $this->getLogger()->setRequestIdentifier($this->RequestIdentifier->getValue());

        $this->previous_exception_handler = set_exception_handler([$this, 'handleExceptions']);

        $this->getModuleManager()->loadModuleDependencies();
        $this->Route = $this->getRouter()->getRouteByRequest($this->getRequest());
        $this->Module = $this->getModuleManager()->getModuleByName($this->Route->getModule());

        if ($this->Module === null) {
            throw new Exception\Core('No module defined for this request');
        }

        if ($this->getRequest()->isAjax()) {
            try {
                $AjaxController = $this->Module->getAjaxController($this->Route->getController());
                if ($this->isCallable($AjaxController, $this->Route->getAction())) {
                    $this->Controller = $AjaxController;
                }
            }
            catch (\Exception $e) {

            }
        }

        if ($this->Controller === null) {
            $this->Controller = $this->Module->getController($this->Route->getController());
        }

        $this->Controller->setCurrentRoute($this->Route);
    }

    /**
     * @inheritdoc
     */
    public function run(RequestIdentifier $RequestIdentifier)
    {
        $this->runOnce($RequestIdentifier);
        $this->Controller->execute($this->Route->getAction());
    }

    /**
     * @return RequestIdentifier
     */
    public function getRequestIdentifier()
    {
        return $this->RequestIdentifier;
    }

    public function shutdown()
    {
        $data = $this->getRequestIdentifier()->getStats();
        $sbs = vsprintf('%0dkb', $data['memory_at_start'] / 1024);
        $sas = vsprintf('%0dkb', $data['memory_total'] / 1024);
        $mu = vsprintf('%0dkb', ($data['memory_total'] - $data['memory_at_start']) / 1024);
        $time = vsprintf('%.3f', round($data['time'], 3));
        $s = "${time}s $mu $sbs/$sas";

        return $s;
    }

    /**
     * @inheritdoc
     */
    public function handleExceptions(\Exception $Exception)
    {
        $this->restorePreviousExceptionHandler();
        $this->showException($Exception, null);
    }

    /**
     * @param \Exception $Exception
     * @param \Everon\Mvc\Interfaces\Controller|null $Controller
     */
    protected function showException(\Exception $Exception, $Controller)
    {
        $this->getLogger()->error($Exception);
        
        /**
         * @var \Everon\Mvc\Interfaces\Controller $Controller
         */
        if ($Controller === null) {
            try {
                $error_module = $this->getConfigManager()->getConfigValue('everon.error_handler.module', null);
                $error_controller = $this->getConfigManager()->getConfigValue('everon.error_handler.controller', null);
                
                if ($this->getRequest()->isAjax()) {
                    $error_controller = 'Ajax\\'.$error_controller;
                }
                
                $Module = $this->getModuleManager()->getModuleByName($error_module);
                $Controller = $Module->getController($error_controller);
            }
            catch (\Exception $e) {
                //todo display the error here based on config
                $this->getLogger()->error($e);
            }
        }

        if ($Controller instanceof Interfaces\Controller) {
            $Controller->showException($Exception);
        }
    }

    protected function restorePreviousExceptionHandler()
    {
        if ($this->previous_exception_handler !== null) {
            restore_exception_handler($this->previous_exception_handler);
        }
    }

    /**
     * @return Interfaces\Controller
     */
    public function getController()
    {
        return $this->Controller;
    }

    /**
     * @param Interfaces\Controller $Controller
     */
    public function setController(Interfaces\Controller $Controller)
    {
        $this->Controller = $Controller;
    }
    
    public function terminate()
    {
        die();
    }

    /**
     * @inheritdoc
     */
    public function redirectAndTerminate($name, $query=[], $get=[])
    {
        $this->shutdown();
        $this->terminate();
    }
}