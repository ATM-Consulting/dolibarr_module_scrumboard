-- Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

-- Created first by a framework without AUTO_INCREMENT: replayed safely on each activation.
ALTER TABLE llx_projet_storie MODIFY rowid integer NOT NULL AUTO_INCREMENT;
ALTER TABLE llx_projet_storie ADD INDEX idx_projet_storie_fk_projet (fk_projet);
ALTER TABLE llx_projet_storie ADD INDEX idx_llx_projet_storie_story_order (storie_order);
