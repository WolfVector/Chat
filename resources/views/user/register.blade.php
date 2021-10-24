@extends('layout')

@section('content')
    <div class='bg-gray-200 h-full pt-16'>
        <div class='m-auto text-white'>
            <div class='text-2xl text-center mb-3 text-gray-600'>
                Register
            </div>
            <div class='bg-gray-400 m-auto w-96 px-3 py-3 rounded'>
                <form action='/register' method='post'>
                    @csrf
                    <input type='text' name='name' placeholder='Name' class='bg-gray-200 mb-3 rounded w-80 placeholder-gray-500 px-1 py-1 text-gray-700'>
                    <input type='text' name='username' placeholder='User name' class='bg-gray-200 mb-3 rounded w-80 placeholder-gray-500 px-1 py-1 text-gray-700'>
                    <input type='email' name='email' placeholder='Email' class='bg-gray-200 mb-3 rounded w-80 placeholder-gray-500 px-1 py-1 text-gray-700'>
                    <input type='password' name='password' placeholder='Password' class='bg-gray-200 mb-3 rounded w-80 placeholder-gray-500 px-1 py-1 text-gray-700'>
                    <input type='password' name='password_confirmation' placeholder='Password confirmation' class='bg-gray-200 mb-3 rounded w-80 placeholder-gray-500 text-gray-700 px-1 py-1'>
                    <input type='submit' value='Register' class='rounded mb-3 bg-white px-1 py-2 text-black hover:bg-blue-300'>
                    <br>
                    <a href='/login' class='text-blue-600 hover:underline'>Are you already register? Sign in</a>
                </form>
            </div>
            <div class='text-center mt-4'>
                @if($errors->any())
                    @foreach($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
