@extends('layouts.master')

@section('content')

<meta name="_token" content="{{csrf_token()}}" />

<div class="container">


	<div class="row">
	    <div class="col-md-12 text-left">
	    	<p class='infos'>Tarefas de {{ str_replace("  ", " ", $resource['name']) }} | <b>{{ $current_date }}</b></p>
	    </div>
	</div>

	<div class="row filters" style="margin-top: 15px;">
	    <div class="col-sm-3 text-center">
	    	{{ Form::text('search_input', '', array('class'=>'form-control input-sm', 'placeholder'=>'Procurar', 'autofocus'=>'autofocus', 'id'=>'search_input', 'onkeyup'=>'search()')) }}
	    </div>
	    <div class="col-sm-9 text-right">

	    	{{ Form::open(array('url' => '/resource/'.Route::input('idResource'), 'method'=>'get', 'class'=>'form-inline')) }}
	    	<a href="{{ url('/') }}"><button type="button" class="btn btn-primary btn-sm">Usuários</button></a>
	    	<a href="{{ url('/resource/'.Route::input('idResource')) }}?date={{ $current_date }}&day=hoje"><button type="button" class="btn btn-default btn-sm">hoje</button></a>
	    	<a href="{{ url('/resource/'.Route::input('idResource')) }}?date={{ $current_date }}&day=-1"><button type="button" class="btn btn-default btn-sm">-1 dia</button></a>
	    	<a href="{{ url('/resource/'.Route::input('idResource')) }}?date={{ $current_date }}&day=+1"><button type="button" class="btn btn-default btn-sm">+1 dia</button></a>
            <div class="form-group"><input class="form-control input-sm input-date" name="date" type="date" value="{{ $current_date }}"> {{ Form::submit('OK', array('class'=>'btn btn-sm btn-default button-date')) }}</div>

           
          	{{ Form::close() }}
	    </div>
	</div>

    <div class="row">
	    <div class="col-md-12 text-center" id="resources-list">

	    	@if(is_array($bookings) && count($bookings)>0)
	    		@php
	    			$timeResources = 0;
	    			$timeDay = 0;
	    		@endphp
	    		@foreach($bookings as $booking)
	    			@php
	    				$project = $projects[$booking['project_id']];
	    			@endphp
	    			@php
	    				$resourceData = $resource_saveds[$booking['id']];
	    			@endphp
	    			@php
		    			$timeResources += $booking['durations'][0]['duration'];
		    			$timeDay += $resourceData['timer_count'];
		    		@endphp
    				@if($resourceData['timer_count']>0)
    					@php $resourceData['timer_count'] = App\Http\Controllers\Controller::minutosToHour($resourceData['timer_count']); @endphp
    				@endif
				    <section class="project-item">
					    <div class="panel panel-default" style="border-left: 5px solid {{ $project['color'] }}">
						   	<div class="media"> 
							   	<div class="media-body" id="media-body-{{ $booking['id'] }}" @if($resourceData['status']=='FINALIZED') style="background: #f4f4f4" @endif > 
							   		<div class="row">
								   		<div class="col-md-6">

									   		<h4 class="media-heading">{{ $project['client']['name'] }} | {{ $project['name'] }}</h4> 
									   		<p><?php echo htmlspecialchars_decode($booking['details']); ?></p>
									   		<p class="infos"><b>Booker</b>: {{ $booking['booker']['name'] }} | <b>Time</b>: {{  App\Http\Controllers\Controller::minutosToHour($booking['durations'][0]['duration']) }}h</p>
									   	
									   	</div>
									   	<div class="col-md-6 text-right">

									   		@if($resourceData['status']=='NEW')
									   			<button type="button" class="btn btn-success btn-xs finelized-booking" data-idbooking="{{ $booking['id'] }}">Finalizar tarefa</button> 
									   			
									   			@if($resourceData['timer']=='OFF')
									   				<button type="button" class="btn btn-info btn-xs timer-booking" data-idbooking="{{ $booking['id'] }}">Iniciar timer</button> 
									   			@elseif($resourceData['timer']=='ON')
									   				<button type="button" class="btn btn-warning btn-xs timer-booking" data-idbooking="{{ $booking['id'] }}">Parar timer</button>
									   			@endif

									   			<button type="button" class="btn btn-default btn-xs" id="timer_display-{{ $booking['id'] }}">{{ $resourceData['timer_count'] }}h</button>

									   		@elseif($resourceData['status']=='FINALIZED')
									   			<button type="button" class="btn btn-default btn-xs finelized-booking" data-idbooking="{{ $booking['id'] }}">Finalizado em ({{ $resourceData['status_updated_at'] }})</button> 

									   			@if($resourceData['timer']=='OFF')
									   				<button type="button" class="btn btn-info btn-xs timer-booking" data-idbooking="{{ $booking['id'] }}">Iniciar timer</button> 
									   			@elseif($resourceData['timer']=='ON')
									   				<button type="button" class="btn btn-warning btn-xs timer-booking" data-idbooking="{{ $booking['id'] }}">Parar timer</button>
									   			@endif
									   			
									   			<button type="button" class="btn btn-default btn-xs" id="timer_display-{{ $booking['id'] }}">{{ $resourceData['timer_count'] }}h</button>
									   		@endif

									   	</div>

									   	
								   </div>

							   		
							   	</div> 
						   </div>
						</div>
					</section>
	    		@endforeach

	    	@endif

	    </div>
    </div>
    <div class="row" style="margin-bottom: 40px;">
	    <div class="col-md-12">
	    	@if(isset($timeResources))
		    	<p class='infos'>
		    		<b>Horas alocadas:</b> {{ App\Http\Controllers\Controller::minutosToHour($timeResources) }}h | 
		    		<b>Horas trabalhadas:</b> {{ App\Http\Controllers\Controller::minutosToHour($timeDay) }}h
		    	</p>
		    @endif
	    </div>
	</div>
