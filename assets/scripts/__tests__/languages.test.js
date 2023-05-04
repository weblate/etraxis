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

import languages from '@utilities/languages';

test('Languages are sorted', () => {
    expect(languages()).toStrictEqual({
        bg: 'Български',
        cs: 'Čeština',
        de: 'Deutsch',
        en: 'English',
        es: 'Español',
        fr: 'Français',
        hu: 'Magyar',
        it: 'Italiano',
        ja: '日本語',
        lv: 'Latviešu',
        nl: 'Nederlands',
        pl: 'Polski',
        pt_BR: 'Português do Brasil',
        ro: 'Română',
        ru: 'Русский',
        sv: 'Svenska',
        tr: 'Türkçe'
    });
});
