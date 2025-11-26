// My Bookings Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    const bookingsGrid = document.getElementById('bookingsGrid');
    let allBookings = [];

    // Load bookings on page load
    loadBookings('all');

    // Filter tab click handlers
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get filter status
            const filterStatus = this.getAttribute('data-filter');
            
            // Filter and display bookings
            filterBookings(filterStatus);
        });
    });

    function loadBookings(status = 'all') {
        // Show loading state
        bookingsGrid.innerHTML = `
            <div class="loading-bookings">
                <div class="loading-spinner"></div>
                <p>Loading your bookings...</p>
            </div>
        `;

        // Fetch bookings from server
        fetch('/ireserve/user/get_bookings.php?status=' + status)
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    allBookings = data.bookings;
                    displayBookings(data.bookings);
                } else {
                    showError('Failed to load bookings');
                }
            })
            .catch(error => {
                console.error('Error loading bookings:', error);
                showError('Error loading bookings. Please try again.');
            });
    }

    function filterBookings(status) {
        if (status === 'all') {
            displayBookings(allBookings);
        } else {
            const filtered = allBookings.filter(booking => booking.status === status);
            displayBookings(filtered);
        }
    }

    function displayBookings(bookings) {
        if (bookings.length === 0) {
            bookingsGrid.innerHTML = `
                <div class="empty-bookings">
                    <i class="ri-inbox-line"></i>
                    <h2>No Bookings Found</h2>
                    <p>You haven't made any bookings yet</p>
                    <a href="/ireserve/user/bookingForm.php" class="btn-start-booking">
                        <i class="ri-add-circle-line"></i> Make Your First Booking
                    </a>
                </div>
            `;
            return;
        }

        bookingsGrid.innerHTML = '';

        bookings.forEach(booking => {
            const card = createBookingCard(booking);
            bookingsGrid.appendChild(card);
        });
    }

    function createBookingCard(booking) {
        const card = document.createElement('div');
        card.className = 'booking-card';

        const statusClass = `status-${booking.status}`;
        const statusText = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);

        // Format dates
        const startDate = formatDate(booking.start_at);
        const endDate = formatDate(booking.end_at);
        const createdDate = formatDateTime(booking.created_at);

        card.innerHTML = `
            <div class="booking-card-header">
                <div class="booking-ref">
                    <i class="ri-bookmark-line"></i>
                    ${booking.ref_code}
                </div>
                <span class="booking-status ${statusClass}">${statusText}</span>
            </div>

            <div class="booking-card-body">
                <div class="booking-item">
                    <img src="${booking.item_image || '/ireserve/assets/images/no-image.png'}" 
                         alt="${booking.item_name}" 
                         class="booking-item-image">
                    <div class="booking-item-info">
                        <h3>${booking.item_name}</h3>
                        <p><i class="ri-price-tag-3-line"></i> ${booking.item_sku || 'N/A'}</p>
                        <p><i class="ri-map-pin-line"></i> ${booking.item_location || 'N/A'}</p>
                    </div>
                </div>

                <div class="booking-details">
                    <div class="detail-item">
                        <i class="ri-calendar-line"></i>
                        <div class="detail-content">
                            <div class="detail-label">Start Date</div>
                            <div class="detail-value">${startDate}</div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="ri-calendar-check-line"></i>
                        <div class="detail-content">
                            <div class="detail-label">End Date</div>
                            <div class="detail-value">${endDate}</div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="ri-stack-line"></i>
                        <div class="detail-content">
                            <div class="detail-label">Quantity</div>
                            <div class="detail-value">${booking.qty} ${booking.qty > 1 ? 'units' : 'unit'}</div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="ri-time-line"></i>
                        <div class="detail-content">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value">${calculateDuration(booking.start_at, booking.end_at)}</div>
                        </div>
                    </div>
                </div>

                ${booking.purpose ? `
                    <div class="booking-purpose">
                        <p><strong><i class="ri-file-text-line"></i> Purpose:</strong> ${booking.purpose}</p>
                    </div>
                ` : ''}

                ${booking.admin_note ? `
                    <div class="admin-note">
                        <p><strong><i class="ri-message-3-line"></i> Admin Note:</strong> ${booking.admin_note}</p>
                    </div>
                ` : ''}
            </div>

            <div class="booking-card-footer">
                <div class="created-time">
                    <i class="ri-time-line"></i>
                    Booked on ${createdDate}
                </div>
                ${booking.status === 'pending' ? `
                    <div class="booking-actions">
                        <button class="btn-edit-booking" data-ref="${booking.ref_code}">
                            <i class="ri-edit-line"></i> Edit
                        </button>
                        <button class="btn-cancel-booking" data-ref="${booking.ref_code}">
                            <i class="ri-close-circle-line"></i> Cancel
                        </button>
                    </div>
                ` : ''}
            </div>
        `;

        // Add event listeners for edit and cancel buttons
        if (booking.status === 'pending') {
            const editBtn = card.querySelector('.btn-edit-booking');
            const cancelBtn = card.querySelector('.btn-cancel-booking');
            
            if (editBtn) {
                editBtn.addEventListener('click', () => editBooking(booking.ref_code));
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => cancelBooking(booking.ref_code));
            }
        }

        return card;
    }

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    function formatDateTime(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    function calculateDuration(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) return 'Same day';
        if (diffDays === 1) return '1 day';
        return `${diffDays} days`;
    }

    function showError(message) {
        bookingsGrid.innerHTML = `
            <div class="empty-bookings">
                <i class="ri-error-warning-line" style="color: #dc3545;"></i>
                <h2>Error</h2>
                <p>${message}</p>
                <button onclick="location.reload()" class="btn-start-booking">
                    <i class="ri-refresh-line"></i> Reload Page
                </button>
            </div>
        `;
    }

    function editBooking(refCode) {
        // Redirect to edit booking page
        window.location.href = `/ireserve/user/editBooking.php?ref=${refCode}`;
    }

    function cancelBooking(refCode) {
        if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
            return;
        }

        // Send cancel request to server
        fetch('/ireserve/user/cancel_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ref_code=${encodeURIComponent(refCode)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                alert('Booking cancelled successfully!');
                // Reload bookings
                loadBookings('all');
            } else {
                alert('Failed to cancel booking: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error cancelling booking:', error);
            alert('Error cancelling booking. Please try again.');
        });
    }
});
