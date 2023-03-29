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
 * Generates unique ID.
 *
 * @param {string} prefix Optional prefix for the ID
 *
 * @return {string} Generated ID
 */
export default (prefix = '__etraxis_') => prefix + Math.random().toString(36).substring(2);
