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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * "Sticky" locale.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly RequestStack $requestStack, protected readonly string $locale)
    {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'saveLocale',

            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['setLocale', 20]],
        ];
    }

    /**
     * Saves user's locale when they have been authenticated.
     */
    public function saveLocale(LoginSuccessEvent $event): void
    {
        /** @var \App\Entity\User $user */
        $user = $event->getUser();

        $this->requestStack->getSession()->set('_locale', $user->getLocale()->value);
    }

    /**
     * Overrides current locale with one saved in the session.
     */
    public function setLocale(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->hasPreviousSession()) {
            $request->setLocale($request->getSession()->get('_locale', $this->locale));
        }
    }
}
