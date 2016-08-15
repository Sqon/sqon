<?php

namespace Sqon\Event\Subscriber;

use Sqon\Event\AfterCommitEvent;
use Sqon\Exception\Event\SubscriberException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Changes the permissions for the committed Sqon.
 *
 * This subscriber will change the permissions for a Sqon that has been
 * committed. The constructor accepts a decimal representation of the octal
 * file permissions that should be set for the Sqon (i.e. `0755` not `755`).
 *
 * ```php
 * $dispatcher->addSubscriber(new ChmodSubscriber(0755));
 * ```
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ChmodSubscriber implements EventSubscriberInterface
{
    /**
     * The permissions.
     *
     * @var integer
     */
    private $permissions;

    /**
     * Initializes the new subscriber.
     *
     * @param integer $permissions The permissions.
     */
    public function __construct($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterCommitEvent::NAME => [
                ['afterCommit', 0]
            ]
        ];
    }

    /**
     * Changes the permission of the Sqon.
     *
     * @param AfterCommitEvent $event The event manager.
     *
     * @throws SubscriberException If the permissions could not be changed.
     */
    public function afterCommit(AfterCommitEvent $event)
    {
        $path = $event->getSqon()->getPathToSqon();

        if (!chmod($path, $this->permissions)) {
            // @codeCoverageIgnoreStart
            throw new SubscriberException(
                sprintf(
                    'The permissions for "%s" could not be changed to "%o".',
                    $path,
                    $this->permissions
                )
            );
            // @codeCoverageIgnoreEnd
        }
    }
}
