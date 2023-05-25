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

import * as convert from '@utilities/convert';

const nullValue = null;
let undefinedValue;

test('toBoolean', () => {
    expect(convert.toBoolean(nullValue)).toBe(false);
    expect(convert.toBoolean(undefinedValue)).toBe(false);
    expect(convert.toBoolean('')).toBe(false);
    expect(convert.toBoolean(' ')).toBe(false);
    expect(convert.toBoolean(0)).toBe(false);
    expect(convert.toBoolean(1)).toBe(true);
    expect(convert.toBoolean(false)).toBe(false);
    expect(convert.toBoolean(true)).toBe(true);
});

test('toString', () => {
    expect(convert.toString(nullValue)).toBeNull();
    expect(convert.toString(undefinedValue)).toBeNull();
    expect(convert.toString('')).toBeNull();
    expect(convert.toString(' ')).toBeNull();
    expect(convert.toString(0)).toBe('0');
    expect(convert.toString(1)).toBe('1');
    expect(convert.toString(' test ')).toBe('test');
});

test('toNumber', () => {
    expect(convert.toNumber(nullValue)).toBeNull();
    expect(convert.toNumber(undefinedValue)).toBeNull();
    expect(convert.toNumber('')).toBeNull();
    expect(convert.toNumber(' ')).toBeNull();
    expect(convert.toNumber('0')).toBe(0);
    expect(convert.toNumber('1')).toBe(1);
    expect(convert.toNumber(' 00.0 ')).toBe(0);
    expect(convert.toNumber(' 01.0 ')).toBe(1);
    expect(convert.toNumber(0)).toBe(0);
    expect(convert.toNumber(1)).toBe(1);
});
