@extends('layout')

@section('content')
    <div class='bg-gray-900 h-full pt-16'>
        <div class='m-auto text-white'>
            <div class='text-2xl text-center mb-3'>
                Register
            </div>
            <div class='bg-gray-800 m-auto w-96 px-3 py-3 rounded'>
                <form action='/login/auth' method='post'>
                    @csrf
                    <input type='text' name='username' placeholder='User name' class='bg-gray-900 mb-3 rounded w-80 placeholder-gray-500 px-1 py-1'>
                    <input type='password' name='password' placeholder='Password' class='bg-gray-900 mb-3 rounded w-80 placeholder-gray-500 px-1 py-1'>
                    <input type='submit' value='Register' class='rounded mb-3 bg-white px-1 py-2 text-black hover:bg-blue-300'>
                    <br>
                    <a href='/' class='text-blue-300 hover:underline'>Sign up</a>
                </form>
            </div>
            <div class='text-center mt-4'>
                @if($errors->any())
                    @foreach($errors->all() as $errors)
                        {{ $error }}
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection