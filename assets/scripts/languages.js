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
    bg: 'Български', // Bulgarian
    cs: 'Čeština', // Czech
    de: 'Deutsch', // German
    en: 'English', // English
    es: 'Español', // Spanish
    fr: 'Français', // French
    hu: 'Magyar', // Hungarian
    it: 'Italiano', // Italian
    ja: '日本語', // Japanese
    lv: 'Latviešu', // Latvian
    nl: 'Nederlands', // Dutch
    pl: 'Polski', // Polish
    pt_BR: 'Português do Brasil', // Portuguese (Brazil)
    ro: 'Română', // Romanian
    ru: 'Русский', // Russian
    sv: 'Svenska', // Swedish
    tr: 'Türkçe' // Turkish
};

/**
 * Returns list of supported languages, sorted by their name.
 *
 * @return {Object} Keys are locales, values are language names
 */
export default () => Object.fromEntries(
    Object.entries(languages).sort((language1, language2) => language1[1].localeCompare(language2[1]))
);
