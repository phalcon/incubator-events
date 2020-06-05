# Phalcon\Incubator\Events
Usage examples of the features is here:

## Manager

### Classic event handling

Classic event handling requires one class with a bunch of methods named equal to event name.

And multiple `$eventsManager->attach()` calls if you use more than one handler for single event:
```php
class DispatchEventsHandler
{
    public function beforeCallActionMethod(
        Phalcon\Events\EventInterface $event, 
        Phalcon\Dispatcher\DispatcherInterface $dispatcher
    ): void {
        //do some stuff
    }

    public function beforeDoSomeMistakes(
        Phalcon\Events\EventInterface $event, 
        Phalcon\Dispatcher\DispatcherInterface $dispatcher
    ): void {
        //do some right stuff
    }
}

class DispatchEventsHandlerTwo
{
    public function beforeCallActionMethod(
        Phalcon\Events\EventInterface $event, 
        Phalcon\Dispatcher\DispatcherInterface $dispatcher
    ): void {
        //do another stuff in same event
    }
}

$eventsManager = new Phalcon\Events\Manager();
$eventsManager->attach('dispatch:beforeCallActionMethod',new DispatchEventsHandler(), 100);
$eventsManager->attach('dispatch:beforeCallActionMethod',new DispatchEventsHandlerTwo(), 101);
$eventsManager->attach('dispatch:beforeDoSomeMistakes',new DispatchEventsHandler());
//or global way
$eventsManager->attach('dispatch',new DispatchEventsHandler(), 100);
$eventsManager->attach('dispatch',new DispatchEventsHandlerTwo(), 101);
```

### Featured event handling
This manager version provides a feature of single responsible event handlers via configs, using `__invoke`.

Create your handler class:
```php
class BeforeCallActionMethod
{
    public function __invoke(
        Phalcon\Events\EventInterface $event, 
        Phalcon\Dispatcher\DispatcherInterface $dispatcher
    ): void {
        //do some stuff
    }
}
```

Define configuration in config file:
```php
$config = new Phalcon\Config([
  'handlers' => [
      'dispatch:beforeCallActionMethod' => BeforeCallActionMethod::class,
  ],
]);
```

Define `Phalcon\Incubator\Events\Manager` in container as eventsManager service using config of handlers:
```php
$handlers = $config->get('handlers')->toArray();

$eventsManager = new Phalcon\Incubator\Events\Manager();
$eventsManager->loadHandlers($handlers);
```

There are multiple ways to define configs of handlers:

Flat classname usage, if your handler is invokable:
```php
$flat = new Phalcon\Config([
    'handlers' => [
        'dispatch:beforeCallActionMethod' => BeforeCallActionMethod::class,
    ],
]);
```

Same, but more informative and with optional priority:
```php
$verbose = new Phalcon\Config([
    'handlers' => [
        'dispatch:beforeCallActionMethod' => [
            'class' => BeforeCallActionMethod::class,
            'priority' => 100, //optional
        ],
    ],
]);
```

Using callable constructions:
```php
$callable = new Phalcon\Config([
    'handlers' => [
        //you can use any other public method name instead of method, for example run()
        'dispatch:beforeCallActionMethod' => [new BeforeCallActionMethod(), 'run'],
        //or
        'dispatch:beforeCallActionMethod' => [BeforeCallActionMethod::class, 'staticRun'],
    ],
]);
```

Grouping more than one handler for one event:
```php
$grouped = new Phalcon\Config([
    'handlers' => [
        'dispatch:beforeCallActionMethod' => [
            BeforeCallActionMethod::class,
            BeforeCallActionMethodAnother::class,
            BeforeCallActionMethodThird::class,
        ],
    ],
]);

$groupedVerbose = new Phalcon\Config([
    'handlers' => [
        'dispatch:beforeCallActionMethod' => [
            [
                'class' => BeforeCallActionMethod::class,
                'priority' => 100, //optional
            ],
            [
                'class' => BeforeCallActionMethodAnother::class,
                'priority' => 101, //optional
            ],
            [
                'class' => BeforeCallActionMethodThird::class,
                'priority' => 101, //optional
            ],
        ],
    ],
]);
```
