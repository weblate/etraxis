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

import axios from "axios";

/**
 * Makes an API call for DataTable component.
 *
 * @param {string} url     Absolute URL of the call
 * @param {number} offset  Zero-based index of the first entry to return
 * @param {number} limit   Maximum number of entries to return
 * @param {string} search  Current value of the search
 * @param {Object} filters Current values of the column filters ({ "column id": value })
 * @param {Object} order   Current sorting order ({ "column id": "asc"|"desc" })
 *
 * @return {Object} An object with the following properties:
 *   {number} total - total number of entries in the source
 *   {Array}  rows  - returned entries
 */
export default async (url, offset, limit, search, filters, order) => {
    let params = {
        offset,
        limit,
        search,
        filters: JSON.stringify(filters),
        order: JSON.stringify(order)
    };

    try {
        let response = await axios.get(url, { params });

        return {
            total: response.data.total,
            rows: response.data.items
        };
    } catch (exception) {
        throw exception.response.data;
    }
};
