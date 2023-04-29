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

import locale from '@utilities/locale';

test('Simplified locale', () => {
    document.querySelector('html').lang = 'ja';
    expect(locale()).toBe('ja');
});

test('Extended locale', () => {
    document.querySelector('html').lang = 'pt_BR';
    expect(locale()).toBe('pt-BR');
});

test('Missing locale', () => {
    document.querySelector('html').removeAttribute('lang');
    expect(locale()).toBe('en');
});
