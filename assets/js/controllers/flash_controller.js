/**
 * Flash Controller - Gestion des messages flash
 *
 * Usage:
 *   <div data-controller="flash" data-flash-timeout="5000">
 *     <span data-flash-target="message">{{ message }}</span>
 *     <button data-action="click->flash#dismiss">×</button>
 *   </div>
 */
import { Controller } from '../ogan-stimulus.js';

export default class FlashController extends Controller {
    static targets = ['message'];

    connect() {
        const timeout = this.data('timeout') || 5000;
        this._timeout = setTimeout(() => this.dismiss(), parseInt(timeout));
    }

    disconnect() {
        if (this._timeout) {
            clearTimeout(this._timeout);
        }
    }

    dismiss() {
        this.element.style.transition = 'opacity 0.3s ease-out';
        this.element.style.opacity = '0';
        setTimeout(() => this.element.remove(), 300);
    }
}
