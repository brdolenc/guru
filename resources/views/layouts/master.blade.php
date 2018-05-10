<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

	<title>Jobs</title>

	{{ Html::style('css/bootstrap.min.css') }}
    <link href='https://fonts.googleapis.com/css?family=Muli:400,300' rel='stylesheet' type='text/css'>
    {{ Html::style('css/style.css') }}
    {{ Html::script('js/jquery-3.3.1.min.js') }}
    {{ Html::script('js/main.js') }}

</head>
	
	<body>

		@if(Session::has('name'))
			<header>
				<span>Olá, {{ Session::get('name') }}</span>
				<a href="{{ url('/logout') }}"><button type="button" class="btn btn-default btn-sm">Sair</button></a>
			</header>
		@endif

		@yield('content')

	</body>
</html>
