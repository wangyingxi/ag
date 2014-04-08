<?php

class Angel_Model_Photo extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\Photo';
    protected $_document_user_class = '\Documents\User';

    public function removePhoto($id, $owner) {
        $result = false;
        if (is_object($owner) && ($owner instanceof $this->_document_user_class)) {
            $photo = $this->getPhotoById($id);
            if ($owner->id == $photo->owner->id || $owner->user_type = 'admin') {
                // when owner is self or admin
                try {
                    // remove document
                    $result = $this->_dm->createQueryBuilder($this->_document_class)
                            ->remove()
                            ->field('id')
                            ->equals($id)
                            ->getQuery()
                            ->execute();

                    // delete files
                    $imageService = $this->_container->get('image');
                    $filename = $photo->name . $photo->type;
                    $target = $this->getPhotoPath($filename);
                    $imageService->deleteThumbnail($target, $this->_bootstrap_options['size']['photo']);
                } catch (Exception $e) {
                    $result = false;
                }
            } else {
                
            }
        }
        return $result;
    }

    public function addPhoto($photo, $owner) {
        $result = false;
        if (is_object($owner) && ($owner instanceof $this->_document_user_class)) {
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
                        $newPhoto->owner = $owner;

                        $this->_dm->persist($newPhoto);
                        $this->_dm->flush();

                        $result = true;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 根据id获取photo document
     * 
     * @param string $id
     * @return mix - when the photo found, return the photo document
     */
    public function getPhotoById($id) {
        $result = false;
        $photo = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('id')->equals($id)
                ->getQuery()
                ->getSingleResult();

        if (!empty($photo)) {
            $result = $photo;
        }

        return $result;
    }
    
    /**
     * 根据name获取photo document
     * 
     * @param string $name
     * @return mix - when the photo found, return the photo document
     */
    public function getPhotoByName($name) {
        $result = false;
        $photo = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('name')->equals($name)
                ->getQuery()
                ->getSingleResult();

        if (!empty($photo)) {
            $result = $photo;
        }

        return $result;
    }
    public function getPhotoByUser($user_id, $return_as_paginator = true) {
        $query = $this->_dm->createQueryBuilder($this->_document_class)
                ->field('owner.$id')->equals(new MongoId($user_id))
                ->field('status')->equals('online')
                ->sort('created_at', -1);
        $result = null;
        if ($return_as_paginator) {
            $result = $this->paginator($query);
        } else {
            $result = $query->getQuery()->execute();
        }
        return $result;
    }

    public function getPhoto($return_as_paginator = true) {
        $query = $this->_dm->createQueryBuilder($this->_document_class)
                ->sort('created_at', -1);
        $result = null;
        if ($return_as_paginator) {
            $result = $this->paginator($query);
        } else {
            $result = $query->getQuery()->execute();
        }
        return $result;
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
    public function getPhotoByVersion($image, $version) {
        $imageService = $this->_container->get('image');
        return $imageService->generateImageFilename($this->getImagePath($image), $version, false);
    }

    /**
     * 获取photo的位置 (＊这个方法还需要根据environment不同做修改)
     * @param string $photoname
     * @return string 
     */
    public function getImagePath($photoname) {
        $dir = $this->_bootstrap_options['image']['photo_path'];
        return $dir . DIRECTORY_SEPARATOR . $photoname;
    }

}
