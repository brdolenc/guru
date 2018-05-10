@extends('layouts.master')

@section('content')

<div class="container">

	<div class="row">
	    <div class="col-md-12 text-center">
	    	{{ Form::text('search_input', '', array('class'=>'form-control', 'placeholder'=>'Procurar', 'autofocus'=>'autofocus', 'id'=>'search_input', 'onkeyup'=>'search()')) }}
	    </div>
	</div>

    <div class="row">
	    <div class="col-md-12 text-center" id="resources-list">

	    	@if(is_array($resources) && count($resources)>0)
	    		@foreach($resources as $resource)
	    			@php
	    				if(strstr($resource['image'], 'default')) $resource['image'] = 'https://ionz.resourceguruapp.com/images/fallback/resources/person/thumb_default.png';
	    			@endphp
	    				<a href="{{ url('/resource/'.$resource['id']) }}" class="resource-item">
						    <div class="panel panel-default">
							   	<div class="media"> 
							   		<div class="media-left"> 
								   		<img src="{{ $resource['image'] }}" data-holder-rendered="true" style="width: 64px; height: 64px;"> 
								   	</div> 
								   	<div class="media-body" style="padding-top: 10px;"> 
								   		<h4 class="media-heading">{{ str_replace("  ", " ", $resource['name']) }}</h4> 
								   		{{ $resource['job_title'] }}
								   	</div> 
							   </div>
							</div>
						</a>
	    		@endforeach

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
	    tr = table.getElementsByTagName("a");

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
</script>

@stop
