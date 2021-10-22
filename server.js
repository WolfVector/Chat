const SERVER_PORT = 3000;

var axios = require('axios');
var app = require('express')();
var server = require('http').Server(app);

var io = require('socket.io')(server);

var redis = require('redis');
var ioredis = require('socket.io-redis');

io.adapter(ioredis({host: 'localhost', port: 6379}));

/* Redis pub/sub */
/* Listen to the channels where laravel will publish its messages */
var sub = redis.createClient();

//Listening in the error channle
sub.on('error', function(err){
	console.log('ERROR ' + err);
});

//Listening in the subscribe channel
sub.on('subscribe', function(channel, count){
	console.log('SUBSCRIBE', channel, count);
});

sub.on('message', function(channel, payload){
	console.log('INCOMING MESSAGE', channel, payload);

	payload = JSON.parse(payload);

	//Send data to the specific room 
	io.sockets.in(channel).emit(payload.event, payload.data)
});

io.sockets.on('connection', function(socket){
	console.log("NEW CLIENT CONNECTED");

	socket.on('subscribe-to-channel', function(data){
		console.log('SUBSCRIBE TO CHANNEL', data);

		/* Join private channel */
		sub.subscribe(data.channel);

		/* Join private room */
		socket.join(data.channel);
	});

	socket.on('disconnecting', function(){
		console.log('DISCONNECTING');
	});

});

server.listen(SERVER_PORT, function(){
	console.log('Socket server is running');
});