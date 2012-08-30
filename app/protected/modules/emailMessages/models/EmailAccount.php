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
     * Model for user's email accounts
     */
    class EmailAccount extends Item
    {

        const DEFAULT_NAME    = 'Default';

        public function __toString()
        {
            if (trim($this->name) == '')
            {
                return Yii::t('Default', '(Unnamed)');
            }
            return $this->name;
        }

        public static function getModuleClassName()
        {
            return 'EmailMessagesModule';
        }

        public static function getByUserAndName(User $user, $name = null)
        {
            assert('is_string($name)');
            if ($name == null)
            {
                $name = self::DEFAULT_NAME;
            }
            else
            {
                throw new NotSupportedException(Yii::t('Default', 'For now Zurmo still does not support multiple Email Accounts'));
            }
            $bean = R::findOne(EmailAccount::getTableName('EmailAccount'), "_user_id = ? AND name = ?", array($user->id, $name));
            assert('$bean === false || $bean instanceof RedBean_OODBBean');
            if ($bean === false)
            {
                throw new NotFoundException(Yii::t('Default', 'Email Account not found for current user and name.'));
            }
            else
            {
                $emailAccount = self::makeModel($bean);
            }
            return $emailAccount;
        }

        public static function resolveAndGetByUserAndName(User $user, $name = null)
        {
            try
            {
                $emailAccount = static::getByUserAndName($user, $name);
            }
            catch (NotFoundException $e)
            {
                $emailAccount = new EmailAccount();
                $emailAccount->user = $user;
                $emailAccount->name = $name;
                $saved  = $emailAccount->save();
                assert('$saved');
            }
            return $emailAccount;
        }

        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'name',
                    'fromAddress',
                    'fromName',
                    'replyToName',
                    'replyToAddress',
                    'outboundType',
                    'outboundHost',
                    'outboundPort',
                    'outboundUsername',
                    'outboundPassword',
                    'outboundSecurity'
                ),
                'relations' => array(
                    'messages' => array(RedBeanModel::HAS_MANY, 'EmailMessage'),
                    'user'     => array(RedBeanModel::HAS_ONE, 'User'),
                )
            );
            return $metadata;
        }

        public static function isTypeDeletable()
        {
            return true;
        }
    }
?>
