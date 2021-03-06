<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2015 Zurmo Inc.
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
     * "Copyright Zurmo Inc. 2015. All rights reserved".
     ********************************************************************************/

    class NotificationsUtilTest extends ZurmoBaseTest
    {
        protected $user;

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            UserTestHelper::createBasicUser('billy');
        }

        public function setup()
        {
            parent::setup();
            $this->user = User::getByUsername('super');
        }

        public function teardown()
        {
            parent::setup();
        }

        public function testSubmitNonCritical()
        {
            $user                                     = $this->user;
            $emailAddress                             = new Email();
            $emailAddress->emailAddress               = 'sometest@zurmoalerts.com';
            $user->primaryEmail                       = $emailAddress;
            $saved                                    = $user->save();
            $this->assertTrue($saved);
            $billy                                    = User::getByUsername('billy');
            $emailAddress                             = new Email();
            $emailAddress->emailAddress               = 'sometest2@zurmoalerts.com';
            $billy->primaryEmail                      = $emailAddress;
            $saved                                    = $billy->save();
            $this->assertTrue($saved);
            $notifications              = Notification::getAll();
            $this->assertEquals(0, count($notifications));
            $message                    = new NotificationMessage();
            $message->textContent       = 'text content';
            $message->htmlContent       = 'html content';
            $rules                      = new SimpleNotificationRules();
            $rules->addUser($user);
            $rules->addUser($billy);
            NotificationsUtil::submit($message, $rules);

            //non critical notification emails are queued.
            $this->assertEquals(2, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(0, Yii::app()->emailHelper->getSentCount());
            $notifications              = Notification::getAll();
            $this->assertCount(2, $notifications);
        }

        public function testSubmitCritical()
        {
            Notification::deleteAll();
            EmailMessage::deleteAll();
            $user                                    = $this->user;
            $emailAddress                             = new Email();
            $emailAddress->emailAddress               = 'sometest@zurmoalerts.com';
            $user->primaryEmail                      = $emailAddress;
            $saved                                    = $user->save();
            $this->assertTrue($saved);
            $billy                                    = User::getByUsername('billy');
            $emailAddress                             = new Email();
            $emailAddress->emailAddress               = 'sometest2@zurmoalerts.com';
            $billy->primaryEmail                      = $emailAddress;
            $saved                                    = $billy->save();
            $this->assertTrue($saved);
            $notifications              = Notification::getAll();
            $this->assertEquals(0, count($notifications));
            $message                    = new NotificationMessage();
            $message->textContent       = 'text content';
            $message->htmlContent       = 'html content';
            $rules                      = new SimpleNotificationRules();
            $rules->addUser($user);
            $rules->addUser($billy);
            $rules->setCritical(true);
            NotificationsUtil::submit($message, $rules);

            //critical notification emails are sent directly
            $this->assertEquals(0, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(2, Yii::app()->emailHelper->getSentCount());
            $notifications              = Notification::getAll();
            $this->assertCount(2, $notifications);
        }

        public function testSubmittingDuplicateNotifications()
        {
            $user                       = $this->user;
            Notification::deleteAll();
            EmailMessage::deleteAll();
            $message                    = new NotificationMessage();
            $message->textContent       = 'text content';
            $message->htmlContent       = 'html content';
            $rules                      = new SimpleNotificationRules();
            $rules->setCritical(true);
            $rules->setAllowDuplicates(false);
            $rules->addUser($user);
            NotificationsUtil::submit($message, $rules);
            $this->assertEquals(1, Yii::app()->emailHelper->getSentCount());
            $this->assertCount (1, Notification::getAll());
            NotificationsUtil::submit($message, $rules);
            $this->assertEquals(1, Yii::app()->emailHelper->getSentCount());
            $this->assertCount (1, Notification::getAll());
            $rules->setAllowDuplicates(true);
            NotificationsUtil::submit($message, $rules);
            $this->assertEquals(2, Yii::app()->emailHelper->getSentCount());
            $this->assertCount (2, Notification::getAll());
        }

        public function testSubmitWithInboxNotificationSettingEnabledAndEmailNotificationSettingDisabled()
        {
            $initialNotificationCount = Notification::getCount();
            $initialEmailMessageCount  = EmailMessage::getCount();
            $rules                     = new SimpleNotificationRules();
            $rules->setAllowDuplicates(true);
            $rules->addUser($this->user);

            $inboxAndEmailNotificationSettings = UserTestHelper::getDefaultNotificationSettingsValuesForTestUser();
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['email'] = false;
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['inbox'] = true;
            UserNotificationUtil::setValue(
                $this->user, $inboxAndEmailNotificationSettings, 'inboxAndEmailNotificationSettings', false);
            $this->assertFalse(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'email'));
            $this->assertTrue(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'inbox'));
            $message                    = new NotificationMessage();
            $message->textContent       = 'text content for' . __FUNCTION__;
            $message->htmlContent       = 'html content for' . __FUNCTION__;
            NotificationsUtil::submit($message, $rules);
            $this->assertEquals($initialNotificationCount + 1, Notification::getCount());
            $this->assertEquals($initialEmailMessageCount, EmailMessage::getCount());
        }

        public function testSubmitWithInboxNotificationSettingDisabledAndEmailNotificationSettingEnabled()
        {
            $initialNotificationCount = Notification::getCount();
            $initialEmailMessageCount  = EmailMessage::getCount();
            $rules                     = new SimpleNotificationRules();
            $rules->setAllowDuplicates(true);
            $rules->addUser($this->user);

            $inboxAndEmailNotificationSettings = UserTestHelper::getDefaultNotificationSettingsValuesForTestUser();
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['email'] = true;
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['inbox'] = false;
            UserNotificationUtil::setValue(
                $this->user, $inboxAndEmailNotificationSettings, 'inboxAndEmailNotificationSettings', false);
            $this->assertTrue(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'email'));
            $this->assertFalse(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'inbox'));
            $message                    = new NotificationMessage();
            $message->textContent       = 'text content for' . __FUNCTION__;
            $message->htmlContent       = 'html content for' . __FUNCTION__;
            NotificationsUtil::submit($message, $rules);
            $this->assertEquals($initialNotificationCount, Notification::getCount());
            $this->assertEquals($initialEmailMessageCount + 1, EmailMessage::getCount());
        }

        public function testSubmitWithInboxNotificationSettingEnabledAndEmailNotificationSettingEnabled()
        {
            $initialNotificationCount = Notification::getCount();
            $initialEmailMessageCount  = EmailMessage::getCount();
            $rules                     = new SimpleNotificationRules();
            $rules->setAllowDuplicates(true);
            $rules->addUser($this->user);

            $inboxAndEmailNotificationSettings = UserTestHelper::getDefaultNotificationSettingsValuesForTestUser();
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['email'] = true;
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['inbox'] = true;
            UserNotificationUtil::setValue(
                $this->user, $inboxAndEmailNotificationSettings, 'inboxAndEmailNotificationSettings', false);
            $this->assertTrue(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'email'));
            $this->assertTrue(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'inbox'));
            $message                    = new NotificationMessage();
            $message->textContent       = 'text content for' . __FUNCTION__;
            $message->htmlContent       = 'html content for' . __FUNCTION__;
            NotificationsUtil::submit($message, $rules);
            $this->assertEquals($initialNotificationCount + 1, Notification::getCount());
            $this->assertEquals($initialEmailMessageCount + 1, EmailMessage::getCount());
        }

        public function testSubmitCriticalNotificationWithInboxNotificationSettingEnabledAndEmailNotificationSettingEnabled()
        {
            $initialNotificationCount     = Notification::getCount();
            $initialEmailMessageCount     = EmailMessage::getCount();
            $initialSentEmailMessageCount = count(EmailMessage::getAllByFolderType(EmailFolder::TYPE_SENT));
            $rules                        = new SimpleNotificationRules();
            $rules->setAllowDuplicates(true);
            $rules->addUser($this->user);
            $rules->setCritical(true);

            $inboxAndEmailNotificationSettings = UserTestHelper::getDefaultNotificationSettingsValuesForTestUser();
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['email'] = true;
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['inbox'] = true;
            UserNotificationUtil::setValue(
                $this->user, $inboxAndEmailNotificationSettings, 'inboxAndEmailNotificationSettings', false);
            $this->assertTrue(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'email'));
            $this->assertTrue(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'inbox'));
            $message                    = new NotificationMessage();
            $message->textContent       = 'text content for' . __FUNCTION__;
            $message->htmlContent       = 'html content for' . __FUNCTION__;
            NotificationsUtil::submit($message, $rules);
            $this->assertEquals($initialNotificationCount + 1, Notification::getCount());
            // because it was a critical notification, an email should have been sent immediately.
            $this->assertEquals($initialEmailMessageCount + 1, EmailMessage::getCount());
            $this->assertEquals($initialSentEmailMessageCount + 1, count(EmailMessage::getAllByFolderType(EmailFolder::TYPE_SENT)));
        }

        public function testSubmitCriticalNotificationWithInboxNotificationSettingEnabledAndEmailNotificationSettingDisabled()
        {
            $initialNotificationCount   = Notification::getCount();
            $initialEmailMessageCount   = EmailMessage::getCount();
            $rules                      = new SimpleNotificationRules();
            $rules->setAllowDuplicates(true);
            $rules->addUser($this->user);
            $rules->setCritical(true);

            $inboxAndEmailNotificationSettings = UserTestHelper::getDefaultNotificationSettingsValuesForTestUser();
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['email'] = false;
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['inbox'] = true;
            UserNotificationUtil::setValue(
                $this->user, $inboxAndEmailNotificationSettings, 'inboxAndEmailNotificationSettings', false);
            $this->assertFalse(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'email'));
            $this->assertTrue(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'inbox'));
            $message                    = new NotificationMessage();
            $message->textContent       = 'text content for' . __FUNCTION__;
            $message->htmlContent       = 'html content for' . __FUNCTION__;
            NotificationsUtil::submit($message, $rules);
            $this->assertEquals($initialNotificationCount + 1, Notification::getCount());
            $this->assertEquals($initialEmailMessageCount, EmailMessage::getCount());
        }

        public function testSubmitWithInboxNotificationSettingDisabledAndEmailNotificationSettingDisabled()
        {
            $initialNotificationCount = Notification::getCount();
            $initialEmailMessageCount  = EmailMessage::getCount();
            $rules                     = new SimpleNotificationRules();
            $rules->setAllowDuplicates(true);
            $rules->addUser($this->user);

            $inboxAndEmailNotificationSettings = UserTestHelper::getDefaultNotificationSettingsValuesForTestUser();
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['email'] = false;
            $inboxAndEmailNotificationSettings['enableSimpleNotification']['inbox'] = false;
            UserNotificationUtil::setValue(
                $this->user, $inboxAndEmailNotificationSettings, 'inboxAndEmailNotificationSettings', false);
            $this->assertFalse(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'email'));
            $this->assertFalse(UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($this->user, 'enableSimpleNotification', 'inbox'));
            $message                    = new NotificationMessage();
            $message->textContent       = 'text content for' . __FUNCTION__;
            $message->htmlContent       = 'html content for' . __FUNCTION__;
            NotificationsUtil::submit($message, $rules);
            $this->assertEquals($initialNotificationCount, Notification::getCount());
            $this->assertEquals($initialEmailMessageCount, EmailMessage::getCount());
        }
    }
?>
