@extends('layouts.app')

@section('content')
  @include('partials.menu-tarjetas', [
    'titulo' => 'Eventos',
    'aria' => 'Menú de Eventos',
    'items' => $menuEventos,
  ])
@endsection
