window.renderTablePagination = function ({
    containerId,
    totalItems,
    currentPage,
    perPage = 10,
    onPageChange
}) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const totalPages = Math.max(1, Math.ceil(totalItems / perPage));
    const safePage = Math.min(Math.max(1, currentPage), totalPages);

    if (totalItems === 0) {
        container.innerHTML = `<small class="text-muted">Showing 0 entries</small><div></div>`;
        return;
    }

    const startItem = (safePage - 1) * perPage + 1;
    const endItem = Math.min(safePage * perPage, totalItems);

    // Build compact page number list (with ellipsis when > 7 pages)
    const pages = [];
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        pages.push(1);
        if (safePage > 3) pages.push('...');
        const start = Math.max(2, safePage - 1);
        const end = Math.min(totalPages - 1, safePage + 1);
        for (let i = start; i <= end; i++) pages.push(i);
        if (safePage < totalPages - 2) pages.push('...');
        pages.push(totalPages);
    }

    const pageButtonsHtml = pages.map(p => {
        if (p === '...') {
            return `<li class="page-item disabled"><span class="page-link border-0 text-muted px-2">...</span></li>`;
        }
        const isActive = p === safePage;
        const btnClass = isActive
            ? 'page-link bg-coffee text-white border-0 fw-bold shadow-sm rounded mx-1'
            : 'page-link text-dark bg-white border rounded mx-1';
        return `
            <li class="page-item ${isActive ? 'active' : ''}">
                <button type="button" class="${btnClass}" data-page="${p}">${p}</button>
            </li>
        `;
    }).join('');

    container.innerHTML = `
        <small class="text-muted">
            Showing <span class="fw-bold text-dark">${startItem}–${endItem}</span> of <span class="fw-bold text-dark">${totalItems}</span> entries
        </small>
        <nav aria-label="Table pagination">
            <ul class="pagination pagination-sm mb-0 align-items-center">
                <li class="page-item ${safePage === 1 ? 'disabled' : ''}">
                    <button type="button" class="page-link text-dark bg-white border rounded me-1" data-page="${safePage - 1}" ${safePage === 1 ? 'disabled' : ''}>
                        Previous
                    </button>
                </li>
                ${pageButtonsHtml}
                <li class="page-item ${safePage === totalPages ? 'disabled' : ''}">
                    <button type="button" class="page-link text-dark bg-white border rounded ms-1" data-page="${safePage + 1}" ${safePage === totalPages ? 'disabled' : ''}>
                        Next
                    </button>
                </li>
            </ul>
        </nav>
    `;

    container.querySelectorAll('button[data-page]').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetPage = parseInt(this.dataset.page, 10);
            if (!isNaN(targetPage) && targetPage >= 1 && targetPage <= totalPages && targetPage !== safePage) {
                onPageChange(targetPage);
            }
        });
    });
};