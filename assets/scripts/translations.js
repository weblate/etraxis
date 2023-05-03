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

const FALLBACK_LOCALE = 'en';

/**
 * @internal
 *
 * @see default export below
 */
const loadTranslations = (locale) => {
    // List of translation domains to return.
    const domains = [
        'buttons',
        'datatable',
        'fields',
        'groups',
        'issues',
        'messages',
        'passwords',
        'projects',
        'security',
        'states',
        'templates',
        'users'
    ];

    const fallback = locale === FALLBACK_LOCALE ? {} : loadTranslations(FALLBACK_LOCALE);

    const translations = [];

    for (const domain of domains) {
        const module = require(`@translations/${domain}/${domain}.${locale}.yaml`);
        translations.push(module.default);
    }

    return Object.assign(fallback, ...translations);
};

/**
 * Returns translations for specified locale.
 *
 * @param {string} locale Locale
 *
 * @return {Object} Key is a prompt ID, value is a prompt translation
 */
export default (locale) => loadTranslations(locale);
