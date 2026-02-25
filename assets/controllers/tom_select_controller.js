import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static values = {
        autoSubmit: { type: Boolean, default: false },
    };

    connect() {
        this.tomSelect = new TomSelect(this.element, {
            plugins: ['remove_button'],
            render: {
                option: (data, escape) => {
                    const color = data.color || null;
                    const dot = color
                        ? `<span class="ts-label-dot" style="background-color: ${escape(color)};"></span>`
                        : '';
                    return `<div>${dot}${escape(data.text)}</div>`;
                },
                item: (data, escape) => {
                    const color = data.color || null;
                    const dot = color
                        ? `<span class="ts-label-dot" style="background-color: ${escape(color)};"></span>`
                        : '';
                    return `<div>${dot}${escape(data.text)}</div>`;
                },
            },
            onInitialize() {
                const options = this.input.querySelectorAll('option[data-color]');
                options.forEach(opt => {
                    if (opt.value && this.options[opt.value]) {
                        this.options[opt.value].color = opt.dataset.color;
                    }
                });
            },
        });

        if (this.autoSubmitValue) {
            this.tomSelect.on('change', () => {
                this.element.closest('form').requestSubmit();
            });
        }
    }

    disconnect() {
        if (this.tomSelect) {
            this.tomSelect.destroy();
        }
    }
}
