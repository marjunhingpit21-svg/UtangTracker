const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const sidebarToggleBtn = document.querySelectorAll('.sidebar-toggle');
const sidebarLogo = document.querySelector('.sidebar-logo');
const searchForm = document.querySelector('.search-form');
const taskPanel = document.getElementById('task-panel');
const taskOverlay = document.getElementById('task-panel-overlay');
const taskDetailsContent = document.getElementById('task-details-content');
const taskPanelTitle = document.getElementById('task-panel-title');
const taskPanelSubtitle = document.getElementById('task-panel-subtitle');
const closeTaskPanelBtn = document.getElementById('closeTaskPanel');

function openSidebar() {
    const isMobile = window.innerWidth <= 1024;
    if (!isMobile && sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
    }
}

function formatMoney(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(value || 0);
}

function formatDeadline(deadline) {
    if (!deadline) return 'No deadline';
    const date = new Date(deadline);
    if (Number.isNaN(date.getTime())) return 'No deadline';
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function openTaskPanel(listId, card) {
    if (!taskPanel || !taskDetailsContent) return;

    const listItems = window.listItems?.[listId] ?? [];
    const title = card?.querySelector('h3')?.textContent.trim() || 'List Details';
    const subtitle = card?.querySelector('p')?.textContent.trim() || '';

    taskPanelTitle.textContent = title;
    taskPanelSubtitle.textContent = subtitle;

    if (listItems.length === 0) {
        taskDetailsContent.innerHTML = '<div class="task-detail-empty">No list content available.</div>';
    } else {
        taskDetailsContent.innerHTML = listItems.map(item => {
            const statusClass = item.debt_status === 'paid' ? 'status-paid' : 'status-unpaid';
            return `
                <div class="task-item">
                    <div class="task-item-top">
                        <span class="task-item-name">${item.content_name}</span>
                        <span class="task-item-status ${statusClass}">${item.debt_status}</span>
                    </div>
                    <div class="task-item-meta">
                        <span>Amount: ${formatMoney(item.money_owed)}</span>
                        <span>Deadline: ${formatDeadline(item.deadline)}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    taskPanel.classList.add('open');
    taskOverlay.classList.add('active');
    taskPanel.setAttribute('aria-hidden', 'false');
}

function closeTaskPanel() {
    if (!taskPanel) return;
    taskPanel.classList.remove('open');
    taskOverlay.classList.remove('active');
    taskPanel.setAttribute('aria-hidden', 'true');
}

document.querySelectorAll('.sidebar-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const isMobile = window.innerWidth <= 1024;
        if (isMobile) {
            sidebar.classList.toggle('mobile-active');
            overlay.classList.toggle('active');
        } else {
            sidebar.classList.toggle('collapsed');
        }
    });
});

if (sidebarLogo) {
    sidebarLogo.addEventListener('click', openSidebar);
}

if (searchForm) {
    searchForm.addEventListener('click', event => {
        if (sidebar.classList.contains('collapsed')) {
            event.preventDefault();
            openSidebar();
        }
    });
}

if (closeTaskPanelBtn) {
    closeTaskPanelBtn.addEventListener('click', closeTaskPanel);
}

if (taskOverlay) {
    taskOverlay.addEventListener('click', closeTaskPanel);
}

overlay.addEventListener('click', () => {
    sidebar.classList.remove('mobile-active');
    overlay.classList.remove('active');
});

document.querySelectorAll('.debt-list').forEach(card => {
    card.addEventListener('click', event => {
        if (event.target.closest('button, form, a')) return;
        const listId = card.dataset.listId;
        if (listId) {
            openTaskPanel(listId, card);
        }
    });
});