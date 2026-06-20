-- Packaging on-hand count requests (HR set_on_hand) + executive count correction
ALTER TABLE `material_adjustment_requests`
  MODIFY COLUMN `action` ENUM('add', 'deduct', 'set_on_hand') NOT NULL;
