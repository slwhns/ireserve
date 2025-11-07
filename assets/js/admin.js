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