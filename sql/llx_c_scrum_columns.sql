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


CREATE TABLE IF NOT EXISTS llx_c_scrum_columns(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	-- BEGIN MODULEBUILDER FIELDS
	label VARCHAR(255),
    col_order INTEGER UNIQUE NOT NULL DEFAULT 0,
	active INTEGER NOT NULL DEFAULT 1,
    code varchar(5) NOT NULL DEFAULT '1'
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
