-- Insert dummy bookings data for testing
-- IMPORTANT: This will auto-generate unique ref codes and use your actual user/item IDs

-- Get available user and item IDs
SET @user1 = (SELECT id FROM users WHERE role='user' ORDER BY id LIMIT 1);
SET @user2 = (SELECT id FROM users WHERE role='user' ORDER BY id LIMIT 1 OFFSET 1);
SET @admin = (SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1);
SET @item1 = (SELECT item_id FROM inventory_items ORDER BY item_id LIMIT 1);
SET @item2 = (SELECT item_id FROM inventory_items ORDER BY item_id LIMIT 1 OFFSET 1);
SET @item3 = (SELECT item_id FROM inventory_items ORDER BY item_id LIMIT 1 OFFSET 2);
SET @item4 = (SELECT item_id FROM inventory_items ORDER BY item_id LIMIT 1 OFFSET 3);

-- Clean up old test data (optional - uncomment if you want to start fresh)
-- DELETE FROM bookings WHERE ref_code LIKE 'BK-2025-%';

-- If you don't have enough items or users, this will fail gracefully
-- Sample bookings with different statuses (insert one by one to avoid column name issues)

-- Pending bookings
INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status) 
VALUES ('BK-2025-001', @user1, @item1, 2, '2025-11-05 09:00:00', '2025-11-05 17:00:00', 'Class presentation and demo', 'pending');

INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status) 
VALUES ('BK-2025-002', IFNULL(@user2, @user1), IFNULL(@item2, @item1), 1, '2025-11-06 10:00:00', '2025-11-06 15:00:00', 'Workshop materials', 'pending');

INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status) 
VALUES ('BK-2025-003', @user1, IFNULL(@item3, @item1), 3, '2025-11-07 08:00:00', '2025-11-08 18:00:00', 'Student project exhibition', 'pending');

-- Approved bookings
INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status, admin_note, decided_by, decided_at) 
VALUES ('BK-2025-004', IFNULL(@user2, @user1), @item1, 1, '2025-11-03 13:00:00', '2025-11-03 16:00:00', 'Lab experiment', 'approved', 'Approved for afternoon slot', @admin, '2025-11-02 14:30:00');

INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status, admin_note, decided_by, decided_at) 
VALUES ('BK-2025-005', @user1, IFNULL(@item4, @item1), 2, '2025-11-10 09:00:00', '2025-11-10 12:00:00', 'Field trip equipment', 'approved', 'All set, pickup at 8:30am', @admin, '2025-11-02 15:00:00');

-- Rejected bookings
INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status, admin_note, decided_by, decided_at) 
VALUES ('BK-2025-006', IFNULL(@user2, @user1), IFNULL(@item2, @item1), 5, '2025-11-04 10:00:00', '2025-11-04 18:00:00', 'Event setup', 'rejected', 'Quantity not available', @admin, '2025-11-02 10:15:00');

INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status, admin_note, decided_by, decided_at) 
VALUES ('BK-2025-007', @user1, IFNULL(@item3, @item1), 1, '2025-11-04 14:00:00', '2025-11-04 17:00:00', 'Personal use', 'rejected', 'Purpose does not meet policy requirements', @admin, '2025-11-02 11:00:00');

-- Returned bookings
INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status, admin_note, decided_by, decided_at) 
VALUES ('BK-2025-008', IFNULL(@user2, @user1), @item1, 1, '2025-11-01 09:00:00', '2025-11-01 17:00:00', 'Class demo', 'returned', 'Item returned in good condition', @admin, '2025-11-01 18:00:00');

INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status, admin_note, decided_by, decided_at) 
VALUES ('BK-2025-009', @user1, IFNULL(@item2, @item1), 2, '2025-10-30 10:00:00', '2025-10-30 16:00:00', 'Training session', 'returned', 'Returned on time', @admin, '2025-10-30 16:30:00');

INSERT INTO bookings (ref_code, user_id, item_id, qty, start_at, end_at, purpose, status, admin_note, decided_by, decided_at) 
VALUES ('BK-2025-010', IFNULL(@user2, @user1), IFNULL(@item4, @item1), 1, '2025-10-28 08:00:00', '2025-10-29 17:00:00', 'Two-day conference', 'returned', 'All items returned', @admin, '2025-10-29 17:15:00');

-- Note: Make sure user_id and item_id exist in your users and inventory_items tables
-- Adjust the IDs above to match actual records in your database
-- If decided_by references admin user, ensure that user ID exists in users table with role='admin'
