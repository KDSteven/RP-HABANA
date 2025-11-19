console.log("🔔 notifications.js loaded");

/* ===============================
   GLOBAL VARIABLES
=============================== */
let latestNotifications = [];
let lastCount = parseInt(localStorage.getItem("lastNotifCount")) || 0;
let modalOpen = false;
const POLL_INTERVAL = 5000; // check every 5 seconds

// Ask notification permission one time
if (Notification.permission !== "granted") {
    Notification.requestPermission();
}

/* ===============================
   FETCH NOTIFICATIONS
=============================== */
function fetchNotifications() {
    // Skip fetching when tab is inactive
    if (document.hidden) return;

    fetch("../../get_notifications.php")
        .then(res => res.json())
        .then(data => {
            if (!data || !Array.isArray(data.items)) return;

            updateBadge(data.count);

            // Store original items
            latestNotifications = data.items.map(i => ({
                ...i,
                category: i.category || "other",
                expiry_date: i.expiration_date || null
            }));

            // Detect NEW notifications
            const hasNew = data.count > lastCount;

            if (hasNew && !modalOpen) {
                console.log("🔔 New alert — popup triggered");
                openNotificationModal(latestNotifications);
                playSound();
                showDesktopNotification("Stock Alert", "You have new stock alerts!");
            }

            // Save last count
            lastCount = data.count;
            localStorage.setItem("lastNotifCount", lastCount);
        })
        .catch(err => console.error("❌ Notification fetch error:", err));
}

/* ===============================
   UPDATE BADGE
=============================== */
function updateBadge(count) {
    const badge = document.getElementById("notifCount");
    if (!badge) return;

    badge.textContent = count;
    badge.style.display = count > 0 ? "inline-block" : "none";

    if (count > lastCount) {
        const bell = document.getElementById("notifBell");
        if (bell) {
            bell.classList.add("pulse");
            setTimeout(() => bell.classList.remove("pulse"), 1000);
        }
    }
}

/* ===============================
   SOUND
=============================== */
function playSound() {
    const audio = document.getElementById("notifSound");
    if (audio) audio.play().catch(() => {});
}

/* ===============================
   DESKTOP NOTIFICATION
=============================== */
function showDesktopNotification(title, body) {
    if (Notification.permission === "granted") {
        new Notification(title, { body, icon: "stock-icon.png" });
    }
}

/* ===============================
   MODAL POPUP
=============================== */
function openNotificationModal(items) {
    modalOpen = true;

    // Remove old modal
    const existing = document.getElementById("notifModalOverlay");
    if (existing) existing.remove();

    // Group items by category
    const outItems      = items.filter(i => i.category === "out");
    const criticalItems = items.filter(i => i.category === "critical");
    const expiryItems   = items.filter(i => i.category === "expiry");
    const expiredItems  = items.filter(i => i.category === "expired");

    const overlay = document.createElement("div");
    overlay.id = "notifModalOverlay";

    overlay.innerHTML = `
        <div class="notif-modal">
            <div class="notif-header">
                <i class="fas fa-bell"></i> Stock Alerts
            </div>

            <div class="notif-tabs">
                <div class="notif-tab active" data-tab="out">Out (${outItems.length})</div>
                <div class="notif-tab" data-tab="critical">Critical (${criticalItems.length})</div>
                <div class="notif-tab" data-tab="expiry">Expiring Soon (${expiryItems.length})</div>
                <div class="notif-tab" data-tab="expired">Expired (${expiredItems.length})</div>
            </div>

            <div class="notif-content" id="notifContent"></div>
        </div>
    `;

    document.body.appendChild(overlay);

    renderTable("out");

    // Tab switching
    document.querySelectorAll(".notif-tab").forEach(tab => {
        tab.addEventListener("click", () => {
            document.querySelectorAll(".notif-tab").forEach(t => t.classList.remove("active"));
            tab.classList.add("active");
            renderTable(tab.dataset.tab);
        });
    });

    // Click outside to close
    overlay.onclick = e => {
        if (e.target.id === "notifModalOverlay") closeModal();
    };

    // ESC key
    document.addEventListener("keydown", escClose);

    /* TABLE RENDER FUNCTION */
    function renderTable(type) {
        const content = document.getElementById("notifContent");

        const rows =
            type === "out"      ? outItems :
            type === "critical" ? criticalItems :
            type === "expiry"   ? expiryItems :
            type === "expired"  ? expiredItems :
            [];

        if (!rows.length) {
            content.innerHTML = `<p>No ${type} items.</p>`;
            return;
        }

        content.innerHTML = `
            <table class="notif-table">
                <tr>
                    <th>Product</th>
                    <th>Stock</th>
                    <th>Branch</th>
                    <th>Expiration</th>
                </tr>
                ${rows.map(item => `
                    <tr class="row-${item.category}">
                        <td>
                            <strong>${item.product_name}</strong>
                            <br>
                            <span class="priority-badge ${item.category}-badge">
                                ${item.category === "out" ? "Out of Stock" :
                                  item.category === "critical" ? "Critical" :
                                  item.category === "expiry" ? "Expiring Soon" :
                                  item.category === "expired" ? "Expired" :
                                  "Info"}
                            </span>
                        </td>
                        <td style="text-align:center;">${item.stock}</td>
                        <td>${item.branch}</td>
                        <td>${item.expiry_date ? "Exp: " + item.expiry_date : "-"}</td>
                    </tr>
                `).join("")}
            </table>
        `;
    }

    function closeModal() {
        modalOpen = false;
        const o = document.getElementById("notifModalOverlay");
        if (o) o.remove();
        document.removeEventListener("keydown", escClose);
    }

    function escClose(e) {
        if (e.key === "Escape") closeModal();
    }
}

/* ===============================
   INIT
=============================== */
document.addEventListener("DOMContentLoaded", () => {
    fetchNotifications();
    setInterval(fetchNotifications, POLL_INTERVAL);

    const bell = document.getElementById("notifBell");
    if (bell) {
        bell.addEventListener("click", () => {
            if (latestNotifications.length > 0) {
                openNotificationModal(latestNotifications);
            } else {
                alert("No notifications available.");
            }
        });
    }
});
