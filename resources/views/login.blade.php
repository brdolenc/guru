@extends('layouts.master')

@section('content')



<div class="container">
  <div class="row">
    <div class="col-md-4 col-md-offset-4 text-center">
      <div class="panel panel-default">
        <div class="panel-heading">Log in with your Resource Guru ID</div>
        <div class="panel-body">

          @foreach ($errors->all() as $message) 
            <p class="text-danger">{{ $message }}</p>
          @endforeach

          {{ Form::open(array('url' => '/login', 'class'=>'form-signin', 'method'=>'POST')) }}

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                {{ Form::email('email', '', array('class'=>'form-control border-input', 'placeholder'=>'Email address', 'autofocus'=>'autofocus', 'required'=>'required')) }}
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-group">
                {{ Form::password('password', array('class'=>'form-control border-input', 'placeholder'=>'Password', 'required'=>'required')) }}
                </div>
              </div>
            </div>

            {{ Form::submit('log in', array('class'=>'btn btn-md btn-default')) }}

          {{ Form::close() }}

        </div>
      </div>
    </div>
  </div>
</div>

@stop
