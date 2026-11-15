-- Mendly sample/testing data
-- Import this AFTER mendly-schema.sql (needs the tables to already exist).

USE mendly_db;

INSERT INTO categories (category_name, icon) VALUES
('Leather Repair', 'leather.svg'),
('Book Restoration', 'book.svg'),
('Shoe Repair', 'shoe.svg'),
('Clothing Alteration', 'clothing.svg'),
('Plushie & Doll Repair', 'doll.svg');

-- Sample password hash below corresponds to a test password used across
-- all sample accounts (set by whoever generated this seed data).
INSERT INTO experts
(full_name, email, password, phone, location, business_name, experience,
 description, avatar, bio, years_experience, completed_repairs, response_rate, average_response_time, verified)
VALUES
('Alex Tan', 'alex@mendly.com', '$2y$10$T4YhVavDUHqgFEcv7Uy/AeclkNLlAHVVOv.pr2hPq9Cw4zVdvagma', '0123456789', 'Kuala Lumpur',
 'Alex Tan Leather Studio', '5-10', 'Luxury leather restoration specialist.', 'images/experts/alex.jpg',
 'Luxury leather restoration specialist.', 8, 312, 98.50, '2 hours', TRUE),

('Sarah Lim', 'sarah@mendly.com', '$2y$10$T4YhVavDUHqgFEcv7Uy/AeclkNLlAHVVOv.pr2hPq9Cw4zVdvagma', '0123456790', 'Selangor',
 'Sarah Lim Alterations', '10+', 'Professional clothing alteration expert.', 'images/experts/sarah.jpg',
 'Professional clothing alteration expert.', 10, 486, 99.20, '1 hour', TRUE),

('Jason Lee', 'jason@mendly.com', '$2y$10$T4YhVavDUHqgFEcv7Uy/AeclkNLlAHVVOv.pr2hPq9Cw4zVdvagma', '0123456791', 'Penang',
 'Jason Lee Book Restoration', '10+', 'Antique book restoration expert.', 'images/experts/jason.jpg',
 'Antique book restoration expert.', 12, 193, 97.00, '3 hours', TRUE),

('Grace Ng', 'grace@mendly.com', '$2y$10$T4YhVavDUHqgFEcv7Uy/AeclkNLlAHVVOv.pr2hPq9Cw4zVdvagma', '0123456792', 'Johor',
 'Grace Ng Toy & Doll Repair', '5-10', 'Plushie and doll restoration.', 'images/experts/grace.jpg',
 'Plushie and doll restoration.', 6, 241, 96.80, '2 hours', TRUE),

('Nur Aisyah', 'aisyah@mendly.com', '$2y$10$T4YhVavDUHqgFEcv7Uy/AeclkNLlAHVVOv.pr2hPq9Cw4zVdvagma', '0123456793', 'Johor',
 'Aisyah Shoe Repair', '5-10', 'Luxury shoe repair specialist.', 'images/experts/aisyah.jpg',
 'Luxury shoe repair specialist.', 7, 278, 98.00, '2 hours', TRUE),

('Daniel Wong', 'daniel@mendly.com', '$2y$10$T4YhVavDUHqgFEcv7Uy/AeclkNLlAHVVOv.pr2hPq9Cw4zVdvagma', '0123456794', 'Kuala Lumpur',
 'Daniel Wong Leather Craft', '5-10', 'Vintage leather craftsman.', 'images/experts/daniel.jpg',
 'Vintage leather craftsman.', 9, 354, 99.00, '1 hour', TRUE);

INSERT INTO customers (full_name, email, password, phone, location) VALUES
('Emily Tan',   'emily@gmail.com',   '$2y$10$T4YhVavDUHqgFEcv7Uy/AeclkNLlAHVVOv.pr2hPq9Cw4zVdvagma', '0111111111', 'Kuala Lumpur'),
('Michael Lim', 'michael@gmail.com', '$2y$10$T4YhVavDUHqgFEcv7Uy/AeclkNLlAHVVOv.pr2hPq9Cw4zVdvagma', '0111111112', 'Penang'),
('Siti Aminah', 'siti@gmail.com',    '$2y$10$T4YhVavDUHqgFEcv7Uy/AeclkNLlAHVVOv.pr2hPq9Cw4zVdvagma', '0111111113', 'Johor');

