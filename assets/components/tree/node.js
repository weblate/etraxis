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
 * A tree node.
 *
 * @property {number|string} id      Unique ID
 * @property {string}        title   Title
 * @property {boolean}       enabled Whether the node is enabled
 * @property {Array}         nodes   List of children
 */
export default class {
    constructor(id, title, enabled = true, nodes = []) {
        this.id = id;
        this.title = title;
        this.enabled = enabled;
        this.nodes = nodes;
    }
}
