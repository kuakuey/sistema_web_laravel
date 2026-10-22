@extends('layouts.app')

@section('content')
  @include('partials.menu-tarjetas', [
    'titulo' => $titulo,
    'aria' => 'Menú de '.$titulo,
    'items' => $menuSeccion,
  ])
@endsection
