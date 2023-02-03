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
 * Modal dialog.
 */
export default {
    props: {
        /**
         * @property {string} header Header text
         */
        header: {
            type: String,
            required: true
        },

        /**
         * @property {boolean} autoClose Automatically close the dialog on cancellation
         */
        autoClose: {
            type: Boolean,
            default: false
        }
    },

    emits: ["submit", "cancel"],

    expose: ["open", "close"],

    data: () => ({
        /**
         * @property {boolean} isActive Whether the modal is active or hidden
         */
        isActive: false,

        /**
         * @property {number} cursorX,cursorX Initial mouse coordinates where the dialog was started to drag
         */
        cursorX: 0,
        cursorY: 0,

        /**
         * @property {number} offsetX,offsetY Current offset of the dialog's position
         */
        offsetX: 0,
        offsetY: 0
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} offset Offset of the dialog's position
         */
        offset() {
            return {
                transform: `translate(${this.offsetX}px, ${this.offsetY}px)`
            };
        }
    },

    methods: {
        /**
         * @public Opens the dialog.
         */
        open() {
            this.$el.showModal();
            this.isActive = true;

            this.offsetX = 0;
            this.offsetY = 0;
        },

        /**
         * @public Closes the dialog.
         */
        close() {
            this.$el.close();
            this.isActive = false;
        },

        /**
         * Initialises the dialog's dragging when the mouse button is pressed down while being over the dialog's header.
         *
         * @param {MouseEvent} event
         */
        onMouseDown(event) {
            event.preventDefault();

            this.cursorX = event.clientX;
            this.cursorY = event.clientY;

            document.onmousemove = this.onMouseMove;
            document.onmouseup = this.onMouseUp;
        },

        /**
         * Moves the dialog into new position if it's in the dragging mode.
         *
         * @param {MouseEvent} event
         */
        onMouseMove(event) {
            event.preventDefault();

            this.offsetX += event.clientX - this.cursorX;
            this.offsetY += event.clientY - this.cursorY;

            this.cursorX = event.clientX;
            this.cursorY = event.clientY;
        },

        /**
         * Stops dragging the dialog (the mouse button is released).
         */
        onMouseUp() {
            document.onmousemove = null;
            document.onmouseup = null;
        },

        /**
         * Submits the dialog.
         */
        onSubmit() {
            this.$emit("submit");
        },

        /**
         * Cancels the dialog.
         */
        onCancel() {
            this.$emit("cancel");

            if (this.autoClose) {
                this.close();
            }
        }
    },

    mounted() {
        // Cancel the dialog when the "Esc" is pressed.
        this.$el.addEventListener("cancel", (event) => {
            event.preventDefault();
            this.onCancel();
        });
    }
};
