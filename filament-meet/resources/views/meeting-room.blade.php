@extends('filament-meet::layouts.meeting', ['title' => $meeting->title])

@section('content')
    @livewire('filament-meet::meeting-room', ['meeting' => $meeting])
@endsection
