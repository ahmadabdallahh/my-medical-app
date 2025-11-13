-- Fix database structure for proper admin role support
-- This script will update the users table to properly handle admin roles

-- Update the users table to add 'admin' to the role enum
ALTER TABLE `users` MODIFY `role` enum('patient','doctor','hospital','admin') DEFAULT 'patient';

-- Update existing users with user_type 'admin' to have role 'admin'
UPDATE `users` SET `role` = 'admin' WHERE `user_type` = 'admin';

-- Create a proper admin user if it doesn't exist
INSERT INTO `users` (
    `username`, 
    `full_name`, 
    `email`, 
    `password`, 
    `phone`, 
    `gender`, 
    `role`, 
    `user_type`, 
    `is_active`
) VALUES (
    'admin',
    'Administrator',
    'admin@medical.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '01000000000',
    'male',
    'admin',
    'admin',
    1
) ON DUPLICATE KEY UPDATE 
    `role` = 'admin',
    `user_type` = 'admin',
    `is_active` = 1;

-- Update the login function to use the correct column names
-- The users table structure should now be consistent
