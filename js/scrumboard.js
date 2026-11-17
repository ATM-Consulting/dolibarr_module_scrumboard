/* Copyright (C) 2025 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    js/scrumboard.js
 * \ingroup scrumboard
 * \brief   Behaviour of the scrumboard page. Texts, urls, token and options come from the JSON block #scrumboard-context.
 */

let scrumboardContextCache = null;

function scrumboardContext() {
	if (scrumboardContextCache === null) {
		const element = document.getElementById('scrumboard-context');
		try {
			scrumboardContextCache = element ? JSON.parse(element.textContent) : {};
		} catch (error) {
			scrumboardContextCache = {};
		}
	}

	return scrumboardContextCache;
}


jQuery.ajaxPrefilter(function (options) {
	if (options.url && options.url.indexOf('script/interface.php') !== -1) {
		options.data = (options.data ? options.data + '&' : '') + 'token=' + encodeURIComponent(scrumboardContext().token);
	}
});

(function ($) {
	$.fn.serializeFormJSON = function () {

		let o = {};
		let a = this.serializeArray();
		$.each(a, function () {
			if (o[this.name]) {
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		return o;
	};
})(jQuery);

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

	let data = $('#scrum_filter_by_user').serializeFormJSON();
	data.get = 'tasks';
	data.status = status;
	data.id_project = id_project;
	data.async = false;

	$.ajax({
		url : "./script/interface.php"
		,data: data
		,dataType: 'json'
	})
	.done(function (tasks) {
		if (tasks.error) {
			$.jnotify(tasks.message, 'error', true);
			return;
		}
		$.each(tasks, function(i, task) {
			let l_status = status;
			// Si on utilise la conf de backlog et review, il faut tester si le scrum_status est vide pour mettre la tache dans la colonne la plus à gauche par défaut (test à faire unique si conf activé sinon on perd les taches sans scrum_status si désactivé)
			// TODO: Voir avec Geoffrey pour l'avenir de la conf SCRUM_ADD_BACKLOG_REVIEW_COLUMN
			if(status == 'todo' && (task.scrum_status =='backlog' || (scrumboardContext().addBacklogReviewColumn && task.scrum_status == "") ) ) {
				l_status = 'backlog';
			}
			else if(status == 'finish' && task.scrum_status =='review' ) {
				l_status = 'review';
			}
			else if(status == 'unknownColumn') {
				l_status = scrumboardContext().defaultColumn;
			}

			let $ul;
			if($('tr[project-id='+task.fk_project+'][story-k='+task.story_k+']').length>0) {
				$ul = $('tr[project-id='+task.fk_project+'][story-k='+task.story_k+']').find('ul[rel="'+l_status+'"]');
			}
			else{
				$ul = $('tr[project-id='+task.fk_project+'][default-k=1]').find('ul[rel="'+l_status+'"]');
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

		const $ul = $('tr[default-k=1]').find('ul[rel=' + (scrumboardContext().addBacklogReviewColumn ? 'backlog' : 'todo') + ']');


		project_draw_task(id_project, task, $ul);
		project_develop_task(task.id);
	});

}

function project_draw_task(id_project, task, ul) {
	let id = task.id;

	if($('#task-'+id).length == 0) { // Si tâche déjà affichée, on ne clone pas le noeud
		$('#task-blank').clone().attr('id', 'task-'+id).appendTo(ul);
	}

	project_refresh_task(id_project, task);
}

function project_refresh_task(id_project, task) {
	$item = $('#task-'+task.id);

	if(id_project == 0)
	{
		$storyTR = $item.parent().parent().parent();


		if($storyTR.is(':hidden'))
		{
			$storyTR.show();
			$target = $storyTR;
			while($target.is('.hiddable'))
			{
				$target = $target.prev();
			}
			$target.show().prev().show();

			let icon = $target.find('i');
			icon.removeClass().addClass('fa fa-eye-slash fa-lg').attr('title', scrumboardContext().texts.hide);


		}




	}

	// Generate js variable with all scrumboard project to showdesc
	const TShowDesc = scrumboardContext().showDesc || {};

	$item.attr('task-id', task.id);

	$item.removeClass('idea todo inprogress finish backlog review');
	$item.addClass(task.status);

	let progress= Math.round(task.progress / 5) * 5 ; // round 5
	$item.find('.task-progress select').val( progress ).attr('task-id', task.id).off( "change").on("change", function() {
		let id_projet = $('#scrum').attr('id_projet');
		let id_task = $(this).attr('task-id');
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
		$item.find('.task-title span').html(task.label).attr("title", task.long_description).addClass("classfortooltip");
		$item.find(".classfortooltip").tooltip({
			show: { collision: "flipfit", effect:'toggle', delay:50 },
			hide: { delay: 50 },
			tooltipClass: "mytooltip",
			content: function () {
				  return $(this).prop('title');		/* To force to get title as is */
				}
		});
	}
		$item.find('.task-extrafields').html(task.options_display);

	$item.find('.task-ref a').html(task.ref).attr("href", scrumboardContext().urls.task + '?withproject=1&id='+task.id);
	$item.find('.task-users-affected').html(task.internal_contacts).append(task.external_contacts);
	if (task.formatted_date_start_end) {
		$item.find('.task-dates').html('<span>' + task.formatted_date_start_end + '</span>');
	} else {
		$item.find('.task-dates').hide();
	}
	$item.find('.task-real-time span').html(task.aff_time).attr('task-id', task.id);
	$item.find('.task-allowed-time span').html(task.aff_planned_workload).attr('task-id', task.id);

	$item.find('.task-real-time, .task-allowed-time').off('click').on("click", function() {
		pop_time( $('#scrum').attr('id_projet'), $(this).find('span').attr('task-id'));
	});

	$item.find('.task-add-contact').off('click').on("click", function(e) {
		e.preventDefault();
		pop_contact( $('#scrum').attr('id_projet'), task.id );
	});

	if(task.origin == 'order') $item.find('.task-origin a').attr('href', scrumboardContext().urls.order + '?id='+task.origin_id);
	else if(task.origin == 'propal') $item.find('.task-origin a').attr('href', scrumboardContext().urls.propal + '?id='+task.origin_id);
	else $item.find('.task-origin a').remove();

	if (scrumboardContext().showLinkedContact) {
		$item.find(".task-add-contact a").attr("href", scrumboardContext().urls.scrum + "?action=addressourcetotask&id=" + $("#scrum").attr("id_projet") + "&id_task=" + task.id + "&token=" + scrumboardContext().pageToken);
	}

	if (scrumboardContext().allowComment) {
	$item.find('.task-comment span').html(task.nbcomment).attr('task-id', task.id);

	$item.find('.task-comment').off('click').on("click", function() {
		pop_comment( $('#scrum').attr('id_projet'), $(this).find('span').attr('task-id'));
	});
	}


	let percent_progress = Math.round(task.duration_effective / task.planned_workload * 100);
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

		let t = new Date().getTime() /1000;

		if( task.time_date_end>0 && task.time_date_end < t ) {
			$item.addClass('late-time');
		}
		else if(task.time_date_delivery>0 && task.time_date_delivery>task.time_date_end) {
			$item.addClass('late-delivery');
		}
	}


	$item.trigger('endRenderTask', [task]);
}

function project_get_task(id_project, id_task) {
	let taskReturn="";
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
		  let sortedIDs = $( this ).sortable( "toArray" );

		  let TTaskID=[];
		  $.each(sortedIDs, function(i, id) {

			  const taskid = $('#'+id).attr('task-id');
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
	scrumboardContext().columns.forEach(function (code) {
		project_get_tasks(id_projet, code);
	});
	project_get_tasks(id_projet , 'unknownColumn');
}
function create_task(id_projet) {

	if($('#dialog-create-task').length==0) {
		$('body').append('<div id="dialog-create-task"></div>');
	}
	let url =scrumboardContext().urls.tasks + "?action=create&id="+id_projet

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
			title: scrumboardContext().texts.addTask
			,width:800
			,modal:true
		});

	});
}


function pop_contact(id_project, id_task) {

	$.ajax({
		url: scrumboardContext().urls.scrum + '?id_task=' + id_task + '&action=addressourcetotask&token=' + scrumboardContext().pageToken + '&ajaxcall=1&id=' + id_project
		, method: 'GET'
		, dataType: 'html'
		, success: function(data)
		{
			$('#saisie').empty();
			$(data).find('#form-add-ressource-task-' + id_task).first().appendTo('#saisie');
			let fk_user = $("#fk_user").val();
			let input = $('<input type="hidden" name="fk_user" value="'+fk_user+'" >')
			let form = $('#saisie').find('form');

			form.append(input);

			$('#saisie').dialog({
				modal:true
				, minWidth:1200
				, minHeight:200
				, title:$('li[task-id=' + id_task + '] .task-title span').text()
			});
		}
	});
}


function pop_time(id_project, id_task) {
	let path = scrumboardContext().urls.time + '?id=' + id_task + '&action=createtime&token=' + scrumboardContext().pageToken;
		$("#saisie")
					.load(path+' div.fiche form'
					,function() {
						$('textarea[name=timespent_note]').attr('cols',25).focus();
					$('#time').datepicker({
											showOn: 'button',
											buttonImage: scrumboardContext().urls.calendarIcon,
											buttonImageOnly: true
											});

					$('#saisie').on('click', 'form input[name="cancel"]', function(event)
					{
						$('#saisie').dialog('close');

						return false;
					});

					$('#saisie form').submit(function() {

						$.post( $(this).attr('action')
							, {
								token : $(this).find('input[name=token]').val()
								,action : 'addtimespent'
								,id : id_task
								,taskid : id_task
								,withproject : 0
								,time : $(this).find('input[name=time]').val()
								,timeday : $(this).find('input[name=timeday]').val()
								,timemonth : $(this).find('input[name=timemonth]').val()
								,timeyear : $(this).find('input[name=timeyear]').val()
								,progress : $(this).find('select[name=progress]').val()
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
								if(message != "" && message.length < 1000) { // Test on message empty. But could be jEnd > 0
								// ERror case
													$.jnotify(message,'error');
								}else{
									$.jnotify(scrumboardContext().texts.timeAdded);
								}
							}
							else
							{
								$.jnotify(scrumboardContext().texts.timeAdded);
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
					,title:$('li[task-id='+id_task+'] .task-title').text()
				});
}



function pop_comment(id_project, id_task) {
	$("#saisie")
				.load(scrumboardContext().urls.comment + '?id=' + id_task+' #comment'
				,function() {
					$('textarea[name="comment_description"]').attr('cols',25).focus();

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
								$.jnotify(scrumboardContext().texts.commentAdded);
							}
							else {
								$.jnotify(scrumboardContext().texts.commentAdded, "ok");
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

function reset_the_dates(id_project) {

	let velocity = parseFloat($('#current-velocity').val());
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
				{ text: scrumboardContext().texts.yes, click: function() { reset_the_dates(id_project); $( this ).dialog( "close" ); } }
				, { text: scrumboardContext().texts.no, click: function() { $( this ).dialog( "close" ); } }
			]
	});
}

function add_storie(id_project) {

	let storie_name = $('#newStorieName').val();
	let storie_order = parseInt($('#add_storie_k').val());
	let add_storie_date_startday = $('#add_storie_date_startday').val();
	let add_storie_date_startmonth = $('#add_storie_date_startmonth').val();
	let add_storie_date_startyear = $('#add_storie_date_startyear').val();
	let add_storie_date_endday = $('#add_storie_date_endday').val();
	let add_storie_date_endmonth = $('#add_storie_date_endmonth').val();
	let add_storie_date_endyear = $('#add_storie_date_endyear').val();

	$.ajax({
		url : "./script/interface.php"
		,data: {
			json:1
			,put : 'add_new_storie'
			,id_project : id_project
			,storie_name : storie_name
			,storie_order : storie_order
			,add_storie_date_startday: add_storie_date_startday
			,add_storie_date_startmonth: add_storie_date_startmonth
			,add_storie_date_startyear: add_storie_date_startyear
			,add_storie_date_endday: add_storie_date_endday
			,add_storie_date_endmonth: add_storie_date_endmonth
			,add_storie_date_endyear: add_storie_date_endyear
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
				{ text: scrumboardContext().texts.add, click: function() { add_storie(id_project); $( this ).dialog( "close" ); location.href = location.pathname+'?id='+id_project; } }
				, { text: scrumboardContext().texts.cancel, click: function() { $( this ).dialog( "close" ); } }
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
		,async: true
	}).done(function(data) {

	});
}

function toggle_visibility(id_project, storie_order) {
	toggle_storie_visibility(id_project, storie_order);

	// On récupère le tr à cacher/afficher
	let tr = $('tr.hiddable[story-k='+storie_order+'][project-id='+id_project+']');
	let icon = $('i[data-story-k='+storie_order+'][data-project-id='+id_project+']');

	// S'il n'est pas encore caché
	if(tr.attr('style').indexOf('display') < 0) {
		tr.hide(200);
		// On change l'image du bouton
		icon.removeClass().addClass('fa fa-eye fa-lg').attr('title', scrumboardContext().texts.show);
	}
	else {
		tr.show(200);
		// On change l'image du bouton
		icon.removeClass().addClass('fa fa-eye-slash fa-lg').attr('title', scrumboardContext().texts.hide);
	}
}

/**
 * Send an ajax query to interface.php, which returns a HTML string with
 * a newe <select> (with select2) input whose options are restricted to the
 * selected country; replaces the old <select> with the one returned.
 */
function state_filter_on_change() {
	let country_id = $('#selectcountry_id').children('option:selected').val();
	$.ajax({
		url : "./script/interface.php"
		,data: {
			json:0
			,get: 'get_state_selector'
			,country_id: country_id
			,preselected_state_id: 0
			,async:true
		}
		,dataType: 'html'
	}).done(function(html) {
		$('#state_id').parent().html(html);
	});
}

$(function () {
	const context = scrumboardContext();
	if (context.columnWidth) {
		document.documentElement.style.setProperty('--scrum-column-width', context.columnWidth + '%');
	}
	if (document.getElementById('scrumboard-context')) {
		project_loadTasks(context.projectId);
		project_init_change_type(context.projectId);
		project_velocity(context.projectId);
	}
});
