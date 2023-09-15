<?php

namespace app\controllers;

use app\models\User;
use app\models\Message;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;
use app\middleware\AuthMiddleware;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'tokenValidation' => [
                'class' => AuthMiddleware::class,
                'only' => ['list'],
            ]
        ];
    }

    public function beforeAction($action)
    {
        Yii::$app->controller->enableCsrfValidation = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        return parent::beforeAction($action);
    }

    /**
     * Register a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionRegister()
    {
        $model = new User();

        $formData = json_decode($this->request->getRawBody(), true);

        if ($this->request->isPost) {
            if ($model->load($formData, '') && $model->validate()) {
                $model->password = Yii::$app->security->generatePasswordHash($model->password);

                if($model->save(false)) {
                    return [
                        'status' => true,
                        'message' => 'User registered successfully'
                    ];
                }
            }
        }

        return [
            'status' => false,
            'message' => 'Something went wrong',
            'errors' => $model->getErrors()
        ];
    }

    /**
     * Login User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionLogin()
    {
        $formData = json_decode($this->request->getRawBody(), true);

        $model = User::findByUsername($formData['username']);

        if ($this->request->isPost) {
            if ($model && $model->validatePassword($formData['password'])) {
                $model->access_token = Yii::$app->security->generateRandomString();

                if($model->save(false)) {
                    return [
                        'status' => true,
                        'message' => 'User login successfully',
                        'token' => $model->access_token,
                        'user' => [
                            'id' => $model->id,
                            'name' => $model->name,
                            'email' => $model->email,
                            'username' => $model->username,
                        ]
                    ];
                }
            }
            
            return [
                'status' => false,
                'message' => 'Username or Password incorrect'
            ];
        }

        return [
            'status' => false,
            'message' => 'Something went wrong'
        ];
    }

    /**
     * Logout User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionLogout()
    {
        $formData = json_decode($this->request->getRawBody(), true);

        $model = User::find()->where(['access_token' => $formData['token']])->one();

        if ($this->request->isPost && $model) {
            $model->access_token = null;

            if($model->save(false)) {
                return [
                    'status' => true,
                    'message' => 'User logout successfully'
                ];
            }
        }

        return [
            'status' => false,
            'message' => 'Something went wrong'
        ];
    }

    /**
     * Lists all User models.
     *
     * @return string
     */
    public function actionList()
    {
        $userId = Yii::$app->user->id;
        $model = User::find()
            ->select([
                'id',
                'name',
                'username',
                'email'
            ])
            ->where(['not', ['id' => $userId]])
            ->asArray()
            ->all();

        foreach($model as $key => $user) {
            $model[$key]['messages'] = Message::find()
                ->where(['and', ['sender_id' => $userId], ['receiver_id' => $user['id']]])
                ->orWhere(['and', ['sender_id' => $user['id']], ['receiver_id' => $userId]])
                ->all();
        }
        return [
            'status' => true,
            'data' => $model
        ];
    }

    /**
     * Displays a single User model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
