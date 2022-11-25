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

namespace App\Controller;

/**
 * Marker interface for API controllers.
 */
interface ApiControllerInterface
{
    // Data types.
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_NUMBER  = 'number';
    public const TYPE_STRING  = 'string';
    public const TYPE_ARRAY   = 'array';
    public const TYPE_OBJECT  = 'object';

    // Parameter placement.
    public const PARAMETER_QUERY  = 'query';
    public const PARAMETER_HEADER = 'header';
    public const PARAMETER_PATH   = 'path';
    public const PARAMETER_COOKIE = 'cookie';
}
