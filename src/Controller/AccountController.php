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

namespace App\Controller;

use App\Entity\Template;
use App\Entity\User;
use App\Message\UserSettings as Message;
use App\MessageBus\Contracts\CommandBusInterface;
use App\MessageBus\Contracts\QueryBusInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * API controller for user's account management.
 */
#[Route('/api/my')]
#[IsGranted('ROLE_USER')]
#[API\Tag('My Account')]
#[API\Response(response: 401, description: 'Full authentication is required to access this resource.')]
#[API\Response(response: 429, description: 'API rate limit exceeded.')]
class AccountController extends AbstractController implements ApiControllerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        protected readonly CommandBusInterface $commandBus,
        protected readonly QueryBusInterface $queryBus
    ) {
    }

    /**
     * Returns profile of the current user.
     */
    #[Route('/profile', name: 'api_profile_get', methods: [Request::METHOD_GET])]
    #[API\Response(response: 200, description: 'Success.', content: new Model(type: User::class, groups: ['info', 'profile']))]
    public function getProfile(NormalizerInterface $normalizer): JsonResponse
    {
        return $this->json($normalizer->normalize($this->getUser(), 'json', [AbstractNormalizer::GROUPS => ['info', 'profile']]));
    }

    /**
     * Updates profile of the current user.
     */
    #[Route('/profile', name: 'api_profile_update', methods: [Request::METHOD_PATCH])]
    #[API\RequestBody(content: new Model(type: User::class, groups: ['settings']))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 409, description: 'Resource already exists.')]
    public function updateProfile(Request $request, SerializerInterface $serializer): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Message\UpdateProfileCommand $profile */
        $profile = $serializer->deserialize($request->getContent() ?: '{}', Message\UpdateProfileCommand::class, 'json', [
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                Message\UpdateProfileCommand::class => [
                    'email'    => $user->getEmail(),
                    'fullname' => $user->getFullname(),
                ],
            ],
        ]);

        /** @var Message\UpdateSettingsCommand $settings */
        $settings = $serializer->deserialize($request->getContent() ?: '{}', Message\UpdateSettingsCommand::class, 'json', [
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                Message\UpdateSettingsCommand::class => [
                    'locale'   => $user->getLocale(),
                    'theme'    => $user->getTheme(),
                    'darkMode' => $user->isDarkMode(),
                    'timezone' => $user->getTimezone(),
                ],
            ],
        ]);

        if (!$user->isAccountExternal()) {
            $this->commandBus->handle($profile);
        }

        $this->commandBus->handle($settings);

        return $this->json(null);
    }

    /**
     * Sets new password for the current user.
     */
    #[Route('/password', name: 'api_password', methods: [Request::METHOD_PUT])]
    #[API\RequestBody(content: new API\JsonContent(
        type: self::TYPE_OBJECT,
        properties: [
            new API\Property(property: 'current', type: self::TYPE_STRING, description: 'Current password.'),
            new API\Property(property: 'new', type: self::TYPE_STRING, description: 'New password.'),
        ]
    ))]
    #[API\Response(response: 200, description: 'Success.')]
    #[API\Response(response: 400, description: 'The request is malformed.')]
    #[API\Response(response: 403, description: 'Access denied.')]
    public function setPassword(Request $request, UserPasswordHasherInterface $hasher, TranslatorInterface $translator): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = $request->toArray();

        if (!$user->isAccountExternal() && !$hasher->isPasswordValid($user, $data['current'] ?? '')) {
            return $this->json($translator->trans('Invalid credentials.', [], 'security'), Response::HTTP_BAD_REQUEST);
        }

        $command = new Message\SetPasswordCommand($user->getId(), $data['new'] ?? '');

        $this->commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns list of templates which specified user can use to create new issue.
     */
    #[Route('/templates', name: 'api_profile_templates', methods: [Request::METHOD_GET])]
    #[API\Response(response: 200, description: 'Success.', content: new API\JsonContent(
        type: self::TYPE_ARRAY,
        items: new API\Items(ref: new Model(type: Template::class, groups: ['profile']))
    ))]
    public function getTemplates(NormalizerInterface $normalizer): JsonResponse
    {
        /** @var User $user */
        $user  = $this->getUser();
        $query = new Message\GetTemplatesQuery($user->getId());

        $collection = $this->queryBus->execute($query);

        return $this->json($normalizer->normalize($collection, 'json', [AbstractNormalizer::GROUPS => 'profile']));
    }
}
