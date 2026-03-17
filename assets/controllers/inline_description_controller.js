import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';

export default class extends Controller {
    static targets = ['display', 'editorContainer', 'actions', 'quillDiv'];
    static values = { url: String, csrf: String, html: String, placeholder: String };

    connect() {
        this.quill = null;
        this.savedHtml = this.htmlValue;
        this.displayTarget.addEventListener('click', () => this.edit());
    }

    edit() {
        this.displayTarget.style.display = 'none';
        this.editorContainerTarget.style.display = 'block';
        this.actionsTarget.style.display = 'flex';

        if (!this.quill) {
            this.quill = new Quill(this.quillDivTarget, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link'],
                        ['clean'],
                    ],
                },
            });
            this.quill.root.innerHTML = this.savedHtml;
        }

        this.quill.focus();
    }

    cancel() {
        this.editorContainerTarget.style.display = 'none';
        this.actionsTarget.style.display = 'none';
        this.displayTarget.style.display = 'block';

        if (this.quill) {
            this.quill.root.innerHTML = this.savedHtml;
        }
    }

    async save() {
        if (!this.quill) return;

        const rawHtml = this.quill.root.innerHTML;
        const description = rawHtml === '<p><br></p>' ? '' : rawHtml;

        const saveBtn = this.actionsTarget.querySelector('[data-action*="save"]');
        if (saveBtn) saveBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('description', description);
            formData.append('_token', this.csrfValue);

            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Server error');

            const data = await response.json();
            if (data.success) {
                this.savedHtml = description;
                this.htmlValue = description;
                this.updateDisplay(description);
                this.cancel();
            }
        } catch (e) {
            console.error('Failed to save description', e);
        } finally {
            if (saveBtn) saveBtn.disabled = false;
        }
    }

    updateDisplay(html) {
        if (html) {
            this.displayTarget.innerHTML = `<div class="card-description ql-editor">${html}</div>`;
        } else {
            this.displayTarget.innerHTML = `<p class="card-description-placeholder">${this.placeholderValue}</p>`;
        }
    }
}
