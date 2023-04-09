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

import { alert, info, confirm } from '@utilities/messagebox';

let modalResult = null;

HTMLDialogElement.prototype.showModal = function () {
    modalResult = null;
};

HTMLDialogElement.prototype.close = function (result) {
    const element = document.getElementById(this.id);
    /** @type {Event} */
    const event = new Event('close');
    element.dispatchEvent(event);
    modalResult = result;
};

beforeEach(() => {
    window.i18n = {
        'button.close': 'Close',
        'button.no': 'No',
        'button.yes': 'Yes',
        'text.error': 'Error'
    };

    document.body.innerHTML = '<body></body>';
});

test('Error message box', () => {
    alert('Error message');

    const body = document.querySelector('body dialog section p');
    expect(body).not.toBeNull();
    expect(body.innerHTML).toBe('Error message');

    const header = document.querySelector('body dialog header p');
    expect(header).not.toBeNull();
    expect(header.innerHTML).toBe('Error');

    const buttons = document.querySelectorAll('body dialog footer button');
    expect(buttons.length).toBe(1);
    expect(buttons[0].innerHTML).toBe('Close');

    buttons[0].click();

    const element = document.querySelector('body dialog');
    expect(element).toBeNull();
});

test('Information message box', () => {
    info('Success message');

    const body = document.querySelector('body dialog section p');
    expect(body).not.toBeNull();
    expect(body.innerHTML).toBe('Success message');

    const header = document.querySelector('body dialog header p');
    expect(header).not.toBeNull();
    expect(header.innerHTML).toBe('eTraxis');

    const buttons = document.querySelectorAll('body dialog footer button');
    expect(buttons.length).toBe(1);
    expect(buttons[0].innerHTML).toBe('Close');

    buttons[0].click();

    const element = document.querySelector('body dialog');
    expect(element).toBeNull();
});

test('Confirmation message box closed by "Yes" button', () => {
    confirm('Are you sure?');

    const body = document.querySelector('body dialog section p');
    expect(body).not.toBeNull();
    expect(body.innerHTML).toBe('Are you sure?');

    const header = document.querySelector('body dialog header p');
    expect(header).not.toBeNull();
    expect(header.innerHTML).toBe('eTraxis');

    const buttons = document.querySelectorAll('body dialog footer button');
    expect(buttons.length).toBe(2);
    expect(buttons[0].innerHTML).toBe('Yes');
    expect(buttons[1].innerHTML).toBe('No');

    expect(modalResult).toBeNull();
    buttons[0].click();
    expect(modalResult).toBe('yes');

    const element = document.querySelector('body dialog');
    expect(element).toBeNull();
});

test('Confirmation message box closed by "No" button', () => {
    confirm('Are you sure?');

    const body = document.querySelector('body dialog section p');
    expect(body).not.toBeNull();
    expect(body.innerHTML).toBe('Are you sure?');

    const header = document.querySelector('body dialog header p');
    expect(header).not.toBeNull();
    expect(header.innerHTML).toBe('eTraxis');

    const buttons = document.querySelectorAll('body dialog footer button');
    expect(buttons.length).toBe(2);
    expect(buttons[0].innerHTML).toBe('Yes');
    expect(buttons[1].innerHTML).toBe('No');

    expect(modalResult).toBeNull();
    buttons[1].click();
    expect(modalResult).toBe('no');

    const element = document.querySelector('body dialog');
    expect(element).toBeNull();
});

test('Confirmation message box closed by ESC button', () => {
    confirm('Are you sure?');

    const body = document.querySelector('body dialog section p');
    expect(body).not.toBeNull();
    expect(body.innerHTML).toBe('Are you sure?');

    const header = document.querySelector('body dialog header p');
    expect(header).not.toBeNull();
    expect(header.innerHTML).toBe('eTraxis');

    const buttons = document.querySelectorAll('body dialog footer button');
    expect(buttons.length).toBe(2);
    expect(buttons[0].innerHTML).toBe('Yes');
    expect(buttons[1].innerHTML).toBe('No');

    expect(modalResult).toBeNull();
    let element = document.querySelector('body dialog');
    /** @type {Event} */
    const event = new Event('cancel');
    element.dispatchEvent(event);
    element.close(element.returnValue);
    expect(modalResult).toBe('no');

    element = document.querySelector('body dialog');
    expect(element).toBeNull();
});