-- expert_id 1-6 map to Alex, Sarah, Jason, Grace, Aisyah, Daniel (insertion order above)
INSERT INTO services (expert_id, category_id, title, description, starting_price, estimated_duration) VALUES
(1, 1, 'Luxury Bag Repair',           'Professional restoration for premium leather bags.', 80,  '3-5 Days'),
(2, 4, 'Wedding Dress Alteration',    'Custom fitting and alteration for wedding dresses.', 120, '5-7 Days'),
(3, 2, 'Book Restoration',            'Repair damaged books and antique collections.',      40,  '2-4 Days'),
(4, 5, 'Vintage Doll Repair',         'Restore damaged dolls and plushies.',                 55,  '3 Days'),
(5, 3, 'Luxury Shoe Repair',          'Leather shoe cleaning and sole replacement.',         65,  '2-3 Days'),
(6, 1, 'Leather Wallet Restoration',  'Restore old leather wallets.',                        35,  '2 Days');

INSERT INTO service_images (service_id, image_path, caption, display_order) VALUES
(1, 'images/services/bag/bag1.jpg', 'Front View', 1),
(1, 'images/services/bag/bag2.jpg', 'Before Repair', 2),
(1, 'images/services/bag/bag3.jpg', 'After Repair', 3),
(1, 'images/services/bag/bag4.jpg', 'Leather Detail', 4),

(2, 'images/services/dress/dress1.jpg', 'Wedding Dress', 1),
(2, 'images/services/dress/dress2.jpg', 'Sleeve Alteration', 2),
(2, 'images/services/dress/dress3.jpg', 'Finished Result', 3),

(3, 'images/services/book/book1.jpg', 'Old Book', 1),
(3, 'images/services/book/book2.jpg', 'Page Repair', 2),
(3, 'images/services/book/book3.jpg', 'Restored Book', 3),

(4, 'images/services/doll/doll1.jpg', 'Vintage Doll', 1),
(4, 'images/services/doll/doll2.jpg', 'Repair Process', 2),
(4, 'images/services/doll/doll3.jpg', 'Completed', 3),

(5, 'images/services/shoe/shoe1.jpg', 'Luxury Shoes', 1),
(5, 'images/services/shoe/shoe2.jpg', 'Sole Replacement', 2),
(5, 'images/services/shoe/shoe3.jpg', 'Finished', 3),

(6, 'images/services/wallet/wallet1.jpg', 'Leather Wallet', 1),
(6, 'images/services/wallet/wallet2.jpg', 'Cleaning', 2),
(6, 'images/services/wallet/wallet3.jpg', 'Completed', 3);

-- customer_id 1-3 map to Emily, Michael, Siti (insertion order above)
INSERT INTO repair_requests (customer_id, service_id, item_name, repair_description, reference_image, quoted_price, status) VALUES
(1, 1, 'Louis Vuitton Bag', 'Handle cracked and leather scratched.', 'uploads/request1.jpg', 120, 'Completed'),
(2, 3, 'Old Novel',         'Torn pages and damaged cover.',        'uploads/request2.jpg', 60,  'Completed'),
(3, 5, 'Leather Boots',     'Replace sole and polish.',              'uploads/request3.jpg', 90,  'In Progress'),
(1, 4, 'Teddy Bear',        'Eye missing and torn arm.',             'uploads/request4.jpg', 45,  'Pending'),
(2, 2, 'Wedding Dress',     'Need waist adjustment.',                'uploads/request5.jpg', 150, 'Accepted');

