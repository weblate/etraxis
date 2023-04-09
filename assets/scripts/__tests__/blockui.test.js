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

/* eslint-disable sonarjs/no-duplicate-string */

import { beforeEach, expect, test } from '@jest/globals';

import { block, unblock } from '@utilities/blockui';

const getDialogElement = () => {
    const nodes = document.querySelectorAll('body dialog p');
    return nodes.length ? nodes[nodes.length - 1] : null;
};

HTMLDialogElement.prototype.showModal = function () {
};

HTMLDialogElement.prototype.close = function () {
    const element = document.getElementById(this.id);
    /** @type {Event} */
    const event = new Event('close');
    element.dispatchEvent(event);
};

beforeEach(() => {
    window.i18n = {
        'text.please_wait': 'Please wait'
    };

    document.body.innerHTML = '<body></body>';
});

test('Blocking with default message', () => {
    block();
    const element = getDialogElement();
    expect(element).not.toBeNull();
    expect(element.innerHTML).toBe('Please wait');

    unblock();
    expect(getDialogElement()).toBeNull();
});

test('Blocking with custom message', () => {
    block('Processing');
    const element = getDialogElement();
    expect(element).not.toBeNull();
    expect(element.innerHTML).toBe('Processing');

    unblock();
    expect(getDialogElement()).toBeNull();
});

test('Nested blocks', () => {
    block('First');
    let element = getDialogElement();
    expect(element).not.toBeNull();
    expect(element.innerHTML).toBe('First');

    block('Second');
    element = getDialogElement();
    expect(element).not.toBeNull();
    expect(element.innerHTML).toBe('Second');

    block();
    element = getDialogElement();
    expect(element).not.toBeNull();
    expect(element.innerHTML).toBe('Please wait');

    unblock();
    element = getDialogElement();
    expect(element).not.toBeNull();
    expect(element.innerHTML).toBe('Second');

    unblock();
    element = getDialogElement();
    expect(element).not.toBeNull();
    expect(element.innerHTML).toBe('First');

    unblock();
    expect(getDialogElement()).toBeNull();
});

test('Block ignores the ESC button', () => {
    block();
    expect(getDialogElement()).not.toBeNull();

    const element = document.querySelector('body dialog');
    /** @type {Event} */
    const event = new Event('cancel');
    element.dispatchEvent(event);
    expect(getDialogElement()).not.toBeNull();

    unblock();
    expect(getDialogElement()).toBeNull();
});
