<?php

/*
 * This file is part of the she crm package
 *
 * Copyright (c) 2016-2019 Aston Herencia && Devtia Soluciones
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Daniel González <daniel@devtia.com>
 * @author Carlos Garbajosa <carlos@devtia.com>
 * @author Jaime Martínez <jaime@devtia.com>
 */

namespace Desarrolla2\AsyncEventDispatcherBundle\Twig;

use Desarrolla2\AsyncEventDispatcherBundle\Entity\Message;
use Desarrolla2\AsyncEventDispatcherBundle\Entity\State;
use Desarrolla2\AsyncEventDispatcherBundle\Manager\MessageManager;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use Twig_Extension;
use Twig_SimpleFunction;

class DefaultExtension extends Twig_Extension
{
    protected $manager;
    protected $em;

    public function __construct(MessageManager $manager, EntityManagerInterface $entityManager)
    {
        $this->manager = $manager;
        $this->em = $entityManager;
    }

    public function canBePaused(Message $message): bool
    {
        if ($this->manager->isReady($message)) {
            return true;
        }

        return false;
    }

    public function canBePlayed(Message $message): bool
    {
        if ($this->manager->isPaused($message)) {
            return true;
        }

        return false;
    }

    public function canBeRemoved(Message $message): bool
    {
        if ($this->manager->isReady($message) || $this->manager->isPaused($message)) {
            return true;
        }
        if ($this->manager->isFinish($message)) {
            return true;
        }

        return false;
    }

    public function countPending(): int
    {
        return $this->em->getRepository(Message::class)->count(
            ['state' => [State::PENDING, State::EXECUTING]]
        );
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('async_event_var_dump', [$this, 'varDump']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new Twig_SimpleFunction('async_event_message_can_be_removed', [$this, 'canBeRemoved']),
            new Twig_SimpleFunction('async_event_message_can_be_played', [$this, 'canBePlayed']),
            new Twig_SimpleFunction('async_event_message_can_be_paused', [$this, 'canBePaused']),
            new Twig_SimpleFunction('async_event_count_pending', [$this, 'countPending']),
            new Twig_SimpleFunction(
                'async_event_render_pending',
                [$this, 'renderPending'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
            new Twig_SimpleFunction(
                'async_event_render_latest',
                [$this, 'renderLatest'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }

    public function renderLatest(Environment $twig): string
    {
        return $twig->render('@AsyncEventDispatcher/table.html.twig', ['messages' => $this->getLatestMessages()]);
    }

    public function renderPending(Environment $twig): string
    {
        return $twig->render('@AsyncEventDispatcher/table.html.twig', ['messages' => $this->getPendingMessages()]);
    }

    public function varDump($value)
    {
        return json_encode($value, JSON_PRETTY_PRINT);
    }

    private function getLatestMessages(): array
    {
        return $this->em->getRepository(Message::class)->findBy(
            ['state' => [State::FINISH]],
            ['updatedAt' => 'DESC'],
            10
        );
    }

    private function getPendingMessages(): array
    {
        return $this->em->getRepository(Message::class)->findBy(
            ['state' => [State::PENDING, State::PAUSED, State::EXECUTING]],
            ['createdAt' => 'ASC'],
            10
        );
    }
}
