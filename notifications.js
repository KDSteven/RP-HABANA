// ===============================
// GLOBAL VARS
// ===============================
console.log("✅ notifications.js is loaded");



let latestNotifications = [];
let lastCount = parseInt(localStorage.getItem('lastNotifCount')) || 0;
let lastPopupTime = parseInt(localStorage.getItem('lastPopupTime')) || 0;
let modalOpen = false;
const POLL_INTERVAL = 5000;  // Check every 5s
const POPUP_INTERVAL = 600000; // Show popup every 30s if items exist

// Request desktop notification permission
if (Notification.permission !== "granted") Notification.requestPermission();

// ===============================
// FETCH NOTIFICATIONS
// ===============================
function fetchNotifications() {  console.log("⏳ Running fetchNotifications...");
    fetch('get_notifications.php')
        .then(r => r.json())
        .then(data => { console.log("DEBUG: API Response", data);
            updateBadge(data.count);
            latestNotifications = data.items;

            const now = Date.now();
const newNotifications = data.count > lastCount; // Check if there are new ones

if ((newNotifications || (data.count > 0 && now - lastPopupTime >= POPUP_INTERVAL)) && !modalOpen) {
    console.log("✅ Triggering popup now...");
    openNotificationModal(data.items);
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

    // Categorize items
    const outItems = items.filter(i => i.category === 'out');
    const criticalItems = items.filter(i => i.category === 'critical');
    const lowItems = items.filter(i => i.category === 'low');

    overlay.innerHTML = `
        <div class="notif-modal">
            <div class="notif-header">📢 Stock Alerts</div>
            <div class="notif-tabs">
                <div class="notif-tab active" data-tab="out">Out (${outItems.length})</div>
                <div class="notif-tab" data-tab="critical">Critical (${criticalItems.length})</div>
                <div class="notif-tab" data-tab="low">Low (${lowItems.length})</div>
            </div>
            <div class="notif-content" id="notifContent"></div>
            <div class="notif-footer">
                <button id="markSeenBtn" class="btn-primary">Mark as Seen</button>
                <button id="closeModalBtn" class="btn-warning">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    renderTable('out');

    // Switch Tabs
    document.querySelectorAll('.notif-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            renderTable(tab.dataset.tab);
        });
    });

    // Close Modal
    document.getElementById('closeModalBtn').onclick = closeModal;
    overlay.onclick = e => { if (e.target.id === 'notifModalOverlay') closeModal(); };
    document.addEventListener('keydown', escClose);

    // Mark as Seen
    document.getElementById('markSeenBtn').addEventListener('click', () => {
        fetch('mark_seen.php', { method: 'POST' }).then(() => {
            closeModal();
            const badge = document.getElementById('notifCount');
            if (badge) badge.style.display = 'none';
            lastCount = 0;
            localStorage.setItem('lastNotifCount', 0);
        });
    });

    function renderTable(type) {
        const content = document.getElementById('notifContent');
        let rows = [];

        if (type === 'out') {
            rows = outItems;
        } else if (type === 'critical') {
            rows = criticalItems;
        } else {
            rows = lowItems;
        }

        const html = rows.length
            ? `<table class="notif-table">
                ${rows.map(item => `
                    <tr class="${type}-row">
                        <td>${item.product_name}</td>
                        <td style="text-align:center;">${item.stock}</td>
                        <td>${item.branch}</td>
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
