-- Phase 25: Supplies HR lot stock-in + issue requests (requester name)

ALTER TABLE `material_adjustment_requests`
  MODIFY COLUMN `action` ENUM('add', 'deduct', 'set_on_hand', 'issue') NOT NULL;

ALTER TABLE `material_adjustment_requests`
  ADD COLUMN `requester_name` VARCHAR(160) NULL AFTER `reason`;

SET @dept_csr := (SELECT id FROM departments WHERE name = 'csr' LIMIT 1);
SET @dept_csr := IFNULL(@dept_csr, (SELECT id FROM departments ORDER BY id ASC LIMIT 1));

-- Merge duplicate supply names (keep lowest id)
DELETE s_dup FROM stocks s_dup
INNER JOIN stocks s_keep ON s_keep.category = 'supply' AND s_dup.category = 'supply'
  AND s_keep.name = s_dup.name AND s_keep.id < s_dup.id
WHERE s_dup.category = 'supply';

UPDATE stocks s1
INNER JOIN (
  SELECT name, MIN(id) AS keep_id, SUM(on_hand) AS total_on_hand
  FROM stocks WHERE category = 'supply' GROUP BY name HAVING COUNT(*) > 1
) agg ON s1.name = agg.name AND s1.id = agg.keep_id
SET s1.on_hand = agg.total_on_hand;

DELETE s FROM stocks s
INNER JOIN (
  SELECT name, MIN(id) AS keep_id FROM stocks WHERE category = 'supply' GROUP BY name HAVING COUNT(*) > 1
) agg ON s.name = agg.name AND s.id <> agg.keep_id
WHERE s.category = 'supply';

UPDATE stocks SET dept_id = @dept_csr WHERE category = 'supply' AND @dept_csr IS NOT NULL;
