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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Route("/async_event/message", name="_async_event.")
 */
class MessageController extends Controller
{
    /**
     * @Route("/{hash}/reset", name="message.reset", requirements={"hash"="\w{64}"})
     * @Method({"GET"})
     */
    public function resetAction(Request $request, Message $message)
    {
        if (State::PENDING == $message->getState()) {
            throw  new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();
        $newMessage = new Message();
        $newMessage->setName($message->getName());

        $data = array_merge(
            $message->getData(),
            [
                'reseted' => true,
                'user_id' => $this->getUser()->getId(),
                'original_message_id' => $message->getId(),
                'original_user_id' => $message->getData()['user_id'],
            ]
        );

        $newMessage->setData($data);
        $em->persist($newMessage);
        $em->flush();

        return new RedirectResponse($request->get('referer'));
    }
}
