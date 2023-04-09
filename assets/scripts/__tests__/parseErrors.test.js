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

import { expect, test } from '@jest/globals';

import parseErrors from '@utilities/parseErrors';

HTMLDialogElement.prototype.showModal = function () {
};

HTMLDialogElement.prototype.close = function () {
    const element = document.getElementById(this.id);
    element.parentNode.removeChild(element);
};

test('Exception with single error', () => {
    window.i18n = {
        'button.close': 'Close',
        'text.error': 'Error'
    };

    document.body.innerHTML = '<body></body>';

    const exception = {
        response: {
            data: 'Invalid credentials'
        }
    };

    expect(parseErrors(exception)).toStrictEqual({});

    const body = document.querySelector('body dialog section p');
    expect(body).not.toBeNull();
    expect(body.innerHTML).toBe('Invalid credentials');

    const header = document.querySelector('body dialog header p');
    expect(header).not.toBeNull();
    expect(header.innerHTML).toBe('Error');
});

test('Exception with multiple errors', () => {
    const exception = {
        response: {
            data: [
                { property: 'email', message: 'Invalid email address' },
                { property: 'password', message: 'This field is required' }
            ]
        }
    };

    expect(parseErrors(exception)).toStrictEqual({
        email: 'Invalid email address',
        password: 'This field is required'
    });
});
