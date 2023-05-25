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
 * Converts arbitrary value to its boolean representation.
 *
 * @param {*} value Original value
 * @return {boolean} Converted value
 */
export const toBoolean = (value) => typeof value === 'string' ? !!value.trim() : !!value;

/**
 * Converts arbitrary value to its string representation.
 *
 * @param {*} value Original value
 * @return {string|null} Converted value
 */
export const toString = (value) => {
    const string = (value ?? '').toString().trim();
    return string.length === 0 ? null : string;
};

/**
 * Converts arbitrary value to its number representation.
 *
 * @param {*} value Original value
 * @return {number|null} Converted value
 */
export const toNumber = (value) => {
    const number = parseInt(toString(value));
    return isNaN(number) ? null : number;
};
