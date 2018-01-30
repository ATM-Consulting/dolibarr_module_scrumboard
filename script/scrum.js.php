<?php
	require('../config.php');
	dol_include_once('/scrumboard/lib/scrumboard.lib.php');
	dol_include_once('/scrumboard/class/scrumboard.class.php');
	

?>
/* <script type="text/javascript"> */
	
function project_velocity(id_project) {
	$.ajax({
		url : "./script/interface.php"
		,data: {
			json:1
			,get : 'velocity'
			,id_project : id_project
			,async:true
		}
		,dataType: 'json'
	})
	.done(function (data) {
		
		if(data.current) {
			$('td[rel=currentVelocity]').html(data.current);
		}
		if(data.inprogress) {
			$('span[rel=velocityInProgress]').html(data.inprogress);
		}
		if(data.todo) {
			$('span[rel=velocityToDo]').html(data.todo);
		}
		if(data.velocity) {
			$('#current-velocity').val(Math.round(data.velocity / 3600 * 100) / 100);
		}
				
	}); 
	
	
}

function project_get_tasks(id_project, status) {
	$('ul[rel="'+status+'"]').empty();
	
	var fk_user = 0;
	<?php if (!empty($conf->global->SCRUM_FILTER_BY_USER_ENABLE)) { ?>
		fk_user = $('#fk_user').val();
	<?php } ?>
	
	$.ajax({
		url : "./script/interface.php"
		,data: {
			json:1
			,get : 'tasks'
			,status : status
			,id_project : id_project
			,async:false
			,fk_user:fk_user
		}
		,dataType: 'json'
	})
	.done(function (tasks) {
		
		$.each(tasks, function(i, task) {
			var l_status = status;
			// Si on utilise la conf de backlog et review, il faut tester si le scrum_status est vide pour mettre la tache dans la colonne la plus à gauche par défaut (test à faire unique si conf activé sinon on perd les taches sans scrum_status si désactivé)
			// TODO: Voir avec Geoffrey pour l'avenir de la conf SCRUM_ADD_BACKLOG_REVIEW_COLUMN
			if(status == 'todo' && (task.scrum_status =='backlog' <?php if (!empty($conf->global->SCRUM_ADD_BACKLOG_REVIEW_COLUMN)) echo '|| task.scrum_status == ""'; ?> ) ) {
				l_status = 'backlog';
			}
			else if(status == 'finish' && task.scrum_status =='review' ) {
				l_status = 'review';
			}
			
			if($('tr[story-k='+task.story_k+']').length>0) {
				$ul = $('tr[story-k='+task.story_k+']').find('ul[rel="'+l_status+'"]');
			}
			else{
				$ul = $('tr[default-k=1]').find('ul[rel="'+l_status+'"]');
			}

			project_draw_task(id_project, task, $ul);
		});
				
	}); 
}

function project_create_task(id_project) {
	$.ajax({
		url : "./script/interface.php"
		,data: {
			json:1
			,put : 'task'
			,id_project : id_project
			,status:'idea'
		}
		,dataType: 'json'
	})
	.done(function (task) {
	
		<?php 
		// TODO: Conf SCRUM_ADD_BACKLOG_REVIEW_COLUMN !
					if(!empty($conf->global->SCRUM_ADD_BACKLOG_REVIEW_COLUMN)) {
						echo '$ul = $(\'tr[default-k=1]\').find(\'ul[rel=backlog]\')';
					}
					else{
						
						echo '$ul = $(\'tr[default-k=1]\').find(\'ul[rel=todo]\')';
					}
		?>
		
		
		project_draw_task(id_project, task, $ul);
		project_develop_task(task.id);
	}); 
	
}

function project_draw_task(id_project, task, ul) {
	var id = task.id;

	if($('#task-'+id).length == 0) { // Si tâche déjà affichée, on ne clone pas le noeud
		$('#task-blank').clone().attr('id', 'task-'+id).appendTo(ul);
	}

	project_refresh_task(id_project, task);
}

