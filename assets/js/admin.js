let list = document.querySelectorAll(".navigation li");

function activeLink() {
    list.forEach((item) => {
        item.classList.remove("hovered");
    });
    this.classList.add("hovered");
}

list.forEach((item) => item.addEventListener("mouseover", activeLink));

//Menu toggle
let toggle = document.querySelector(".toggle");
let navigation =document.querySelector(".navigation");
let main = document.querySelector(".main");

toggle.onclick = function() {
    navigation.classList.toggle("active");
    main.classList.toggle("active");
};

// ============================================
// Bookings Page Functions
// ============================================

// Event delegation for bookings actions and modal
document.addEventListener('click', function(e) {
    const t = e.target;

    // View modal
    if (t.matches('[data-view]')) {
        try {
            const data = JSON.parse(t.getAttribute('data-view'));
            openBookingModal(data);
        } catch(err) {
            console.error('View error:', err);
            alert('Error loading booking details');
        }
    }

    // Approve/Reject/Returned
    if (t.matches('[data-action]')) {
        const action = t.getAttribute('data-action');
        const ref    = t.getAttribute('data-ref');
        const note = prompt(`Add note for ${action.toUpperCase()} (optional):`, '');
        // Check if user clicked Cancel (null) vs OK with empty string ('')
        if (note === null) {
            // User clicked Cancel, do nothing
            return;
        }
        if (ref) {
            postAction(action, ref, note);
        }
    }

    // Close modal
    if (t.id === 'md-close' || t.id === 'mb') {
        closeModal();
    }
});

// Helper functions for bookings
function formatDT(s) {
    const d = new Date(s.replace(' ', 'T'));
    return d.toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

function formatDate(s) {
    const d = new Date(s.replace(' ', 'T'));
    return d.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });
}

function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[m]));
}

function openBookingModal(data) {
    // Build status badge HTML
    const statusColors = {
        'pending': { bg: '#fef3c7', border: '#fcd34d', text: '#92400e' },
        'approved': { bg: '#d1fae5', border: '#6ee7b7', text: '#065f46' },
        'rejected': { bg: '#fee2e2', border: '#fca5a5', text: '#991b1b' },
        'taken': { bg: '#e0e7ff', border: '#a5b4fc', text: '#3730a3' },
        'returned': { bg: '#dbeafe', border: '#93c5fd', text: '#1e40af' }
    };
    const statusColor = statusColors[data.status] || statusColors.pending;
    const statusBadge = `<span class="modal-status-badge" style="background:${statusColor.bg}; border:1px solid ${statusColor.border}; color:${statusColor.text}">${escapeHtml(data.status)}</span>`;

    // Build date range chip
    const dateRange = `
        <div class="date-range-chip">
            <i class="bi bi-calendar-event"></i>
            <span>${formatDate(data.start_at)}</span>
            <span class="date-separator">→</span>
            <span>${formatDate(data.end_at)}</span>
        </div>
    `;

    // Build audit strip if decision was made
    let auditStrip = '';
    if (data.decided_at && data.status !== 'pending') {
        const auditClass = data.status === 'rejected' ? 'rejected' : (data.status === 'returned' ? 'returned' : (data.status === 'taken' ? 'taken' : ''));
        const auditIcon = data.status === 'approved' ? 'bi-check-circle-fill' : (data.status === 'rejected' ? 'bi-x-circle-fill' : (data.status === 'taken' ? 'bi-box-arrow-up' : 'bi-arrow-return-left'));
        const statusText = data.status === 'approved' ? 'Approved' : (data.status === 'rejected' ? 'Rejected' : (data.status === 'taken' ? 'Marked as taken' : 'Marked as returned'));
        auditStrip = `
            <div class="audit-strip ${auditClass}">
                <i class="bi ${auditIcon}"></i>
                <span><strong>${escapeHtml(statusText)}</strong> on ${formatDT(data.decided_at)}</span>
            </div>
        `;
    }

    // Build modal HTML with sections
    const html = `
        <div class="modal-section">
            <div class="modal-section-title">Item & User Details</div>
            <div class="modal-grid">
                <div class="modal-row">
                    <div class="modal-label">Item</div>
                    <div class="modal-value">${escapeHtml(data.item_name)}</div>
                </div>
                <div class="modal-row">
                    <div class="modal-label">Item ID</div>
                    <div class="modal-value">${escapeHtml(String(data.item_code))}</div>
                </div>
                <div class="modal-row">
                    <div class="modal-label">Location</div>
                    <div class="modal-value ${data.item_location ? '' : 'empty'}">${escapeHtml(data.item_location || 'Not specified')}</div>
                </div>
                <div class="modal-row">
                    <div class="modal-label">Quantity</div>
                    <div class="modal-value">${escapeHtml(String(data.qty))} unit(s)</div>
                </div>
                <div class="modal-row">
                    <div class="modal-label">User</div>
                    <div class="modal-value">${escapeHtml(data.user_name)}</div>
                </div>
                <div class="modal-row">
                    <div class="modal-label">Matrics Number</div>
                    <div class="modal-value ${data.matrics_number ? '' : 'empty'}">${escapeHtml(data.matrics_number || 'Not provided')}</div>
                </div>
                <div class="modal-row">
                    <div class="modal-label">Phone</div>
                    <div class="modal-value ${data.phone_number ? '' : 'empty'}">${escapeHtml(data.phone_number || 'Not provided')}</div>
                </div>
            </div>
        </div>
        
        <div class="modal-section">
            <div class="modal-section-title">Booking Period</div>
            ${dateRange}
        </div>
        
        <div class="modal-section">
            <div class="modal-section-title">Purpose & Status</div>
            <div class="modal-grid">
                <div class="modal-row">
                    <div class="modal-label">Purpose</div>
                    <div class="modal-value ${data.purpose ? '' : 'empty'}">${escapeHtml(data.purpose || 'Not specified')}</div>
                </div>
                <div class="modal-row">
                    <div class="modal-label">Status</div>
                    <div class="modal-value">${statusBadge}</div>
                </div>
                ${data.admin_note ? `
                <div class="modal-row">
                    <div class="modal-label">Admin Note</div>
                    <div class="modal-value">${escapeHtml(data.admin_note)}</div>
                </div>
                ` : ''}
            </div>
        </div>
        
        ${auditStrip ? `<div class="modal-section">${auditStrip}</div>` : ''}
    `;

    // Set modal content
    document.getElementById('md-title').innerHTML = `
        <h3>
            Booking Details
            <span class="ref-code">#${escapeHtml(data.ref_code)}</span>
        </h3>
    `;
    document.getElementById('md-body').innerHTML = html;
    document.getElementById('mb').style.display = 'block';
    document.getElementById('md').style.display = 'block';
    
    // Focus on close button for keyboard accessibility
    setTimeout(() => {
        document.getElementById('md-close').focus();
    }, 100);
}

