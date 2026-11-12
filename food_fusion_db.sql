CREATE DATABASE IF NOT EXISTS food_fusion_db;
USE food_fusion_db;


CREATE TABLE IF NOT EXISTS users( 
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255) NOT NULL,
  profile_image_url VARCHAR(2048) NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  user_password VARCHAR(255) NOT NULL,
  failed_attempts INT DEFAULT 0,
  locked_until DATETIME NULL
);
CREATE TABLE IF NOT EXISTS recipes (
  recipe_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  cuisine_type VARCHAR(255),
  diet_type VARCHAR(100) NULL,
  description TEXT NULL,
  prep_time_minutes SMALLINT UNSIGNED NULL,
  cook_time_minutes SMALLINT UNSIGNED NULL,
  servings VARCHAR(100) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  difficulty ENUM('Easy', 'Intermediate', 'Advanced'),
  ingredients TEXT NOT NULL,
  instructions TEXT NOT NULL,
  user_id INT,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL, 
  is_community BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS interactions (
  interaction_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  recipe_id INT NOT NULL,
  FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
  rating INT CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  interacted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS news_feed (
  news_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  event_date DATE NOT NULL,
  location VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS culinary_resources (
  resource_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  resource_url VARCHAR(512),
  thumbnail_url VARCHAR(512) NULL,
  is_educational TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Curated external resources (government PDFs + reputable free videos). Re-run only if you want a fresh seed; duplicates are possible without TRUNCATE.
INSERT INTO culinary_resources (title, description, resource_url, is_educational) VALUES
(
  'Kitchen Companion: Your Safe Food Handbook (USDA)',
  'Official FSIS handbook covering shopping, storage, prep, cooking temperatures, and avoiding foodborne illness—solid kitchen fundamentals from a safety angle.',
  'https://www.fsis.usda.gov/sites/default/files/media_file/2020-12/Kitchen-Companion.pdf',
  1
),
(
  'FDA Food Safety A to Z Reference Guide',
  'Downloadable reference from the U.S. FDA on safe handling, storage, and common food-safety topics—useful to keep on hand while you cook.',
  'https://www.fda.gov/downloads/food/foodscienceresearch/toolsmaterials/ucm430363.pdf',
  1
),
(
  'How to Master 5 Basic Cooking Skills — Gordon Ramsay',
  'Short video covering core techniques such as chopping an onion, rice, pasta, fish prep, and knife care—great confidence boosters for new cooks.',
  'https://www.youtube.com/watch?v=ZJy1ajvMU1k',
  1
),
(
  'Cooking Tips for Kitchen Beginners — Epicurious 101',
  'Professional-style tips aimed at beginners who want clearer, calmer habits at the stove.',
  'https://www.youtube.com/watch?v=aopS3q6f1GY',
  1
),
(
  'Knife Skills: How to Chop — Serious Eats',
  'Focused demo on the chop cut—one of the most-used motions for vegetables and herbs.',
  'https://www.youtube.com/watch?v=XyS915RynEQ',
  0
),
(
  'How to Make Ice Cream (Part 1) — Bake It Up a Notch with Erin McDowell',
  'Video introduction to custard-style ice cream and frozen desserts from a pastry-forward perspective.',
  'https://www.youtube.com/watch?v=DBFAoO8kITk',
  0
);

CREATE TABLE IF NOT EXISTS media (
  media_id INT AUTO_INCREMENT PRIMARY KEY,
  recipe_id INT NULL,
  user_id INT NULL,
  cloudinary_public_id VARCHAR(512) NOT NULL,
  secure_url VARCHAR(2048) NOT NULL,
  width INT NULL,
  height INT NULL,
  format VARCHAR(32) NULL,
  bytes INT NULL,
  resource_type VARCHAR(32) NOT NULL DEFAULT 'image',
  alt_text VARCHAR(255) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_media_recipe (recipe_id),
  INDEX idx_media_recipe_primary (recipe_id, is_primary)
);

-- Structured ingredients (one row per line; mirrors textarea for editing / detail layout)
CREATE TABLE IF NOT EXISTS recipe_ingredients (
  ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
  recipe_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  line_text TEXT NOT NULL,
  FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
  INDEX idx_recipe_ingredients_recipe (recipe_id)
);

-- Numbered steps; optional image/video URL per step (e.g. Cloudinary or external)
CREATE TABLE IF NOT EXISTS recipe_steps (
  step_id INT AUTO_INCREMENT PRIMARY KEY,
  recipe_id INT NOT NULL,
  step_number INT NOT NULL,
  body TEXT NOT NULL,
  media_secure_url VARCHAR(2048) NULL,
  media_type VARCHAR(32) NULL,
  FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
  INDEX idx_recipe_steps_recipe (recipe_id)
);

-- Per-recipe nutrition (all optional fields)
CREATE TABLE IF NOT EXISTS recipe_nutrition (
  recipe_id INT NOT NULL PRIMARY KEY,
  calories INT NULL,
  protein_g DECIMAL(6,1) NULL,
  fat_g DECIMAL(6,1) NULL,
  fiber_g DECIMAL(6,1) NULL,
  sugars_g DECIMAL(6,1) NULL,
  carbs_g DECIMAL(6,1) NULL,
  FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tags (
  tag_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_tags_name (name)
);

CREATE TABLE IF NOT EXISTS recipe_tags (
  recipe_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (recipe_id, tag_id),
  FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
);

-- Optional likes for related-recipe cards and future “heart” UI
CREATE TABLE IF NOT EXISTS recipe_likes (
  user_id INT NOT NULL,
  recipe_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, recipe_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
  INDEX idx_recipe_likes_recipe (recipe_id)
);

-- One-time reset tokens for "forgot password"
CREATE TABLE IF NOT EXISTS password_resets (
  reset_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY uq_password_resets_token (token_hash),
  INDEX idx_password_resets_user (user_id),
  INDEX idx_password_resets_expires (expires_at)
);
