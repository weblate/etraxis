<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2022 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\EventSubscriber;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * @internal
 *
 * @coversDefaultClass \App\EventSubscriber\EmailMessageSubscriber
 */
final class EmailMessageSubscriberTest extends WebTestCase
{
    private EmailMessageSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = new EmailMessageSubscriber('noreply@example.com');
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $expected = [
            MessageEvent::class,
        ];

        self::assertSame($expected, array_keys(EmailMessageSubscriber::getSubscribedEvents()));
    }

    /**
     * @covers ::onMessageEvent
     */
    public function testHasFrom(): void
    {
        $email = new Email();

        $email
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test message')
            ->text('Message body')
        ;

        $event = new MessageEvent($email, new Envelope($email->getFrom()[0], $email->getTo()), 'smtp://null');

        $this->subscriber->onMessageEvent($event);

        /** @var Email $message */
        $message = $event->getMessage();

        $from = $message->getFrom();
        self::assertSame('sender@example.com', $from[0]->getAddress());

        $replyTo = $message->getReplyTo();
        self::assertSame('sender@example.com', $replyTo[0]->getAddress());
    }

    /**
     * @covers ::onMessageEvent
     */
    public function testHasFromAndReplyTo(): void
    {
        $email = new Email();

        $email
            ->from('sender@example.com')
            ->replyTo('reply@example.com')
            ->to('recipient@example.com')
            ->subject('Test message')
            ->text('Message body')
        ;

        $event = new MessageEvent($email, new Envelope($email->getFrom()[0], $email->getTo()), 'smtp://null');

        $this->subscriber->onMessageEvent($event);

        /** @var Email $message */
        $message = $event->getMessage();

        $from = $message->getFrom();
        self::assertSame('sender@example.com', $from[0]->getAddress());

        $replyTo = $message->getReplyTo();
        self::assertSame('reply@example.com', $replyTo[0]->getAddress());
    }

    /**
     * @covers ::onMessageEvent
     */
    public function testNoFrom(): void
    {
        $email = new Email();

        $email
            ->to('recipient@example.com')
            ->subject('Test message')
            ->text('Message body')
        ;

        $event = new MessageEvent($email, new Envelope(new Address('sender@example.com'), $email->getTo()), 'smtp://null');

        $this->subscriber->onMessageEvent($event);

        /** @var Email $message */
        $message = $event->getMessage();

        $from = $message->getFrom();
        self::assertSame('noreply@example.com', $from[0]->getAddress());

        $replyTo = $message->getReplyTo();
        self::assertSame('noreply@example.com', $replyTo[0]->getAddress());
    }

    /**
     * @covers ::onMessageEvent
     */
    public function testNotMessage(): void
    {
        $email = new Email();

        $email
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test message')
            ->text('Message body')
        ;

        $event = new MessageEvent(new RawMessage($email->toIterable()), new Envelope($email->getFrom()[0], $email->getTo()), 'smtp://null');

        $this->subscriber->onMessageEvent($event);

        $message = $event->getMessage();

        foreach ($message->toIterable() as $entry) {
            self::assertDoesNotMatchRegularExpression('/Reply-To: /', $entry);
        }
    }
}