INSERT INTO reviews (request_id, rating, comment) VALUES
(1, 5, 'Amazing craftsmanship. My bag looks brand new.'),
(2, 5, 'Professional restoration with great attention to detail.');

INSERT INTO favorites (customer_id, service_id) VALUES
(1, 1),
(1, 3),
(2, 2),
(2, 5),
(3, 4);

INSERT INTO request_images (request_id, image_path, display_order) VALUES
(1, 'uploads/request1_1.jpg', 1),
(1, 'uploads/request1_2.jpg', 2),
(2, 'uploads/request2_1.jpg', 1),
(3, 'uploads/request3_1.jpg', 1),
(3, 'uploads/request3_2.jpg', 2),
(4, 'uploads/request4_1.jpg', 1),
(5, 'uploads/request5_1.jpg', 1);

-- ============================================================
-- More test reviews for Emily Tan (customer_id 1)
-- ============================================================
-- Adds 3 more COMPLETED repair requests for Emily across experts she
-- hasn't been matched with yet, then a review for each — with tags,
-- and media on one — so her review history has more depth for testing
-- review.php, expert-review.php, and profile-style "my reviews" views.

-- Emily Tan (customer_id 1) had shoes fixed by Aisyah (service_id 5)
INSERT INTO repair_requests (customer_id, service_id, item_name, repair_description, reference_image, quoted_price, status)
VALUES (1, 5, 'Suede Ankle Boots', 'Salt stains and a loose heel from the rainy season.', 'uploads/request11.jpg', 60, 'Completed');
SET @r11 = LAST_INSERT_ID();

-- Emily Tan (customer_id 1) had a dress altered by Sarah (service_id 2)
INSERT INTO repair_requests (customer_id, service_id, item_name, repair_description, reference_image, quoted_price, status)
VALUES (1, 2, 'Silk Slip Dress', 'Needed the hem taken up and a broken zip replaced.', 'uploads/request12.jpg', 45, 'Completed');
SET @r12 = LAST_INSERT_ID();

-- Emily Tan (customer_id 1) had a plushie repaired by Grace (service_id 4)
INSERT INTO repair_requests (customer_id, service_id, item_name, repair_description, reference_image, quoted_price, status)
VALUES (1, 4, 'Childhood Teddy Bear', 'Second bear from the same collection — missing an ear and faded fur.', 'uploads/request13.jpg', 42, 'Completed');
SET @r13 = LAST_INSERT_ID();

INSERT INTO reviews (request_id, rating, comment) VALUES
(@r11, 4, 'Boots came back looking clean and the heel is solid again. A bit pricier than I expected but good work.'),
(@r12, 5, 'Perfect fit and the zip is smoother than the original. Sarah was quick to respond to every message too.'),
(@r13, 5, 'Second time trusting Grace with a childhood toy and she nailed it again. So gentle with these old pieces.');

SET @rev11 = (SELECT review_id FROM reviews WHERE request_id = @r11);
SET @rev12 = (SELECT review_id FROM reviews WHERE request_id = @r12);
SET @rev13 = (SELECT review_id FROM reviews WHERE request_id = @r13);

INSERT INTO review_tags (review_id, tag_name) VALUES
(@rev11, 'Craftsmanship'),
(@rev12, 'Craftsmanship'),
(@rev12, 'Communication'),
(@rev13, 'Craftsmanship'),
(@rev13, 'Communication'),
(@rev13, 'Value');

-- ============================================================
-- Unreviewed completed repairs for Emily Tan (customer_id 1)
-- ============================================================
-- Completed but no matching row in `reviews`, so review.php's LEFT JOIN
-- leaves review_id NULL and these render in the "awaiting feedback" list.

-- Emily Tan (customer_id 1) — leather wallet by Daniel Wong (service_id 6)
INSERT INTO repair_requests (customer_id, service_id, item_name, repair_description, reference_image, quoted_price, status)
VALUES (1, 6, 'Bifold Leather Wallet', 'Edges were peeling and stitching had come loose on one corner.', 'uploads/request14.jpg', 38, 'Completed');

