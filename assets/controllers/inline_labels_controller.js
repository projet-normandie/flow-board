import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static targets = ['display', 'editorContainer', 'select', 'actions'];
    static values = { url: String, csrf: String };

    connect() {
        this.tomSelect = null;
        this.savedIds = [];
        this.displayTarget.addEventListener('click', () => this.edit());
    }

    edit() {
        if (this.tomSelect) return;

        // Capture current selection before TomSelect takes over
        this.savedIds = Array.from(this.selectTarget.selectedOptions).map(o => o.value);

        this.displayTarget.style.display = 'none';
        this.editorContainerTarget.style.display = 'block';
        this.actionsTarget.style.display = 'flex';

        this.tomSelect = new TomSelect(this.selectTarget, {
            plugins: ['remove_button'],
            render: {
                option: (data, escape) => {
                    const dot = data.color
                        ? `<span class="ts-label-dot" style="background-color:${escape(data.color)};"></span>`
                        : '';
                    return `<div>${dot}${escape(data.text)}</div>`;
                },
                item: (data, escape) => {
                    const dot = data.color
                        ? `<span class="ts-label-dot" style="background-color:${escape(data.color)};"></span>`
                        : '';
                    return `<div>${dot}${escape(data.text)}</div>`;
                },
            },
            onInitialize() {
                this.input.querySelectorAll('option[data-color]').forEach(opt => {
                    if (opt.value && this.options[opt.value]) {
                        this.options[opt.value].color = opt.dataset.color;
                    }
                });
            },
        });
    }

    cancel() {
        if (this.tomSelect) {
            this.tomSelect.destroy();
            this.tomSelect = null;
        }
        // Restore <select> to saved state
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
        selectedIds.forEach(id => formData.append('labelIds[]', id));
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
                this.updateDisplay(data.labels);
                return;
            }
        } catch (e) {
            console.error('Failed to save labels', e);
        } finally {
            if (saveBtn) saveBtn.disabled = false;
        }

        this.cancel();
    }

    updateDisplay(labels) {
        if (labels.length === 0) {
            this.displayTarget.innerHTML = `<span class="card-description-placeholder">${this.element.dataset.placeholder ?? '—'}</span>`;
        } else {
            this.displayTarget.innerHTML = labels
                .map(l => `<span class="badge rounded-pill" style="background-color:${this.escape(l.color)};">${this.escape(l.name)}</span>`)
                .join(' ');
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
