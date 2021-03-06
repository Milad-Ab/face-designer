<?php
/**
 * Project: Negarang FaceDesigner
 * This file is part of Negarang.
 *
 * (c) Milad Abdollahnia
 * http://milad-ab.ir
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Negarang\FaceDesigner;

use Negarang\FaceDesigner\Items\Body;
use Negarang\FaceDesigner\Items\Head;
use Negarang\FaceDesigner\Items\Background\Filled as FilledBackground;
use Negarang\FaceDesigner\Items\Background\Pattern as PatternBackground;
use Negarang\FaceDesigner\Items\Background\Theme as ThemeBackground;
use Negarang\FaceDesigner\Items\Effect\Weather as WeatherEffect;
use Negarang\FaceDesigner\Items\Wearable\Hat;
use Negarang\GraphicsUtils\Designer;

/**
 * Class FaceDesigner
 * @package Negarang\FaceDesigner
 * @author Milad Abdollahnia (milad_abdollahnia@yahoo.com)
 */
class FaceDesigner extends Designer {

    const IMAGE_WIDTH = 180;
    const IMAGE_HEIGHT = 187;
    const IMAGE_DEFAULT_FORMAT = "image/gif";
    const IMAGE_DEFAULT_EXTENSION ="gif";

    /**
     * @var Head
     */
    private $head;

    /**
     * @var Body
     */
    private $body;

    /**
     * @var FilledBackground
     */
    private $bgFilled;

    /**
     * @var PatternBackground
     */
    private $bgPattern;

    /**
     * @var ThemeBackground
     */
    private $bgTheme;

    /**
     * @var WeatherEffect
     */
    private $effWeather;

    /**
     * FaceDesigner constructor.
     * @param string $graphicsDirectory
     */
    public function __construct($graphicsDirectory) {
        Item::setGraphicsDirectory($graphicsDirectory);
    }

    /**
     * @param int $shapeId
     * @param int $skinColorId
     * @return Head
     */
    public function drawHead($shapeId, $skinColorId) {
        $this->head = new Head($shapeId, $skinColorId);
        return $this->head;
    }

    /**
     * @param int $id
     * @param int $colorId
     * @return Body
     */
    public function drawBody($id, $colorId) {
        $this->body = new Body($id, $colorId);
        return $this->body;
    }

    /**
     * @param int $filledId
     * @param int $patternId
     * @param int $patternColorId
     * @param int $themeId
     * @return $this
     */
    public function setBackground($filledId, $patternId, $patternColorId, $themeId) {
        $this->bgFilled = new FilledBackground($filledId);
        $this->bgPattern = new PatternBackground($patternId, $patternColorId);
        $this->bgTheme = new ThemeBackground($themeId);
        return $this;
    }

    /**
     * @param int $weatherId
     * @return $this
     */
    public function setEffects($weatherId) {
        $this->effWeather = new WeatherEffect($weatherId);
        return $this;
    }

    /**
     * Check Items of Head and Body.
     */
    private function checkItemsRelation() {
        if ($this->body->getHat()->isVisible()) {
            // Set size of the hair.
            switch (true) {
                case $this->head->getHair()->isShortHair():
                    $this->body->getHat()->setSizeMode(Hat::SIZE_MODE_SMALL);
                    break;
                case $this->head->getHair()->isMediumHair():
                    $this->body->getHat()->setSizeMode(Hat::SIZE_MODE_MEDIUM);
                    break;
                case $this->head->getHair()->isLongHair():
                    $this->body->getHat()->setSizeMode(Hat::SIZE_MODE_LARGE);
                    break;
                case $this->head->getHair()->isVeryLongHair():
                    $this->body->getHat()->setSizeMode(Hat::SIZE_MODE_MEDIUM);
                    // We require an special hair style
                    // to keep the hair under the hat regularly.
                    if (!$this->body->getHat()->isOpenTop()) {
                        // The bald style. Its the idea.
                        $this->head->getHair()->baldStyle();
                    }
                    break;
            }
        }
        // If the hat is a swim cap.
        // Hair and Ears must be hidden.
        if ($this->body->getHat()->isSwimCap()) {
            $this->head->getHair()->hide();
            $this->head->getLeftEar()->hide();
            $this->head->getRightEar()->hide();
        }
    }

    /**
     * Build The Image
     */
    private function build() {
        // Check some items.
        $this->checkItemsRelation();
        // Create the base image.
        $this->image = imagecreatetruecolor(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
        // Put items on the base image.
        $this->dropLayer(
            $this->bgFilled,
            $this->bgPattern,
            $this->bgTheme,
            $this->head->getHair()->getHairBack(),
            $this->body,
            $this->body->getShirt(),
            $this->body->getJacket(),
            $this->body->getScarf(),
            $this->head->getLeftEar(),
            $this->head->getRightEar(),
            $this->head,
            $this->head->getBeard(),
            $this->head->getMouth(),
            $this->head->getMustache(),
            $this->head->getNose(),
            $this->head->getLeftEyeShadow(),
            $this->head->getRightEyeShadow(),
            $this->head->getLeftEye(),
            $this->head->getRightEye(),
            $this->head->getLeftEyebrow(),
            $this->head->getRightEyebrow(),
            $this->body->getGlasses(),
            $this->head->getHair(),
            $this->body->getHat(),
            $this->effWeather
        );
    }

    /**
     * @param $size
     */
    private function crop($size) {
        if(($size != 110) && ($size != 70)
            && ($size != 40)) {
            return;
        }
        $fixSize = 180;
        $croppedImage = imagecreatetruecolor($fixSize, $fixSize);
        imagecopy($croppedImage, $this->image, 0,
            self::IMAGE_HEIGHT - $fixSize, 0, 0,
            self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
        $this->image = imagecreatetruecolor($size, $size);
        imagecopyresampled($this->image, $croppedImage, 0, 0, 0, 0,
            $size, $size, $fixSize, $fixSize);
        imagedestroy($croppedImage);
    }

    /**
     * @return string
     */
    public function getImageFormat() {
        return self::IMAGE_DEFAULT_FORMAT;
    }

    /**
     * @param int $format
     * @param int $size
     * @return string
     */
    public function preview($format, $size=0) {
        $this->build();
        // Crop the image (optional).
        $this->crop($size);
        $imageType = $imageContent = '';
        switch ($format) {
            case self::FORMAT_GIF:
                $imageType = "gif";
                ob_start();
                imagegif($this->image);
                $imageContent = ob_get_contents();
                ob_end_clean();
        }
        $this->erase();
        return "data:image/" . $imageType . ";base64,"
            . base64_encode($imageContent);
    }

    /**
     * @param string $directory
     * @param string $name
     * @param int $quality
     * @return null
     */
    public function saveAs($directory, $name, $quality) {
        // TODO: Implement saveAs() method.
        return null;
    }
}