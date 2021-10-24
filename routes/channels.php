<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

function checkChatRoom($room_id, $user_id)
{
    $room = explode(':', $room_id);
    $ret = false;

    if($room[0] == $user_id || $room[0] == $user_id)
        $ret = true;

    return $ret;
}


/*Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});*/

Broadcast::channel('chatRoom.{room_id}', function($user, $room_id) {

    return checkChatRoom($room_id, $user->id);

}, ['guards' => ['user']]);

