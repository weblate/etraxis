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

import locale from '@utilities/locale';

/**
 * Converts Unix Epoch timestamp to human-readable localized date.
 *
 * @param {number} timestamp Unix Epoch timestamp
 *
 * @return {string} Localized date
 */
export const date = (timestamp) => {
    const date = new Date(0);
    date.setUTCSeconds(timestamp);

    return date.toLocaleDateString(locale(), {
        day: 'numeric',
        month: 'numeric',
        year: 'numeric'
    });
};

/**
 * Converts Unix Epoch timestamp to human-readable localized time.
 *
 * @param {number} timestamp Unix Epoch timestamp
 *
 * @return {string} Localized time
 */
export const time = (timestamp) => {
    const date = new Date(0);
    date.setUTCSeconds(timestamp);

    return date.toLocaleTimeString(locale(), {
        hour: 'numeric',
        minute: 'numeric'
    });
};

/**
 * Converts Unix Epoch timestamp to human-readable localized date and time.
 *
 * @param {number} timestamp Unix Epoch timestamp
 *
 * @return {string} Localized date and time
 */
export const datetime = (timestamp) => {
    const date = new Date(0);
    date.setUTCSeconds(timestamp);

    return date.toLocaleString(locale(), {
        day: 'numeric',
        month: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: 'numeric'
    });
};
