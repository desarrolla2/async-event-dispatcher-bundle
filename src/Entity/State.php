<?php

/*
 * This file is part of the she crm package.
 *
 * Copyright (c) 2016-2019 Devtia Soluciones.
 * All rights reserved.
 *
 * @author Daniel González <daniel@devtia.com>
 */

namespace Desarrolla2\AsyncEventDispatcherBundle\Entity;

abstract class State
{
    const FAILED = 'FAILED';
    const EXECUTING = 'EXECUTING';
    const FINISH = 'FINISH';
    const PAUSED = 'PAUSED';
    const PENDING = 'PENDING';
}
