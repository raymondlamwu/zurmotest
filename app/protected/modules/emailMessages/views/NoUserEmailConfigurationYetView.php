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
     * Class for showing in the user interface when there is no user email configuration yet.  This needs to be
     * configured first before a user can send email from the application.
     */
    class NoUserEmailConfigurationYetView extends View
    {
        protected function renderContent()
        {
            $params  = array('label' => $this->getCreateLinkDisplayLabel());
            $url     = Yii::app()->createUrl('/users/default/emailConfiguration',
                                             array('id' => Yii::app()->user->userModel->id));
            $content = '<div class="' . $this->getIconName() . '">';
            $content .= $this->getMessageContent();
            $content .= ZurmoHtml::link(ZurmoHtml::tag('span', array(), $this->getCreateLinkDisplayLabel()), $url);
            $content .= '</div>';
            return $content;
        }

        protected function getIconName()
        {
            return 'EmailMessage';
        }

        protected function getCreateLinkDisplayLabel()
        {
            return Yii::t('Default', 'Configure');
        }

        protected function getMessageContent()
        {
            return Yii::t('Default', '<h2>First things first</h2></i>' .
                                     '<div class="large-icon"></div><p>Set up your email before you can send emails.</p>');
        }
    }
?>