function project_refresh_task(id_project, task) {
	$item = $('#task-'+task.id);
	
	// Generate js var with all scrumboard project to showdesc
	var TShowDesc = (<?php echo json_encode($_SESSION['scrumboard']['showdesc']); ?>);
	
	$item.attr('task-id', task.id);
	
	$item.removeClass('idea todo inprogress finish backlog review');
	$item.addClass(task.status);
	
	var progress= Math.round(task.progress / 5) * 5 ; // round 5
	$item.find('.task-progress select').val( progress ).attr('task-id', task.id).off( "change").on("change", function() {
		var id_projet = $('#scrum').attr('id_projet');
		var id_task = $(this).attr('task-id');
		task=project_get_task(id_projet, id_task);
		task.progress = parseInt($(this).val());
		task.status = 'inprogress';
		task.story_k = $(this).closest('ul').attr('story-k');
		task.scrum_status = $(this).closest('ul').attr('rel');
		
		project_save_task(id_project, task);
	});
	if(task.status != 'todo' && task.status != 'finish') {
		$item.find('.task-progress').show(0);
	}else{
		$item.find('.task-progress').hide(0);
	}
	
	// Test sur conf voir description taches
	if(TShowDesc[id_project] == 1) {
		$item.find('.task-title span').html(task.label);
		$item.find('.task-desc span').html(task.long_description);
	} else {
		$item.find('.task-title span').html(task.label).attr("title", task.long_description).addClass("classfortooltip").tipTip({maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});;
	}
	$item.find('.task-ref a').html(task.ref).attr("href", '<?php echo dol_buildpath('/projet/tasks/task.php?withproject=1&id=',1) ?>'+task.id);
	$item.find('.task-users-affected').html(task.internal_contacts).append(task.external_contacts);
	
	$item.find('.task-real-time span').html(task.aff_time).attr('task-id', task.id);
	$item.find('.task-allowed-time span').html(task.aff_planned_workload).attr('task-id', task.id);
	
	$item.find('.task-real-time, .task-allowed-time').on("click", function() {
		pop_time( $('#scrum').attr('id_projet'), $(this).find('span').attr('task-id'));
	});
	
	
	<?php if(!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK)) { ?>
	<!--  Commentary conf -->
	$item.find('.task-comment span').html(task.nbcomment).attr('task-id', task.id);
		
	$item.find('.task-comment').on("click", function() {
		pop_comment( $('#scrum').attr('id_projet'), $(this).find('span').attr('task-id'));
	});
	<!--  fin conf -->
	<?php } ?>
	

	var percent_progress = Math.round(task.duration_effective / task.planned_workload * 100);
	if(percent_progress > 100) {
		$item.find('div.progressbar').css('background-color', '#dd0000');
		$item.find('div.progressbar').css('width', '100%');
	}
	else if(percent_progress > progress) {
		$item.find('div.progressbar').css('background-color', 'orange');
		$item.find('div.progressbar').css('width', percent_progress+'%');

	}
	else {
		$item.find('div.progressbar').css('width', percent_progress+'%');	
		$item.find('div.progressbar').css('background-color', '');
	
	}
	

	$item.find('div.progressbaruser').css('width', progress+'%');	
	
	if(progress<100 && (task.scrum_status=='todo' || task.scrum_status=='inprogress' ) ) {
		
		var t = new Date().getTime() /1000;
		
		if( task.time_date_end>0 && task.time_date_end < t ) {
			$item.css('background-color','#dd4545');
		}	
		else if(task.time_date_delivery>0 && task.time_date_delivery>task.time_date_end) {
			$item.css('background-color','orange');
		}
	}
}

function project_get_task(id_project, id_task) {
	var taskReturn="";
	$.ajax({
		url : "./script/interface.php"
		,data: {
			json:1
			,get : 'task'
			,id : id_task
			,id_project : id_project
		}
		,dataType: 'json'
		,async:false
	})
	.done(function (lTask) {
		//alert(lTask.name);
		taskReturn = lTask;
	}); 
	
	return taskReturn;
}

function project_init_change_type(id_project) {
	
    $('.task-list').sortable( {
    	connectWith: ".task-list"
    	, placeholder: "ui-state-highlight"
    	,start: function(e, ui){
	        ui.placeholder.height(ui.helper[0].scrollHeight / 2);
	    }
    	,receive: function( event, ui ) {
			task=project_get_task(id_project, ui.item.attr('task-id'));
			task.status = $(this).attr('rel');
			task.story_k = $(this).closest('ul').attr('story-k');
			task.scrum_status = $(this).closest('ul').attr('rel');
			
			$('#task-'+task.id).css('top','');
	        $('#task-'+task.id).css('left','');	
			$('#list-task-'+task.status).prepend( $('#task-'+task.id) );	
			
			if(task.scrum_status=='backlog') task.status = 'todo';
			else if(task.scrum_status=='review') task.status = 'finish';
			
			project_save_task(id_project, task);
					        
	  }  
	  ,update:function(event,ui) {
	  	var sortedIDs = $( this ).sortable( "toArray" );
	  	
	  	var TTaskID=[];
	  	$.each(sortedIDs, function(i, id) {
	  		
	  		taskid = $('#'+id).attr('task-id');
	  		TTaskID.push( taskid );
	  	});
	  		
	  	$.ajax({
			url : "./script/interface.php"
			,data: {
				json:1
				,put : 'sort-task'
				,TTaskID : TTaskID
			}
			,dataType: 'json'
		});
	  	
	  }
    });
}

