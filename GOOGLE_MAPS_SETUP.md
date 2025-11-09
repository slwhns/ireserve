# Google Maps Integration Setup Instructions

## 📍 Google Maps for iReserve

This feature allows users to see the physical location of items on Google Maps.

---

## ⚙️ Setup Steps

### 1. Get Google Maps API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable **Maps JavaScript API**
4. Go to **Credentials** → Create **API Key**
5. (Optional but recommended) Restrict your API key:
   - Application restrictions: HTTP referrers
   - Add your domain: `http://localhost/*` for testing
   - API restrictions: Maps JavaScript API

### 2. Run Database Migration

Execute the SQL file in phpMyAdmin:

```sql
-- File: add_location_columns.sql
ALTER TABLE `inventory_items` 
ADD COLUMN `latitude` DECIMAL(10, 6) NULL AFTER `location`,
ADD COLUMN `longitude` DECIMAL(10, 6) NULL AFTER `latitude`;
```

### 3. Update API Keys in Code

Replace `YOUR_GOOGLE_MAPS_API_KEY` with your actual API key in these files:

1. **user/viewItem.php** (line ~380)
2. **admin/inventory_add.php** (line ~220)
3. **admin/inventory_edit.php** (line ~280)

Search for:
```javascript
src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap"
```

Replace `YOUR_GOOGLE_MAPS_API_KEY` with your actual key.

---

## 🎯 Features

### Admin Panel
- **Add Item**: Interactive map to set item location by clicking
- **Edit Item**: Update item location, drag marker to adjust
- **Coordinates**: Latitude and longitude auto-filled when clicking map

### User View
- **View Item**: Display item location on Google Maps
- **Info Window**: Click marker to see item details
- **Navigation**: Street view and map controls available

---

## 🗺️ How to Use

### For Admins (Adding/Editing Items)

1. Fill in item details
2. Enter location name (e.g., "Library, Level 2")
3. Scroll to map section
4. Click on the map where the item is located
5. Marker will appear and coordinates will auto-fill
6. Drag marker to adjust position if needed
7. Save item

### For Users (Viewing Items)

1. Go to "View Item" page
2. Scroll down to see the map (if location is set)
3. Map shows the exact location of the item
4. Use map controls to zoom, street view, etc.

---

## 📝 Default Location

Default map center: **Kota Bharu, Kelantan, Malaysia**
- Latitude: 6.1167
- Longitude: 102.2833

You can change this in the `initMap()` function in both admin files.

---

## 🔧 Troubleshooting

**Map not showing?**
- Check if API key is correct
- Make sure Maps JavaScript API is enabled
- Check browser console for errors
- Verify database columns exist

**Coordinates not saving?**
- Check database migration was successful
- Verify latitude/longitude columns in `inventory_items` table

---

## 💡 Future Enhancements

- Search location by address (Geocoding API)
- Auto-detect current location
- Multiple item locations on one map (All Items page)
- Directions to item location

