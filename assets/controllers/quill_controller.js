import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';

export default class extends Controller {
    static values = {
        toolbar: { type: String, default: 'minimal' },
        minHeight: { type: String, default: '200px' },
    };

    connect() {
        const editorDiv = document.createElement('div');
        editorDiv.style.minHeight = this.minHeightValue;
        this.element.parentNode.insertBefore(editorDiv, this.element);
        this.element.style.display = 'none';

        this.quill = new Quill(editorDiv, {
            theme: 'snow',
            modules: {
                toolbar: this.getToolbarConfig(),
            },
        });

        if (this.element.value) {
            this.quill.root.innerHTML = this.element.value;
        }

        this.quill.on('text-change', () => {
            this.element.value = this.quill.root.innerHTML;
            this.element.dispatchEvent(new Event('change', { bubbles: true }));
        });

        this.editorDiv = editorDiv;
    }

    disconnect() {
        if (this.editorDiv) {
            this.editorDiv.remove();
        }
    }

    getToolbarConfig() {
        if (this.toolbarValue === 'full') {
            return [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['blockquote', 'code-block'],
                ['link'],
                ['clean'],
            ];
        }

        // Minimal toolbar (default)
        return [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['link'],
            ['clean'],
        ];
    }
}
