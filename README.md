AsyncEventDispatcher
=============

The `AsyncEventDispatcherBundle` means an asynchronous message management easy to implement in Symfony!

## Installation

### Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require desarrolla2/async-event-dispatcher-bundle
```
This command requires you to have Composer installed globally, as explained
in the `installation chapter` of the Composer documentation.

### Enable the Bundle


Then, enable the bundle by adding the following line in the ``app/AppKernel.php``
file of your project:

```php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Desarrolla2\AsyncEventDispatcherBundle\AsyncEventDispatcherBundle(),
        );

        // ...
    }

    // ...
}
```

### Config the Bundle


You need put something like this in your config.yml

```yaml
async_event_dispatcher:
  num_messages_per_execution: 1
```
    
## Create your first message asynchronous    

A controller that creates an example message would look like this:

```php

<?php

namespace CoreBundle\Controller;

use CoreBundle\EventDispatcher\AsyncEvents;
use Desarrolla2\AsyncEventDispatcherBundle\Event\Event;

class MessageController extends AbstractController
{
    public function createAction()
    {
        $manager = $this->container->get('desarrolla2.async_event_dispatcher');
        $manager->dispatch(
            AsyncEvents::NAME_EVENT,
            new Event(['date' => (new \DateTime())->format('Ymd')])
        );
    }
}
```

The class that consumes our message would look like this

```php
<?php

namespace CoreBundle\EventSubscriber;

use CoreBundle\EventDispatcher\AsyncEvents;
use Desarrolla2\AsyncEventDispatcherBundle\Event\Event;

class ExampleSubscriber extends AbstractEventSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            AsyncEvents::NAME_EVENT => 'onUpdateRequested',
        ];
    }

    public function onUpdateRequested(Event $event)
    {
        /* CODE */
    }
}
```

Configuration of the class that consumes our message:

```yaml
services:
  _defaults: { public: true }

  core.event_subscriber.example_subscriber:
    class: 'CoreBundle\EventSubscriber\ExampleSubscriber'
    arguments:
      - '@service_container'
    tags:
      - { name: 'kernel.event_subscriber' }
```