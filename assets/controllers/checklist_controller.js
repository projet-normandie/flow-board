import { Controller } from '@hotwired/stimulus'
import Sortable from 'sortablejs'

export default class extends Controller {
    static targets = ['checklistList', 'checklist', 'itemList']

    connect () {
        this._initSortableChecklists()
        this.checklistTargets.forEach(cl => this._initSortableItems(cl))
    }

    // ── Add checklist ────────────────────────────────────────────────────────

    addChecklist () {
        const list = this.checklistListTarget
        const prototype = list.dataset.prototype
        const index = list.dataset.index ? parseInt(list.dataset.index) : list.children.length
        const html = prototype.replace(/__name__/g, index)
        list.dataset.index = index + 1

        const tpl = document.createElement('template')
        tpl.innerHTML = html.trim()
        const node = tpl.content.firstChild
        list.appendChild(node)

        this._initSortableItems(node)
        this._updatePositions(list)
    }

    // ── Remove checklist ─────────────────────────────────────────────────────

    removeChecklist (event) {
        const checklist = event.currentTarget.closest('[data-checklist-target="checklist"]')
        checklist.remove()
        this._updatePositions(this.checklistListTarget)
    }

    // ── Add item ─────────────────────────────────────────────────────────────

    addItem (event) {
        const checklist = event.currentTarget.closest('[data-checklist-target="checklist"]')
        const itemList = checklist.querySelector('[data-checklist-target="itemList"]')
        const prototype = itemList.dataset.prototype
        const index = itemList.dataset.index ? parseInt(itemList.dataset.index) : itemList.children.length
        const html = prototype.replace(/__name__/g, index)
        itemList.dataset.index = index + 1

        const tpl = document.createElement('template')
        tpl.innerHTML = html.trim()
        itemList.appendChild(tpl.content.firstChild)

        this._updatePositions(itemList)
    }

    // ── Remove item ──────────────────────────────────────────────────────────

    removeItem (event) {
        const item = event.currentTarget.closest('.checklist-item-row')
        const itemList = item.closest('[data-checklist-target="itemList"]')
        item.remove()
        this._updatePositions(itemList)
    }

    // ── Sortable init ────────────────────────────────────────────────────────

    _initSortableChecklists () {
        new Sortable(this.checklistListTarget, {
            handle: '.checklist-drag-handle',
            animation: 150,
            onEnd: () => this._updatePositions(this.checklistListTarget),
        })
    }

    _initSortableItems (checklistEl) {
        const itemList = checklistEl.querySelector('[data-checklist-target="itemList"]')
        if (!itemList) return
        new Sortable(itemList, {
            handle: '.item-drag-handle',
            animation: 150,
            onEnd: () => this._updatePositions(itemList),
        })
    }

    // ── Position update ──────────────────────────────────────────────────────

    _updatePositions (list) {
        Array.from(list.children).forEach((el, i) => {
            const posInput = el.querySelector('input[name*="[position]"]')
            if (posInput) posInput.value = (i + 1) * 1000
        })
    }
}
