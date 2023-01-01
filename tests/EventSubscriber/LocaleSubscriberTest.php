<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  You should have received a copy of the GNU General Public License
//  along with the file. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\EventSubscriber;

use App\Entity\Enums\LocaleEnum;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @internal
 *
 * @coversDefaultClass \App\EventSubscriber\LocaleSubscriber
 */
final class LocaleSubscriberTest extends WebTestCase
{
    private ?ManagerRegistry $doctrine;
    private RequestStack     $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine     = self::getContainer()->get('doctrine');
        $this->requestStack = self::getContainer()->get('request_stack');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $this->requestStack->push($request);
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $expected = [
            LoginSuccessEvent::class,
            KernelEvents::REQUEST,
        ];

        self::assertSame($expected, array_keys(LocaleSubscriber::getSubscribedEvents()));
    }

    /**
     * @covers ::saveLocale
     */
    public function testSaveLocale(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $this->requestStack->getSession();

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);
        $user->setLocale(LocaleEnum::Russian);

        $authenticator = $this->createMock(AuthenticatorInterface::class);

        $passport = $this->createMock(Passport::class);
        $passport
            ->method('getUser')
            ->willReturn($user)
        ;

        $token = $this->createMock(TokenInterface::class);

        $event = new LoginSuccessEvent($authenticator, $passport, $token, $request, null, 'main');

        self::assertNull($session->get('_locale'));

        $object = new LocaleSubscriber($this->requestStack, 'en');
        $object->saveLocale($event);

        self::assertSame('ru', $session->get('_locale'));
    }

    /**
     * @covers ::setLocale
     */
    public function testSetLocaleFromSession(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $this->requestStack->getSession();

        $request->cookies->set($session->getName(), $session->getId());
        $session->set('_locale', 'ja');

        $event = new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $object = new LocaleSubscriber($this->requestStack, 'ru');
        $object->setLocale($event);

        self::assertSame('ja', $event->getRequest()->getLocale());
    }

    /**
     * @covers ::setLocale
     */
    public function testSetLocaleFromDefault(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $this->requestStack->getSession();

        $request->cookies->set($session->getName(), $session->getId());

        $event = new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $object = new LocaleSubscriber($this->requestStack, 'ru');
        $object->setLocale($event);

        self::assertSame('ru', $event->getRequest()->getLocale());
    }
}
