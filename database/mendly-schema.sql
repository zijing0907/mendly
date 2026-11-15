-- Mendly database schema. Import this first, then mendly-data.sql.
-- Works on MySQL 8+ and on the MariaDB bundled with XAMPP.
SET NAMES utf8mb4;
DROP DATABASE IF EXISTS mendly_db;
CREATE DATABASE mendly_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mendly_db;

-- ============================================================
-- Accounts
-- ============================================================

-- One row per customer account.
-- password stores a bcrypt hash (via PHP password_hash), never plain text.
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    phone       VARCHAR(20)  NOT NULL,
    location    VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    avatar      MEDIUMBLOB,
    avatar_mime VARCHAR(20),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- One row per repair expert account. Business + public profile details
-- (avatar, bio, stats shown on Browse Services / Service Details) live on
-- the same row.
CREATE TABLE experts (
    expert_id              INT AUTO_INCREMENT PRIMARY KEY,
    full_name               VARCHAR(100) NOT NULL,
    email                    VARCHAR(100) NOT NULL UNIQUE,
    phone                    VARCHAR(20)  NOT NULL,
    location                 VARCHAR(150) NOT NULL,
    password                 VARCHAR(255) NOT NULL,
    business_name            VARCHAR(150) NOT NULL,
    experience                VARCHAR(20)  NOT NULL,
    description              TEXT,

    -- Set to FALSE for a newly registered expert, so their first login
    -- redirects them to add-service.php to set up their first service
    -- listing (category, price, etc.) instead of the home page. Flips to
    -- TRUE the moment that first login happens, whether or not they
    -- actually finish adding a service — it's a one-time nudge, not a
    -- permanent gate. Defaults to TRUE here so any experts inserted
    -- directly via seed data aren't forced through onboarding.
    first_login_done         BOOLEAN DEFAULT TRUE,

    photo_path                MEDIUMBLOB,
    photo_path_mime           VARCHAR(20),

    -- Public profile fields used by Browse Services / Service Details
    avatar                    MEDIUMBLOB,
    avatar_mime               VARCHAR(20),
    bio                       TEXT,
    years_experience          INT DEFAULT 0,
    completed_repairs         INT DEFAULT 0,
    response_rate             DECIMAL(5,2) DEFAULT 0,
    average_response_time     VARCHAR(50),
    verified                  BOOLEAN DEFAULT FALSE,

    created_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Services catalogue
-- ============================================================

-- category_group buckets each category under one of the three sections
-- shown on the home page and the Experts page. image_path is the
-- category's cover photo, also used as a fallback thumbnail for any
-- service that has no photos of its own yet.
CREATE TABLE categories (
    category_id    INT AUTO_INCREMENT PRIMARY KEY,
    category_name  VARCHAR(100) NOT NULL,
    category_group ENUM('textiles', 'toys', 'leather') NOT NULL,
    image_path     VARCHAR(255)
);

CREATE TABLE services (
    service_id         INT AUTO_INCREMENT PRIMARY KEY,
    expert_id          INT NOT NULL,
    category_id        INT NOT NULL,
    title              VARCHAR(150) NOT NULL,
    description        TEXT,
    starting_price     DECIMAL(10,2),
    estimated_duration VARCHAR(50),
    status             ENUM('active', 'inactive') DEFAULT 'active',
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (expert_id)   REFERENCES experts(expert_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

CREATE TABLE service_images (
    image_id      INT AUTO_INCREMENT PRIMARY KEY,
    service_id    INT NOT NULL,
    image_path    VARCHAR(255) NOT NULL,
    caption       VARCHAR(255),
    display_order INT DEFAULT 1,

    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
);

-- ============================================================
-- Repair requests, reviews, favorites
-- ============================================================

CREATE TABLE repair_requests (
    request_id         INT AUTO_INCREMENT PRIMARY KEY,
    customer_id         INT NOT NULL,
    service_id          INT NOT NULL,
    item_name            VARCHAR(150),
    brand                 VARCHAR(100),
    repair_description   TEXT,
    additional_notes      TEXT,
    reference_image      VARCHAR(255),
    quoted_price          DECIMAL(10,2),

    status ENUM(
        'Pending',
        'Accepted',
        'In Progress',
        'Ready for Collection',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending',

    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (service_id)  REFERENCES services(service_id)
);

CREATE TABLE request_images (
    image_id      INT AUTO_INCREMENT PRIMARY KEY,
    request_id    INT NOT NULL,
    image_path    VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 1,

    FOREIGN KEY (request_id) REFERENCES repair_requests(request_id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    review_id   INT AUTO_INCREMENT PRIMARY KEY,
    request_id  INT NOT NULL UNIQUE,
    rating      INT NOT NULL,
    comment     TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (request_id) REFERENCES repair_requests(request_id) ON DELETE CASCADE
);

CREATE TABLE favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id  INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(customer_id, service_id),

    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id)  REFERENCES services(service_id) ON DELETE CASCADE
);

-- ============================================================
-- Password reset tokens
-- ============================================================

-- A short-lived token generated when someone requests a password reset.
-- Since the project can't send real email, the reset link is shown
-- directly on screen instead — in production this table would be the
-- same, just paired with an actual email being sent.
CREATE TABLE password_resets (
    reset_id   INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(100) NOT NULL,
    role       ENUM('customer', 'expert') NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used       BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Chat
-- ============================================================
CREATE TABLE conversations (
    conversation_id  INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT NOT NULL,
    expert_id        INT NOT NULL,
    last_message_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 
    UNIQUE KEY unique_pair (customer_id, expert_id),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (expert_id)   REFERENCES experts(expert_id)   ON DELETE CASCADE
);
 
CREATE TABLE messages (
    message_id       INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id  INT NOT NULL,
    sender_type      ENUM('customer', 'expert') NOT NULL,
    sender_id        INT NOT NULL, -- matches customer_id or expert_id depending on sender_type
    message_text     TEXT,
    attachment_path  VARCHAR(255) DEFAULT NULL,
    is_read          BOOLEAN DEFAULT FALSE,
    sent_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 
    FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE
);
 
CREATE INDEX idx_messages_conversation ON messages(conversation_id, sent_at);

-- ============================================================
-- Review
-- ============================================================
CREATE TABLE IF NOT EXISTS review_tags (
    review_tag_id INT AUTO_INCREMENT PRIMARY KEY,
    review_id     INT NOT NULL,
    tag_name      VARCHAR(50) NOT NULL,
 
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE
);
 
CREATE INDEX idx_review_tags_review ON review_tags(review_id);
 
CREATE TABLE IF NOT EXISTS review_media (
    media_id      INT AUTO_INCREMENT PRIMARY KEY,
    review_id     INT NOT NULL,
    media_path    VARCHAR(255) NOT NULL,
    media_type    ENUM('image', 'video') NOT NULL,
    display_order INT DEFAULT 1,
 
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE
);
 
CREATE INDEX idx_review_media_review ON review_media(review_id);

-- ============================================================
-- Contact form inquiries + admin accounts
-- ============================================================

-- One row per message sent through the Contact page. user_type is worked
-- out from the email address at submit time (registered customer, expert,
-- or guest). Admins move status along from admin/inquiries.php.
CREATE TABLE inquiries (
    inquiry_id   INT AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(100) NOT NULL,
    user_type    ENUM('Guest', 'Customer', 'Expert') NOT NULL DEFAULT 'Guest',
    subject_type ENUM('Suggestion', 'Complaint', 'Question') NOT NULL,
    message      TEXT NOT NULL,
    status       ENUM('Pending', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Staff accounts for the admin area. Kept separate from customers/experts
-- so a normal account can never be promoted to admin by mistake.
CREATE TABLE admins (
    admin_id   INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- "Remember me" logins
-- ============================================================
-- The cookie holds "selector:validator". Only a SHA-256 hash of the
-- validator is stored, so someone who reads this table still can't log in.
-- Rows are deleted on logout, password change/reset and account deletion.
CREATE TABLE remember_tokens (
    token_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_type  ENUM('customer', 'expert') NOT NULL,
    user_id    INT NOT NULL,
    selector   CHAR(24) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_remember_user (user_type, user_id)
);
