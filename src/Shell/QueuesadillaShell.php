<?php
namespace Josegonzalez\CakeQueuesadilla\Shell;

use Cake\Console\Shell;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Josegonzalez\CakeQueuesadilla\Queue\Queue;

class QueuesadillaShell extends Shell
{
    /**
     * Override main() to handle action
     * Starts a Queuesadilla worker
     *
     * @return void
     */
    public function main()
    {
        $logger = Log::engine($this->params['logger']);
        $engine = $this->getEngine($logger);
        $worker = $this->getWorker($engine, $logger);
        $worker->work();
    }

    /**
     * Retrieves a queue engine
     *
     * @param \Psr\Log\LoggerInterface $logger logger
     * @return \josegonzalez\Queuesadilla\Engine\Base
     */
    public function getEngine($logger)
    {
        $config = Hash::get($this->params, 'config');
        $engine = Queue::engine($config);
        $engine->setLogger($logger);
        if (!empty($this->params['queue'])) {
            $engine->config('queue', $this->params['queue']);
        }

        return $engine;
    }

    /**
     * Retrieves a queue worker
     *
     * @param \josegonzalez\Queuesadilla\Engine\Base $engine engine to run
     * @param \Psr\Log\LoggerInterface $logger logger
     * @return \josegonzalez\Queuesadilla\Worker\Base
     */
    public function getWorker($engine, $logger)
    {
        $interval = $this->params['interval'];
        $worker = $this->params['worker'];
        $WorkerClass = "josegonzalez\\Queuesadilla\\Worker\\" . $worker . "Worker";

        return new $WorkerClass($engine, $logger, [
            'queue' => $engine->config('queue'),
            'maxRuntime' => $engine->config('maxRuntime'),
            'interval' => $interval
        ]);
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addOption('config', [
            'default' => 'default',
            'help' => 'Name of a queue config to use',
            'short' => 'c',
        ]);
        $parser->addOption('interval', [
            'default' => 1,
            'help' => 'Interval looking for jobs',
            'short' => 'i',
        ]);
        $parser->addOption('queue', [
            'help' => 'Name of queue to override from loaded config',
            'short' => 'Q',
        ]);
        $parser->addOption('logger', [
            'help' => 'Name of a configured logger',
            'default' => 'stdout',
            'short' => 'l',
        ]);
        $parser->addOption('worker', [
            'choices' => [
                'Sequential',
                'Test',
            ],
            'default' => 'Sequential',
            'help' => 'Name of worker class',
            'short' => 'w',
        ])->description(__('Runs a Queuesadilla worker.'));

        return $parser;
    }
}