-- Emily Tan (customer_id 1) — book restoration by Jason Lee (service_id 3)
INSERT INTO repair_requests (customer_id, service_id, item_name, repair_description, reference_image, quoted_price, status)
VALUES (1, 3, 'Vintage Cookbook', 'Spine was cracked and several pages had come loose from the binding.', 'uploads/request15.jpg', 55, 'Completed');

-- Emily Tan (customer_id 1) — another bag repair by Alex Tan (service_id 1)
INSERT INTO repair_requests (customer_id, service_id, item_name, repair_description, reference_image, quoted_price, status)
VALUES (1, 1, 'Canvas Tote Bag', 'Strap had torn away from the base seam and needed reinforcing.', 'uploads/request16.jpg', 65, 'Completed');

INSERT INTO review_media (review_id, media_path, media_type, display_order) VALUES
(@rev13, 'assets/uploads/reviews/sample_teddy_after.jpg', 'image', 1);

-- Sample chat data for testing the chat widget.
-- Run AFTER mendly-schema.sql and this file.
-- Mirrors customer/expert pairs already above in repair_requests, so the
-- conversations line up with a real request.

-- Emily Tan (customer_id 1) <-> Alex Tan (expert_id 1) — Louis Vuitton bag repair
INSERT INTO conversations (customer_id, expert_id, last_message_at) VALUES
(1, 1, '2026-08-20 15:32:00');
SET @c1 = LAST_INSERT_ID();

INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@c1, 'customer', 1, 'Hi Alex, just checking in on my bag repair — any update?', 1, '2026-08-20 14:50:00'),
(@c1, 'expert',   1, 'Thanks for your order, it''s being prepared!', 1, '2026-08-20 15:10:00'),
(@c1, 'expert',   1, 'Should be ready for collection by Friday.', 0, '2026-08-20 15:32:00');

-- Emily Tan (customer_id 1) <-> Grace Ng (expert_id 4) — Teddy bear repair
INSERT INTO conversations (customer_id, expert_id, last_message_at) VALUES
(1, 4, '2026-08-19 11:05:00');
SET @c2 = LAST_INSERT_ID();

INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@c2, 'customer', 1, 'Hi! My teddy bear is missing an eye and has a torn arm, can you take a look?', 1, '2026-08-19 10:40:00'),
(@c2, 'expert',   4, 'I can do a custom glaze colour if you''d like.', 0, '2026-08-19 11:05:00');

-- Michael Lim (customer_id 2) <-> Jason Lee (expert_id 3) — Old novel restoration
INSERT INTO conversations (customer_id, expert_id, last_message_at) VALUES
(2, 3, '2026-08-18 09:20:00');
SET @c3 = LAST_INSERT_ID();

INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@c3, 'customer', 2, 'Your item is ready to be collected.', 1, '2026-08-17 16:00:00'),
(@c3, 'expert',   3, 'Great, thank you for confirming!', 1, '2026-08-18 09:20:00');

-- Siti Aminah (customer_id 3) <-> Nur Aisyah (expert_id 5) — Leather boots
INSERT INTO conversations (customer_id, expert_id, last_message_at) VALUES
(3, 5, '2026-08-16 13:15:00');
SET @c4 = LAST_INSERT_ID();

INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@c4, 'customer', 3, 'What sizing do you need for the sole replacement?', 1, '2026-08-16 12:50:00'),
(@c4, 'expert',   5, 'Happy to help with sizing!', 0, '2026-08-16 13:15:00');

-- ============================================================
-- Repair requests
-- ============================================================

