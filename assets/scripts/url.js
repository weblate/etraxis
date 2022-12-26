//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2022 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

/**
 * Returns absolute URL of the specified relative one.
 *
 * @param {string} url Relative URL (should start with '/')
 *
 * @return {string} Absolute URL
 */
export default (url) => window.location.origin + url;