</div>

<script>
    function search() {

	    // Declare variables 
	    var input, filter, table, tr, td, i;
	    input = document.getElementById("search_input");
	    filter = input.value.toUpperCase();
	    table = document.getElementById("resources-list");
	    tr = table.getElementsByTagName("section");

        for (i = 0; i < tr.length; i++) {
        	td = tr[i].getElementsByTagName("h4")[0];
          	if (td) {
	            if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
	            	tr[i].style.display = "";
	            } else {
	            	tr[i].style.display = "none";
	            }
          	} 
        }

    }

    $(document).ready(function(){

		$(".finelized-booking").click(function(){

			var bt = $(this);
			var idBooking = bt.data('idbooking');
			var bodyMedia = $("#media-body-"+idBooking);
			var newDate = new Date();
			var datetime = newDate.getFullYear() + "-"+(newDate.getMonth()+1) + "-" + newDate.getDate() + "  " + newDate.getHours() + ":" + newDate.getMinutes() + ":" + newDate.getSeconds();
			
			//altera o html do botao durante a requisição
			bt.html('Aguarde...').attr('disabled','disabled');

			$.ajaxSetup({
		        headers: {
		            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
		        }
		    });
			$.ajax({
				url: "{{ url('/booking/status') }}",
				method: 'post',
				data: {
					id: idBooking
				},
				success: function(result){
					if(result=='NEW'){
						bt.html('Finalizar tarefa').removeAttr('disabled','disabled').removeClass('btn-default').addClass('btn-success');
						bodyMedia.css('background','#FFFFFF');
					}else if(result=='FINALIZED'){
						bt.html('Finalizado em ('+datetime+')').removeAttr('disabled','disabled').removeClass('btn-success').addClass('btn-default');
						bodyMedia.css('background','#f4f4f4');
					}else {
						bt.html('Finalizar tarefa').removeAttr('disabled','disabled');
						bodyMedia.css('background','#FFFFFF');
					}
				},
				error: function(result){
					bt.html('Finalizar tarefa').removeAttr('disabled','disabled');
				}
			});
		});


		$(".timer-booking").click(function(){

			var bt = $(this);
			var idBooking = bt.data('idbooking');
			var timer_display = $("#timer_display-"+idBooking);
			
			//altera o html do botao durante a requisição
			bt.html('Aguarde...').attr('disabled','disabled');

			$.ajaxSetup({
		        headers: {
		            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
		        }
		    });
			$.ajax({
				url: "{{ url('/booking/timer') }}",
				method: 'post',
				dataType: "JSON",
				data: {
					id: idBooking
				},
				success: function(result){
					if(result.response=='ON'){
						bt.html('Parar timer').removeAttr('disabled','disabled').removeClass('btn-info').addClass('btn-warning');
						timer_display.html(result.timer_count + 'h');
					}else if(result.response=='OFF'){
						bt.html('Iniciar timer').removeAttr('disabled','disabled').removeClass('btn-warning').addClass('btn-info');
						timer_display.html(result.timer_count + 'h');
					}else {
						bt.html('Iniciar timer').removeAttr('disabled','disabled');
					}
				},
				error: function(result){
					bt.html('Iniciar timer').removeAttr('disabled','disabled');
				}
			});

		});

	});

</script>

@stop
