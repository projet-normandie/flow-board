import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static targets = ['display', 'editorContainer', 'select', 'actions'];
    static values = { url: String, csrf: String, placeholder: String };

    connect() {
        this.tomSelect = null;
        this.savedIds = [];
        this.displayTarget.addEventListener('click', () => this.edit());
    }

    edit() {
        if (this.tomSelect) return;

        this.savedIds = Array.from(this.selectTarget.selectedOptions).map(o => o.value);

        this.displayTarget.style.display = 'none';
        this.editorContainerTarget.style.display = 'block';
        this.actionsTarget.style.display = 'flex';

        this.tomSelect = new TomSelect(this.selectTarget, {
            plugins: ['remove_button'],
        });
    }

    cancel() {
        if (this.tomSelect) {
            this.tomSelect.destroy();
            this.tomSelect = null;
        }
        Array.from(this.selectTarget.options).forEach(opt => {
            opt.selected = this.savedIds.includes(opt.value);
        });

        this.editorContainerTarget.style.display = 'none';
        this.actionsTarget.style.display = 'none';
        this.displayTarget.style.display = 'block';
    }

    async save() {
        const selectedIds = Array.from(this.tomSelect.getValue());

        const formData = new FormData();
        selectedIds.forEach(id => formData.append('userIds[]', id));
        formData.append('_token', this.csrfValue);

        const saveBtn = this.actionsTarget.querySelector('[data-action*="save"]');
        if (saveBtn) saveBtn.disabled = true;

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Server error');

            const data = await response.json();
            if (data.success) {
                this.savedIds = selectedIds;
                this.cancel();
                this.updateDisplay(data.assignees);
                return;
            }
        } catch (e) {
            console.error('Failed to save assignees', e);
        } finally {
            if (saveBtn) saveBtn.disabled = false;
        }

        this.cancel();
    }

    updateDisplay(assignees) {
        if (assignees.length === 0) {
            this.displayTarget.innerHTML = `<span class="card-description-placeholder">${this.placeholderValue}</span>`;
        } else {
            this.displayTarget.innerHTML = `<div class="avatar-group">${assignees.map(u =>
                `<img class="avatar-circle" src="${this.escape(u.gravatarUrl)}" alt="${this.escape(u.fullName)}" title="${this.escape(u.fullName)}">`
            ).join('')}</div>`;
        }
    }

    escape(str) {
        const el = document.createElement('span');
        el.textContent = str;
        return el.innerHTML;
    }

    disconnect() {
        if (this.tomSelect) {
            this.tomSelect.destroy();
        }
    }
}
