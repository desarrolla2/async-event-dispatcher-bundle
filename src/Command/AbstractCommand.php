<?php

/*
 * This file is part of the desarrolla2 download bundle package
 *
 * Copyright (c) 2017-2018 Daniel González
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Daniel González <daniel@desarrolla2.com>
 */

namespace Desarrolla2\AsyncEventDispatcherBundle\Command;

use DateTime;
use Desarrolla2\Timer\Formatter\Human;
use Desarrolla2\Timer\Timer;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractCommand extends ContainerAwareCommand
{
    use ContainerAwareTrait;

    protected function get(string $serviceName)
    {
        return $this->getContainer()->get($serviceName);
    }

    protected function getParameter(string $parameterName)
    {
        return $this->getContainer()->getParameter($parameterName);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->timer = new Timer(new Human());
        $this->output = $output;
        $this->logger = $this->getContainer()->get('logger');
        $this->em = $this->get('doctrine.orm.entity_manager');
        $message = sprintf('# starting "%s" at "%s" #', $this->getName(), (new DateTime())->format('d/m/Y H:i:s'));
        $this->output->writeln(
            [
                '',
                str_pad('', strlen($message), '#'),
                $message,
                str_pad('', strlen($message), '#'),
                '',
            ]
        );
    }

    protected function finalize()
    {
        $mark = $this->timer->mark();
        $this->output->writeln(
            [
                '',
                sprintf(
                    '# executed on "%s" and consumed "%s"',
                    $mark['time']['from_start'],
                    $mark['memory']['from_start']
                ),
                '',
                '',
            ]
        );
    }

    protected function log(string $message, array $parameters = [])
    {
        if (count($parameters)) {
            $message = vsprintf($message, $parameters);
        }
        $this->output->writeln($message);
        $this->logger->info(sprintf('%s: %s', $this->getName(), $message));
    }

    /**
     * @param Exception $exception
     *
     * @throws Exception
     */
    protected function notifyError(Exception $exception)
    {
        $this->output->writeln(sprintf('attempting to notify to developers'));
        $mailer = $this->getContainer()->get('desarrolla2.exception_listener.mailer');

        try {
            $mailer->notify($exception);
        } catch (Exception $exception) {
        }
    }
}
