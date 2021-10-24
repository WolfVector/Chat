<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Chat;
use App\Models\ChatRoom;
use App\Models\User;
use App\Models\Participants;
use App\Events\ChatEvent;
use Response;

class ChatController extends Controller
{
    private function formChatId($id, $user_id)
    {
        return (($id > $user_id) ? $id.':'.$user_id : $user_id.':'.$id);
    }

    private function getChatId($chat_id)
    {
        $room_id = ChatRoom::select('id')
            ->where('roomId', '=', $chat_id)
            ->first(); //'first' returns null if nothing was found

        return(($room_id == null) ? null : $room_id->id);
    }

    private function createRoomId($chat_id, $to_id, $from_id)
    {
        $room = new ChatRoom;
        $room->roomId = $chat_id;
        $room->save();

        $this->addParticipant($room->id, $from_id);
        $this->addParticipant($room->id, $to_id);

        return $room->id;
    }

    private function addParticipant($room_id, $user_id)
    {
        $participant = new Participants;
        $participant->roomId = $room_id;
        $participant->user_id = $user_id;
        $participant->save();
    }

    private function checkUserId($username, $user_id)
    {
        $user = User::select('id')
            ->where('username', '=', $username)
            ->first();

        return(($user->id == $user_id) ? true : false);
    }

    private function pullRecentMessages($user_id, $page)
    {
        /*
            SELECT users.username, chats.body, chats.created_at 
            FROM chats
            JOIN(
                SELECT MAX(chats.id) AS id, users.id AS user_id
                FROM chats 
                JOIN(
                    SELECT roomId, user_id 
                    FROM participants
                    WHERE roomId IN (SELECT roomId FROM participants WHERE user_id=$user_id)
                    AND user_id!=$user_id
                )sub ON sub.roomId=chats.chatId 
                JOIN users ON users.id=sub.user_id
                GROUP BY chatId
            )var1 ON var1.id=chats.id JOIN users ON users.id=var1.user_id;

            SELECT chats.body, chats.created_at FROM chats JOIN(SELECT MAX(id) AS id FROM chats WHERE chatId IN (SELECT roomId FROM participants WHERE user_id=1) GROUP BY chatId)sub ON chats.id=sub.id JOIN(SELECT user_id FROM participants WHERE roomId IN (SELECT roomId FROM participants WHERE user_id=1) AND user_id!=1)sub2 ON sub2.user_id
            
            SELECT users.username, chats.body, chats.created_at FROM chats JOIN(SELECT MAX(id) AS id, chatId FROM chats WHERE chatId IN (SELECT roomId FROM participants WHERE user_id=1) GROUP BY chatId)sub ON chats.id=sub.id JOIN participants ON participants.roomId=sub.chatId AND user_id!=1 JOIN users ON users.id=participants.user_id;
            

        */

        $rooms = Chat::select(DB::raw('MAX(id) as id'), 'chatId')
            ->whereIn('chatId', function($query) use ($user_id){
                $query->select('roomId')->from('participants')->where('user_id', '=', $user_id);
            })
            ->groupBy('chatId');

        $recent_messages = Chat::select('chats.body', 'users.id', 'users.username', 'chats.created_at')
            ->join(DB::raw('('.$rooms->toSql().') as sub'), function($join) use ($rooms) {
                $join->on('sub.id', '=', 'chats.id')
                ->addBinding($rooms->getBindings());
            })
            ->join('participants', function($join) use($user_id) {
                $join->on('participants.roomId', '=', 'sub.chatId')
                     ->where('participants.user_id', '<>', $user_id);
            })
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->orderBy('chats.created_at', 'desc')
            ->limit(15)
            ->offset($page * 15)
            ->get();

        return $recent_messages;
    }

    private function pullMessages($limit, $qwhere,$room_id)
    {
        /*
            
        Obten los ultimos 15 mensajes ordenados por fecha
        Usando un cursor

        SELECT * FROM (chats.body, chats.created_at, users.username 
        FROM chats
        JOIN users ON users.id=chats.sender
        WHERE chats.chatId=$room_id AND chats.id < $last_id
        ORDER BY chats.created_at DESC
        LIMIT 15
        OFFSET 15 * $page
        )Var1
        ORDER BY created_at

        */

        $messages = Chat::select('chats.id', 'chats.body', 'chats.created_at', 'users.username')
            ->join('users', 'users.id', '=', 'chats.sender')
            ->whereRaw($qwhere)
            ->orderBy('chats.id', 'desc')
            ->limit($limit);

        $last_messages = DB::table(DB::raw("({$messages->toSql()}) as sub"))
            ->mergeBindings($messages->getQuery())
            ->orderBy('id')
            ->get();

        return $last_messages;
    }

    /* Get the messages */

