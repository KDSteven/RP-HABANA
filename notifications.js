// ===============================
// GLOBAL VARS
// ===============================
let latestNotifications = [];
let lastCount = parseInt(localStorage.getItem('lastNotifCount')) || 0;
let lastPopupTime = parseInt(localStorage.getItem('lastPopupTime')) || 0;
let modalOpen = false;
const POLL_INTERVAL = 5000; // 5s
const POPUP_INTERVAL = 30000; // 30s

// Request desktop notification permission
if (Notification.permission !== "granted") Notification.requestPermission();

// ===============================
// FETCH NOTIFICATIONS
// ===============================
function fetchNotifications() {
    fetch('get_notifications.php')
        .then(r => r.json())
        .then(data => {
            updateBadge(data.count);
            latestNotifications = data.items;

            const now = Date.now();
            const canPopup = !localStorage.getItem('popupInProgress') && !modalOpen;

            if ((data.count > lastCount || now - lastPopupTime >= POPUP_INTERVAL) && canPopup) {
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
// UPDATE BADGE + BELL ANIMATION
// ===============================
function updateBadge(count) {
    const badge = document.getElementById('notifCount');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-block' : 'none';

    if (count > lastCount) {
        const bell = document.getElementById('notifBell');
        bell.classList.add('shake');
        setTimeout(() => bell.classList.remove('shake'), 1000);
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
// MODAL WITH TABS
// ===============================
function openNotificationModal(items) {
    modalOpen = true;
    const old = document.getElementById('notifModalOverlay');
    if (old) old.remove();

    const overlay = document.createElement('div');
    overlay.id = 'notifModalOverlay';

    const criticalItems = items.filter(i => i.stock <= i.critical_point);
    const lowItems = items.filter(i => i.stock > i.critical_point);

    overlay.innerHTML = `
        <div class="notif-modal">
            <div class="notif-header">📢 Stock Alerts</div>
            <div class="notif-tabs">
                <div class="notif-tab active" data-tab="critical">Critical (${criticalItems.length})</div>
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

    // Default show critical tab
    renderTable('critical');

    // Tab switching
    document.querySelectorAll('.notif-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            renderTable(tab.dataset.tab);
        });
    });

    // Close actions
    document.getElementById('closeModalBtn').onclick = closeModal;
    overlay.onclick = e => { if (e.target.id === 'notifModalOverlay') closeModal(); };
    document.addEventListener('keydown', escClose);

    // Mark as seen
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
        const rows = (type === 'critical' ? criticalItems : lowItems)
            .map(item => `
                <tr class="${type === 'critical' ? 'critical-row' : 'low-row'}">
                    <td>${item.product_name}</td>
                    <td style="text-align:center;">${item.stock}</td>
                    <td>${item.branch}</td>
                </tr>`).join('');
        content.innerHTML = rows ? `<table class="notif-table">${rows}</table>` : `<p>No ${type} stock items.</p>`;
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
// MULTI-TAB SYNC
// ===============================
function broadcastPopup() {
    const now = Date.now();
    lastPopupTime = now;
    localStorage.setItem('lastPopupTime', now);
    localStorage.setItem('popupInProgress', 'true');
    setTimeout(() => localStorage.removeItem('popupInProgress'), 1000);
}

// ===============================
// START POLLING
// ===============================
setInterval(fetchNotifications, POLL_INTERVAL);
fetchNotifications();

// ===============================
// BELL ICON CLICK
// ===============================
document.getElementById('notifBell').addEventListener('click', () => {
    if (latestNotifications.length > 0) {
        openNotificationModal(latestNotifications);
    } else {
        alert('No new notifications.');
    }
});
