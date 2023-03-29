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

namespace App\Serializer\Normalizer;

use App\Entity\Change;
use App\Entity\Comment;
use App\Entity\DecimalValue;
use App\Entity\Dependency;
use App\Entity\Event;
use App\Entity\FieldValue;
use App\Entity\File;
use App\Entity\Issue;
use App\Entity\ListItem;
use App\Entity\RelatedIssue;
use App\Entity\StringValue;
use App\Entity\TextValue;
use App\Entity\Transition;
use App\Entity\User;
use App\Entity\Watcher;
use App\LoginTrait;
use App\TransactionalTestCase;
use App\Utils\OpenApiInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \App\Serializer\Normalizer\IssueEntityNormalizer
 */
final class IssueEntityNormalizerTest extends TransactionalTestCase
{
    use LoginTrait;

    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = self::getContainer()->get('serializer');
    }

    /**
     * @covers ::normalize
     */
    public function testNormalize(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $expected = [
            'id'          => $issue->getId(),
            'subject'     => $issue->getSubject(),
            'state'       => $this->normalizer->normalize($issue->getState(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'author'      => $this->normalizer->normalize($issue->getAuthor(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'responsible' => null,
            'createdAt'   => $issue->getCreatedAt(),
            'changedAt'   => $issue->getChangedAt(),
            'closedAt'    => $issue->getClosedAt(),
            'resumesAt'   => $issue->getResumesAt(),
            'fullId'      => $issue->getFullId(),
            'project'     => $this->normalizer->normalize($issue->getProject(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'template'    => $this->normalizer->normalize($issue->getTemplate(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'cloned'      => $issue->isCloned(),
            'age'         => $issue->getAge(),
            'closed'      => $issue->isClosed(),
            'critical'    => $issue->isCritical(),
            'frozen'      => $issue->isFrozen(),
            'suspended'   => $issue->isSuspended(),
        ];

        self::assertSame($expected, $this->normalizer->normalize($issue, 'json', [AbstractNormalizer::GROUPS => 'info']));
    }

    /**
     * @covers ::normalize
     */
    public function testNormalizeWithActions(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneByEmail('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $related */
        [/* skipping */ , /* skipping */ , $related] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $watcher */
        [$watcher] = $this->doctrine->getRepository(Watcher::class)->findAllByIssue($issue);

        $events = $issue->getEvents()->map(fn (Event $event) => [
            'id'        => $event->getId(),
            'user'      => $this->normalizer->normalize($event->getUser(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'type'      => $event->getType()->value,
            'createdAt' => $event->getCreatedAt(),
            'parameter' => $event->getParameter(),
        ]);

        $transitions = $this->doctrine->getRepository(Transition::class)->findBy([
            'event' => $issue->getEvents()->map(fn (Event $event) => $event->getId())->toArray(),
        ]);

        $values   = $this->doctrine->getRepository(FieldValue::class)->findAllByIssue($issue, $user);
        $changes  = $this->doctrine->getRepository(Change::class)->findAllByIssue($issue, $user);
        $comments = $this->doctrine->getRepository(Comment::class)->findAllByIssue($issue, false);
        $files    = $this->doctrine->getRepository(File::class)->findAllByIssue($issue);

        $expected = [
            'id'           => $issue->getId(),
            'subject'      => $issue->getSubject(),
            'state'        => $this->normalizer->normalize($issue->getState(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'author'       => $this->normalizer->normalize($issue->getAuthor(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'responsible'  => null,
            'createdAt'    => $issue->getCreatedAt(),
            'changedAt'    => $issue->getChangedAt(),
            'closedAt'     => $issue->getClosedAt(),
            'resumesAt'    => $issue->getResumesAt(),
            'fullId'       => $issue->getFullId(),
            'project'      => $this->normalizer->normalize($issue->getProject(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'template'     => $this->normalizer->normalize($issue->getTemplate(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            'cloned'       => $issue->isCloned(),
            'age'          => $issue->getAge(),
            'closed'       => $issue->isClosed(),
            'critical'     => $issue->isCritical(),
            'frozen'       => $issue->isFrozen(),
            'suspended'    => $issue->isSuspended(),
            'events'       => $events->toArray(),
            'transitions'  => array_map(fn (Transition $transition) => $this->normalizer->normalize($transition, 'json', [AbstractNormalizer::GROUPS => 'info']), $transitions),
            'states'       => [
                $this->normalizer->normalize($issue->getTemplate()->getInitialState(), 'json', [AbstractNormalizer::GROUPS => 'info']),
            ],
            'assignees'    => [],
            'values'       => array_map(fn (FieldValue $value) => $this->normalizer->normalize($value, 'json', [AbstractNormalizer::GROUPS => 'info']), $values),
            'changes'      => array_map(fn (Change $change) => $this->normalizer->normalize($change, 'json', [AbstractNormalizer::GROUPS => 'info']), $changes),
            'watchers'     => [
                $this->normalizer->normalize($watcher, 'json', [AbstractNormalizer::GROUPS => 'info']),
            ],
            'comments'     => array_map(fn (Comment $comment) => $this->normalizer->normalize($comment, 'json', [AbstractNormalizer::GROUPS => 'info']), $comments),
            'files'        => array_map(fn (File $file) => $this->normalizer->normalize($file, 'json', [AbstractNormalizer::GROUPS => 'info']), $files),
            'dependencies' => [],
            'related'      => [
                $this->normalizer->normalize($related, 'json', [AbstractNormalizer::GROUPS => 'info']),
            ],
            'actions'      => [
                'clone'             => true,
                'update'            => true,
                'delete'            => true,
                'changeState'       => true,
                'reassign'          => false,
                'suspend'           => false,
                'resume'            => false,
                'addPublicComment'  => true,
                'addPrivateComment' => true,
                'attachFile'        => true,
                'deleteFile'        => true,
                'addDependency'     => false,
                'removeDependency'  => false,
                'addRelated'        => true,
                'removeRelated'     => true,
            ],
        ];

        self::assertSame($expected, $this->normalizer->normalize($issue, 'json', [
            AbstractNormalizer::GROUPS => 'info',
            OpenApiInterface::ACTIONS  => true,
        ]));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization(): void
    {
        $security     = self::getContainer()->get('security.authorization_checker');
        $tokenStorage = self::getContainer()->get('security.token_storage');

        $normalizer = new IssueEntityNormalizer(
            $security,
            $tokenStorage,
            $this->doctrine->getRepository(Issue::class),
            $this->doctrine->getRepository(FieldValue::class),
            $this->doctrine->getRepository(DecimalValue::class),
            $this->doctrine->getRepository(StringValue::class),
            $this->doctrine->getRepository(TextValue::class),
            $this->doctrine->getRepository(ListItem::class),
            $this->doctrine->getRepository(Change::class),
            $this->doctrine->getRepository(Watcher::class),
            $this->doctrine->getRepository(Comment::class),
            $this->doctrine->getRepository(File::class),
            $this->doctrine->getRepository(Dependency::class),
            $this->doctrine->getRepository(RelatedIssue::class),
        );

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        self::assertTrue($normalizer->supportsNormalization($issue));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
