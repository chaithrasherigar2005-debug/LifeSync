-- LifeSync Database
-- Run this in phpMyAdmin to set up all tables

CREATE DATABASE IF NOT EXISTS lifesync;
USE lifesync;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Expenses table
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(50) NOT NULL,  -- Food, Travel, Shopping, etc.
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Monthly budget limits
CREATE TABLE budget_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month VARCHAR(7) NOT NULL,  -- e.g., '2025-06'
    limit_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Food / Meals table
CREATE TABLE meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_name VARCHAR(100) NOT NULL,
    calories INT NOT NULL,
    meal_type ENUM('breakfast','lunch','dinner','snack') NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Calorie goals
CREATE TABLE calorie_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    daily_goal INT NOT NULL DEFAULT 2000,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Workout table
CREATE TABLE workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    workout_type VARCHAR(100) NOT NULL,  -- Running, Gym, Yoga, etc.
    duration_minutes INT NOT NULL,
    calories_burned INT,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sleep table
CREATE TABLE sleep_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sleep_time TIME NOT NULL,
    wake_time TIME NOT NULL,
    hours_slept DECIMAL(4,2) NOT NULL,
    quality ENUM('poor','average','good','excellent') DEFAULT 'average',
    date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Habits table
CREATE TABLE habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    habit_name VARCHAR(100) NOT NULL,
    current_streak INT DEFAULT 0,
    best_streak INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Daily habit completion log
CREATE TABLE habit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    completed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Diary table
CREATE TABLE diary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200),
    content TEXT NOT NULL,
    mood ENUM('happy','sad','anxious','excited','neutral','angry','grateful') DEFAULT 'neutral',
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pre-stored food calorie values
CREATE TABLE food_library (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_name VARCHAR(100) NOT NULL,
    calories_per_100g INT NOT NULL,
    category VARCHAR(50)
);

-- Sample food data
INSERT INTO food_library (food_name, calories_per_100g, category) VALUES
('Rice (cooked)', 130, 'Grains'),
('Roti/Chapati', 297, 'Grains'),
('Dal (cooked)', 116, 'Protein'),
('Chicken (grilled)', 165, 'Protein'),
('Egg (boiled)', 155, 'Protein'),
('Banana', 89, 'Fruits'),
('Apple', 52, 'Fruits'),
('Milk (whole)', 61, 'Dairy'),
('Paneer', 265, 'Dairy'),
('Potato (boiled)', 87, 'Vegetables'),
('Broccoli', 34, 'Vegetables'),
('Idli (1 piece ~40g)', 39, 'South Indian'),
('Dosa', 168, 'South Indian'),
('Sambar (per 100ml)', 55, 'South Indian');

-- Reading tracker table
CREATE TABLE IF NOT EXISTS reading_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_title VARCHAR(200) NOT NULL,
    author VARCHAR(150),
    total_pages INT,
    pages_read INT DEFAULT 0,
    status ENUM('reading','completed','want_to_read','dropped') DEFAULT 'reading',
    rating TINYINT DEFAULT NULL,
    notes TEXT,
    started_date DATE,
    finished_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Daily reading sessions
CREATE TABLE IF NOT EXISTS reading_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    pages_this_session INT NOT NULL,
    minutes_spent INT,
    date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES reading_logs(id) ON DELETE CASCADE
);
