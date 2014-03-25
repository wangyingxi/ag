<?php

class Angel_Model_Photo extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\Photo';

    public function addPhoto($photo, $owner) {
        $result = false;
//        if (is_object($owner) && ($owner instanceof $this->_document_user_class)) {
        $imageService = $this->_container->get('image');
        if (!$imageService->isAcceptedImage($photo)) {
            throw new Angel_Exception_Common(Angel_Exception_Common::IMAGE_NOT_ACCEPTED);
        } else {
            $extension = $imageService->getImageTypeExtension($photo);
            $utilService = $this->_container->get('util');
            $filename = $utilService->generateFilename($extension);
            $destination = $this->getPhotoPath($filename);
            if (copy($photo, $destination)) {
                if ($imageService->generateThumbnail($destination, $this->_bootstrap_options['size']['photo'])) {
                    $newPhoto = new $this->_document_class();
                    $newPhoto->name = basename($filename, $extension);
                    $newPhoto->type = $extension;
//                        $newPhoto->owner = $owner;

                    $this->_dm->persist($newPhoto);
                    $this->_dm->flush();

                    $result = true;
                }
            }
//            }
        }
        return $result;
    }

    public function getPhotoByUser($user_id) {
        $photo = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('owner.$id')->equals(new MongoId($user_id))
                ->field('status')->equals('online')
                ->getQuery()
                ->execute();
        return $photo;
    }

    public function getPhoto() {
        $photo = $this->_dm->createQueryBuilder($this->_document_class)
                ->getQuery()
                ->execute();
        return $photo;
    }

    /**
     * 获取photo的位置 (＊这个方法还需要根据environment不同做修改)
     * @param string $photoname
     * @return string 
     */
    public function getPhotoPath($photoname) {
        return APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public' . $this->_bootstrap_options['image']['photo_path'] . DIRECTORY_SEPARATOR . $photoname;
    }
    /**
     * 获取photo的url (＊这个方法还需要根据environment不同做修改)
     * @param string $photoname
     * @return string 
     */
    public function getPhotoUrl($photoname) {
        return $this->_bootstrap_options['image']['photo_path'] . DIRECTORY_SEPARATOR . $photoname;
    }
    
    
    /**
     * 获取某size图片名字
     * @param string $name - image type, could be company logo or company image
     * @param string $image － 基本文件名称
     * @param int $version - 文件size
     * @return string 
     */
    public function getPhotoByVersion($image, $version){
        $imageService = $this->_container->get('image');
        return $imageService->generateImageFilename($this->getImagePath($image), $version, false);
    }
    
    /**
     * 获取photo的位置 (＊这个方法还需要根据environment不同做修改)
     * @param string $photoname
     * @return string 
     */
    public function getImagePath($photoname){
        $dir = $this->_bootstrap_options['image']['photo_path'];
        return $dir.DIRECTORY_SEPARATOR.$photoname;
    }
}
