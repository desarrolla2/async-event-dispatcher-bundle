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

namespace Desarrolla2\AsyncEventDispatcherBundle\Controller;

use Desarrolla2\AsyncEventDispatcherBundle\Entity\Message;
use Desarrolla2\AsyncEventDispatcherBundle\Entity\State;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/async_event/message", name="_async_event.")
 */
class MessageController extends Controller
{
    /**
     * @Route("/{hash}/pause", name="message.pause", requirements={"hash"="\w{64}"})
     * @Method({"GET"})
     */
    public function pauseAction(Request $request, Message $message)
    {
        $manager = $this->get('desarrolla2_async_event_dispatcher.manager.message_manager');
        if ($manager->isReady($message)) {
            $manager->update($message, State::PAUSED);
            $this->addFlash('success', 'message paused');

            return new RedirectResponse($request->get('referer'));
        }
        $this->addFlash('error', 'message cannot be paused');

        return new RedirectResponse($request->get('referer'));
    }

    /**
     * @Route("/{hash}/play", name="message.play", requirements={"hash"="\w{64}"})
     * @Method({"GET"})
     */
    public function playAction(Request $request, Message $message)
    {
        $manager = $this->get('desarrolla2_async_event_dispatcher.manager.message_manager');
        if ($manager->isPaused($message)) {
            $manager->update($message, State::PENDING);
            $this->addFlash('success', 'message played');

            return new RedirectResponse($request->get('referer'));
        }
        $this->addFlash('error', 'message cannot be played');

        return new RedirectResponse($request->get('referer'));
    }

    /**
     * @Route("/{hash}/remove", name="message.remove", requirements={"hash"="\w{64}"})
     * @Method({"GET"})
     */
    public function removeAction(Request $request, Message $message)
    {
        $manager = $this->get('desarrolla2_async_event_dispatcher.manager.message_manager');
        if ($manager->isReady($message) || $manager->isPaused($message) || $manager->isFinish($message)) {
            $manager->remove($message);
            $this->addFlash('success', 'message removed');

            return new RedirectResponse($request->get('referer'));
        }
        $this->addFlash('error', 'message cannot be removed');

        return new RedirectResponse($request->get('referer'));
    }

    /**
     * @Route("/{hash}/reset", name="message.reset", requirements={"hash"="\w{64}"})
     * @Method({"GET"})
     */
    public function resetAction(Request $request, Message $message)
    {
        $manager = $this->get('desarrolla2_async_event_dispatcher.manager.message_manager');
        $newMessage = $manager->create(
            $message->getName(),
            array_merge(
                $message->getData(),
                [
                    'reseted' => true,
                    'user_id' => $this->getUser()->getId(),
                    'original_message_id' => $message->getId(),
                    'original_user_id' => $message->getData()['user_id'],
                ]
            )
        );

        return new RedirectResponse($request->get('referer'));
    }
}
