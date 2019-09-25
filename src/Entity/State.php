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

use DateTime;
use Doctrine\ORM\Mapping as ORM;

class State
{
    const PENDING = 'PENDING';
    const EXECUTING = 'EXECUTING';
    const FINALIZED = 'FINALIZED';
}
