<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2014 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU Affero General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
     * details.
     *
     * You should have received a copy of the GNU Affero General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 27 North Wacker Drive
     * Suite 370 Chicago, IL 60606. or at email address contact@zurmo.com.
     *
     * The interactive user interfaces in original and modified versions
     * of this program must display Appropriate Legal Notices, as required under
     * Section 5 of the GNU Affero General Public License version 3.
     *
     * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
     * these Appropriate Legal Notices must retain the display of the Zurmo
     * logo and Zurmo copyright notice. If the display of the logo is not reasonably
     * feasible for technical reasons, the Appropriate Legal Notices must display the words
     * "Copyright Zurmo Inc. 2014. All rights reserved".
     ********************************************************************************/

    /**
     * Form to all editing and viewing of mail related configuration values in the user interface.
     */
    class SendGridWebApiConfigurationForm extends ConfigurationForm
    {
        public $username;
        public $password;
        public $eventWebhookUrl;
        public $eventWebhookFilePath;
        public $aTestToAddress;

        /**
         * @return array
         */
        public function rules()
        {
            return array(
                array('username, password, eventWebhookUrl, eventWebhookFilePath', 'required'),
                array('username',                          'type',      'type' => 'string'),
                array('username',                          'length',    'min'  => 1, 'max' => 64),
                array('password',                          'type',      'type' => 'string'),
                array('password',                          'length',    'min'  => 1, 'max' => 64),
                array('aTestToAddress',                    'email'),
                array('eventWebhookUrl',                   'url'),
                array('eventWebhookFilePath',              'type',      'type' => 'string'),
            );
        }

        /**
         * @return array
         */
        public function attributeLabels()
        {
            return array(
                'username'                             => Zurmo::t('ZurmoModule', 'Username'),
                'password'                             => Zurmo::t('ZurmoModule', 'Password'),
                'aTestToAddress'                       => Zurmo::t('SendGridModule', 'Send a test email to'),
                'eventWebhookUrl'                      => Zurmo::t('SendGridModule', 'Event webhook url'),
                'eventWebhookFilePath'                 => Zurmo::t('SendGridModule', 'Event webhook file path'),
            );
        }
    }
?>