function openModal(title, html) {
    document.getElementById('md-title').innerHTML = `<h3>${title}</h3>`;
    document.getElementById('md-body').innerHTML = html;
    document.getElementById('mb').style.display = 'block';
    document.getElementById('md').style.display = 'block';
}

function closeModal() {
    document.getElementById('mb').style.display = 'none';
    document.getElementById('md').style.display = 'none';
}

// Keyboard support: ESC to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('md');
        if (modal && modal.style.display === 'block') {
            closeModal();
        }
    }
});

async function postAction(action, ref_code, note) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('ref_code', ref_code);
    fd.append('note', note || '');

    const resp = await fetch('bookings_action.php', { method: 'POST', body: fd });
    const data = await resp.json().catch(() => ({ ok: false, message: 'Invalid response' }));
    if (data.ok) {
        location.reload();
    } else {
        alert(data.message || 'Failed to update booking.');
    }
}

// ============================================
// Global Search Functionality
// ============================================

let searchTimeout = null;
const searchInput = document.getElementById('globalSearch');
const searchResults = document.getElementById('searchResults');

if (searchInput && searchResults) {
    // Search on input
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            searchResults.innerHTML = '';
            return;
        }
        
        // Debounce search requests
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    // Focus search input on Ctrl+K or Cmd+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
        
        // ESC to close search results
        if (e.key === 'Escape' && searchResults.style.display === 'block') {
            searchResults.style.display = 'none';
            searchInput.blur();
        }
    });
}

async function performSearch(query) {
    try {
        const response = await fetch(`/ireserve/admin/search.php?q=${encodeURIComponent(query)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        if (!data.results || data.total === 0) {
            searchResults.innerHTML = '<div class="no-results"><i class="bi bi-search"></i><p>No results found</p></div>';
            searchResults.style.display = 'block';
            return;
        }
        
        let html = '<div class="search-results-container">';
        
        // Users section
        if (data.results.users && data.results.users.length > 0) {
            html += '<div class="search-section"><div class="search-section-title"><i class="bi bi-people"></i> Users</div>';
            data.results.users.forEach(user => {
                const avatar = user.picture_url || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"%3E%3Cpath d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/%3E%3C/svg%3E';
                const roleBadge = user.role === 'admin' ? '<span class="role-badge admin">Admin</span>' : '<span class="role-badge user">User</span>';
                html += `
                    <a href="${user.url}" class="search-item">
                        <img src="${avatar}" alt="${escapeHtml(user.name)}" class="search-avatar">
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(user.name)} ${roleBadge}</div>
                            <div class="search-item-subtitle">${escapeHtml(user.email)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        // Inventory section
        if (data.results.inventory && data.results.inventory.length > 0) {
            html += '<div class="search-section"><div class="search-section-title"><i class="bi bi-box"></i> Inventory</div>';
            data.results.inventory.forEach(item => {
                const statusClass = item.status.toLowerCase();
                const statusBadge = `<span class="status-badge ${statusClass}">${item.status}</span>`;
                html += `
                    <a href="${item.url}" class="search-item">
                        <div class="search-item-icon"><i class="bi bi-box-seam"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(item.name)} ${statusBadge}</div>
                            <div class="search-item-subtitle">SKU: ${escapeHtml(item.sku)} • ${escapeHtml(item.location)} • Qty: ${item.quantity}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        // Bookings section
        if (data.results.bookings && data.results.bookings.length > 0) {
            html += '<div class="search-section"><div class="search-section-title"><i class="bi bi-calendar-check"></i> Bookings</div>';
            data.results.bookings.forEach(booking => {
                const statusClass = booking.status.toLowerCase();
                const statusBadge = `<span class="status-badge ${statusClass}">${booking.status}</span>`;
                html += `
                    <a href="${booking.url}" class="search-item">
                        <div class="search-item-icon"><i class="bi bi-calendar-check"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(booking.reference)} ${statusBadge}</div>
                            <div class="search-item-subtitle">${escapeHtml(booking.user_name)} • ${escapeHtml(booking.item_name)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        html += '</div>';
        searchResults.innerHTML = html;
        searchResults.style.display = 'block';
        
    } catch (error) {
        console.error('Search error:', error);
        searchResults.innerHTML = '<div class="no-results"><i class="bi bi-exclamation-triangle"></i><p>Search error occurred</p></div>';
        searchResults.style.display = 'block';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}