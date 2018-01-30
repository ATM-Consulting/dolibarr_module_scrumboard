<?php

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
	