function project_getsave_task(id_project, id_task) {
	
	task = project_get_task(id_project, id_task);
	$item = $('#task-'+task.id);
	
	task.name = $item.find('[rel=name]').val();
	task.status = $item.find('[rel=status]').val();
	task.type = $item.find('[rel=type]').val();
	task.point = $item.find('[rel=point]').val();
	task.description = $item.find('[rel=description]').val();
	task.story_k = $item.closest('ul').attr('story-k');
	task.scrum_status = $item.closest('ul').attr('rel');
	
	if(task.scrum_status=='backlog') task.status = 'todo';
	else if(task.scrum_status=='review') task.status = 'finish';
	
	project_save_task(id_project, task);
}

function project_save_task(id_project, task) {
	$('#task-'+task.id).css({ opacity:.5 });
	$.ajax({
		url : "./script/interface.php"
		,data: {
			json:1
			,put : 'task'
			,id : task.id
			,status : task.status
			,id_project : id_project
			,label : task.label
			,progress : task.progress
			,story_k : task.story_k
			,scrum_status : task.scrum_status
		}
		,dataType: 'json'
		,type:'POST'
	})
	.done(function (task) {
		project_refresh_task(id_project, task);
		project_velocity(id_project);				
		$('#task-'+task.id).css({ opacity:1 });
	}); 
	
}

function project_develop_task(id_task) {
	$('#task-'+id_task+' div.view').toggle();
}

function project_loadTasks(id_projet) {
	<?php
	$scrumboardColumn = new ScrumboardColumn;
	$TColumn = $scrumboardColumn->getTColumnOrder();
	foreach($TColumn as $column) {
		echo 'project_get_tasks(id_projet ,  \''.$column->code.'\');';
	}
	?>
	
}
function create_task(id_projet) {
	
	if($('#dialog-create-task').length==0) {
		$('body').append('<div id="dialog-create-task"></div>');
	}
	var url ="<?php echo  dol_buildpath('/projet/tasks.php?action=create&id=',1) ?>"+id_projet
		
	$('#dialog-create-task').load(url+" div.fiche form",function() {
		
		$('#dialog-create-task input[name=cancel]').remove();
		$('#dialog-create-task form').submit(function() {
			
			$.post($(this).attr('action'), $(this).serialize(), function() {
				project_loadTasks(id_projet);
			});
		
			$('#dialog-create-task').dialog('close');			
			
			return false;
	
			
		});
		
		$(this).dialog({
			title: "<?php echo $langs->trans('AddTask') ?>"
			,width:800
			,modal:true
		});
		
	});
}
		
function pop_time(id_project, id_task) {
	$("#saisie")
				.load('<?php echo dol_buildpath('/projet/tasks/time.php',2) ?>?id='+id_task+' div.fiche form'
				,function() {
					$('textarea[name=timespent_note]').attr('cols',25);
					
					$('#saisie form').submit(function() {
						
						$.post( $(this).attr('action')
							, {
								token : $(this).find('input[name=token]').val()
								,action : 'addtimespent'
								,id : $(this).find('input[name=id]').val()
								,withproject : 0
								,time : $(this).find('input[name=time]').val()
								,timeday : $(this).find('input[name=timeday]').val()
								,timemonth : $(this).find('input[name=timemonth]').val()
								,timeyear : $(this).find('input[name=timeyear]').val()
								
								<?php if((float) DOL_VERSION > 3.6) {
									?>
									,progress : $(this).find('select[name=progress]').val()
									<?php
								}
								?>
								
								,userid : $(this).find('[name=userid]').val()
								,timespent_note : $(this).find('textarea[name=timespent_note]').val()
								,timespent_durationmin : $(this).find('[name=timespent_durationmin]').val()
								,timespent_durationhour : $(this).find('[name=timespent_durationhour]').val()
								
							}
							
						) .done(function(data) {
							/*
							 * Récupération de l'erreur de sauvegarde du temps
							 */
							jStart = data.indexOf("$.jnotify(");
							if(jStart>0) {
								jStart=jStart+11;
								jEnd = data.indexOf('"error"', jStart) - 10; 
								message = data.substr(jStart,  jEnd - jStart).replace(/\\'/g,'\'');
								if(message != "") { // Test on message empty. But could be jEnd > 0
								// ERror case
                                					$.jnotify(message,'error');
								}else{
									$.jnotify('<?php echo $langs->trans('TimeAdded') ?>');
								}
							}
							else
							{
								$.jnotify('<?php echo $langs->trans('TimeAdded') ?>');
							}
							
						});
						
						$("#saisie").dialog('close');
						
						
						task = project_get_task(id_project, id_task);
						task.status = 'inprogress';
						project_refresh_task(id_project, task);
	
						return false;
					
					});
				}
				)
				.dialog({
					modal:true
					,minWidth:1200
					,minHeight:200
					,title:$('li[task-id='+id_task+'] span[rel=label]').text()
				});
}

