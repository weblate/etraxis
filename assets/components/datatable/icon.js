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
 * Icon in a DataTable column.
 *
 * @property {string}  id       Unique ID
 * @property {string}  title    Title
 * @property {string}  css      CSS class
 * @property {boolean} disabled Status
 */
export default class {
    constructor(id, title, css, disabled = false) {
        this.id = id;
        this.title = title;
        this.css = css;
        this.disabled = disabled;
    }
}
