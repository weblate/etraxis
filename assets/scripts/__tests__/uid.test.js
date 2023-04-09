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

import { expect, test } from '@jest/globals';

import generateUid from '@utilities/uid';

test('UID generated with default prefix', () => {
    expect(generateUid()).toMatch(/__etraxis_[0-9a-z]+/);
});

test('UID generated with alternative prefix', () => {
    expect(generateUid('test-')).toMatch(/test-[0-9a-z]+/);
});

test('UID generated with empty prefix', () => {
    expect(generateUid('')).toMatch(/[0-9a-z]+/);
});
