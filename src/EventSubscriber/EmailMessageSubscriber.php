<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Sets sender info for each outgoing email.
 */
class EmailMessageSubscriber implements EventSubscriberInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly string $sender)
    {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => 'onMessageEvent',
        ];
    }

    /**
     * Sets sender info for each outgoing email.
     */
    public function onMessageEvent(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (!$message instanceof Email) {
            return;
        }

        if (!$message->getFrom()) {
            $message->from(new Address($this->sender, 'eTraxis'));
        }

        if (!$message->getReplyTo()) {
            $from = $message->getFrom();
            $message->replyTo($from[0]);
        }
    }
}
