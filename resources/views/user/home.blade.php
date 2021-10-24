@extends('layout')

@section('content')
<div class="bg-gray-400 py-2 px-3">
    <div class="text-gray-100 px-1 text-base">
        <a class="hover:text-gray-500" href="/home">Home</a>
        <a class="pl-2 hover:text-gray-500" href="/profile">Profile</a>
        <a class="pl-2 hover:text-gray-500" href="/logout">Logout</a>
    </div>
</div>
    <div class='h-full px-10 py-3' style="background: #edf2f7;">
        <div class='text-gray-600 m-auto h-full w-full px-3 py-3'>
            <div class='mb-3 text-2xl'>
                Users
            </div>
            @foreach($users as $user)
                <div class='mb-3 text-blue-600'>
                    <a class="hover:underline" href="/home/message/{{ $user->id }}/{{ $user->username }}">{{ $user->username }}</a>
                </div>
            @endforeach

            <div class="mt-3">
                <a class="text-blue-300 hover:underline" href="/logout">Logout</a>
            </div>
        </div>
    </div>
@endsection
