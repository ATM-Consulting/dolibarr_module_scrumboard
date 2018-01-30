-- Copyright (C) ---Put here your own copyright and developer email---
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.


CREATE TABLE IF NOT EXISTS llx_projet_storie(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	-- BEGIN MODULEBUILDER FIELDS
    fk_projet INTEGER NOT NULL,
    storie_order INTEGER NOT NULL DEFAULT 1,
    label VARCHAR(255) DEFAULT '',
    visible INTEGER NOT NULL DEFAULT 1,
    date_start DATE DEFAULT NULL,
    date_end DATE DEFAULT NULL,
    UNIQUE(fk_projet, storie_order)
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
