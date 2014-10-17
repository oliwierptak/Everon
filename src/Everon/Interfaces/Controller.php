<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Interfaces;

use Everon\Config\Interfaces\ItemRouter;
use Everon\Exception;
use Everon\Http;

/**
 * @method Response getResponse()
 * @method void setResponse(Response $Response)
 */
interface Controller extends \Everon\Interfaces\Dependency\GetUrl
{
    /**
     * @param string $action
     */
    function setAction($action);

    /**
     * @return string
     */
    function getAction();
        
    function getName();

    /**
     * @param $name
     * @return mixed
     */
    function setName($name);

    /**
     * @param $action
     * @return void
     * @throws Exception\InvalidControllerMethod
     * @throws Exception\InvalidControllerResponse
     */
    function execute($action);

    /**
     * @param ItemRouter $CurrentRoute
     */
    function setCurrentRoute(ItemRouter $CurrentRoute);

    /**
     * @return ItemRouter
     */
    function getCurrentRoute();

    /**
     * @param \Exception $Exception
     */
    function showException(\Exception $Exception);

    /**
     * @param \Everon\Module\Interfaces\Module $Module
     */
    function setModule(\Everon\Module\Interfaces\Module $Module);

    /**
     * @return \Everon\Module\Interfaces\Module
     */
    function getModule();
}
