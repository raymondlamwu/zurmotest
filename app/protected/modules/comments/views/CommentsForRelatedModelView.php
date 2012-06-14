<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2012 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
     * details.
     *
     * You should have received a copy of the GNU General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 113 McHenry Road Suite 207,
     * Buffalo Grove, IL 60089, USA. or at email address contact@zurmo.com.
     ********************************************************************************/

    /**
     * A view that displays comments for a related model
     *
     */
    class CommentsForRelatedModelView extends View
    {
        protected $controllerId;

        protected $moduleId;

        protected $commentsData;

        protected $pageSize;

        protected $getParams;

        public function __construct($controllerId, $moduleId, $commentsData, $pageSize, $getParams)
        {
            assert('is_string($controllerId)');
            assert('is_string($moduleId)');
            assert('is_array($commentsData)');
            assert('is_int($pageSize) || $pageSize == null');
            assert('is_array($getParams)');
            $this->controllerId           = $controllerId;
            $this->moduleId               = $moduleId;
            $this->commentsData           = $commentsData;
            $this->pageSize               = $pageSize;
            $this->getParams              = $getParams;
        }

        protected function renderContent()
        {
            $content = '<div>';
            $content .= '<div>' . $this->renderHiddenRefreshLinkContent() . '</div>';
            if(count($this->commentsData) > 0)
            {
                if(count($this->commentsData) > $this->pageSize && $this->pageSize != null)
                {
                    $content .= '<div>' . $this->renderShowAllLinkContent() . '</div>';
                }
                $content .= '<div>' . $this->renderCommentsContent() . '</div>';
            }
            $content .= '</div>';
            return $content;
        }

        protected function renderHiddenRefreshLinkContent()
        {
            $url     =   Yii::app()->createUrl($this->moduleId . '/' . $this->controllerId . '/ajaxListForRelatedModel',
                            $this->getParams);
            return       ZurmoHtml::ajaxLink('Refresh', $url,
                         array('type' => 'GET',
                               'success' => 'function(data){$("#CommentsForRelatedModelView").replaceWith(data)}'),
                         array('id'         => 'hiddenCommentRefresh',
                                'class'     => 'hiddenCommentRefresh',
                                'namespace' => 'refresh',
                                'style'     => 'display:none;'));
        }

        protected function renderShowAllLinkContent()
        {
            $url     =   Yii::app()->createUrl($this->moduleId . '/' . $this->controllerId . '/ajaxListForRelatedModel',
                            array_merge($this->getParams, array('noPaging' => true)));
            return       ZurmoHtml::ajaxLink(Yii::t('Default', 'Show older comments'), $url,
                         array('type' => 'GET',
                               'success' => 'function(data){$("#CommentsForRelatedModelView").replaceWith(data)}'),
                         array('id'         => 'showAllCommentsLink',
                                'class'     => 'showAllCommentsLink',
                                'namespace' => 'refresh'));
        }

        protected function renderCommentsContent()
        {
            $content  = '<table>';
            $content .= '<tbody>';
            $rows = 0;
            foreach(array_reverse($this->commentsData) as $comment)
            {
            //Render date
                $stringContent  = '<strong>'. DateTimeUtil::convertDbFormattedDateTimeToLocaleFormattedDisplay(
                                              $comment->createdDateTime, 'long', null) . '</strong> ';
                $stringContent .= Yii::t('Default', 'by {ownerStringContent}',
                                        array('{ownerStringContent}' => strval($comment->createdByUser)));
                $stringContent .= '<br/>';
                $stringContent .= $comment->description;
                //attachments
                if($comment->files->count() > 0)
                {
                    $stringContent .= '<br/>';
                    $stringContent .= FileModelDisplayUtil::renderFileDataDetailsWithDownloadLinksContent($comment, 'files');
                }
                $content .= '<tr>';
                $content .= '<td>' . $stringContent . '</td>';
                $content .= '</tr>';
                $rows ++;
                if($rows == $this->pageSize && $this->pageSize != null)
                {
                    break;
                }
            }
            $content .= '</tbody>';
            $content .= '</table>';
            return $content;
        }

        public function isUniqueToAPage()
        {
            return true;
        }
    }
?>