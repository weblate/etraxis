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

import query from '@utilities/query';

test('Successful query', async () => {
    const result = await query('/months', 0, 10, '', {}, {});
    expect(result.total).toBe(12);
    expect(Array.isArray(result.rows)).toBeTruthy();
    expect(result.rows.length).toBe(5);
});

test('Failed query', async () => {
    expect.assertions(1);

    try {
        await query('/unknown', 0, 10, '', {}, {});
    } catch (exception) {
        expect(exception).toMatch('Unknown URL');
    }
});
