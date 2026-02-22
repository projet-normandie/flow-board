import './stimulus_bootstrap.js';

// CSS/SCSS
import './styles/app.scss';

// Bootstrap JS
import 'bootstrap';

// ============================================
// Ajax Modal Component
// ============================================

/**
 * Open the ajax modal and load content from URL
 * @param {string} url - URL to fetch content from
 */
window.openAjaxModal = async function(url) {
    const modal = document.getElementById('ajaxModal');
    if (!modal) return;

    const modalContent = modal.querySelector('.ajax-modal-content');

    // Show modal with loading spinner
    modalContent.innerHTML = '<div class="ajax-modal-body text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    try {
        const response = await fetch(url);
        const html = await response.text();
        modalContent.innerHTML = html;
    } catch (error) {
        modalContent.innerHTML = '<div class="ajax-modal-header"><h5 class="ajax-modal-title">Error</h5><button type="button" class="ajax-modal-close" onclick="closeAjaxModal()">&times;</button></div><div class="ajax-modal-body text-center text-danger py-4"><i class="bi bi-exclamation-triangle"></i> An error occurred</div>';
    }
};

/**
 * Close the ajax modal
 */
window.closeAjaxModal = function() {
    const modal = document.getElementById('ajaxModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
};

// Close modal on background click or Escape key
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('ajaxModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeAjaxModal();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAjaxModal();
        }
    });
});
