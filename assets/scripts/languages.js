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

/**
 * List of supported languages.
 */
const languages = {
    en: 'English', // English
    ru: 'Русский' // Russian
};

/**
 * Returns list of supported languages, sorted by their name.
 *
 * @return {Object} Keys are locales, values are language names
 */
export default () => Object.fromEntries(
    Object.entries(languages).sort((language1, language2) => language1[1].localeCompare(language2[1]))
);
