<?php

namespace app\commands;

use consik\yii2websocket\WebSocketServer;
use yii\console\Controller;
use consik\yii2websocket\events\WSClientMessageEvent;
use app\models\Message;

class ServerController extends Controller
{
    public $members = [];

    public function actionStart($port = null)
    {
        $server = new WebSocketServer();
        $server->port = 8000; //This port must be busy by WebServer and we handle an error

        $server->on(WebSocketServer::EVENT_WEBSOCKET_OPEN_ERROR, function($e) use($server) {
            echo "Error opening port " . $server->port . "\n";
            $server->port += 1; //Try next port to open
            $server->start();
        });

        $server->on(WebSocketServer::EVENT_WEBSOCKET_OPEN, function($e) use($server) {
            echo "Server started at port " . $server->port;
        });

        $server->on(WebSocketServer::EVENT_CLIENT_MESSAGE, function (WSClientMessageEvent $e) {
            $data = json_decode($e->message, true);
            if(empty($data['_id'])) {
                $model = new Message();
                $model->load($data, '');
                $model->save(false);

                $receiver_id = $data['receiver_id'];
                if(!empty($this->members[$receiver_id])) {
                    $this->members[$receiver_id]->send(json_encode($model->toArray()));
                }
            } else {
                if(empty($this->members[$data['_id']])) {
                    $this->members[$data['_id']] = $e->client;
                }
            }
        });        

        $server->start();
    }
}