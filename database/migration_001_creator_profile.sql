-- Migration script: Add new tables for single-creator redesign
-- This adds new tables without dropping existing data

USE patron_platform;

-- Creator profile (single creator)
CREATE TABLE IF NOT EXISTS creator_profile (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    bio TEXT,
    avatar_url VARCHAR(500),
    cover_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Video categories
CREATE TABLE IF NOT EXISTS video_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add category_id to videos table if it doesn't exist
ALTER TABLE videos
ADD COLUMN IF NOT EXISTS category_id INT AFTER thumbnail_url,
ADD FOREIGN KEY IF NOT EXISTS fk_videos_category (category_id) REFERENCES video_categories(id) ON DELETE SET NULL,
ADD INDEX IF NOT EXISTS idx_category (category_id);

-- Insert creator profile if not exists
INSERT IGNORE INTO creator_profile (user_id, display_name, bio)
SELECT id, full_name, 'Vítajte na mojom HeroHero\n- exkluzívny obsah\n- obsah DOPREDU...'
FROM users
WHERE is_admin = TRUE
LIMIT 1;

-- Insert video categories if not exist
INSERT IGNORE INTO video_categories (name, slug) VALUES
('JOVI A BERGI', 'jovi-a-bergi'),
('SNAMI epizódy', 'snami-epizody'),
('NEZOSTRIHANÉ epizódy', 'nezostihane-epizody'),
('POLITIKA', 'politika');

-- Update subscription_tiers currency to EUR if needed
UPDATE subscription_tiers SET currency = 'EUR' WHERE currency = 'USD';

-- Add basic tier if not exists
INSERT IGNORE INTO subscription_tiers (id, name, description, price, currency, benefits, is_active) VALUES
(999, 'Základný', 'Prístup k exkluzívnemu obsahu', 6.00, 'EUR', 'Prístup k exkluzívnym videám\nPodpora tvorcu\nŠpeciálny odznak', TRUE);

SELECT 'Migration completed successfully!' as status;
