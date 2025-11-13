-- Fix the role column to include admin option
ALTER TABLE `users` MODIFY `role` enum('patient','doctor','hospital','admin') DEFAULT 'patient';

-- Update the admin user to have the correct role
UPDATE `users` SET `role` = 'admin' WHERE `email` = 'admin@medical.com';

-- Update any other users that should be admin
UPDATE `users` SET `role` = 'admin' WHERE `user_type` = 'admin' AND `role` != 'admin';
