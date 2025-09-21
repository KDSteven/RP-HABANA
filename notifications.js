// ===============================
// GLOBAL VARS
// ===============================
console.log("✅ notifications.js is loaded");

let latestNotifications = [];
let lastCount = parseInt(localStorage.getItem('lastNotifCount')) || 0;
let lastPopupTime = parseInt(localStorage.getItem('lastPopupTime')) || 0;
let modalOpen = false;
const POLL_INTERVAL = 5000;  // Check every 5s
const POPUP_INTERVAL = 6000000; // Show popup every 100 min if items exist

// Request desktop notification permission
if (Notification.permission !== "granted") Notification.requestPermission();

// ===============================
// FETCH NOTIFICATIONS
// ===============================
function fetchNotifications() {
    console.log("⏳ Running fetchNotifications...");
    fetch('../../get_notifications.php')
        .then(r => r.json())
        .then(data => {
            updateBadge(data.count);
            latestNotifications = data.items;

            // ====== NEAR EXPIRY ======
            const nearExpiryItems = data.items.filter(item => {
                if (!item.expiry_date) return false;
                const today = new Date();
                const expiry = new Date(item.expiry_date);
                const diffDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
                return diffDays >= 0 && diffDays <= 7; // expiring in 7 days
            }).map(item => ({ ...item, category: 'expiry' }));

            const allItems = [...latestNotifications, ...nearExpiryItems];

            const now = Date.now();
            const newNotifications = data.count > lastCount;

            if ((newNotifications || (data.count > 0 && now - lastPopupTime >= POPUP_INTERVAL)) && !modalOpen) {
                console.log("✅ Triggering popup now...");
                lastPopupTime = now;
                localStorage.setItem('lastPopupTime', now);
                openNotificationModal(allItems);
                playSound();
                showDesktopNotification("Stock Alert", "You have new stock alerts!");
                broadcastPopup();
            }

            lastCount = data.count;
            localStorage.setItem('lastNotifCount', lastCount);
        })
        .catch(err => console.error('Notification fetch error:', err));
}


// ===============================
// UPDATE BADGE
// ===============================
function updateBadge(count) {
    const badge = document.getElementById('notifCount');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-block' : 'none';

    if (count > lastCount) {
        const bell = document.getElementById('notifBell');
        if (bell) {
            bell.classList.add('shake');
            setTimeout(() => bell.classList.remove('shake'), 1000);
        }
    }
}

// ===============================
// SOUND ALERT
// ===============================
function playSound() {
    const audio = document.getElementById('notifSound');
    if (audio) audio.play().catch(() => {});
}

// ===============================
// DESKTOP NOTIFICATION
// ===============================
function showDesktopNotification(title, body) {
    if (Notification.permission === "granted") {
        new Notification(title, { body, icon: "stock-icon.png" });
    }
}

// ===============================
// MODAL POPUP
// ===============================
function openNotificationModal(items) {
    modalOpen = true;
    const old = document.getElementById('notifModalOverlay');
    if (old) old.remove();

    const overlay = document.createElement('div');
    overlay.id = 'notifModalOverlay';

    const outItems = items.filter(i => i.category === 'out');
    const criticalItems = items.filter(i => i.category === 'critical');
    const expiryItems = items.filter(i => i.category === 'expiry'); // NEW

    overlay.innerHTML = `
        <div class="notif-modal modern">
            <div class="notif-header"><i class="fas fa-bell"></i> Stock Alerts</div>
            <div class="notif-tabs">
                <div class="notif-tab active" data-tab="out">Out (${outItems.length})</div>
                <div class="notif-tab" data-tab="critical">Critical (${criticalItems.length})</div>
                <div class="notif-tab" data-tab="expiry">Expiring Soon (${expiryItems.length})</div>
            </div>
            <div class="notif-content" id="notifContent"></div>
        </div>
    `;
    document.body.appendChild(overlay);

    renderTable('out');

    document.querySelectorAll('.notif-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            renderTable(tab.dataset.tab);
        });
    });

    overlay.onclick = e => { if (e.target.id === 'notifModalOverlay') closeModal(); };
    document.addEventListener('keydown', escClose);

    function renderTable(type) {
    const content = document.getElementById('notifContent');
    const rows = type === 'out' ? outItems 
               : type === 'critical' ? criticalItems 
               : type === 'expiry' ? latestNotifications.filter(i => i.category === 'expiry')
               : latestNotifications.filter(i => i.category === 'expired');

    const html = rows.length
        ? `<table class="notif-table">
            <tr>
                <th>Product</th>
                <th>Stock</th>
                <th>Branch</th>
                <th>Expiration</th>
            </tr>
            ${rows.map(item => `
                <tr class="${type}-row">
                    <td>${item.product_name}</td>
                    <td style="text-align:center;">${item.stock}</td>
                    <td>${item.branch}</td>
                    <td>${item.expiration_date ? 'Exp: ' + item.expiration_date : '-'}</td>
                </tr>`).join('')}
           </table>`
        : `<p>No ${type} stock items.</p>`;

    content.innerHTML = html;
}


    function closeModal() {
        overlay.remove();
        modalOpen = false;
        document.removeEventListener('keydown', escClose);
    }

    function escClose(e) {
        if (e.key === 'Escape') closeModal();
    }
}


// ===============================
// SYNC BETWEEN TABS
// ===============================
function broadcastPopup() {
    const now = Date.now();
    lastPopupTime = now;
    localStorage.setItem('lastPopupTime', now);
    localStorage.setItem('popupInProgress', 'true');
    setTimeout(() => localStorage.removeItem('popupInProgress'), 1000);
}

// ===============================
// INIT
// ===============================
document.addEventListener('DOMContentLoaded', () => {
    setInterval(fetchNotifications, POLL_INTERVAL);
    fetchNotifications();

    const bell = document.getElementById('notifBell');
    if (bell) {
        bell.addEventListener('click', () => {
            if (latestNotifications.length > 0) {
                openNotificationModal(latestNotifications);
            } else {
                alert('No new notifications.');
            }
        });
    }
});

