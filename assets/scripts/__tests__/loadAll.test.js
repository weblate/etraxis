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

import loadAll from '@utilities/loadAll';

test('Successful single query', async () => {
    const result = await loadAll('/weekdays');
    expect(Array.isArray(result)).toBeTruthy();
    expect(result.length).toBe(7);
    expect(result.toString()).toBe('Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday');
});

test('Successful multiple queries', async () => {
    const result = await loadAll('/months');
    expect(Array.isArray(result)).toBeTruthy();
    expect(result.length).toBe(12);
    expect(result.toString()).toBe('January,February,March,April,May,June,July,August,September,October,November,December');
});

test('Failed query', async () => {
    expect.assertions(1);

    try {
        await loadAll('/unknown');
    } catch (exception) {
        expect(exception.response.data).toBe('Unknown URL');
    }
});
