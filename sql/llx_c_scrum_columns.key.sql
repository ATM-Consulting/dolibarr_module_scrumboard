-- Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

-- Created first by a framework without AUTO_INCREMENT: replayed safely on each activation.
ALTER TABLE llx_c_scrum_columns MODIFY rowid integer NOT NULL AUTO_INCREMENT;
ALTER TABLE llx_c_scrum_columns ADD INDEX idx_c_scrum_columns_entity (entity);
