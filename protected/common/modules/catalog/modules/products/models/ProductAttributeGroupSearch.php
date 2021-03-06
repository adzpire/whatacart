<?php
/**
 * @copyright Copyright (C) 2016 Usha Singhai Neo Informatique Pvt. Ltd
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
namespace products\models;

use usni\library\components\TranslatedActiveDataProvider;
use yii\base\Model;
use products\models\ProductAttributeGroup;
use usni\UsniAdaptor;
use usni\library\utils\AdminUtil;

/**
 * ProductAttributeGroupSearch class file
 * This is the search class for model ProductAttributeGroup.
 * 
 * @package products\models
 */
class ProductAttributeGroupSearch extends ProductAttributeGroup
{
    /**
     * @inheritdoc
     */
	public function rules()
	{
		return [
                    [['name', 'sort_order'], 'safe'],
               ];
	}

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return Model::scenarios();
    }
    
    /**
     * Make [[$translationAttributes]] readable
     * @inheritdoc
     */
    public function __get($name)
    {
        if (in_array($name, $this->translationAttributes))
        {
            $model = parent::getTranslation();
            return $model->$name;
        }
        return parent::__get($name);
    }
    
    /**
     * Search based on get params.
     *
     * @return yii\data\ActiveDataProvider
     */
    public function search()
    {
        $query          = ProductAttributeGroup::find();
        $tableName      = ProductAttributeGroup::tableName();  
        $query->innerJoinWith('translations');
        $dataProvider   = new TranslatedActiveDataProvider([
            'query' => $query,
        ]);

        // Validate data
        if (!$this->validate())
        {
            return $dataProvider;
        }
        $query->andFilterWhere(['language' => UsniAdaptor::app()->languageManager->getContentLanguage()]);
        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['like', 'sort_order', $this->sort_order]);
        $user     = UsniAdaptor::app()->user->getUserModel();
        if(!AdminUtil::doesUserHaveOthersPermissionsOnModel(ProductAttributeGroup::className(), $user))
        {
            $query->andFilterWhere([$tableName . '.created_by' => $user->id]);
        }
        return $dataProvider;
    }
}