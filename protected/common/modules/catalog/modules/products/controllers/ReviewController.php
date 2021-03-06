<?php
/**
 * @copyright Copyright (C) 2016 Usha Singhai Neo Informatique Pvt. Ltd
 * @license https://www.gnu.org/licenses/gpl.html
 */
namespace products\controllers;

use products\models\ProductReview;
use common\modules\catalog\controllers\BaseController;
use products\utils\ProductUtil;
use usni\UsniAdaptor;
use usni\library\modules\auth\managers\AuthManager;
use products\views\ProductReviewTrashGridView;
use common\modules\catalog\utils\CatalogPermissionUtil;
use usni\library\utils\ArrayUtil;
/**
 * Review controller for products.
 * 
 * @package products\controllers
 */
class ReviewController extends BaseController
{
    /**
     * @inheritdoc
     */
    protected function resolveModelClassName()
    {
        return ProductReview::className();
    }
    
    /**
     * Approve a product review.
     * @param integer $id ID of the model to be approved.
     * @return void
     */
    public function actionApprove($id)
    {
        $loggedInUser   = UsniAdaptor::app()->user->getUserModel();
        $productReview  = $this->loadModel(ProductReview::className(), $id);
        $isAllowed      = CatalogPermissionUtil::doesUserHavePermissionToPerformAction($productReview, $loggedInUser, 'productreview.approve');
        if($isAllowed)
        {
            $productReview->scenario    = 'approve';
            $productReview->status      = ProductReview::STATUS_APPROVED;
            $productReview->save();
        }
    }

    /**
     * Unapprove a product review.
     * @param integer $id ID of the model to be approved.
     * @return void
     */
    public function actionUnapprove($id)
    {
        $loggedInUser   = UsniAdaptor::app()->user->getUserModel();
        $productReview  = $this->loadModel(ProductReview::className(), $id);
        $isAllowed      = CatalogPermissionUtil::doesUserHavePermissionToPerformAction($productReview, $loggedInUser, 'productreview.approve');
        if($isAllowed)
        {
            $productReview              = $this->loadModel(ProductReview::className(), $id);
            $productReview->scenario    = 'unapprove';
            $productReview->status      = ProductReview::STATUS_PENDING;
            $productReview->save();
        }
    }

    /**
     * Mark product review as spam.
     * @param integer $id ID of the model to be approved.
     * @return void
     */
    public function actionSpam($id)
    {
        if(UsniAdaptor::app()->request->isAjax)
        {
            $productReview              = $this->loadModel(ProductReview::className(), $id);
            $productReview->scenario    = 'spam';
            if(CatalogPermissionUtil::doesUserHavePermissionToPerformAction($productReview, UsniAdaptor::app()->user->getUserModel(), 'productreview.spam'))
            {
                $productReview->status      = ProductReview::STATUS_SPAM;
                $productReview->save();
            }
            else
            {
                throw new \yii\web\ForbiddenHttpException(\Yii::t('yii','You are not authorized to perform this action.'));
            }
        }
    }

    /**
     * Remove spam from the product review.
     * @param integer $id ID of the model to be approved.
     * @return void
     */
    public function actionRemoveSpam($id)
    {
        if(UsniAdaptor::app()->request->isAjax)
        {
            $productReview              = $this->loadModel(ProductReview::className(), $id);
            $productReview->scenario    = 'removespam';
            if(CatalogPermissionUtil::doesUserHavePermissionToPerformAction($productReview, UsniAdaptor::app()->user->getUserModel(), 'productreview.spam'))
            {
                $productReview->status      = ProductReview::STATUS_PENDING;
                $productReview->save();
            }
            else
            {
                throw new \yii\web\ForbiddenHttpException(\Yii::t('yii','You are not authorized to perform this action.'));
            }
        }
    }

    /**
     * Delete a product review.
     * @param integer $id ID of the model to be processed.
     * @return void
     */
    public function actionDeleteFromGrid($id)
    {
        if(UsniAdaptor::app()->request->isAjax)
        {
            $user               = UsniAdaptor::app()->user->getUserModel();
            $productReview      = $this->loadModel(ProductReview::className(), $id);
            if(CatalogPermissionUtil::doesUserHavePermissionToPerformAction($productReview, $user, 'productreview.delete'))
            {   
                $productReview->status          = ProductReview::STATUS_DELETED;
                $productReview->scenario        = 'delete';
                $productReview->save();
                $this->redirect(UsniAdaptor::createUrl('catalog/products/review/manage'));
            }
        }
    }

    /**
     * Perform BulkApprove on product review gridview
     * @return void
     */
    public function actionBulkApprove()
    {
        if (UsniAdaptor::app()->request->isAjax && isset($_GET['id']))
        {
            $loggedInUser   = UsniAdaptor::app()->user->getUserModel();
            $productReview  = ProductReview::findOne($_GET['id']);
            $isAllowed      = CatalogPermissionUtil::doesUserHavePermissionToPerformAction($productReview, $loggedInUser, 'productreview.approve');
            if($isAllowed)
            {
                $selectedItemsIds = $_GET['id'];
                ProductUtil::updateStatusForSelectedRecords($selectedItemsIds, ProductReview::STATUS_PENDING, ProductReview::STATUS_APPROVED);
            }
        }
    }

