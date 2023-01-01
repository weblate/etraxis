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

namespace App\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Security\AuthenticationEntryPoint
 */
final class AuthenticationEntryPointTest extends TestCase
{
    private AuthenticationEntryPointInterface $entryPoint;

    protected function setUp(): void
    {
        parent::setUp();

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnMap([
                ['login', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/login'],
            ])
        ;

        $this->entryPoint = new AuthenticationEntryPoint($urlGenerator);
    }

    /**
     * @covers ::start
     */
    public function testStart(): void
    {
        $request = new Request();

        $response = $this->entryPoint->start($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertTrue($response->isRedirect('/login'));
    }

    /**
     * @covers ::start
     */
    public function testStartAjaxNoException(): void
    {
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->entryPoint->start($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertEmpty(json_decode($response->getContent(), true));
    }

    /**
     * @covers ::start
     */
    public function testStartAjaxWithException(): void
    {
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $exception = new AuthenticationException('Invalid credentials.');

        $response = $this->entryPoint->start($request, $exception);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Invalid credentials.', json_decode($response->getContent(), true));
    }
}
