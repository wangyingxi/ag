<?php

class Angel_Model_Photo extends Angel_Model_AbstractModel {

    protected $_document_class = '\Documents\Photo';
    protected $_document_user_class = '\Documents\User';

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
//            echo "photo:" . $photo . "; dest:" . $destination;exit;
            if (copy($photo, $destination)) {
                if ($imageService->resizeImage($destination)) {
                    if ($imageService->generateThumbnail($destination, $this->_bootstrap_options['size']['photo'])) {
                        $newPhoto = new $this->_document_class();
                        $newPhoto->name = $filename;
                        $newPhoto->type = $extension;
//                        $newPhoto->owner = $owner;

                        $this->_dm->persist($newPhoto);
                        $this->_dm->flush();

                        $result = true;
                    }
                }
            }
//            }
        }

        return $result;
    }

    /**
     * 获取photo的位置 (＊这个方法还需要根据environment不同做修改)
     * @param string $imagename
     * @return string 
     */
    public function getPhotoPath($photoname) {
        return APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public' . $this->_bootstrap_options['image']['photo_path'] . DIRECTORY_SEPARATOR . $photoname;
    }

}
