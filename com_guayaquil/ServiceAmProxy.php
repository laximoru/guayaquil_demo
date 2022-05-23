<?php

namespace guayaquil;

use GuayaquilLib\Command;
use GuayaquilLib\objects\BaseObject;
use GuayaquilLib\ServiceAm;

class ServiceAmProxy extends ServiceAm
{
    /**
     * @var View
     */
    private $view;

    public function __construct($view, string $login, string $password, string $serviceUrl)
    {
        parent::__construct($login, $password);
        $this->soap->setAmServiceUrl($serviceUrl);
        $this->view = $view;
    }

    protected function parseXml(string $xmlString)
    {
        if ($this->view) {
            $this->view->appendLastXmlResponse($xmlString);
        }

        return parent::parseXml($xmlString);
    }

    public function executeCommand(Command $command): BaseObject
    {
        $timeStart = microtime(true);

        try {
            $result = parent::executeCommand($command);
        } finally {
            $timeEnd = microtime(true);
            if ($this->view) {
                $this->view->setLastExecutionTime($timeEnd - $timeStart);
                $this->view->appendLastExecutionCommand([$command->getCommand()]);
            }
        }

        return $result;
    }

    public function queryButch(array $commands): array
    {
        $timeStart = microtime(true);

        try {
            $result = parent::queryButch($commands);
        } finally {
            if ($this->view) {
                $timeEnd = microtime(true);
                $this->view->setLastExecutionTime($timeEnd - $timeStart);

                $commandTexts = [];
                foreach ($commands as $command) {
                    $commandTexts[] = $command->getCommand();
                }

                $this->view->appendLastExecutionCommand($commandTexts);
            }
        }

        return $result;
    }
}