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

--INSERT INTO llx_produit VALUES (
--	1, 1, 'mydata'
--);

-- Mettre le code à '0' empêche la suppression ou la désactivation de ces lignes depuis l'interface des dictionnaires
INSERT INTO llx_c_scrum_columns(rowid, code, label, rang, active, entity) VALUES
(1, 'backlog', 'Ideas', 5, 1, 1),
(2, 'todo', 'toDo', 10, 1, 1),
(3, 'inprogress', 'inProgress', 15, 1, 1),
(4, 'review', 'review', 20, 1, 1),
(5, 'finish', 'finish', 25, 1, 1);