-- Test data for the customer-request -> automated-chat-message flow.
-- Import AFTER mendly-schema.sql and mendly-data.sql (needs their tables +
-- existing customers/experts/services rows).
--
-- Covers one request at each stage of the pipeline so you can see the
-- request list, the status badges, and the matching auto-messages in
-- chat.php all at once:
--   #1  Pending               (just submitted, no expert action yet)
--   #2  Accepted               (expert accepted, hasn't started)
--   #3  In Progress            (expert is repairing it — the "Repairing" step)
--   #4  Ready for Collection   (done, waiting on customer pickup)
--   #5  Completed              (finished, eligible for review.php)
--   #6  Cancelled              (expert declined)
--
-- Existing IDs this relies on (from mendly-data.sql):
--   customers: 1 Emily Tan, 2 Michael Lim, 3 Siti Aminah
--   experts:   1 Alex Tan (leather), 2 Sarah Lim (clothing), 3 Jason Lee (book),
--              4 Grace Ng (doll), 5 Nur Aisyah (shoe), 6 Daniel Wong (leather)
--   services:  1 Luxury Bag Repair (expert 1), 2 Wedding Dress Alteration (expert 2),
--              3 Book Restoration (expert 3), 4 Vintage Doll Repair (expert 4),
--              5 Luxury Shoe Repair (expert 5), 6 Leather Wallet Restoration (expert 6)

INSERT INTO repair_requests
    (customer_id, service_id, item_name, brand, repair_description, additional_notes, reference_image, quoted_price, status, request_date)
VALUES
-- #1 Pending — Emily -> Alex (leather), submitted a few minutes ago
(1, 1, 'Chanel Flap Bag', 'Chanel',
 'Corner of the bag is scuffed and the chain strap has lost some gold plating.',
 'Prefer WhatsApp updates if possible. No rush, within 2 weeks is fine.',
 'uploads/request_test1.jpg', NULL, 'Pending', NOW() - INTERVAL 10 MINUTE),

-- #2 Accepted — Michael -> Sarah (clothing), accepted, work not started
(2, 2, 'Navy Blazer', 'Zara',
 'Sleeves need to be shortened by about 2cm and the top button is loose.',
 'Item can be picked up/dropped off after 6pm on weekdays only.',
 'uploads/request_test2.jpg', 45.00, 'Accepted', NOW() - INTERVAL 2 DAY),

-- #3 In Progress — Siti -> Nur Aisyah (shoe), currently being repaired
(3, 5, 'Suede Chelsea Boots', 'Clarks',
 'Sole is separating from the upper on both boots, and there is a small tear near the left ankle.',
 NULL,
 'uploads/request_test3.jpg', 85.00, 'In Progress', NOW() - INTERVAL 4 DAY),

-- #4 Ready for Collection — Emily -> Grace (doll), finished, awaiting pickup
(1, 4, 'Vintage Porcelain Doll', NULL,
 'Cracked cheek and missing an eye. Family heirloom, handle with extra care.',
 'Customer requested photos before final collection.',
 'uploads/request_test4.jpg', 60.00, 'Ready for Collection', NOW() - INTERVAL 6 DAY),

-- #5 Completed — Michael -> Jason (book), eligible for review.php
(2, 3, 'First Edition Hardcover', NULL,
 'Spine is cracked and several pages are loose.',
 NULL,
 'uploads/request_test5.jpg', 70.00, 'Completed', NOW() - INTERVAL 12 DAY),

-- #6 Cancelled — Siti -> Daniel (leather), expert declined
(3, 6, 'Leather Card Holder', 'Coach',
 'Stitching has come undone along one edge.',
 'Customer cancelled after receiving a quote elsewhere.',
 'uploads/request_test6.jpg', NULL, 'Cancelled', NOW() - INTERVAL 1 DAY);

