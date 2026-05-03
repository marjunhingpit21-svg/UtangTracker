const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const sidebarToggleBtn = document.querySelectorAll(".sidebar-toggle");
const sidebarLogo = document.querySelector('.sidebar-logo');
const searchForm = document.querySelector('.search-form');

function openSidebar() {
    const isMobile = window.innerWidth <= 1024;
    if (!isMobile && sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
    }
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

overlay.addEventListener('click', () => {
    sidebar.classList.remove('mobile-active');
    overlay.classList.remove('active');
});