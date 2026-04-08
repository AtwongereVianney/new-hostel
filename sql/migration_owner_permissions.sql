-- Run once on existing databases that predate owner accounts.
-- Adds role columns and backfills admin by email.

ALTER TABLE users
    ADD COLUMN user_type ENUM('admin','hostel_owner','student') NOT NULL DEFAULT 'student' AFTER role_id,
    ADD COLUMN permissions_json TEXT NULL AFTER user_type;

UPDATE users SET user_type = 'admin' WHERE email = 'admin@mmu.edu' AND deleted_at IS NULL;
