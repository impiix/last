/**
 * Created by px on 12/4/15.
 */
var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);

app.get('/', function(req, res){
   res.send('test');
});

io.on('connection', function(socket){
    console.log("user connected");
});

http.listen(3000, function(){
   console.log("listening...");
});
