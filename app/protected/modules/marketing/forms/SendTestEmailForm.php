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

    class SendTestEmailForm extends CFormModel
    {
        public $selectContactOrLeadSearchBox;

        public $inputEmailAddressBox;

        public $selectContactOrEmailRadioButton;

        public function rules()
        {
            return array(
                array('selectContactOrLeadSearchBox',       'type',    'type' => 'string'),
                array('selectContactOrLeadSearchBox',       'validateSelectedContact'),
                array('inputEmailAddressBox',               'type',    'type' => 'string'),
                array('inputEmailAddressBox',               'email'),
                array('selectContactOrEmailRadioButton',    'type',    'type' => 'string'),
                array('selectContactOrEmailRadioButton',    'validateAtLeastOneIsProvided'),
            );
        }

        public function validateAtLeastOneIsProvided($attribute, $params)
        {
            if (!(empty($this->selectContactOrLeadSearchBox) xor empty($this->inputEmailAddressBox)))
            {
                $this->addError($attribute, Zurmo::t('MarketingModule', 'Please provide an email or select a contact.'));
                return false;
            }
            return true;
        }

        public function validateSelectedContact($attribute, $params)
        {
            $matches    = array();
            preg_match('#\((\d+)\)#', $this->selectContactOrLeadSearchBox, $matches);
            $contactId  = (isset($matches[1]))? $matches[1] : null;
            if (!empty($contactId))
            {
                try
                {
                    Contact::getById($contactId);
                    return true;
                }
                catch (NotFoundException $e)
                {

                }
            }
            $this->addError($attribute, Zurmo::t('MarketingModule', 'Please select a valid contact.'));
            return true;
        }
    }
?>