-- Grab the auto-increment IDs just inserted, in order, so the messages
-- below reference the correct request_id/conversation without hardcoding
-- numbers that depend on what's already in your DB.
SET @req1 = (SELECT request_id FROM repair_requests WHERE item_name = 'Chanel Flap Bag' ORDER BY request_id DESC LIMIT 1);
SET @req2 = (SELECT request_id FROM repair_requests WHERE item_name = 'Navy Blazer' ORDER BY request_id DESC LIMIT 1);
SET @req3 = (SELECT request_id FROM repair_requests WHERE item_name = 'Suede Chelsea Boots' ORDER BY request_id DESC LIMIT 1);
SET @req4 = (SELECT request_id FROM repair_requests WHERE item_name = 'Vintage Porcelain Doll' ORDER BY request_id DESC LIMIT 1);
SET @req5 = (SELECT request_id FROM repair_requests WHERE item_name = 'First Edition Hardcover' ORDER BY request_id DESC LIMIT 1);
SET @req6 = (SELECT request_id FROM repair_requests WHERE item_name = 'Leather Card Holder' ORDER BY request_id DESC LIMIT 1);

-- ============================================================
-- Conversations (reuses existing ones from mendly-data.sql where the
-- customer/expert pair already has one — e.g. Emily<->Alex — and creates
-- new ones for pairs that don't)
-- ============================================================

-- Michael <-> Sarah: no existing conversation, create one
INSERT INTO conversations (customer_id, expert_id, last_message_at) VALUES (2, 2, NOW() - INTERVAL 2 DAY);
SET @convMichaelSarah = LAST_INSERT_ID();

-- Siti <-> Nur Aisyah: mendly-data.sql already created this one (leather boots convo) — reuse it
SET @convSitiAisyah = (SELECT conversation_id FROM conversations WHERE customer_id = 3 AND expert_id = 5);

-- Emily <-> Grace: mendly-data.sql already created this one (teddy bear convo) — reuse it
SET @convEmilyGrace = (SELECT conversation_id FROM conversations WHERE customer_id = 1 AND expert_id = 4);

-- Michael <-> Jason: mendly-data.sql already created this one (old novel convo) — reuse it
SET @convMichaelJason = (SELECT conversation_id FROM conversations WHERE customer_id = 2 AND expert_id = 3);

-- Siti <-> Daniel: no existing conversation, create one
INSERT INTO conversations (customer_id, expert_id, last_message_at) VALUES (3, 6, NOW() - INTERVAL 1 DAY);
SET @convSitiDaniel = LAST_INSERT_ID();

-- Emily <-> Alex: mendly-data.sql already created this one (LV bag convo) — reuse it
SET @convEmilyAlex = (SELECT conversation_id FROM conversations WHERE customer_id = 1 AND expert_id = 1);

-- ============================================================
-- Automated messages — mirrors exactly what sendAutoMessage() would
-- have inserted at each stage of the real flow
-- ============================================================

-- #1 Pending — only the initial auto-message has fired so far
INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@convEmilyAlex, 'expert', 1,
 '🔔 New repair request received: Chanel Flap Bag (Chanel).\nStatus: Pending — we\'ll review your item and get back to you shortly.',
 0, NOW() - INTERVAL 10 MINUTE);

-- #2 Accepted — Pending message, then Accepted message, with a normal
-- reply from the customer in between (shows customer<->expert chatting
-- alongside the automated notices, per your flow: pending -> chat -> repairing)
INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@convMichaelSarah, 'expert', 2,
 '🔔 New repair request received: Navy Blazer (Zara).\nStatus: Pending — we\'ll review your item and get back to you shortly.',
 1, NOW() - INTERVAL 2 DAY - INTERVAL 5 MINUTE),
(@convMichaelSarah, 'customer', 2,
 'Hi, just to confirm — can the sleeves be shortened without affecting the shoulder fit?',
 1, NOW() - INTERVAL 2 DAY - INTERVAL 2 MINUTE),
(@convMichaelSarah, 'expert', 2,
 '✅ Your repair request for Navy Blazer has been accepted. We\'ll begin work soon.',
 0, NOW() - INTERVAL 2 DAY);

-- #3 In Progress — full sequence: Pending -> chat -> Accepted -> In Progress
INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@convSitiAisyah, 'expert', 5,
 '🔔 New repair request received: Suede Chelsea Boots (Clarks).\nStatus: Pending — we\'ll review your item and get back to you shortly.',
 1, NOW() - INTERVAL 4 DAY - INTERVAL 30 MINUTE),
