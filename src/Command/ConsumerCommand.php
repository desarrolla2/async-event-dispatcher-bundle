<?php

/*
 * This file is part of the she crm package.
 *
 * Copyright (c) 2016-2019 Devtia Soluciones.
 * All rights reserved.
 *
 * @author Daniel González <daniel@devtia.com>
 */

namespace Desarrolla2\AsyncEventDispatcherBundle\Command;

use DateTime;
use Desarrolla2\AsyncEventDispatcherBundle\Entity\Message;
use Desarrolla2\AsyncEventDispatcherBundle\Event\Event;
use Desarrolla2\Timer\Formatter\Human;
use Desarrolla2\Timer\Timer;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumerCommand extends ContainerAwareCommand
{
    /** @var OutputInterface */
    protected $output;

    /** @var LoggerInterface */
    protected $logger;

    /** @var EntityManager */
    protected $em;

    /** @var Timer */
    protected $timer;

    protected function configure()
    {
        $this->setName('async-event-consumer:consume');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->executeSecure($input, $output);
        } catch (Exception $exception) {
            throw $exception;
        }

        $this->finalize();
    }

    protected function executeSecure(InputInterface $input, OutputInterface $output)
    {
        $message = $this->em->getRepository(Message::class)->findOneBy([]);
        if (!$message) {
            return;
        }
        $this->em->remove($message);
        $this->em->flush();

        $eventDispatcher = $this->get('event_dispatcher');
        $eventDispatcher->dispatch(
            $message->getName(),
            new Event($message->getData())
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

    protected function get($serviceName)
    {
        return $this->getContainer()->get($serviceName);
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
