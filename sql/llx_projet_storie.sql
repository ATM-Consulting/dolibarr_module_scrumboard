-- Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

CREATE TABLE llx_projet_storie (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  date_cre datetime DEFAULT NULL,
  date_maj datetime DEFAULT NULL,
  fk_projet integer NOT NULL DEFAULT 0,
  storie_order integer NOT NULL DEFAULT 0,
  label varchar(100) DEFAULT NULL,
  visible integer NOT NULL DEFAULT 0,
  date_start datetime DEFAULT NULL,
  date_end datetime DEFAULT NULL
) ENGINE=innodb;
