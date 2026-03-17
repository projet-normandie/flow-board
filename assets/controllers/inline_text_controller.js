import { Controller } from '@hotwired/stimulus';

/**
 * Generic inline text editing controller.
 * Click display → textarea appears. Enter/blur → save. Escape → cancel.
 *
 * Values:
 *   url        - POST endpoint URL
 *   csrf       - CSRF token
 *   field      - POST body key (default: 'value')
 *   allowEmpty - allow saving an empty value (default: false)
 *   placeholder - text shown in display when value is empty
 */
export default class extends Controller {
    static targets = ['display', 'input'];
    static values = {
        url: String,
        csrf: String,
        field: { type: String, default: 'value' },
        allowEmpty: { type: Boolean, default: false },
        placeholder: { type: String, default: '' },
    };

    connect() {
        this.savedText = this.inputTarget.value;
        this.editing = false;
        this.autoResize();
    }

    startEdit() {
        this.editing = true;
        this.displayTarget.style.display = 'none';
        this.inputTarget.style.display = 'block';
        this.autoResize();
        this.inputTarget.focus();
        this.inputTarget.select();
    }

    handleKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.editing = false;
            this.commitSave();
        } else if (e.key === 'Escape') {
            this.editing = false;
            this.inputTarget.value = this.savedText;
            this.showDisplay();
        } else {
            this.autoResize();
        }
    }

    handleBlur() {
        if (!this.editing) return;
        this.editing = false;
        this.commitSave();
    }

    showDisplay() {
        this.inputTarget.style.display = 'none';
        this.displayTarget.style.display = 'block';
    }

    autoResize() {
        const el = this.inputTarget;
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }

    async commitSave() {
        const newText = this.inputTarget.value.trim();
        const isEmpty = newText === '';

        if ((isEmpty && !this.allowEmptyValue) || newText === this.savedText) {
            this.inputTarget.value = this.savedText;
            this.showDisplay();
            return;
        }

        const formData = new FormData();
        formData.append(this.fieldValue, newText);
        formData.append('_token', this.csrfValue);

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Server error');

            const data = await response.json();
            if (data.success) {
                this.savedText = newText;
                this.updateDisplay(newText);
            } else {
                this.inputTarget.value = this.savedText;
            }
        } catch (e) {
            console.error('Failed to save', e);
            this.inputTarget.value = this.savedText;
        }

        this.showDisplay();
    }

    updateDisplay(text) {
        if (text) {
            this.displayTarget.textContent = text;
            this.displayTarget.classList.remove('card-description-placeholder');
        } else {
            this.displayTarget.textContent = this.placeholderValue;
            this.displayTarget.classList.add('card-description-placeholder');
        }
    }
}
