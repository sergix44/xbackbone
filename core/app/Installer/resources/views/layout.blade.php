@extends('layouts::base')

@section('body')
    <div class="grid flex-grow place-items-center py-10">
        <div class="w-full max-w-3xl px-4">
            <h1 class="mb-8 flex justify-center">
                <x-app-brand on-top/>
            </h1>
            {{ $slot }}
        </div>
    </div>
    <x-toast/>
    <x-footer/>
@endsection