    /**
     * Perform BulkUnapprove on product review gridview
     * @return void
     */
    public function actionBulkUnapprove()
    {
        if (UsniAdaptor::app()->request->isAjax && isset($_GET['id']))
        {
            $loggedInUser   = UsniAdaptor::app()->user->getUserModel();
            $productReview  = ProductReview::findOne($_GET['id']);
            $isAllowed      = CatalogPermissionUtil::doesUserHavePermissionToPerformAction($productReview, $loggedInUser, 'productreview.approve');
            if($isAllowed)
            {
                $selectedItemsIds = $_GET['id'];
                ProductUtil::updateStatusForSelectedRecords($selectedItemsIds, ProductReview::STATUS_APPROVED, ProductReview::STATUS_PENDING);
            }
        }
    }
    
    /**
     * Perform BulkDelete on gridview
     * @return void
     */
    public function actionBulkDelete()
    {
        $user     = UsniAdaptor::app()->user->getUserModel();
        if (UsniAdaptor::app()->request->isAjax && isset($_GET['id']))
        {
            $modelClass             = ucfirst($this->resolveModelClassName());
            $model                  = new $modelClass();
            $modelPermissionName    = UsniAdaptor::getObjectClassName($model);
            $selectedItems          = $_GET['id'];
            foreach ($selectedItems as $item)
            {
                if(!in_array($item, $this->getExcludedModelIdsFromBulkDelete()))
                {
                    $model = $modelClass::findOne(intval($item));
                    //Check if allowed to delete
                    if(AuthManager::checkAccess($user, strtolower($modelPermissionName) . '.delete'))
                    {
                        $model->status = ProductReview::STATUS_DELETED;
                        $model->scenario = 'bulkdelete';
                        $model->save();
                    }
                }
            }
        }
    }
    
    /**
     * Comments trash Grid View
     * @return 
     */
    public function actionTrash()
    {
        $review         = new ProductReview(['scenario' => 'search']);
        $breadcrumbs    = [
                              [
                                  'label' => UsniAdaptor::t('products', 'Manage Reviews'),
                                  'url'   => UsniAdaptor::createUrl('catalog/products/review/manage')
                              ],
                              [
                                  'label' => UsniAdaptor::t('products', 'Manage Trash Reviews')
                              ]
                          ];
        $this->getView()->params['breadcrumbs']  = $breadcrumbs;
        $view           = new ProductReviewTrashGridView(['model' => $review]);
        $content        = $this->renderColumnContent([$view]);
        return $this->render($this->getDefaultLayout(), ['content' => $content]);
    }
    
    /**
     * Delete model from trash.
     * @param int $id
     * @return void
     */
    public function actionDeleteFromTrash($id)
    {
        if (UsniAdaptor::app()->request->isAjax)
        {
            $productReview  = $this->loadModel(ProductReview::className(), $id);
            $loggedInUser   = UsniAdaptor::app()->user->getUserModel();
            $isAllowed      = CatalogPermissionUtil::doesUserHavePermissionToPerformAction($productReview, $loggedInUser, 'productreview.delete');
            if($isAllowed)
            {
                $productReview->delete();
                $this->redirect(UsniAdaptor::createUrl('products/review/trash'));
            }
        }
    }
    
    /**
     * @inheritdoc
     */
    public function pageTitles()
    {
        return [
                    'manage' => UsniAdaptor::t('application','Manage') . ' ' . ProductReview::getLabel(2)
               ];
    }
    
    /**
     * Perform BulkDelete on trash gridview
     * 
     * @return void
     */
    public function actionTrashBulkDelete()
    {
        $user     = UsniAdaptor::app()->user->getUserModel();
        if (UsniAdaptor::app()->request->isAjax && isset($_GET['id']))
        {
            $modelClass             = ucfirst($this->resolveModelClassName());
            $model                  = new $modelClass();
            $modelPermissionName    = UsniAdaptor::getObjectClassName($model);
            $selectedItems          = $_GET['id'];
            foreach ($selectedItems as $item)
            {
                if(!in_array($item, $this->getExcludedModelIdsFromBulkDelete()))
                {
                    $model = $modelClass::findOne(intval($item));
                    //Check if allowed to delete
                    if(AuthManager::checkAccess($user, strtolower($modelPermissionName) . '.delete'))
                    {
                        $model->delete();
                    }
                }
            }
        }
    }
    
    /**
     * @inheritdoc
     */
    protected static function getNonPermissibleActions()
    {
        $nonPermissibleActions              = parent::getNonPermissibleActions();
        $additionalNonPermissibleActions    = [
                                                    'bulk-approve', 
                                                    'bulk-unapprove', 
                                                    'bulk-delete', 
                                                    'trash-bulk-delete',
                                                    'approve',
                                                    'unapprove',
                                                    'spam',
                                                    'remove-spam',
                                                    'delete-from-grid',
                                                    'delete-from-trash'
                                              ];
        return ArrayUtil::merge($nonPermissibleActions, $additionalNonPermissibleActions);
    }
    
    /**
     * @inheritdoc
     */
    protected function getActionToPermissionsMap()
    {
        $actionToPermissionsMap = parent::getActionToPermissionsMap();
        $additionalPermissions  = [
                                        'trash'             => 'productreview.delete',
                                        'bulk-approve'      => 'productreview.approve',
                                        'bulk-unapprove'    => 'productreview.approve',
                                        'bulk-delete'       => 'productreview.delete',
                                        'trash-bulk-delete' => 'productreview.delete',
                                        'unapprove'         => 'productreview.approve'
                                  ];
        return array_merge($additionalPermissions, $actionToPermissionsMap);
    }
}
