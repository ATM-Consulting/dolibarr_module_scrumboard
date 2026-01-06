<?php
/* Copyright (C) 2025 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

	require __DIR__.'/config.default.php';


	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/core/lib/project.lib.php');
	dol_include_once('/core/class/html.formfile.class.php');
	dol_include_once('/core/modules/project/modules_project.php');
	dol_include_once('/core/class/extrafields.class.php');

	dol_include_once('/core/lib/date.lib.php');

	dol_include_once('/scrumboard/lib/scrumboard.lib.php');


	$langs->load("projects");
	$langs->load('companies');
	$langs->load('scrumboard@scrumboard');
