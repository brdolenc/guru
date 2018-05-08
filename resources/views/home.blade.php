@extends('layouts.master')

@section('content')

<div class="container">
  <div class="row">
    <div class="col-md-4 col-md-offset-4">

    	@if(is_array($resources) && count($resources)>0)
    		@foreach($resources as $resource)
    			@php
    				if(strstr($resource['image'], 'default')) $resource['image'] = 'https://ionz.resourceguruapp.com/images/fallback/resources/person/thumb_default.png';
    			@endphp
				    <div class="panel panel-default">
					   	<div class="media"> 
					   		<div class="media-left"> 
						   		<a href="{{ url('/user/'.$resource['id']) }}"><img src="{{ $resource['image'] }}" data-holder-rendered="true" style="width: 64px; height: 64px;"></a> 
						   	</div> 
						   	<div class="media-body" style="padding-top: 10px;"> 
						   		<a href="{{ url('/user/'.$resource['id']) }}"><h4 class="media-heading">{{ $resource['name'] }}</h4> 
						   		{{ $resource['job_title'] }}</a>
						   	</div> 
					   </div>
					</div>
    		@endforeach

    	@endif

    </div>
  </div>
</div>

@stop
