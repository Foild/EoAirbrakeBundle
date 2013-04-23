<?php

/*
 * This file is part of the EoAirbrakeBundle package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eo\AirbrakeBundle\EventListener;

use Eo\AirbrakeBundle\Bridge\Client;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Eo\AirbrakeBundle\EventListener\ShutdownListener
 */
class ShutdownListener
{
    /**
     * @var Airbrake\Client
     */
    protected $client;

    /**
     * Class constructor
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
    
    /**
     * Register a function for execution on shutdown
     *
     * @param Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     */
    public function register(FilterControllerEvent $event)
    {
        register_shutdown_function(array($this, 'onShutdown'));
    }

    /**
     * Handles the PHP shutdown event.
     *
     * This event exists almost solely to provide a means to catch and log errors that might have been
     * otherwise lost when PHP decided to die unexpectedly.
     */
    public function onShutdown()
    {
        // Get the last error if there was one, if not, let's get out of here.
        if (!$error = error_get_last()) {
            return;
        }

        $fatal  = array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR,E_RECOVERABLE_ERROR);
        if (!in_array($error['type'], $fatal)) {
            return;
        }

        $message   = '[Shutdown Error]: %s';
        $message   = sprintf($message, $error['message']);
        $backtrace = array(array('file' => $error['file'], 'line' => $error['line']));

        $this->client->notifyOnError($message, $backtrace);
        error_log($message.' in: '.$error['file'].':'.$error['line']);
    }
}
