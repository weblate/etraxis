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

namespace App\DataFixtures;

/**
 * Trait with existing user accounts.
 */
trait UsersTrait
{
    public array $manager1 = [
        'a' => 'user:ldoyle@example.com',
        'b' => 'user:ldoyle@example.com',
        'c' => 'user:jkiehn@example.com',
    ];

    public array $manager2 = [
        'a' => 'user:dorcas.ernser@example.com',
        'b' => 'user:dorcas.ernser@example.com',
        'c' => 'user:berenice.oconnell@example.com',
    ];

    public array $manager3 = [
        'a' => 'user:berenice.oconnell@example.com',
        'b' => 'user:carolyn.hill@example.com',
        'c' => 'user:carolyn.hill@example.com',
    ];

    public array $developer1 = [
        'a' => 'user:fdooley@example.com',
        'b' => 'user:jkiehn@example.com',
        'c' => 'user:nhills@example.com',
    ];

    public array $developer2 = [
        'a' => 'user:labshire@example.com',
        'b' => 'user:labshire@example.com',
        'c' => 'user:dquigley@example.com',
    ];

    public array $developer3 = [
        'a' => 'user:dquigley@example.com',
        'b' => 'user:akoepp@example.com',
        'c' => 'user:akoepp@example.com',
    ];

    public array $support1 = [
        'a' => 'user:jkiehn@example.com',
        'b' => 'user:tmarquardt@example.com',
        'c' => 'user:cbatz@example.com',
    ];

    public array $support2 = [
        'a' => 'user:nhills@example.com',
        'b' => 'user:nhills@example.com',
        'c' => 'user:tmarquardt@example.com',
    ];

    public array $support3 = [
        'a' => 'user:tmarquardt@example.com',
        'b' => 'user:kbahringer@example.com',
        'c' => 'user:cbatz@example.com',
    ];

    public array $client1 = [
        'a' => 'user:lucas.oconnell@example.com',
        'b' => 'user:lucas.oconnell@example.com',
        'c' => 'user:lucas.oconnell@example.com',
    ];

    public array $client2 = [
        'a' => 'user:clegros@example.com',
        'b' => 'user:clegros@example.com',
        'c' => 'user:jmueller@example.com',
    ];

    public array $client3 = [
        'a' => 'user:jmueller@example.com',
        'b' => 'user:dtillman@example.com',
        'c' => 'user:dtillman@example.com',
    ];
}
