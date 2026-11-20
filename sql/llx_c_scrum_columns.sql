-- Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

CREATE TABLE llx_c_scrum_columns (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  date_cre datetime DEFAULT NULL,
  date_maj datetime DEFAULT NULL,
  code varchar(50) DEFAULT NULL,
  label varchar(100) DEFAULT NULL,
  rang integer NOT NULL DEFAULT 0,
  active integer NOT NULL DEFAULT 0,
  entity integer NOT NULL DEFAULT 1
) ENGINE=innodb;
