/**
 * Sidebar Controller - Toggle Sidebar Mobile
 *
 * Usage:
 *   <button data-controller="sidebar" data-action="click->sidebar#toggle">
 *     Menu
 *   </button>
 *
 *   <div id="sidebar-overlay" data-controller="sidebar" data-action="click->sidebar#close"></div>
 *   <aside id="sidebar" class="-translate-x-full md:translate-x-0">
 *     <button data-controller="sidebar" data-action="click->sidebar#close">×</button>
 *     ...
 *   </aside>
 */
import { Controller } from '../ogan-stimulus.js';

export default class SidebarController extends Controller {
    toggle() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (!sidebar) return;

        // Vérifier l'état actuel du DOM
        const isHidden = sidebar.classList.contains('-translate-x-full');

        if (isHidden) {
            this._open(sidebar, overlay);
        } else {
            this._close(sidebar, overlay);
        }
    }

    open() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        this._open(sidebar, overlay);
    }

    close() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        this._close(sidebar, overlay);
    }

    _open(sidebar, overlay) {
        if (sidebar) sidebar.classList.remove('-translate-x-full');
        if (overlay) overlay.classList.remove('hidden');
    }

    _close(sidebar, overlay) {
        if (sidebar) sidebar.classList.add('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
    }
}
