<?php
/**
 * @copyright Copyright (C) 2016 Usha Singhai Neo Informatique Pvt. Ltd
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
namespace common\modules\catalog;

use usni\library\components\UiSecuredModule;
use usni\UsniAdaptor;
use common\modules\catalog\utils\CatalogPermissionUtil;
/**
 * Provides functionality related to entities specific to the catalog.
 *
 * @package common\modules\catalog
 */
class Module extends UiSecuredModule
{
    /**
     * Overrides to register translations.
     */
    public function init()
    {
        parent::init();
        $this->registerTranslations();
    }

    /**
     * Registers translations.
     */
    public function registerTranslations()
    {
        UsniAdaptor::app()->i18n->translations['catalog*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath' => '@approot/messages'
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function getPermissionUtil()
    {
        return CatalogPermissionUtil::className();
    }
}
?>