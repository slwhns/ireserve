function showForm(formId) {
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
    document.getElementById(formId).classList.add("active");
}

function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
    }
}

// ========== User Page Functionality ==========

// Profile Dropdown Toggle
document.addEventListener('DOMContentLoaded', function() {

    const profilePic = document.getElementById('profilePic');
    const dropdownMenu = document.getElementById('dropdownMenu');
    
    if (profilePic && dropdownMenu) {
        profilePic.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.profile-dropdown')) {
                dropdownMenu.classList.remove('active');
            }
        });
    }

    // Notification Modal Handler
    const modal = document.getElementById('notificationModal');
    const btnOk = document.getElementById('notificationBtnOk');
    const urlParams = new URLSearchParams(window.location.search);
    
    // Error messages
    const errorMessages = {
        'invalid_item': 'Invalid item selected.',
        'item_not_found': 'The requested item could not be found.',
        'missing_fields': 'Please fill in all required fields.',
        'invalid_date': 'Invalid date format.',
        'past_date': 'Start date cannot be in the past.',
        'end_before_start': 'End date must be after start date.',
        'item_unavailable': 'This item is currently unavailable.',
        'item_not_bookable': 'This item cannot be booked at this time (Status: ' + (urlParams.get('status') || 'unavailable') + ').',
        'dates_fully_booked': 'Sorry, this item is fully booked for the selected dates (' + (urlParams.get('start') || '') + ' to ' + (urlParams.get('end') || '') + '). Please choose different dates.',
        'insufficient_quantity': 'Not enough quantity available.',
        'insufficient_quantity_for_dates': 'Not enough quantity available for the selected dates. Only ' + (urlParams.get('available') || '0') + ' available, but you requested ' + (urlParams.get('requested') || '0') + '. Please reduce quantity or choose different dates.',
        'booking_failed': 'Failed to submit booking. Please try again.',
        'booking_not_found': 'Booking not found or access denied.',
        'cannot_edit_approved_booking': 'You cannot edit a booking that has already been approved.',
        'cannot_cancel_approved_booking': 'You cannot cancel a booking that has already been approved.',
        'update_failed': 'Failed to update booking. Please try again.',
        'missing_ref': 'Booking reference is missing.'
    };

    // Success messages
    const successMessages = {
        'booking_submitted': 'Booking request submitted successfully! Please wait for admin approval.',
        'booking_updated': 'Booking updated successfully!',
        'booking_cancelled': 'Booking cancelled successfully.',
        'profile_updated': 'Profile information updated successfully!',
        'password_changed': 'Password changed successfully!',
        'picture_updated': 'Profile picture updated successfully!',
        'picture_removed': 'Profile picture removed successfully!'
    };

    // Show error notification
    if (urlParams.has('error')) {
        const error = urlParams.get('error');
        const message = errorMessages[error] || 'An error occurred. Please try again.';
        showNotification(message, 'error');
    }

    // Show success notification
    if (urlParams.has('success')) {
        const success = urlParams.get('success');
        const message = successMessages[success] || 'Action completed successfully!';
        showNotification(message, 'success');
    }

    // Close notification on OK button click
    if (btnOk) {
        btnOk.addEventListener('click', function() {
            closeNotification();
        });
    }

    // Close notification on outside click
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeNotification();
            }
        });
    }
});

// Show notification function
function showNotification(message, type = 'info') {
    const modal = document.getElementById('notificationModal');
    const icon = document.getElementById('notificationIcon');
    const messageEl = document.getElementById('notificationMessage');
    
    if (!modal || !icon || !messageEl) {
        return;
    }

    // Set icon based on type
    const icons = {
        'success': '<i class="ri-checkbox-circle-fill"></i>',
        'error': '<i class="ri-error-warning-fill"></i>',
        'info': '<i class="ri-information-fill"></i>'
    };

    icon.innerHTML = icons[type] || icons['info'];
    icon.className = 'notification-icon ' + type;
    messageEl.innerHTML = message;
    
    modal.classList.add('show');
}

// Close notification function
function closeNotification() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.classList.remove('show');
        
        // Remove URL parameters
        const url = new URL(window.location);
        url.searchParams.delete('error');
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
}