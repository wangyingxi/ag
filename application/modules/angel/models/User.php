<?php

class Angel_Model_User extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\User';

    // 关于用户的一些文件
    // 身份证的正面
    const FILETYPE_IDENTITY_FRONT = 'fif';
    // 身份证的背面
    const FILETYPE_IDENTITY_BACK = 'fib';

    /**
     * 通过emai和password登陆
     * @param String $email
     * @param String $password
     * @return boolean 
     */
    public function auth($email, $password) {
        $result = array('valid' => false, 'msg' => '');

        $auth = Zend_Auth::getInstance();
        $adapter = new Angel_Auth_Adapter_Mongo($this->_dm, $this->_document_class, $email, $password);

        $auth = $auth->authenticate($adapter);
        if (!$auth->isValid()) {
            $result['msg'] = $auth->getMessages();
        } else {
            $result['valid'] = true;
            $result['msg'] = $auth->getIdentity();
        }

        return $result;
    }

    public function addManageUser($email, $password, $salt, $checkemail = true) {
        $usertype = "admin";
        list($username) = split("@", $email, 1);
        return $this->registerUser($email, $password, $username, $usertype, $salt, $checkemail);
    }

    public function addUser($email, $password, $username, $salt, $checkemail = true) {
        $usertype = "user";
        return $this->registerUser($email, $password, $username, $usertype, $salt, $checkemail);
    }

    protected function registerUser($email, $password, $username, $usertype, $salt, $checkmail) {
        $result = false;
        if (empty($email)) {
            throw new Angel_Exception_User(Angel_Exception_User::EMAIL_EMPTY);
        } else {
            $validation = new Zend_Validate_EmailAddress();
            if (!$validation->isValid($email)) {
                throw new Angel_Exception_User(Angel_Exception_User::EMAIL_INVALID);
            } else {
                if ($this->isEmailExist($email)) {
                    throw new Angel_Exception_User(Angel_Exception_User::EMAIL_NOT_UNIQUE);
                }
//                if ($this->isUsernameExist($username)) {
//                    throw new Angel_Exception_User(Angel_Exception_User::USERNAME_NOT_UNIQUE);
//                }
            }
        }

        $user = new $this->_document_class();

        $user->email = $email;
        $user->username = $username;
        $user->salt = $salt;
        $user->user_type = $usertype;
        $user->password = $password;
        $user->active_bln = true;
        $user->email_validated_bln = !$checkemail;
        $user->validated_bln = false;

        try {
            $this->_dm->persist($user);
            $this->_dm->flush();

            $result = $user->id;
        } catch (Exception $e) {
            $this->_logger->info(__CLASS__, __FUNCTION__, $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new Angel_Exception_User(Angel_Exception_User::ADD_USER_FAIL);
        }

        // send email to the new user to notice him to active his account
        if ($result && $checkemail) {
            $this->sendAccountValidationEmail($user);
        }
        // send email to the new user to welcome them
        if ($result && !$checkemail) {
            
        }
        return $result;
    }

    /**
     * 验证某email地址是否已经存在了
     * 
     * @param string $email － 需要被检测的email地址
     * @return boolean 
     */
    public function isEmailExist($email, $return_user_model = false) {
        $result = false;
        if ($email) {
            $email = strtolower($email);
        }
        $user = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('email')->equals($email)
                ->getQuery()
                ->getSingleResult();

        if (!empty($user)) {
            if ($return_user_model) {
                $result = $user;
            } else {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * 根据id获取user document
     * 
     * @param string $id
     * @return mix - when the user found, return the user document
     */
    public function getUserById($id) {
        $result = false;
        $user = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('id')->equals($id)
                ->getQuery()
                ->getSingleResult();

        if (!empty($user)) {
            $result = $user;
        }

        return $result;
    }

    /**
     * 发送新注册帐号的验证邮件
     * @param \Documents\User $user 
     */
    public function sendAccountValidationEmail($user) {
        $token = md5(uniqid(time(), true));
        $tokenModel = new Angel_Model_Token($this->_bootstrap);

        $token = $tokenModel->generateToken($token, $this->_bootstrap_options['token']['expiry']['email_validation'], Zend_Json::encode(array('user_id' => $user->id)));

        if ($token) {
            $router = Zend_Controller_Front::getInstance()->getRouter();
            $params = array();
            $params['url'] = $this->_bootstrap_options['site']['domainurl'] . $router->assemble(array('token' => $token->token), 'email-validation');
            $params['username'] = $user->username;

            Angel_Model_Email::sendEmail($this->_container->get('email'), Angel_Model_Email::EMAIL_NEW_USER_EMAIL_VALIDATION, $user->email, $params);
        }
    }

    /**
     * 纪录用户最后一次登陆的信息
     * @param string $user_id
     * @param string $ip 
     */
    public function updateLoginInfo($user_id, $ip) {
        $result = false;
        $data = array();

        $data['ip'] = $ip;
        $data['last_login'] = new DateTime();

        $user = $this->updateUser($user_id, $data);
        if ($user) {
            $result = true;
        }

        return $result;
    }

    public function updateAddress($user, $contact, $street, $phone, $state, $city, $country, $zip) {
        $result = false;

        if (!(is_object($user) && ($user instanceof $this->_document_class))) {
            $user = $this->validateUserId($user);
        }
        try {
            $user->addAddressDoc($contact, $street, $phone, $state, $city, $country, $zip);
            $this->_dm->persist($user);
            $this->_dm->flush();
            $result = true;
        } catch (Exception $e) {
            $this->_logger->info(__CLASS__, __FUNCTION__, $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new Angel_Exception_User(Angel_Exception_User::USER_UPDATE_FAIL);
        }
        return $result;
    }

    /**
     * 修改用户信息，返回的是用户对象
     * @param mix $user - string 为用户id，model为用户对象
     * @param array $data
     * @return mix - when success, returns the user model, when fail, return false
     * @throws Angel_Exception_User 
     */
    public function updateUser($user, $data) {
        $result = false;

        if (!(is_object($user) && ($user instanceof $this->_document_class))) {
            $user = $this->validateUserId($user);
        }

        try {
            foreach ($data as $key => $value) {
                $user->$key = $value;
            }

            $this->_dm->persist($user);
            $this->_dm->flush();

            $result = $user;
        } catch (Exception $e) {
            $this->_logger->info(__CLASS__, __FUNCTION__, $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new Angel_Exception_User(Angel_Exception_User::USER_UPDATE_FAIL);
        }

        return $result;
    }

    /**
     * 验证用户ID是否有效，这个方法算很多方法的 preValidation
     * @param string $user_id
     * @return user model
     * @throws Angel_Exception_User 
     */
    public function validateUserId($user_id) {
        $user = $this->getUserById($user_id);
        if (!$user) {
            throw new Angel_Exception_User(Angel_Exception_User::USER_NOT_FOUND);
        }

        return $user;
    }

    /**
     * 生成remember me中的cookie值
     * @param string $user_id
     * @return string
     */
    public function getRememberMeValue($user_id, $ip) {
        $user = $this->validateUserId($user_id);

        $token = md5(uniqid(time(), true));
        $tokenModel = new Angel_Model_Token($this->_bootstrap);

        $token = $tokenModel->generateToken($token, $this->_bootstrap_options['token']['expiry']['remember_me'], Zend_Json::encode(array('user_id' => $user_id, 'ip' => $ip)));

        return $token->token;
    }

    /**
     * 根据remember me提供的cookie value，来激活用户的session
     * @param type $id
     * @param type $salt 
     */
    public function isRemembered($token, $ip) {
        $result = false;

        $tokenModel = new Angel_Model_Token($this->_bootstrap);
        $tokenDocument = $tokenModel->getTokenByToken($token);

        if ($tokenDocument) {
            $params = $tokenDocument->params;

            if ($params['ip'] == $ip) {
                Zend_Auth::getInstance()->getStorage()->write($params['user_id']);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * 激活帐号
     * @param string $token
     * @return 当激活成功时，返回的是用户document
     * @throws Angel_Exception_User 
     */
    public function activateAccount($token) {
        $result = 0;
        $user = null;

        $tokenModel = new Angel_Model_Token($this->_bootstrap);
        $tokenDocument = $tokenModel->getTokenByToken($token);

        // check whether the token is valid
        if ($tokenDocument && $tokenDocument->isActive()) {

            // the tokendocument should provides necessary parameter
            $params = $tokenDocument->getParams();
            if (isset($params['user_id'])) {

                // the user should be valid
                $user = $this->getUserById($params['user_id']);
                if ($user) {

                    // check whether the user's email has been already validated
                    if (!$user->isEmailValidated()) {

                        // check whether the token is expired, if it is expired, need to let the user ask for sending another validation email
                        if ($tokenDocument->isExpired()) {
                            $result = 2;

                            Zend_Auth::getInstance()->getStorage()->write($user->id);
                        } else {
                            $user->email_validated_bln = true;
                            $this->_dm->persist($user);

                            $tokenDocument->active_bln = false;
                            $this->_dm->persist($tokenDocument);

                            $this->_dm->flush();
                            $result = true;
                        }
                    } else {
                        // the user has already been activated
                        $result = 1;
                    }
                }
            }
        }

        if ($result === true) {
            return $user;
        }
        if ($result == 0) {
            throw new Angel_Exception_User(Angel_Exception_User::EMAIL_VALIDATION_TOKEN_INVALID);
        } else if ($result == 1) {
            throw new Angel_Exception_User(Angel_Exception_User::EMAIL_VALIDATION_VALIDATED_USER);
        } else if ($result == 2) {
            throw new Angel_Exception_User(Angel_Exception_User::EMAIL_VALIDATION_TOKEN_EXPIRED);
        } else {
            $this->_logger->info(__CLASS__, __FUNCTION__, $token . " is strange");
        }
    }

    /**
     * 重新设置密码
     * @param string $user_id - 用户ID
     * @param string $old － 旧密码
     * @param string $new － 新密码
     */
    public function resetPassword($user_id, $old, $new) {
        $result = 0;
        $user = $this->validateUserId($user_id);

        if ($user->password == crypt($old, $user->salt)) {
            $user->password = $new;

            $this->_dm->persist($user);
            $this->_dm->flush();

            $result = 1;
        } else {
            throw new Angel_Exception_User(Angel_Exception_User::INCORRECT_ORIGINAL_PASSWORD);
        }

        return $result;
    }

    /**
     * 当用户忘记了密码
     * @param string $email
     * @return boolean - true 发送给用户系统生成密码， false 没有发送系统生成密码，可能是email地址不正确
     */
    public function forgotPassword($email) {
        $result = false;

        $user = $this->isEmailExist($email, true);
        if (is_object($user)) {
            $password = substr(md5(uniqid(time(), true)), rand(0, 16), 8);
            $user->password = $password;

            $params = array("username" => $user->username, "password" => $password);
            $router = Zend_Controller_Front::getInstance()->getRouter();
            $params['url'] = $this->_bootstrap_options['site']['domainurl'] . $router->assemble(array(), 'login');
            $result = Angel_Model_Email::sendEmail($this->_container->get('email'), Angel_Model_Email::EMAIL_FORGOT_PASSWORD, $user->email, $params);

            if ($result) {
                $this->_dm->persist($user);
                $this->_dm->flush();
            }
        }

        return $result;
    }

    /**
     * 上传了用户头像的图片，从tmp处copy到profile image目录，生成其resized version供将来裁剪使用， 并修改user的profile_image field
     * @param type $user
     * @param type $image
     * @return boolean - when it is false, add profile image fails, when the image type is not correct, throw the exception. when it is ture, means success
     */
    public function addProfileImage($user, $image) {
        $result = false;

        if (is_object($user) && ($user instanceof $this->_document_class)) {
            $imageService = $this->_container->get('image');
            if (!$imageService->isAcceptedImage($image)) {
                throw new Angel_Exception_Common(Angel_Exception_Common::IMAGE_NOT_ACCEPTED);
            } else {
                $extension = $imageService->getImageTypeExtension($image);
                $utilService = $this->_container->get('util');
                $filename = $utilService->generateFilename($extension);
                $destination = $this->getProfileImagePath($filename);
                if (copy($image, $destination)) {
                    if ($imageService->resizeImage($destination)) {
                        $user->profile_image = $filename;

                        $this->_dm->persist($user);
                        $this->_dm->flush();

                        $result = true;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 获取profile image的位置 (＊这个方法还需要根据environment不同做修改)
     * @param string $imagename
     * @return string 
     */
    public function getProfileImagePath($imagename) {
        return APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public' . $this->_bootstrap_options['image']['profile_path'] . DIRECTORY_SEPARATOR . $imagename;
    }

    /**
     * 裁剪用户上传的头像
     * @param string $orig_image - 被裁图片的路径
     * @param array $coord - array(x, y, w, h) 
     * @return mixed 
     */
    public function generateProfileThumbnail($orig_image, $coord) {
        $imageService = $this->_container->get('image');
        return $imageService->generateThumbnail($orig_image, $this->_bootstrap_options['size']['profile'], $coord);
    }

    /**
     * 获取头像图片名字
     * @param string $image － 基本文件名称
     * @param int $version - 文件size
     * @return string 
     */
    public function getProfileImage($image, $version) {
        $imageService = $this->_container->get('image');
        return $imageService->generateImageFilename($this->getProfileImagePath($image), $version, false);
    }

    
    public function setAttribute($user, $key, $value) {
        $validated_key = array('operating-oid', 'new-msg');
        if (!in_array($key, $validated_key)) {
            return false;
        }
        $attribute = $user->attribute;
        if (!$attribute) {
            $attribute = array();
        }

        $attribute[$key] = $value;
        $result = $this->updateUser($user, array('attribute' => $attribute));
        return $result;
    }

}
