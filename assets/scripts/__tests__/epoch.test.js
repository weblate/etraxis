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

import * as epoch from '@utilities/epoch';

// Friday, April 8, 2005 10:40:00 PM (GMT)
const timestamp = 1113000000;

test('Date', () => {
    document.querySelector('html').lang = 'en_US';
    expect(epoch.date(timestamp)).toBe('4/8/2005');

    document.querySelector('html').lang = 'en_NZ';
    expect(epoch.date(timestamp)).toBe('8/04/2005');

    document.querySelector('html').lang = 'ru';
    expect(epoch.date(timestamp)).toBe('08.04.2005');
});

test('Time', () => {
    document.querySelector('html').lang = 'en_US';
    expect(epoch.time(timestamp)).toBe('10:40 PM');

    document.querySelector('html').lang = 'en_NZ';
    expect(epoch.time(timestamp)).toBe('10:40 pm');

    document.querySelector('html').lang = 'ru';
    expect(epoch.time(timestamp)).toBe('22:40');
});

test('Date & time', () => {
    document.querySelector('html').lang = 'en_US';
    expect(epoch.datetime(timestamp)).toBe('4/8/2005, 10:40 PM');

    document.querySelector('html').lang = 'en_NZ';
    expect(epoch.datetime(timestamp)).toBe('8/04/2005, 10:40 pm');

    document.querySelector('html').lang = 'ru';
    expect(epoch.datetime(timestamp)).toBe('08.04.2005, 22:40');
});
