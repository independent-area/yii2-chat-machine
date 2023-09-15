<?php

namespace app\middleware;

use Yii;
use app\models\User;
use yii\web\ForbiddenHttpException;
use yii\base\ActionFilter;

class AuthMiddleware extends ActionFilter
{
    public $only = [];
    public $except = [];
    
    public function beforeAction($action)
    {
        // Token validation logic goes here
        $bearerToken = Yii::$app->request->headers->get('Authorization');
        $token = explode(' ', $bearerToken)[1];

        if (!$token || !$this->validateToken($token)) {            
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            Yii::$app->response->setStatusCode(401);
            Yii::$app->response->data = ['error' => 'Unauthorized'];
            Yii::$app->end();
        }

        // If token is valid, set the authenticated user
        $user = $this->getUserFromToken($token);
        Yii::$app->user->login($user);

        return true;
    }

    private function validateToken($token)
    {
        $model = User::findIdentityByAccessToken($token);
        return !!$model;
    }

    private function getUserFromToken($token)
    {
        return User::findOne(['access_token' => $token]);
    }
}
