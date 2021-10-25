@extends('layout')
<meta name="csrf-token" content="{{ csrf_token() }}" />

<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"
  integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
  crossorigin="anonymous"></script>

@section('content')
<div class="bg-gray-400 py-2 px-3">
    <div class="text-gray-100 px-1 text-base">
        <a class="hover:text-gray-500" href="/home">Home</a>
        <a class="pl-2 hover:text-gray-500" href="/profile">Profile</a>
        <a class="pl-2 hover:text-gray-500" href="/logout">Logout</a>
    </div>
</div>
    <div class="flex h-screen bg-gray-200 px-3 py-3">
        <div class="bg-gray-800 m-auto rounded w-full h-full grid grid-cols-5">
            <div class="border-r-2 border-gray-200" style="background: #edf2f7;">
                <div id="chats" style="max-height: 600px;" class="my-3 overflow-y-auto">
                    <div id="chatRooms">
                        @foreach($recent_messages as $recent)
                            <div onclick="gotoRoom({{ $recent->id }}, '{{ $recent->username }}')" class="mb-2 border-b-2 border-gray-300 cursor-pointer hover:bg-gray-300">
                                <div class="text-lg flex ml-1 mb-2 text-gray-500">
                                    <span><img class="w-10 h-10 object-cover rounded-full" src="/storage/{{ $recent->image_name }}"></span>
                                    {{ $recent->username }}
                                </div>
                                <div class="text-base text-gray-400 ml-1">
                                    {{ $recent->body }}
                                </div>
                            </div>
                        @endforeach 
                        <div id="loadingChats" class="hidden">
                            ...
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-1 p:2 sm:p-3 justify-between flex flex-col h-screen col-span-4"  style="background: #edf2f7;">
                <div class="flex sm:items-center justify-between border-b-2 border-gray-300">
                  <div class="flex items-center space-x-4">
                     <div class="flex flex-col leading-tight">
                        <div class="text-2xl mt-1 flex items-center">
                           <span><img class="w-10 h-10 object-cover rounded-full" src="/storage/{{ $user_image->name }}"></span>
                           <span class="text-gray-700 mr-3">{{ $user }}</span>
                        </div>
                     </div>
                  </div>
               </div>
                <div class="relative">
                    @isset($error)
                    <div id="noMessages" class="text-red-700 bg-red-100 border border-red-400 rounded px-2 py-1 w-1/2 absolute left-1/4 z-10">
                        {{ $error }}
                    </div>
                    @endisset
                    <div id="errorElement" class="text-red-700 bg-red-100 border border-red-400 rounded px-2 py-1 w-1/2 absolute left-1/4 z-10 hidden">

                    </div>
                    <div id="messageBox" style="max-height: 500px;" class="mt-3 overflow-y-auto z-0">
                        <div id="loading" class="text-center hidden">
                            ...
                        </div>

                        @foreach($messages as $message)
                            @if($message->username == $user)
                                <div style="min-width: 10%; max-width: 50%;" class="bg-gray-300 text-gray-600 p-1 clear-both rounded float-left m-2">
                                    {{ $message->body }}        
                                </div>
                            @else
                                <div style="min-width: 10%; max-width: 50%;" class="bg-blue-600 p-1 clear-both text-white rounded float-right m-1">
                                    {{ $message->body }}
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                 <div class="border-t-2 border-gray-200 px-4 pt-4 mb-2 sm:mb-0">
                    <div class="relative flex">
                        <input id="msg" type="input" placeholder="Write something" name="msg" class="w-full focus:outline-none focus:placeholder-gray-400 text-gray-600 placeholder-gray-600 pl-12 bg-gray-300 rounded-full py-2 mb-2">
                        <input type="hidden" name="to_id" id="to_id" value="{{ $to_id }}">
                        <input type="hidden" name="user_id" id="user_id" value="{{ $user_id }}">
                        <input type="hidden" name="user" id="user" value="{{ $user }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="http://localhost:3000/socket.io/socket.io.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script type="text/javascript">
    let to_id = {{ $to_id }}
    let from_id = {{ $user_id }}
    let to = "{{ $user }}"

    let infinite_obj = {
        page: {{ $last_id }},
        status: 'left'
    }

    let infiniteChats_obj = {
        page: 0,
        status: 'left'
    }

    let message_reference = 0;
    let chat_reference = 0;

    let messageBox = document.getElementById('messageBox');
    messageBoxScrollBottom();
    noMessagesBox();

    window.socket.on('connect', function() {
        console.log('CONNECT');

        socket.on('App\\Events\\ChatEvent', function(data) {
            if(data.sender == from_id)
            {
                $("#messageBox").append('<div style="min-width: 10%; max-width: 50%;" class="bg-gray-600 p-1 clear-both text-white rounded float-right m-1">'+ data.body +'</div>');
            }
            else
            {
                $("#messageBox").append('<div style="min-width: 10%; max-width: 50%;" class="bg-gray-500 p-1 clear-both text-white rounded float-left m-1">'+ data.body +'</div>');
            }

            messageBoxScrollBottom();
        });


        socket.on('disconnect', function(){
            console.log('disconnect');
        });
    });

    window.socket.emit('subscribe-to-channel', {channel:  'private-chatRoom.'+getChatId()});

    $("#msg").on('keyup', function(e){
        if( e.keyCode === 13 ||e.key === 'Enter')
        {
            let msg = document.getElementById('msg');

            if(msg.value != '')
            {
                window.axios.post('http://localhost:8000/home/message/send', {
                    to_id,
                    to,
                    from: from_id,
                    body: msg.value
                }, {headers:{"Content-Type" : "application/json"}})
                .then(response => responseMHandler(response))
                .catch(err => showErrorMsg('ERROR request: there was an error while sending the message'));
            }

            msg.value = '';
        }
    });

    $('#messageBox').on('scroll', function() {
        let scrollTop = $(this).scrollTop();
        
        if(scrollTop <= 0 && infinite_obj.status == 'left')
        {
            //infinite_obj.page++;

            /* Get the reference of the top div */ 
            message_reference = $('#messageBox').children().first();
            
            /* Load more data on scroll */
            infiniteLoadMessages();
        }
    });

    $('#chats').on('scroll', function() {
        if($(this).scrollTop() + $(this).outerHeight() == $('#chatRooms').height())
        {
            infiniteChats_obj.page++;

            /* Get the reference of the top div */ 
            chat_reference = $('#chatRooms').children().first();
            
            /* Load more data on scroll */
            infiniteLoadChats();
        } 
    });

    function responseMHandler(response)
    {
        if(response.status == 'ERROR')
            showErrorMsg(response.message);
    }

    /* Show the message for 4 seconds */
    function showErrorMsg(message)
    {
        let element = document.getElementById('errorElement');
        element.innerHTML = message;
        element.style.display = 'block';

        setTimeout(function() {
            element.style.display = 'none';
        }, 4000);
    }

    function getChatId()
    {
        return ((to_id > from_id) ? to_id+ ':'+ from_id : from_id+':'+to_id);
    }

    function messageBoxScrollBottom()
    {
        messageBox.scrollTop = messageBox.scrollHeight;
    }

    function noMessagesBox()
    {
        let no_messages = document.getElementById('noMessages');
        if(no_messages)
        {
            setTimeout(function() {
                no_messages.style.display = 'none';
            }, 3000)
        }
    }

    function infiniteLoadMessages()
    {
        $.ajax({
            url: "{{ url('/') }}/home/message/pull/"+to_id+"/"+to+'?page='+infinite_obj.page+'&status='+infinite_obj.status,
            datatype: "html",
            type: 'get',
            beforeSend: function() {
                $("#loading").show();
            },
            success: function(response) {
                $('#loading').hide();
                $('#messageBox').prepend(response.html);

                /* Check if all message haven pulled */
                infinite_obj.status = response.status;
                infinite_obj.page = response.last_id;

                /* Loop through the new elements and get its height (this include padding and margin) */                
                let previous_height = 0;
                message_reference.prevAll().each(function() {
                    /* Sum the height of each element */
                    previous_height += $(this).outerHeight();
                });

                /* Set scroll top of the previous height, that is, prevent the scroll for
                    changing position
                 */
                document.getElementById('messageBox').scrollTop = previous_height;
            },
            error: function(response) {
                //console.log(response);
                showErrorMsg('ERROR request: there was a problem while loanding the messages')
            }
        });
    }

    function infiniteLoadChats()
    {
        $.ajax({
            url: "{{ url('/') }}/home/message/chats/"+to_id+"/"+to+'?page='+infiniteChats_obj.page+'&status='+infiniteChats_obj.status,
            datatype: "html",
            type: 'get',
            beforeSend: function() {
                $("#loadingChats").show();
            },
            success: function(response) {
                $('#loadingChats').hide();
                $('#chatRooms').append(response.html);

                /* Check if all message haven pulled */
                infiniteChats_obj.status = response.status;

                /* Loop through the new elements and get its height (this include padding and margin) */                
                /*let previous_height = 0;
                chat_reference.prevAll().each(function() {
                    /* Sum the height of each element */
                   /* previous_height += $(this).outerHeight();
                });*/

                /* Set scroll top of the previous height, that is, prevent the scroll for
                    changing position
                 */
                //document.getElementById('chatRooms').scrollTop = previous_height;
            },
            error: function(response) {
                //console.log(response);
                showErrorMsg('ERROR request: there was a problem while loanding the chats')
            }
        });   
    }

    function gotoRoom(id, username)
    {
        window.location.href = "{{ url('/') }}/home/message/"+id+"/"+username;
    }

</script>
@endsection