    public function chatRoom(Request $request, $id, $user)
    {
        $user_id = Auth::guard('user')->user()->id;
        $chat_id = $this->formChatId($id, $user_id);

        $room_id = $this->getChatId($chat_id);
        if($room_id == null)
        {
            return view('user.chatRoom', [
                'user' => $user,
                'to_id' => $id,
                'user_id' => $user_id,
                'error' => 'No messages',
                'recent_messages' => $this->pullRecentMessages($user_id, 0),
                'messages' => []
            ]);
        }
        
        $qwhere = "chats.chatId=$room_id";
        $recent_messages = $this->pullRecentMessages($user_id, 0);
        $messages = $this->pullMessages(15, $qwhere, $room_id);
        $last_id = $messages[0]->id;

        return view('user.chatRoom', [
            'user' => $user,
            'to_id' => $id,
            'user_id' => $user_id,
            'recent_messages' => $recent_messages,
            'messages' => $messages,
            'last_id' => $last_id
        ]);
    }

    public function infiniteChatRoom(Request $request, $id, $user)
    {
        $user_id = Auth::guard('user')->user()->id;
        $chat_id = $this->formChatId($id, $user_id);

        $room_id = $this->getChatId($chat_id);
        if($room_id == null)
            return redirect('/home/message/'.$id.'/'.$user);

        $request->validate([
            'page' => 'required|numeric',
            'status' => 'required|alpha|in:all,left'
        ]);

        $page = ($request->has('page')) ? $request->input('page') : 0;
        $status = ($request->has('status')) ? $request->input('status') : 'left';

        $limit = 15;

        if($status == 'all')
            return response()->json(['html' => '', 'status' => 'all']);

        $qwhere = "chats.chatId=$room_id AND chats.id < $page";

        $last_messages = $this->pullMessages($limit, $qwhere, $room_id);

        if($request->ajax())
        {
            $username = $request->user()->username;
            $result = [];
            $result['html'] = '';
            $result['status'] = ($last_messages->isEmpty()) ? 'all' : 'left';
            $result['last_id'] = ($result['status'] == 'all') ? $page : $last_messages[0]->id; 

            foreach($last_messages as $message)
            {
                if($username != $message->username)
                {
                    $result['html'] .= '<div style="min-width: 10%; max-width: 50%;" class="bg-gray-300 text-gray-600 p-1 clear-both rounded float-left m-1"'.$message->body.'</div>';
                }
                else
                {
                    $result['html'] .= '<div style="min-width: 10%; max-width: 50%;" class="bg-blue-600 p-1 clear-both text-white rounded float-right m-1">'.$message->body.'</div>'; 
                }
            }

            return $result;
        }

        return redirect('/home/message/'.$id.'/'.$user);
    }

    public function infiniteChats(Request $request, $id, $user)
    {
        $user_id = Auth::guard('user')->user()->id;

        $request->validate([
            'page' => 'required|numeric',
            'status' => 'required|alpha|in:all,left'
        ]);

        if($status == 'all')
            return response()->json(['html' => '', 'status' => 'all']);

        $recent_messages = $this->pullRecentMessages($user_id, $request->input('page'));

        if($request->ajax())
        {
            $result = [];
            $result['html'] = '';
            $result['status'] = ($recent_messages->isEmpty()) ? 'all' : 'left';

            foreach($recent_messages as $recent)
            {
                $result['html'] .= "<div onclick=\"gotoRoom($recent->id, $recent->username)¸\" class='mb-2 border-b-2 border-gray-300 cursor-pointer hover:bg-gray-300'><div class='text-lg ml-1 mb-2 text-gray-500'>$recent->username</div><div class='text-base text-gray-400 ml-1'>$recent->body</div></div>";
            }

            return $result;
        }

        return redirect('/home/message/'.$id.'/'.$user);
    }

    /* Save message */

    public function saveMessage(Request $request)
    {
        $request->validate([
            'to' => 'required|alpha_dash',
            'from' => 'required|alpha_dash',
            'body' => 'required',
            'to_id' => 'required|integer'
        ]);

        /* Check if the given id match the user */
        if(!$this->checkUserId($request->input('to'), $request->input('to_id')))
            return response()->json(['status' => 'ERROR', 'message' => 'There was a problem while sending the message to the given user']);

        $chat_id = $this->formChatId($request->input('to_id'), $request->user()->id);
        $room_id =$this->getChatId($chat_id);
        if($room_id == null) $room_id = $this->createRoomId($chat_id, $request->input('to_id'), $request->user()->id);

        $chat = new Chat;
        $chat->sender = $request->user()->id;
        $chat->chatId = $room_id;
        $chat->body = $request->input('body');
        $chat->save();

        ChatEvent::dispatch($chat_id, $request->user()->id, $request->input('body'));

        return response()->json(['status' => 'OK', 'message' => 'OK']);
    }
}
