<?php
/**
 * views/components/share-modal.php
 * Premium Share Modal with Web Share API support and Desktop fallback.
 */
?>

<div id="share-modal" class="share-modal-overlay">
    <div class="share-modal-content">
        <div class="share-modal-header">
            <h3 class="share-modal-title">Share this product</h3>
            <button class="share-modal-close" onclick="closeShareModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        <div class="share-modal-body">
            <div class="share-grid">
                <!-- WhatsApp -->
                <a href="#" id="share-whatsapp" class="share-item" target="_blank">
                    <div class="share-icon-wrap wa">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 2c-5.508 0-9.987 4.479-9.987 9.987 0 1.763.461 3.42 1.262 4.853L2 22l5.335-1.4c1.401.762 2.993 1.194 4.696 1.194 5.508 0 9.987-4.479 9.987-9.987s-4.479-9.987-9.987-9.987zm0 18.281c-1.524 0-2.946-.406-4.175-1.112l-.299-.173-3.102.814.828-3.023-.191-.303c-.776-1.236-1.185-2.668-1.185-4.153 0-4.401 3.581-7.982 7.982-7.982 4.401 0 7.982 3.581 7.982 7.982s-3.581 7.982-7.982 7.982zm4.385-6.081c-.241-.121-1.423-.701-1.645-.781-.221-.081-.382-.121-.543.121-.161.241-.623.781-.764.942-.141.161-.281.181-.523.061-.241-.121-1.018-.375-1.938-1.196-.716-.639-1.199-1.428-1.34-1.669-.141-.241-.015-.371.106-.491.11-.108.241-.281.362-.421.121-.141.161-.241.241-.402.081-.161.041-.301-.02-.421-.06-.121-.543-1.305-.744-1.787-.195-.47-.394-.406-.543-.414-.141-.007-.301-.008-.462-.008-.161 0-.422.06-.643.301-.221.241-.844.824-.844 2.008 0 1.185.864 2.329.985 2.489.121.161 1.7 2.595 4.118 3.639.575.249 1.025.397 1.375.508.578.184 1.104.158 1.519.096.463-.069 1.423-.582 1.624-1.145.201-.563.201-1.044.141-1.145-.06-.101-.221-.161-.462-.281z"/></svg>
                    </div>
                    <span>WhatsApp</span>
                </a>
                
                <!-- Facebook -->
                <a href="#" id="share-facebook" class="share-item" target="_blank">
                    <div class="share-icon-wrap fb">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg>
                    </div>
                    <span>Facebook</span>
                </a>
                
                <!-- X (Twitter) -->
                <a href="#" id="share-x" class="share-item" target="_blank">
                    <div class="share-icon-wrap x">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </div>
                    <span>X</span>
                </a>
                
                <!-- Instagram -->
                <a href="#" id="share-instagram" class="share-item" onclick="copyShareLink(event)">
                    <div class="share-icon-wrap ig">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </div>
                    <span>Instagram</span>
                </a>
                
                <!-- TikTok -->
                <a href="#" id="share-tiktok" class="share-item" onclick="copyShareLink(event)">
                    <div class="share-icon-wrap tt">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.06-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.03 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96a6.66 6.66 0 0 1 4.44-1.56c.05 1.63.07 3.26.06 4.9-.3-.04-.61-.04-.9-.01-.72.07-1.41.33-1.97.77-.51.41-.86.98-1 1.62-.17.76-.1 1.72.16 2.45.38.9 1.19 1.56 2.14 1.78.36.08.74.1 1.12.08 1.05-.01 2.05-.51 2.67-1.35.3-.41.48-.9.51-1.4.07-2.31.04-4.62.04-6.93V0h-4.01z"/></svg>
                    </div>
                    <span>TikTok</span>
                </a>
            </div>
            
            <div class="share-url-box">
                <input type="text" id="share-url-input" readonly value="">
                <button id="copy-share-btn" onclick="copyShareLink(event)">Copy</button>
            </div>
        </div>
    </div>
</div>

<div id="share-toast" class="share-toast">Link copied to clipboard!</div>

<style>
.share-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s var(--ease);
}
.share-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}
.share-modal-content {
    background: #fff;
    width: 100%;
    max-width: 400px;
    border-radius: 24px;
    padding: 32px;
    transform: translateY(20px);
    transition: all 0.4s var(--ease);
}
.share-modal-overlay.active .share-modal-content {
    transform: translateY(0);
}
.share-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.share-modal-title {
    font-family: var(--f-display);
    font-size: 20px;
    font-weight: 800;
    text-transform: uppercase;
}
.share-modal-close {
    background: var(--off);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
.share-modal-close:hover {
    background: var(--light-gray);
    transform: rotate(90deg);
}
.share-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}
.share-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: var(--mid-gray);
    font-family: var(--f-mono);
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 700;
    transition: all 0.2s;
}
.share-item:hover {
    color: var(--ink);
    transform: translateY(-4px);
}
.share-icon-wrap {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    transition: all 0.3s;
    box-shadow: 0 8px 16px rgba(0,0,0,0.05);
}
.share-icon-wrap.wa { background: #25D366; }
.share-icon-wrap.fb { background: #1877F2; }
.share-icon-wrap.x { background: #000000; }
.share-icon-wrap.ig { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
.share-icon-wrap.tt { background: #000000; }

.share-url-box {
    display: flex;
    background: var(--off);
    border: 1px solid var(--light-gray);
    border-radius: 12px;
    padding: 8px;
    gap: 8px;
}
.share-url-box input {
    flex: 1;
    background: none;
    border: none;
    font-family: var(--f-body);
    font-size: 13px;
    color: var(--mid-gray);
    padding: 0 8px;
    outline: none;
}
.share-url-box button {
    background: var(--ink);
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-family: var(--f-display);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.2s;
}
.share-url-box button:hover {
    background: var(--red);
}

.share-toast {
    position: fixed;
    bottom: 40px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--ink);
    color: #fff;
    padding: 12px 24px;
    border-radius: 100px;
    font-family: var(--f-display);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    z-index: 10000;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.share-toast.active {
    transform: translateX(-50%) translateY(0);
}
</style>

<script>
let currentShareUrl = '';
let currentShareTitle = '';

window.openShareModal = function(url, title, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    currentShareUrl = url;
    currentShareTitle = title;

    // Try Web Share API first
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        }).catch(() => {
            // Fallback to custom modal if user cancels or it fails
            showCustomShareModal();
        });
    } else {
        showCustomShareModal();
    }
};

function showCustomShareModal() {
    const modal = document.getElementById('share-modal');
    const input = document.getElementById('share-url-input');
    
    input.value = currentShareUrl;
    
    // Update Social Links
    document.getElementById('share-whatsapp').href = `https://wa.me/?text=${encodeURIComponent(currentShareTitle + ' ' + currentShareUrl)}`;
    document.getElementById('share-facebook').href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentShareUrl)}`;
    document.getElementById('share-x').href = `https://twitter.com/intent/tweet?url=${encodeURIComponent(currentShareUrl)}&text=${encodeURIComponent(currentShareTitle)}`;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

window.closeShareModal = function() {
    const modal = document.getElementById('share-modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
};

window.copyShareLink = function(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const url = currentShareUrl || window.location.href;
    
    navigator.clipboard.writeText(url).then(() => {
        const toast = document.getElementById('share-toast');
        toast.classList.add('active');
        setTimeout(() => {
            toast.classList.remove('active');
        }, 3000);
    });
};

// Close modal on click outside
document.getElementById('share-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeShareModal();
    }
});
</script>
