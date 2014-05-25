<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Task;

use Everon\Dependency;

class Manager implements Interfaces\Manager
{
    use Dependency\Injection\Logger;


    /**
     * @inheritdoc
     */
    public function process(array $tasks)
    {
        /**
         * @var $Task Interfaces\Item
         */
        foreach ($tasks as $Task) {
            $this->processOne($Task);
        }
    }

    /**
     * @inheritdoc
     */
    public function processOne(Interfaces\Item $Task)
    {
        try {
            $Task->markAsProcessing();
            $result = $Task->execute();
            $Task->setResult($result);
            $Task->markAsExecuted();
        }
        catch (\Exception $e) {
            $Task->markAsFailed();
            $Task->setError($e);
            $Task->setResult(false);
            $this->getLogger()->error($e);
        }
    }
}