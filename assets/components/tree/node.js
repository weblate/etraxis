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
 * @property {Array<string>} classes List of CSS classes to apply
 * @property {Array}         nodes   List of children
 */
export default class {
    constructor(id, title, classes = [], nodes = []) {
        this.id = id;
        this.title = title;
        this.classes = classes;
        this.nodes = nodes;
    }
}
