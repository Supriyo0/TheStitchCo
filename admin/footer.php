<?php
/**
 * Admin Panel Footer Component
 * Includes Proof Screenshot Lightbox & AJAX Handlers
 * The Stitch Co.
 */
?>
    </main>
</div>

<!-- Modal: View Payment Screenshot Proof Lightbox -->
<div class="admin-modal-overlay" id="proof-modal-overlay">
    <div class="admin-modal-box" style="text-align: center;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800;">Payment Screenshot Proof</h3>
            <button onclick="closeProofModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div style="max-height: 400px; overflow-y: auto; background: #F9FAFB; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--admin-border);">
            <img id="proof-modal-img" src="" alt="Proof Screenshot" style="max-width: 100%; border-radius: 6px; margin: 0 auto;">
        </div>
        <button onclick="closeProofModal()" style="padding: 0.6rem 1.5rem; background: #111827; color: #fff; border-radius: 6px; border: none; font-weight: 700; cursor: pointer;">
            Close
        </button>
    </div>
</div>

<script>
function viewProofModal(imgSrc) {
    const modal = document.getElementById('proof-modal-overlay');
    const img = document.getElementById('proof-modal-img');
    if (modal && img) {
        if (imgSrc.startsWith('http://') || imgSrc.startsWith('https://')) {
            img.src = imgSrc;
        } else {
            img.src = '../' + imgSrc;
        }
        modal.classList.add('active');
    }
}

function closeProofModal() {
    const modal = document.getElementById('proof-modal-overlay');
    if (modal) {
        modal.classList.remove('active');
    }
}
</script>
</body>
</html>
