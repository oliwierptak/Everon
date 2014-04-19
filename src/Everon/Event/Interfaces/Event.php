<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Zeger Hoogeboom <zeger_hoogeboom@hotmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Event\Interfaces;

interface Event
{
    /**
     * @return Dispatcher
     */
    public function getDispatcher();

    /**
     * @param mixed $Dispatcher
     */
    public function setDispatcher(Dispatcher $Dispatcher);

    /**
     * @return mixed
     */
    public function getName();

    /**
     * @param mixed $name
     */
    public function setName($name);
}