<?php
?>
<div id="fw-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" data-modal-close></div>
        
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 id="fw-modal-title" class="text-xl font-semibold text-gray-900"></h3>
                <button class="text-gray-400 hover:text-gray-600" data-modal-close>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <p id="fw-modal-message" class="text-gray-600 mb-6"></p>
            
            <div class="flex justify-end gap-2">
                <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-800" data-modal-close>
                    Cancel
                </button>
                <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-white" data-modal-close>
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('fw-modal');
    if (!modal) return;

    const closeModal = () => {
        modal.classList.add('hidden');
    };

    modal.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal || e.target.classList.contains('bg-black')) {
            closeModal();
        }
    });
})();
</script>
