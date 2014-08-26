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
     Yii::import('ext.sendgrid.lib.*');
     Yii::import('ext.sendgrid.lib.SendGrid.*');
     Yii::import('ext.sendgrid.lib.Smtpapi.*');

    /**
     * Class for Zurmo specific sendgrid functionality.
     */
    class ZurmoSendGridMailer extends Mailer
    {
        protected $emailHelper;

        protected $fromUser;

        protected $toAddresses;

        protected $ccAddresses;

        protected $bccAddresses;

        protected $fromUserEmailData;

        public function __construct(SendGridEmailHelper $emailHelper,
                                    $userToSendMessagesFrom,
                                    $toAddresses,
                                    $ccAddresses = array(),
                                    $bccAddresses = array())
        {
            SendGrid::register_autoloader();
            Smtpapi::register_autoloader();
            $this->emailHelper = $emailHelper;
            if(is_array($userToSendMessagesFrom))
            {
                $this->fromUserEmailData = $userToSendMessagesFrom;
            }
            elseif(is_object($userToSendMessagesFrom) && $userToSendMessagesFrom instanceof User)
            {
                $this->fromUser    = $userToSendMessagesFrom;
            }
            if(is_array($toAddresses))
            {
                $this->toAddresses  = $toAddresses;
            }
            else
            {
                $this->toAddresses  = array($toAddresses => null);
            }
            $this->ccAddresses      = $ccAddresses;
            $this->bccAddresses     = $bccAddresses;
        }

        /**
         * Send a test email from user.  Can use to determine if the SMTP settings are configured correctly.
         * @return EmailMessage
         */
        public function sendTestEmailFromUser()
        {
            $this->fromUserEmailData = array(
                'address'   => Yii::app()->emailHelper->resolveFromAddressByUser($this->fromUser),
                'name'      => strval($this->fromUser),
            );
            return $this->sendTestEmail();
        }

        /**
         * Send a test email.
         * @return EmailMessage
         */
        public function sendTestEmail()
        {
            $toAddresses               = array_keys($this->toAddresses);
            $emailMessage              = EmailMessageHelper::processAndCreateEmailMessage($this->fromUserEmailData, $toAddresses[0]);
            $validated                 = $emailMessage->validate();
            if ($validated)
            {
                //Yii::app()->sendGridEmailHelper->sendImmediately($emailMessage);
                list($toAddresses, $ccAddresses, $bccAddresses) = SendGridEmailHelper::resolveRecipientAddressesByType($emailMessage);
                $this->sendEmail($emailMessage);
                $saved        = $emailMessage->save();
                if (!$saved)
                {
                    throw new FailedToSaveModelException();
                }
            }
            return $emailMessage;
        }

        /**
         * Send email.
         * @param EmailMessage $emailMessage
         */
        public function sendEmail(EmailMessage $emailMessage)
        {
            $sendgrid = new SendGrid($this->emailHelper->apiUsername, $this->emailHelper->apiPassword, array("turn_off_ssl_verification" => true));
            $email    = new SendGrid\Email();
            $email->setFrom($this->fromUserEmailData['address'])->
                   setFromName($this->fromUserEmailData['name'])->
                   setSubject($emailMessage->subject)->
                   setText($emailMessage->content->textContent)->
                   setHtml($emailMessage->content->htmlContent)->
                   addHeader('X-Sent-Using', 'SendGrid-API')->
                   addHeader('X-Transport', 'web');
            foreach($this->toAddresses as $emailAddress => $name)
            {
                $email->addTo($emailAddress, $name);
            }
            foreach($this->ccAddresses as $emailAddress => $name)
            {
                $email->addCc($emailAddress);
            }
            foreach($this->bccAddresses as $emailAddress => $name)
            {
                $email->addBcc($emailAddress);
            }
            /*
            TODO: Need to close on attachement as path is not there in file model.
            if (isset($emailMessage->files) && !empty($emailMessage->files))
            {
                foreach ($emailMessage->files as $file)
                {
                    $email->setAttachments($file->fileContent->content, $file->name, $file->type);
                    //$emailMessage->attach($attachment);
                }
            }*/
            $response = $sendgrid->send($email);
            if($response->message == 'success')
            {
                //Here we need to check if
                $emailMessage->error        = null;
                $emailMessage->folder       = EmailFolder::getByBoxAndType($emailMessage->folder->emailBox, EmailFolder::TYPE_SENT);
                $emailMessage->sentDateTime = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
            }
            //In case message is not delivered but there is no api related error than also flow would not enter here.
            elseif($response->message == 'error')
            {
                $content = Zurmo::t('EmailMessagesModule', 'Response from Server') . "\n";
                foreach($response->errors as $error)
                {
                    $content .= $error;
                }
                $emailMessageSendError = new EmailMessageSendError();
                $data                  = array();
                $data['message']                       = $content;
                $emailMessageSendError->serializedData = serialize($data);
                $emailMessage->folder                  = EmailFolder::getByBoxAndType($emailMessage->folder->emailBox,
                                                                                      EmailFolder::TYPE_OUTBOX_ERROR);
                $emailMessage->error                   = $emailMessageSendError;
            }
        }

        public function getBouncedData($startDate, $endDate, $startTime, $endTime)
        {
            $url = 'https://api.sendgrid.com/';
            $user = $this->emailHelper->apiUsername;
            $pass = $this->emailHelper->apiPassword;

            $request =  $url . 'api/bounces.get.json?api_user=' . $user . '&api_key=' . $pass . '&date=1';

            // Generate curl request
            $curl = curl_init($request);
            // Tell curl not to return headers, but do return the response
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

            // obtain response
            if(!$response = curl_exec($curl))
            {
                trigger_error(curl_error($curl));
            }
            curl_close($curl);
            $data = json_decode($response);
        }
    }
?>