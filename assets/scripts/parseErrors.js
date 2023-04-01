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

import { alert } from '@utilities/messagebox';

/**
 * Retrieves all errors caught in the axios `catch` block.
 *
 * If there is a single error, shows a message box with it.
 * If there is a set of errors, returns them as an object where each property is a field name,
 * while its value is an error message.
 *
 * @param {Object} exception Axios exception
 *
 * @return {{ property: string, message: string }} List of errors
 */
export default (exception) => {
    const errors = {};

    if (typeof exception.response.data === 'object') {
        /** @var {{ property: string, message: string }} entry */
        for (const entry of exception.response.data) {
            errors[entry.property] = entry.message;
        }
    } else {
        alert(exception.response.data);
    }

    return errors;
};
