/**
 * Sidebar Controller - Toggle Sidebar Mobile
 *
 * Usage:
 *   <button data-controller="sidebar" data-action="click->sidebar#toggle">
 *     Menu
 *   </button>
 *
 *   <aside data-sidebar-target="menu" class="-translate-x-full md:translate-x-0">
 *     ...
 *   </aside>
 */
import { Controller } from '../ogan-stimulus.js';

export default class SidebarController extends Controller {
    static targets = ['menu'];

    connect() {
        this._isOpen = false;
    }

    toggle() {
        this._isOpen = !this._isOpen;

        const sidebar = this.hasMenuTarget
            ? this.menuTarget
            : document.getElementById('sidebar');

        if (!sidebar) return;

        if (this._isOpen) {
            sidebar.classList.remove('-translate-x-full');
        } else {
            sidebar.classList.add('-translate-x-full');
        }
    }

    open() {
        this._isOpen = true;
        const sidebar = this.hasMenuTarget ? this.menuTarget : document.getElementById('sidebar');
        if (sidebar) sidebar.classList.remove('-translate-x-full');
    }

    close() {
        this._isOpen = false;
        const sidebar = this.hasMenuTarget ? this.menuTarget : document.getElementById('sidebar');
        if (sidebar) sidebar.classList.add('-translate-x-full');
    }
}
