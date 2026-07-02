-- Migration: add quantity_grams to meals table
-- Run this once in phpMyAdmin (SQL tab) against the lifesync database.
-- Safe to run on a table that already has rows; existing rows will get
-- quantity_grams = 100 (a reasonable default) and you can edit later if needed.

USE lifesync;

ALTER TABLE meals
    ADD COLUMN quantity_grams INT NOT NULL DEFAULT 100 AFTER food_name;
