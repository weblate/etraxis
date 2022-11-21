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

namespace App\MessageHandler\Users;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\User;
use App\LoginTrait;
use App\Message\AbstractCollectionQuery;
use App\Message\Users\GetUsersQuery;
use App\MessageBus\Contracts\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageHandler\Users\GetUsersQueryHandler
 */
final class GetUsersQueryHandlerTest extends WebTestCase
{
    use LoginTrait;

    private KernelBrowser     $client;
    private QueryBusInterface $queryBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client   = self::createClient();
        $this->queryBus = self::getContainer()->get(QueryBusInterface::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testDefault(): void
    {
        $this->loginUser('admin@example.com');

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine   = self::getContainer()->get('doctrine');
        $repository = $doctrine->getRepository(User::class);

        $expected = array_map(fn (User $user) => $user->getFullname(), $repository->findAll());
        $actual   = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testOffset(): void
    {
        $expected = [
            'Ted Berge',
            'Tony Buckridge',
            'Tracy Marquardt',
            'Vida Parker',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(30, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testLimit(): void
    {
        $expected = [
            'Albert Einstein',
            'Alyson Schinner',
            'Anissa Marvin',
            'Ansel Koepp',
            'Artem Rodygin',
            'Bell Kemmer',
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Carson Legros',
            'Carter Batz',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, 10, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::querySearch
     */
    public function testSearch(): void
    {
        $expected = [
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Dangelo Hill',
            'Derrick Tillman',
            'Dorcas Ernser',
            'Emmanuelle Bartell',
            'Hunter Stroman',
            'Jarrell Kiehn',
            'Juanita Goodwin',
            'Leland Doyle',
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, 'mAn', [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(10, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByEmail
     */
    public function testFilterByEmail(): void
    {
        $expected = [
            'Berenice O\'Connell',
            'Lucas O\'Connell',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetUsersQuery::USER_EMAIL => 'oCoNNel',
        ];

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByFullname
     */
    public function testFilterByFullname(): void
    {
        $expected = [
            'Berenice O\'Connell',
            'Lucas O\'Connell',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetUsersQuery::USER_FULLNAME => 'o\'cONneL',
        ];

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(2, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByDescription
     */
    public function testFilterByDescription(): void
    {
        $expected = [
            'Bell Kemmer',
            'Carter Batz',
            'Jarrell Kiehn',
            'Kailyn Bahringer',
            'Kyla Schultz',
            'Nikko Hills',
            'Tony Buckridge',
            'Tracy Marquardt',
            'Vida Parker',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetUsersQuery::USER_DESCRIPTION => 'sUPpOrT',
        ];

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(9, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByIsAdmin
     */
    public function testFilterByAdmin(): void
    {
        $expected = [
            'eTraxis Admin',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetUsersQuery::USER_IS_ADMIN => User::ROLE_ADMIN,
        ];

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(1, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByIsDisabled
     */
    public function testFilterByDisabled(): void
    {
        $expected = [
            'Ted Berge',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetUsersQuery::USER_IS_DISABLED => true,
        ];

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(1, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByProvider
     */
    public function testFilterByProvider(): void
    {
        $expected = [
            'Albert Einstein',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetUsersQuery::USER_PROVIDER => AccountProviderEnum::LDAP->value,
        ];

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(1, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryFilterByDescription
     * @covers ::queryFilterByEmail
     * @covers ::queryFilterByFullname
     */
    public function testCombinedFilter(): void
    {
        $expected = [
            'Bell Kemmer',
            'Berenice O\'Connell',
            'Dorcas Ernser',
            'Jeramy Mueller',
        ];

        $this->loginUser('admin@example.com');

        $filters = [
            GetUsersQuery::USER_EMAIL       => 'eR',
            GetUsersQuery::USER_FULLNAME    => '',
            GetUsersQuery::USER_DESCRIPTION => 'a+',
        ];

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters, $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(4, $collection->getTotal());

        $actual = array_map(fn (User $user) => $user->getFullname(), $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testFilterByUnknown(): void
    {
        $this->loginUser('admin@example.com');

        $filters = [
            'unknown' => null,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, $filters);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortById(): void
    {
        $expected = [
            ['eTraxis Admin',       'Built-in administrator'],
            ['Artem Rodygin',       null],
            ['Albert Einstein',     null],
            ['Ted Berge',           'Disabled account'],
            ['Lucas O\'Connell',    'Client A+B+C'],
            ['Carson Legros',       'Client A+B'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Derrick Tillman',     'Client B+C'],
            ['Hunter Stroman',      'Client A'],
            ['Alyson Schinner',     'Client B'],
            ['Denis Murazik',       'Client C'],
            ['Leland Doyle',        'Manager A+B+C+D'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Carolyn Hill',        'Manager B+C'],
            ['Dangelo Hill',        'Manager A'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Juanita Goodwin',     'Manager C'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Lola Abshire',        'Developer A+B'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Christy McDermott',   'Developer A'],
            ['Anissa Marvin',       'Developer B'],
            ['Millie Bogisich',     'Developer C'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetUsersQuery::USER_ID => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, 25, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());

        $actual = array_map(fn (User $user) => [
            $user->getFullname(),
            $user->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByEmail(): void
    {
        $expected = [
            ['eTraxis Admin',       'Built-in administrator'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Anissa Marvin',       'Developer B'],
            ['Artem Rodygin',       null],
            ['Alyson Schinner',     'Client B'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Bell Kemmer',         'Support Engineer A+B'],
            ['Carolyn Hill',        'Manager B+C'],
            ['Carter Batz',         'Support Engineer A+C'],
            ['Christy McDermott',   'Developer A'],
            ['Carson Legros',       'Client A+B'],
            ['Dangelo Hill',        'Manager A'],
            ['Denis Murazik',       'Client C'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Derrick Tillman',     'Client B+C'],
            ['Albert Einstein',     null],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Hunter Stroman',      'Client A'],
            ['Juanita Goodwin',     'Manager C'],
            ['Jarrell Kiehn',       'Support Engineer A, Developer B, Manager C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Kailyn Bahringer',    'Support Engineer B+C'],
            ['Kyla Schultz',        'Support Engineer A'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetUsersQuery::USER_EMAIL => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, 25, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());

        $actual = array_map(fn (User $user) => [
            $user->getFullname(),
            $user->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByFullname(): void
    {
        $expected = [
            ['Albert Einstein',     null],
            ['Alyson Schinner',     'Client B'],
            ['Anissa Marvin',       'Developer B'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Artem Rodygin',       null],
            ['Bell Kemmer',         'Support Engineer A+B'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Carolyn Hill',        'Manager B+C'],
            ['Carson Legros',       'Client A+B'],
            ['Carter Batz',         'Support Engineer A+C'],
            ['Christy McDermott',   'Developer A'],
            ['Dangelo Hill',        'Manager A'],
            ['Denis Murazik',       'Client C'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Derrick Tillman',     'Client B+C'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['eTraxis Admin',       'Built-in administrator'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Hunter Stroman',      'Client A'],
            ['Jarrell Kiehn',       'Support Engineer A, Developer B, Manager C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Juanita Goodwin',     'Manager C'],
            ['Kailyn Bahringer',    'Support Engineer B+C'],
            ['Kyla Schultz',        'Support Engineer A'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, 25, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());

        $actual = array_map(fn (User $user) => [
            $user->getFullname(),
            $user->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByDescription(): void
    {
        $expected = [
            ['Artem Rodygin',       null],
            ['Albert Einstein',     null],
            ['eTraxis Admin',       'Built-in administrator'],
            ['Hunter Stroman',      'Client A'],
            ['Carson Legros',       'Client A+B'],
            ['Lucas O\'Connell',    'Client A+B+C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Alyson Schinner',     'Client B'],
            ['Derrick Tillman',     'Client B+C'],
            ['Denis Murazik',       'Client C'],
            ['Christy McDermott',   'Developer A'],
            ['Lola Abshire',        'Developer A+B'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Anissa Marvin',       'Developer B'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Millie Bogisich',     'Developer C'],
            ['Ted Berge',           'Disabled account'],
            ['Dangelo Hill',        'Manager A'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Leland Doyle',        'Manager A+B+C+D'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Carolyn Hill',        'Manager B+C'],
            ['Juanita Goodwin',     'Manager C'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetUsersQuery::USER_DESCRIPTION => AbstractCollectionQuery::SORT_ASC,
            GetUsersQuery::USER_FULLNAME    => AbstractCollectionQuery::SORT_DESC,
        ];

        $query = new GetUsersQuery(0, 25, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());

        $actual = array_map(fn (User $user) => [
            $user->getFullname(),
            $user->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByAdmin(): void
    {
        $expected = [
            ['eTraxis Admin',       'Built-in administrator'],
            ['Albert Einstein',     null],
            ['Alyson Schinner',     'Client B'],
            ['Anissa Marvin',       'Developer B'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Artem Rodygin',       null],
            ['Bell Kemmer',         'Support Engineer A+B'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Carolyn Hill',        'Manager B+C'],
            ['Carson Legros',       'Client A+B'],
            ['Carter Batz',         'Support Engineer A+C'],
            ['Christy McDermott',   'Developer A'],
            ['Dangelo Hill',        'Manager A'],
            ['Denis Murazik',       'Client C'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Derrick Tillman',     'Client B+C'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Hunter Stroman',      'Client A'],
            ['Jarrell Kiehn',       'Support Engineer A, Developer B, Manager C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Juanita Goodwin',     'Manager C'],
            ['Kailyn Bahringer',    'Support Engineer B+C'],
            ['Kyla Schultz',        'Support Engineer A'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetUsersQuery::USER_IS_ADMIN => AbstractCollectionQuery::SORT_DESC,
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, 25, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());

        $actual = array_map(fn (User $user) => [
            $user->getFullname(),
            $user->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByProvider(): void
    {
        $expected = [
            ['Albert Einstein',     null],
            ['Vida Parker',         'Support Engineer B'],
            ['Tracy Marquardt',     'Support Engineer A+B+C'],
            ['Tony Buckridge',      'Support Engineer C'],
            ['Ted Berge',           'Disabled account'],
            ['Nikko Hills',         'Support Engineer A+B, Developer C'],
            ['Millie Bogisich',     'Developer C'],
            ['Lucas O\'Connell',    'Client A+B+C'],
            ['Lola Abshire',        'Developer A+B'],
            ['Leland Doyle',        'Manager A+B+C+D'],
            ['Kyla Schultz',        'Support Engineer A'],
            ['Kailyn Bahringer',    'Support Engineer B+C'],
            ['Juanita Goodwin',     'Manager C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Jarrell Kiehn',       'Support Engineer A, Developer B, Manager C'],
            ['Hunter Stroman',      'Client A'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['eTraxis Admin',       'Built-in administrator'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Derrick Tillman',     'Client B+C'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Denis Murazik',       'Client C'],
            ['Dangelo Hill',        'Manager A'],
            ['Christy McDermott',   'Developer A'],
        ];

        $this->loginUser('admin@example.com');

        $order = [
            GetUsersQuery::USER_PROVIDER => AbstractCollectionQuery::SORT_DESC,
            GetUsersQuery::USER_FULLNAME => AbstractCollectionQuery::SORT_DESC,
        ];

        $query = new GetUsersQuery(0, 25, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());

        $actual = array_map(fn (User $user) => [
            $user->getFullname(),
            $user->getDescription(),
        ], $collection->getItems());

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::__invoke
     * @covers ::queryOrder
     */
    public function testSortByUnknown(): void
    {
        $this->loginUser('admin@example.com');

        $order = [
            'unknown' => AbstractCollectionQuery::SORT_ASC,
        ];

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT, null, [], $order);

        /** @var \App\Message\Collection $collection */
        $collection = $this->queryBus->execute($query);

        self::assertSame(34, $collection->getTotal());
    }

    /**
     * @covers ::__invoke
     */
    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You do not have required permissions.');

        $this->loginUser('artem@example.com');

        $query = new GetUsersQuery(0, AbstractCollectionQuery::MAX_LIMIT);

        $this->queryBus->execute($query);
    }
}