(@convSitiAisyah, 'expert', 5,
 '✅ Your repair request for Suede Chelsea Boots has been accepted. We\'ll begin work soon.',
 1, NOW() - INTERVAL 4 DAY - INTERVAL 10 MINUTE),
(@convSitiAisyah, 'expert', 5,
 '🔧 Your repair request for Suede Chelsea Boots is now In Progress — our expert has started repairing it.',
 0, NOW() - INTERVAL 4 DAY);

-- #4 Ready for Collection — full sequence through to ready-for-pickup
INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@convEmilyGrace, 'expert', 4,
 '🔔 New repair request received: Vintage Porcelain Doll.\nStatus: Pending — we\'ll review your item and get back to you shortly.',
 1, NOW() - INTERVAL 6 DAY - INTERVAL 40 MINUTE),
(@convEmilyGrace, 'expert', 4,
 '✅ Your repair request for Vintage Porcelain Doll has been accepted. We\'ll begin work soon.',
 1, NOW() - INTERVAL 6 DAY - INTERVAL 25 MINUTE),
(@convEmilyGrace, 'expert', 4,
 '🔧 Your repair request for Vintage Porcelain Doll is now In Progress — our expert has started repairing it.',
 1, NOW() - INTERVAL 6 DAY - INTERVAL 10 MINUTE),
(@convEmilyGrace, 'expert', 4,
 '📦 Good news — your Vintage Porcelain Doll is repaired and ready for collection.',
 0, NOW() - INTERVAL 6 DAY);

-- #5 Completed — full sequence ending in Completed
INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@convMichaelJason, 'expert', 3,
 '🔔 New repair request received: First Edition Hardcover.\nStatus: Pending — we\'ll review your item and get back to you shortly.',
 1, NOW() - INTERVAL 12 DAY - INTERVAL 50 MINUTE),
(@convMichaelJason, 'expert', 3,
 '✅ Your repair request for First Edition Hardcover has been accepted. We\'ll begin work soon.',
 1, NOW() - INTERVAL 12 DAY - INTERVAL 40 MINUTE),
(@convMichaelJason, 'expert', 3,
 '🔧 Your repair request for First Edition Hardcover is now In Progress — our expert has started repairing it.',
 1, NOW() - INTERVAL 12 DAY - INTERVAL 20 MINUTE),
(@convMichaelJason, 'expert', 3,
 '📦 Good news — your First Edition Hardcover is repaired and ready for collection.',
 1, NOW() - INTERVAL 12 DAY - INTERVAL 10 MINUTE),
(@convMichaelJason, 'expert', 3,
 '🎉 Your repair request for First Edition Hardcover is marked Completed. Thanks for using Mendly!',
 1, NOW() - INTERVAL 12 DAY);

-- #6 Cancelled — Pending, then Cancelled shortly after
INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, is_read, sent_at) VALUES
(@convSitiDaniel, 'expert', 6,
 '🔔 New repair request received: Leather Card Holder (Coach).\nStatus: Pending — we\'ll review your item and get back to you shortly.',
 1, NOW() - INTERVAL 1 DAY - INTERVAL 20 MINUTE),
(@convSitiDaniel, 'expert', 6,
 '❌ Your repair request for Leather Card Holder has been cancelled. Reach out in chat if you have any questions.',
 0, NOW() - INTERVAL 1 DAY);

-- Keep each touched conversation's last_message_at in sync with its
-- newest message, same as sendAutoMessage()/chat-send.php would.
UPDATE conversations c
SET last_message_at = (
    SELECT MAX(sent_at) FROM messages m WHERE m.conversation_id = c.conversation_id
)
WHERE c.conversation_id IN (@convEmilyAlex, @convMichaelSarah, @convSitiAisyah, @convEmilyGrace, @convMichaelJason, @convSitiDaniel);