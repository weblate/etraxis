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
            default: true
        }
    },

    emits: ["submit", "cancel"],

    expose: ["open", "close"],

    mounted() {
        // Cancel the dialog when the "Esc" is pressed.
        this.$el.addEventListener("cancel", (event) => {
            event.preventDefault();
            this.onCancel();
        });
    },

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
         * @property {number} offsetX,offsetY Dialog's current position
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
         * @property {{top: string, left: string}} offset Dialog position
         */
        offset() {
            return {
                left: this.offsetX + "px",
                top: this.offsetY + "px"
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

            let x = event.clientX - this.cursorX;
            let y = event.clientY - this.cursorY;

            this.cursorX = event.clientX;
            this.cursorY = event.clientY;

            this.offsetX = this.offsetX + x * 2;
            this.offsetY = this.offsetY + y * 2;
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
    }
};
