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

import axios from 'axios';

/**
 * Loads all existing resources from specified API endpoint.
 *
 * @param {string} url Absolute URL of the endpoint
 *
 * @return {Array} List of loaded resources
 */
export default async (url) => {
    // Make initial request.
    const response = await axios.get(url);

    // We've got all the resources at once.
    if (response.data.items.length === response.data.total) {
        return response.data.items;
    }

    // There is a limit on how many resources are returned at once.
    const limit = response.data.items.length;

    // Determine how many requests it takes to load all the resources (includes the initial request).
    const count = Math.ceil(response.data.total / limit);

    // Prepare remaining requests as an asynchronous bunch.
    const promises = [];

    for (let i = 1; i < count; i++) {
        promises.push(axios.get(url, { params: { offset: i * limit } }));
    }

    // Send the remaining requests.
    let results = await Promise.all(promises);
    results = results.map((result) => result.data.items);

    // Merge all the results into one list.
    return response.data.items.concat(...results);
};
