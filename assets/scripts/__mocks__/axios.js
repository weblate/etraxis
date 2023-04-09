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
    get(url) {
        return new Promise((resolve, reject) => {
            if (url === '/months') {
                resolve({
                    data: {
                        total: 12,
                        items: ['January', 'February', 'March', 'April', 'May']
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
