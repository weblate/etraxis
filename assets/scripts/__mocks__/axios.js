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

export default {
    get(url, config = { params: {} }) {
        return new Promise((resolve, reject) => {
            if (url === '/months') {
                const data = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                const offset = config.params.offset || 0;
                const limit = config.params.limit || 10;

                resolve({
                    data: {
                        total: data.length,
                        items: data.slice(offset, offset + limit)
                    }
                });
            } else if (url === '/weekdays') {
                const data = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                const offset = config.params.offset || 0;
                const limit = config.params.limit || 10;

                resolve({
                    data: {
                        total: data.length,
                        items: data.slice(offset, offset + limit)
                    }
                });
            } else {
                const error = new Error('Exception');
                error.response = { data: 'Unknown URL' };
                reject(error);
            }
        });
    }
};
