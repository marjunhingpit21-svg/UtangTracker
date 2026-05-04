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
            const isPaid = item.debt_status === 'paid';
            const statusClass = isPaid ? 'status-paid' : 'status-unpaid';
            return `
                <div class="task-item" data-content-id="${item.content_id}">
                    <div class="task-item-top">
                        <span class="task-item-name">${item.content_name}</span>
                        <span class="task-item-status ${statusClass}">${item.debt_status}</span>
                    </div>
                    <div class="task-item-meta">
                        <span>Amount: ${formatMoney(item.money_owed)}</span>
                        <span>Deadline: ${formatDeadline(item.deadline)}</span>
                    </div>
                    <div class="task-item-actions">
                        ${!isPaid ? `<button class="btn-mark-paid" data-content-id="${item.content_id}">
                            <i class="fa fa-check"></i> Mark as Paid
                        </button>` : `<span class="paid-label"><i class="fa fa-check-circle"></i> Paid</span>`}
                    </div>
                </div>
            `;
        }).join('');

        taskDetailsContent.querySelectorAll('.btn-mark-paid').forEach(btn => {
            btn.addEventListener('click', async () => {
                const contentId = btn.dataset.contentId;
                btn.disabled = true;
                btn.textContent = 'Updating…';

                const formData = new FormData();
                formData.append('update_status', '1');
                formData.append('content_id', contentId);
                formData.append('new_status', 'paid');

                try {
                    await fetch('index.php', { method: 'POST', body: formData });

                    // Update local data
                    for (const items of Object.values(window.listItems)) {
                        const item = items.find(i => String(i.content_id) === String(contentId));
                        if (item) { item.debt_status = 'paid'; break; }
                    }

                    // Re-render the task item
                    const taskItem = taskDetailsContent.querySelector(`.task-item[data-content-id="${contentId}"]`);
                    if (taskItem) {
                        taskItem.querySelector('.task-item-status').className = 'task-item-status status-paid';
                        taskItem.querySelector('.task-item-status').textContent = 'paid';
                        taskItem.querySelector('.task-item-actions').innerHTML =
                            `<span class="paid-label"><i class="fa fa-check-circle"></i> Paid</span>`;
                    }
                } catch {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-check"></i> Mark as Paid';
                }
            });
        });
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

const toggleEditBtn = document.getElementById('toggleEditBtn');
const cancelEditBtn = document.getElementById('cancelEditBtn');
const profileCard = document.querySelector('.profile-card.overview-card');

function setProfileEditState(isEditing) {
    if (!profileCard || !toggleEditBtn) return;
    profileCard.classList.toggle('editing', isEditing);
    toggleEditBtn.innerHTML = `
        <span class="material-symbols-outlined">${isEditing ? 'close' : 'edit'}</span>
        ${isEditing ? 'Cancel' : 'Edit'}
    `;
    toggleEditBtn.setAttribute('aria-expanded', isEditing ? 'true' : 'false');
}

if (toggleEditBtn) {
    toggleEditBtn.addEventListener('click', () => {
        const isEditing = profileCard.classList.contains('editing');
        setProfileEditState(!isEditing);
    });
}

if (cancelEditBtn) {
    cancelEditBtn.addEventListener('click', () => {
        setProfileEditState(false);
    });
}

document.querySelectorAll('.debt-list').forEach(card => {
    card.addEventListener('click', event => {
        if (event.target.closest('button, form, a')) return;
        const listId = card.dataset.listId;
        if (listId) {
            openTaskPanel(listId, card);
        }
    });
});