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
 * Returns user's locale.
 *
 * @return {string} Locale
 */
export default () => (document.querySelector('html').getAttribute('lang') || 'en').replace('_', '-');
