<?php
/**
 * Created by PhpStorm.
 * User: uet
 * Date: 20/03/2018
 * Time: 15:24
 */

namespace uet\Image {

    use Exception;

    class Resizer
    {
        /**
         * @var resource $image
         */
        protected $image;

        /**
         * @var int $type
         */
        protected $type;

        /**
         * Resizer constructor.
         * @param null|string $file
         */
        public function __construct($file = NULL)
        {
            $imageInfo = NULL;

            try {

                $imageInfo = getImageSize($file);
            } catch (Exception $exception) {

                $message = $exception->getMessage();
                die($message);
            } finally {

                switch ($imageInfo[2]) {
                    case IMAGETYPE_JPEG:
                        $this->loadJpeg($file);
                        break;
                    case IMAGETYPE_GIF:
                        $this->loadGif($file);
                        break;
                    case IMAGETYPE_PNG:
                        $this->loadPNG($file);
                        break;
                }

                $this->setType($imageInfo[2]);
            }
        }

        /**
         * @param string $file
         */
        public function loadJpeg($file)
        {
            $this->image = imageCreateFromJpeg($file);
        }

        /**
         * @param string $file
         */
        public function loadGif($file)
        {
            $this->image = imageCreateFromGif($file);
        }

        /**
         * @param string $file
         */
        public function loadPNG($file)
        {
            $this->image = imageCreateFromPNG($file);
        }

        /**
         * @return resource
         */
        public function getImage()
        {
            return $this->image;
        }

        /**
         * @param resource $image
         */
        public function setImage($image)
        {
            $this->image = $image;
        }

        /**
         * @return int
         */
        public function getType()
        {
            return $this->type;
        }

        /**
         * @param int $type
         */
        public function setType($type)
        {
            $this->type = $type;
        }

        /**
         * Destroy current image
         */
        public function destroy()
        {
            imageDestroy($this->image);
        }

        /**
         * @param int $ratio
         */
        public function scale($ratio)
        {
            $width = round($this->width() * $ratio / 100);
            $height = round($this->height() * $ratio / 100);

            $this->resize($width, $height);

        }

        /**
         * @return int
         */
        public function width()
        {
            return imageSx($this->image);
        }

        /**
         * @return int
         */
        public function height()
        {
            return imageSy($this->image);
        }

        /**
         * @param int $width
         * @param int $height
         */
        public function resize($width, $height)
        {
            $newImage = imageCreateTrueColor($width, $height);
            imageCopyResampled(
                $newImage, $this->image,
                0, 0,
                0, 0,
                $width, $height,
                $this->width(), $this->height()
            );
            $this->setImage($newImage);
        }

        /**
         * @param int $width
         */
        public function resizeToWidth($width)
        {
            $ratio = $width / $this->width();
            $height = round($this->height() * $ratio);

            $this->resize($width, $height);
        }

        /**
         * @param int $height
         */
        public function resizeToHeight($height)
        {
            $ratio = $height / $this->height();
            $width = round($this->width() * $ratio);

            $this->resize($width, $height);
        }

        /**
         * Fully fill image to be square.
         *
         * @param null $color
         * @param bool $alpha
         */
        public function square($color, $alpha = FALSE)
        {
            $maxDimension = max($this->width(), $this->height());
            $newImage = imageCreateTrueColor($maxDimension, $maxDimension);

            imageFill($newImage, 0, 0, $color);
            $offsetX = ($maxDimension - $this->width()) / 2;
            $offsetY = ($maxDimension - $this->height()) / 2;

            imageCopyResampled(
                $newImage, $this->image,
                0, 0,
                $offsetX, $offsetY,
                $this->width(), $this->height(),
                $this->width(), $this->height()
            );
        }

        /**
         * Output to browser
         *
         * @param null $filename
         * @param int $quality
         */
        public function output($filename = NULL, $quality = 90)
        {
            ob_start();

            $mimeType = image_type_to_mime_type($this->type);
            $extension = image_type_to_extension($this->type, TRUE);

            if ($filename === NULL) {
                $rand = mt_rand(1000, 9999);
                $filename = md5($rand);
                $filename = substr($filename, 16);
            }

            switch ($this->type) {

                case IMAGETYPE_PNG:
                    imagePNG($this->image, NULL, $quality);
                    break;

                case IMAGETYPE_GIF:
                    imageGif($this->image);
                    break;

                case IMAGETYPE_JPEG:
                default:
                    imageJpeg($this->image, NULL, $quality);
                    break;
            }

            header("Content-Type: {$mimeType}");
            header("Content-Disposition: inline; filename={$filename}{$extension}");

            $length = ob_get_length();
            header("Content-Length: {$length}");

            ob_end_flush();
        }

        /**
         * Save to file
         *
         * @param string $filename
         */
        public function save($filename)
        {
            switch ($this->type) {

                case IMAGETYPE_PNG:
                    imagePNG($this->image, $filename, $quality);
                    break;

                case IMAGETYPE_GIF:
                    imageGif($this->image, $filename);
                    break;

                case IMAGETYPE_JPEG:
                default:
                    imageJpeg($this->image, $filename, $quality);
                    break;
            }
        }
    }
}