<?php if(!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK)) { ?>
<!--  Commentary conf -->

		
function pop_comment(id_project, id_task) {
	$("#saisie")
				.load('<?php echo dol_buildpath('/projet/tasks/comment.php',2) ?>?id='+id_task+' #comment'
				,function() {
					$('textarea[name="comment_description"]').attr('cols',25);
					
					$('#saisie form').submit(function() {
						
						$.post( $(this).attr('action')
							, {
								token : $(this).find('input[name=token]').val()
								,action : 'addcomment'
								,id : $(this).find('input[name=id]').val()
								,withproject : 0								
								,userid : $(this).find('[name=userid]').val()
								,comment_element_type : $(this).find('[name=comment_element_type]').val()
								,comment_description : $(this).find('textarea[name=comment_description]').val()
								
							}
							
						) .done(function(data) {
							/*
							 * Récupération de l'erreur de sauvegarde du temps
							 */
							jStart = data.indexOf("$.jnotify(");
							
							if(jStart>0) {
								jStart=jStart+11;
								
								jEnd = data.indexOf('"error"', jStart) - 10; 
								message = data.substr(jStart,  jEnd - jStart).replace(/\\'/g,'\'');
								$.jnotify('<?php echo $langs->trans('CommentAdded') ?>');
							}
							else {
								$.jnotify('<?php echo $langs->trans('CommentAdded') ?>', "ok");
								project_velocity(id_project);	
							}
							
						});
						
						$("#saisie").dialog('close');
						
						task = project_get_task(id_project, id_task);
						project_refresh_task(id_project, task);
	
						return false;
					
					});
				}
				)
				.dialog({
					modal:true
					,minWidth:1200
					,minHeight:200
					,title:$('li[task-id='+id_task+'] span[rel=label]').text()
				});
}
<!--  fin conf -->
<?php } ?>

function reset_the_dates(id_project) {
	
	var velocity = parseFloat($('#current-velocity').val());
	$.ajax({
		url : "./script/interface.php"
		,data: {
			json:1
			,put : 'reset-date-task'
			,id_project : id_project
			,velocity : velocity
		}
		,dataType: 'json'
		,type:'POST'
		,async:false
	})
	.done(function (task) {
		project_loadTasks(id_project);
		project_velocity(id_project);				
	}); 
	
}

function reset_date_task(id_project) {
	$("#reset-date").dialog({
			modal:true
			,minWidth:400
			,minHeight:200
			,buttons: [ 
				{ text: "<?php echo $langs->trans('Yes'); ?>", click: function() { reset_the_dates(id_project); $( this ).dialog( "close" ); } } 
				, { text: "<?php echo $langs->trans('No'); ?>", click: function() { $( this ).dialog( "close" ); } }
			] 
	});
}

function add_storie(id_project) {

	var storie_name = $('#newStorieName').val();
	var storie_order = parseInt($('#add_storie_k').val());
	var add_storie_date_start = $('#add_storie_date_start').val();
	var add_storie_date_end = $('#add_storie_date_end').val();
	
	$.ajax({
		url : "./script/interface.php"
		,data: {
			json:1
			,put : 'add_new_storie'
			,id_project : id_project
			,storie_name : storie_name
			,storie_order : storie_order
			,add_storie_date_start: add_storie_date_start
			,add_storie_date_end: add_storie_date_end
		}
		,dataType: 'json'
		,type:'POST'
		,async:false
	});
}

function add_storie_task(id_project) {
	$("#add-storie").dialog({
			modal:true
			,minWidth:400
			,minHeight:100
			,buttons: [
				{ text: "<?php echo $langs->trans('Add'); ?>", click: function() { add_storie(id_project); $( this ).dialog( "close" ); location.reload(); } }
				, { text: "<?php echo $langs->trans('Cancel'); ?>", click: function() { $( this ).dialog( "close" ); } }
			]
	});
}

function toggle_storie_visibility(id_project, storie_order) {
	$.ajax({
		url : "./script/interface.php"
		,data: {
			json: 1
			,put: 'toggle_storie_visibility'
			,id_project: id_project
			,storie_order: storie_order
		}
		,dataType: 'json'
		,type: 'POST'
		,async: false
	}).done(function(data) {
		
	});
}

function toggle_visibility(id_project, storie_order) {
	toggle_storie_visibility(id_project, storie_order);
	
	location.reload();
}