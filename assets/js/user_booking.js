// User Page Booking Functionality
console.log('user_booking.js loaded!');
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired!');
    // Check if we're on the user page
    const itemSearch = document.getElementById('itemSearch');
    console.log('itemSearch element:', itemSearch);
    if (!itemSearch) {
        console.log('itemSearch not found - exiting');
        return; // Exit if not on user page
    }
    console.log('itemSearch found - continuing...');

    // Autocomplete dropdown for item search
    const searchDropdown = document.getElementById('searchDropdown');
    let searchTimeout;

    // Item name autocomplete
    itemSearch.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 1) {
            searchDropdown.classList.remove('active');
            searchDropdown.innerHTML = '';
            return;
        }

        searchTimeout = setTimeout(() => {
            console.log('Fetching autocomplete for:', query);
            fetch('/ireserve/user/search_items.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    console.log('Autocomplete data:', data);
                    if (data.ok && data.results.length > 0) {
                        displayAutocomplete(data.results);
                    } else {
                        searchDropdown.innerHTML = '<div class="search-item" style="justify-content: center; padding: 10px;"><p>No items found</p></div>';
                        searchDropdown.classList.add('active');
                    }
                })
                .catch(error => {
                    console.error('Autocomplete error:', error);
                });
        }, 300);
    });

    function displayAutocomplete(results) {
        searchDropdown.innerHTML = '';
        
        results.forEach(item => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'search-item';
            itemDiv.innerHTML = `
                <img src="${item.image}" alt="${item.name}" class="search-item-img">
                <div class="search-item-info">
                    <h4>${item.name}</h4>
                    <p>Category: ${item.category} | Location: ${item.location}</p>
                    <p class="availability">Available: <strong>${item.available}</strong> / ${item.quantity}</p>
                </div>
            `;
            
            itemDiv.addEventListener('click', function() {
                itemSearch.value = item.name;
                searchDropdown.classList.remove('active');
                searchDropdown.innerHTML = '';
            });
            
            searchDropdown.appendChild(itemDiv);
        });
        
        searchDropdown.classList.add('active');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (searchDropdown && !itemSearch.contains(e.target) && !searchDropdown.contains(e.target)) {
            searchDropdown.classList.remove('active');
        }
    });


    // Initialize Flatpickr for date inputs
    const startDatePicker = flatpickr("#startDate", {
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            if (endDatePicker) {
                endDatePicker.set('minDate', dateStr);
            }
        }
    });

    const endDatePicker = flatpickr("#endDate", {
        dateFormat: "Y-m-d",
        minDate: "today"
    });

    // Search button click handler
    const searchBtn = document.getElementById('searchBtn');
    const resultsHeader = document.getElementById('resultsHeader');
    const resultsGrid = document.getElementById('resultsGrid');
    
    console.log('Search button:', searchBtn);
    console.log('Results header:', resultsHeader);
    console.log('Results grid:', resultsGrid);
    
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            console.log('Search button clicked!');
            
            const query = itemSearch.value.trim();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const quantity = document.getElementById('quantity').value;
            
            console.log('Query:', query, 'Start:', startDate, 'End:', endDate, 'Qty:', quantity);
            
            if (!query) {
                alert('Please enter an item name to search');
                return;
            }
            
            if (!startDate || !endDate) {
                alert('Please select start and end dates');
                return;
            }
            
            if (!quantity || quantity < 1) {
                alert('Please enter a valid quantity');
                return;
            }
            
            // Show loading
            resultsHeader.textContent = 'Searching...';
            resultsGrid.innerHTML = '<div class="loading">Loading results...</div>';
            
            console.log('Fetching results...');
            
            // Fetch search results
            fetch('/ireserve/user/search_items.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.ok && data.results.length > 0) {
                        displayResults(data.results, startDate, endDate, quantity);
                    } else {
                        resultsHeader.textContent = 'No Items Found';
                        resultsGrid.innerHTML = '<div class="no-items"><p>No items found matching "' + query + '"</p></div>';
                    }
                    
                    // Scroll to results
                    document.getElementById('searchResults').scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => {
                    console.error('Search error:', error);
                    resultsHeader.textContent = 'Error';
                    resultsGrid.innerHTML = '<div class="no-items"><p>Error searching items. Please try again.</p></div>';
                });
        });
    }
    
    function displayResults(results, startDate, endDate, requestedQty) {
        resultsHeader.textContent = 'Search Results (' + results.length + ' items found)';
        resultsGrid.innerHTML = '';
        
        results.forEach(item => {
            const available = item.available;
            const isAvailable = available >= parseInt(requestedQty);
            const status = item.status ? item.status.toLowerCase() : 'active';
            const canBook = isAvailable && (status === 'active' || status === 'available');
            
            // Status badge color
            let statusColor = 'green';
            let statusText = status.charAt(0).toUpperCase() + status.slice(1);
            if (status === 'maintenance') {
                statusColor = 'orange';
            } else if (status === 'unavailable') {
                statusColor = 'red';
            }
            
            const card = document.createElement('div');
            card.className = 'popular_card';
            card.innerHTML = `
                <img src="${item.image}" alt="${item.name}">
                <div class="popular_content">
                    <h3>${item.name}</h3>
                    <h4><i class="ri-map-pin-line"></i> ${item.location}</h4>
                    <p><strong>Category:</strong> ${item.category}</p>
                    <p><strong>SKU:</strong> ${item.sku}</p>
                    <p><strong>Status:</strong> <span style="color: ${statusColor}; font-weight: 600;">${statusText}</span></p>
                    <p><strong>Available:</strong> ${available} / ${item.quantity}</p>
                    <p><strong>Requested:</strong> ${requestedQty} units</p>
                    ${canBook ? 
                        `<p style="color: green;"><i class="ri-checkbox-circle-line"></i> <strong>Available for your booking</strong></p>
                         <form action="/ireserve/user/submit_booking.php" method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="item_id" value="${item.id}">
                            <input type="hidden" name="start_date" value="${startDate}">
                            <input type="hidden" name="end_date" value="${endDate}">
                            <input type="hidden" name="quantity" value="${requestedQty}">
                            <button type="submit" class="btn-book-item">
                                <i class="ri-bookmark-line"></i> Book This Item
                            </button>
                         </form>` 
                        : 
                        (status === 'maintenance' || status === 'unavailable') ?
                        `<p style="color: orange;"><i class="ri-tools-line"></i> <strong>Item is currently ${status}</strong></p>
                         <button class="btn-unavailable" disabled style="background: #ff9800;">
                            <i class="ri-close-circle-line"></i> Cannot Book (${statusText})
                         </button>`
                        :
                        `<p style="color: red;"><i class="ri-close-circle-line"></i> <strong>Insufficient quantity (Only ${available} available)</strong></p>
                         <button class="btn-unavailable" disabled>
                            <i class="ri-close-circle-line"></i> Not Available
                         </button>`
                    }
                </div>
            `;
            
            resultsGrid.appendChild(card);
        });
    }
});
