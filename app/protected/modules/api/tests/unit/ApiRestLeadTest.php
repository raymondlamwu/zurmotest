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

    /**
    * Test Lead related API functions.
    */
    class ApiRestLeadTest extends ApiRestTest
    {
        public function testGetLead()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );
            $this->assertTrue(ContactsModule::loadStartingData());
            $lead = LeadTestHelper::createLeadbyNameForOwner('First', $super);
            $compareData  = $this->getModelToApiDataUtilData($lead);

            $response = $this->createApiCallWithRelativeUrl('read/' . $lead->id, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals($compareData, $response['data']);
            $this->assertArrayHasKey('owner', $response['data']);
            $this->assertCount(2, $response['data']['owner']);
            $this->assertArrayHasKey('id', $response['data']['owner']);
            $this->assertEquals($super->id, $response['data']['owner']['id']);
            $this->assertArrayHasKey('explicitReadWriteModelPermissions', $response['data']);
            $this->assertArrayHasKey('type', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertArrayHasKey('nonEveryoneGroup', $response['data']['explicitReadWriteModelPermissions']);
        }

        /**
         * @depends testGetLead
         */
        public function testDeleteLead()
        {
            Yii::app()->user->userModel        = User::getByUsername('super');
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $leads = Contact::getByName('First Firstson');
            $this->assertEquals(1, count($leads));

            $response = $this->createApiCallWithRelativeUrl('delete/' . $leads[0]->id, 'DELETE', $headers);

            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);

            $response = $this->createApiCallWithRelativeUrl('read/' . $leads[0]->id, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('The ID specified was invalid.', $response['message']);
        }

        /**
         * @depends testGetLead
         */
        public function testCreateLead()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $industryValues = array(
                'Automotive',
                'Adult Entertainment',
                'Financial Services',
                'Mercenaries & Armaments',
            );
            $industryFieldData = CustomFieldData::getByName('Industries');
            $industryFieldData->serializedData = serialize($industryValues);
            $this->assertTrue($industryFieldData->save());

            $sourceValues = array(
                'Word of Mouth',
                'Outbound',
                'Trade Show',
            );
            $sourceFieldData = CustomFieldData::getByName('LeadSources');
            $sourceFieldData->serializedData = serialize($sourceValues);
            $this->assertTrue($sourceFieldData->save());

            $titles = array('Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Swami');
            $customFieldData = CustomFieldData::getByName('Titles');
            $customFieldData->serializedData = serialize($titles);
            $this->assertTrue($customFieldData->save());

            $this->assertEquals(6, count(ContactState::GetAll()));
            $contactStates = ContactState::GetAll();
            $primaryEmail['emailAddress']   = "a@example.com";
            $primaryEmail['optOut']         = 1;

            $secondaryEmail['emailAddress'] = "b@example.com";
            $secondaryEmail['optOut']       = 0;
            $secondaryEmail['isInvalid']    = 1;

            $primaryAddress['street1']      = '129 Noodle Boulevard';
            $primaryAddress['street2']      = 'Apartment 6000A';
            $primaryAddress['city']         = 'Noodleville';
            $primaryAddress['postalCode']   = '23453';
            $primaryAddress['country']      = 'The Good Old US of A';

            $secondaryAddress['street1']    = '25 de Agosto 2543';
            $secondaryAddress['street2']    = 'Local 3';
            $secondaryAddress['city']       = 'Ciudad de Los Fideos';
            $secondaryAddress['postalCode'] = '5123-4';
            $secondaryAddress['country']    = 'Latinoland';

            $account        = new Account();
            $account->name  = 'Some Account';
            $account->owner = $super;
            $this->assertTrue($account->save());

            $data['firstName']           = "Samuel";
            $data['lastName']            = "Smith with no permissions";
            $data['jobTitle']            = "President";
            $data['department']          = "Sales";
            $data['officePhone']         = "653-235-7824";
            $data['mobilePhone']         = "653-235-7821";
            $data['officeFax']           = "653-235-7834";
            $data['description']         = "Some desc.";
            $data['companyName']         = "Samuel Co";
            $data['website']             = "http://sample.com";

            $data['industry']['value']   = $industryValues[2];
            $data['source']['value']     = $sourceValues[1];
            $data['title']['value']      = $titles[3];
            $data['state']['id']         = LeadsUtil::getStartingState()->id;
            $data['account']['id']       = $account->id;

            $data['primaryEmail']        = $primaryEmail;
            $data['secondaryEmail']      = $secondaryEmail;
            $data['primaryAddress']      = $primaryAddress;
            $data['secondaryAddress']    = $secondaryAddress;

            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $leadId     = $response['data']['id'];

            $this->assertArrayHasKey('owner', $response['data']);
            $this->assertCount(2, $response['data']['owner']);
            $this->assertArrayHasKey('id', $response['data']['owner']);
            $this->assertEquals($super->id, $response['data']['owner']['id']);
            $this->assertArrayHasKey('explicitReadWriteModelPermissions', $response['data']);
            $this->assertCount(2, $response['data']['explicitReadWriteModelPermissions']);
            $this->assertArrayHasKey('type', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals(1, $response['data']['explicitReadWriteModelPermissions']['type']);
            $this->assertArrayHasKey('nonEveryoneGroup', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals('', $response['data']['explicitReadWriteModelPermissions']['nonEveryoneGroup']);

            $data['owner'] = array(
                'id' => $super->id,
                'username' => 'super'
            );
            $data['createdByUser']    = array(
                'id' => $super->id,
                'username' => 'super'
            );
            $data['modifiedByUser'] = array(
                'id' => $super->id,
                'username' => 'super'
            );

            // unset explicit permissions, we won't use these in comparison.
            unset($response['data']['explicitReadWriteModelPermissions']);
            // We need to unset some empty values from response.
            unset($response['data']['createdDateTime']);
            unset($response['data']['modifiedDateTime']);
            unset($response['data']['primaryEmail']['id'] );
            unset($response['data']['primaryEmail']['isInvalid']);
            unset($response['data']['secondaryEmail']['id']);
            unset($response['data']['primaryAddress']['id']);
            unset($response['data']['primaryAddress']['state']);
            unset($response['data']['primaryAddress']['longitude']);
            unset($response['data']['primaryAddress']['latitude']);
            unset($response['data']['primaryAddress']['invalid']);

            unset($response['data']['secondaryAddress']['id']);
            unset($response['data']['secondaryAddress']['state']);
            unset($response['data']['secondaryAddress']['longitude']);
            unset($response['data']['secondaryAddress']['latitude']);
            unset($response['data']['secondaryAddress']['invalid']);
            unset($response['data']['industry']['id']);
            unset($response['data']['source']['id']);
            unset($response['data']['title']['id']);
            unset($response['data']['id']);
            unset($response['data']['googleWebTrackingId']);
            unset($response['data']['latestActivityDateTime']);

            ksort($data);
            ksort($response['data']);
            $this->assertEquals($data, $response['data']);

            $response = $this->createApiCallWithRelativeUrl('read/' . $leadId, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('data', $response);
            $this->assertArrayHasKey('owner', $response['data']);
            $this->assertCount(2, $response['data']['owner']);
            $this->assertArrayHasKey('id', $response['data']['owner']);
            $this->assertEquals($super->id, $response['data']['owner']['id']);

            $this->assertArrayHasKey('explicitReadWriteModelPermissions', $response['data']);
            $this->assertCount(2, $response['data']['explicitReadWriteModelPermissions']);
            $this->assertArrayHasKey('type', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals(1, $response['data']['explicitReadWriteModelPermissions']['type']);
            $this->assertArrayHasKey('nonEveryoneGroup', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals('', $response['data']['explicitReadWriteModelPermissions']['nonEveryoneGroup']);
        }

        /**
         * @depends testCreateLead
         */
        public function testCreateLeadWithSpecificOwner()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $billy  = User::getByUsername('billy');
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $industryValues = array(
                'Automotive',
                'Adult Entertainment',
                'Financial Services',
                'Mercenaries & Armaments',
            );
            $industryFieldData = CustomFieldData::getByName('Industries');
            $industryFieldData->serializedData = serialize($industryValues);
            $this->assertTrue($industryFieldData->save());

            $sourceValues = array(
                'Word of Mouth',
                'Outbound',
                'Trade Show',
            );
            $sourceFieldData = CustomFieldData::getByName('LeadSources');
            $sourceFieldData->serializedData = serialize($sourceValues);
            $this->assertTrue($sourceFieldData->save());

            $titles = array('Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Swami');
            $customFieldData = CustomFieldData::getByName('Titles');
            $customFieldData->serializedData = serialize($titles);
            $this->assertTrue($customFieldData->save());

            $this->assertEquals(6, count(ContactState::GetAll()));
            $contactStates = ContactState::GetAll();
            $primaryEmail['emailAddress']   = "a@example.com";
            $primaryEmail['optOut']         = 1;

            $secondaryEmail['emailAddress'] = "b@example.com";
            $secondaryEmail['optOut']       = 0;
            $secondaryEmail['isInvalid']    = 1;

            $primaryAddress['street1']      = '129 Noodle Boulevard';
            $primaryAddress['street2']      = 'Apartment 6000A';
            $primaryAddress['city']         = 'Noodleville';
            $primaryAddress['postalCode']   = '23453';
            $primaryAddress['country']      = 'The Good Old US of A';

            $secondaryAddress['street1']    = '25 de Agosto 2543';
            $secondaryAddress['street2']    = 'Local 3';
            $secondaryAddress['city']       = 'Ciudad de Los Fideos';
            $secondaryAddress['postalCode'] = '5123-4';
            $secondaryAddress['country']    = 'Latinoland';

            $account        = new Account();
            $account->name  = 'Some Account';
            $account->owner = $super;
            $this->assertTrue($account->save());

            $data['firstName']           = "Samuel";
            $data['lastName']            = "Smith with just owner";
            $data['jobTitle']            = "President";
            $data['department']          = "Sales";
            $data['officePhone']         = "653-235-7824";
            $data['mobilePhone']         = "653-235-7821";
            $data['officeFax']           = "653-235-7834";
            $data['description']         = "Some desc.";
            $data['companyName']         = "Samuel Co";
            $data['website']             = "http://sample.com";

            $data['industry']['value']   = $industryValues[2];
            $data['source']['value']     = $sourceValues[1];
            $data['title']['value']      = $titles[3];
            $data['state']['id']         = LeadsUtil::getStartingState()->id;
            $data['account']['id']       = $account->id;

            $data['primaryEmail']        = $primaryEmail;
            $data['secondaryEmail']      = $secondaryEmail;
            $data['primaryAddress']      = $primaryAddress;
            $data['secondaryAddress']    = $secondaryAddress;
            $data['owner']['id']        = $billy->id;

            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $leadId     = $response['data']['id'];

            $this->assertArrayHasKey('owner', $response['data']);
            $this->assertCount(2, $response['data']['owner']);
            $this->assertArrayHasKey('id', $response['data']['owner']);
            $this->assertEquals($billy->id, $response['data']['owner']['id']);
            $this->assertArrayHasKey('explicitReadWriteModelPermissions', $response['data']);
            $this->assertCount(2, $response['data']['explicitReadWriteModelPermissions']);
            $this->assertArrayHasKey('type', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals(1, $response['data']['explicitReadWriteModelPermissions']['type']);
            $this->assertArrayHasKey('nonEveryoneGroup', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals('', $response['data']['explicitReadWriteModelPermissions']['nonEveryoneGroup']);

            $data['owner'] = array(
                'id' => $billy->id,
                'username' => 'billy'
            );
            $data['createdByUser']    = array(
                'id' => $super->id,
                'username' => 'super'
            );
            $data['modifiedByUser'] = array(
                'id' => $super->id,
                'username' => 'super'
            );

            // unset explicit permissions, we won't use these in comparison.
            unset($response['data']['explicitReadWriteModelPermissions']);
            // We need to unset some empty values from response.
            unset($response['data']['createdDateTime']);
            unset($response['data']['modifiedDateTime']);
            unset($response['data']['primaryEmail']['id'] );
            unset($response['data']['primaryEmail']['isInvalid']);
            unset($response['data']['secondaryEmail']['id']);
            unset($response['data']['primaryAddress']['id']);
            unset($response['data']['primaryAddress']['state']);
            unset($response['data']['primaryAddress']['longitude']);
            unset($response['data']['primaryAddress']['latitude']);
            unset($response['data']['primaryAddress']['invalid']);

            unset($response['data']['secondaryAddress']['id']);
            unset($response['data']['secondaryAddress']['state']);
            unset($response['data']['secondaryAddress']['longitude']);
            unset($response['data']['secondaryAddress']['latitude']);
            unset($response['data']['secondaryAddress']['invalid']);
            unset($response['data']['industry']['id']);
            unset($response['data']['source']['id']);
            unset($response['data']['title']['id']);
            unset($response['data']['id']);
            unset($response['data']['googleWebTrackingId']);
            unset($response['data']['latestActivityDateTime']);

            ksort($data);
            ksort($response['data']);
            $this->assertEquals($data, $response['data']);

            $response = $this->createApiCallWithRelativeUrl('read/' . $leadId, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('data', $response);
            $this->assertArrayHasKey('owner', $response['data']);
            $this->assertCount(2, $response['data']['owner']);
            $this->assertArrayHasKey('id', $response['data']['owner']);
            $this->assertEquals($billy->id, $response['data']['owner']['id']);

            $this->assertArrayHasKey('explicitReadWriteModelPermissions', $response['data']);
            $this->assertCount(2, $response['data']['explicitReadWriteModelPermissions']);
            $this->assertArrayHasKey('type', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals(1, $response['data']['explicitReadWriteModelPermissions']['type']);
            $this->assertArrayHasKey('nonEveryoneGroup', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals('', $response['data']['explicitReadWriteModelPermissions']['nonEveryoneGroup']);
        }

        /**
         * @depends testCreateLead
         */
        public function testCreateLeadWithSpecificExplicitPermissions()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $industryValues = array(
                'Automotive',
                'Adult Entertainment',
                'Financial Services',
                'Mercenaries & Armaments',
            );
            $industryFieldData = CustomFieldData::getByName('Industries');
            $industryFieldData->serializedData = serialize($industryValues);
            $this->assertTrue($industryFieldData->save());

            $sourceValues = array(
                'Word of Mouth',
                'Outbound',
                'Trade Show',
            );
            $sourceFieldData = CustomFieldData::getByName('LeadSources');
            $sourceFieldData->serializedData = serialize($sourceValues);
            $this->assertTrue($sourceFieldData->save());

            $titles = array('Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Swami');
            $customFieldData = CustomFieldData::getByName('Titles');
            $customFieldData->serializedData = serialize($titles);
            $this->assertTrue($customFieldData->save());

            $this->assertEquals(6, count(ContactState::GetAll()));
            $contactStates = ContactState::GetAll();
            $primaryEmail['emailAddress']   = "a@example.com";
            $primaryEmail['optOut']         = 1;

            $secondaryEmail['emailAddress'] = "b@example.com";
            $secondaryEmail['optOut']       = 0;
            $secondaryEmail['isInvalid']    = 1;

            $primaryAddress['street1']      = '129 Noodle Boulevard';
            $primaryAddress['street2']      = 'Apartment 6000A';
            $primaryAddress['city']         = 'Noodleville';
            $primaryAddress['postalCode']   = '23453';
            $primaryAddress['country']      = 'The Good Old US of A';

            $secondaryAddress['street1']    = '25 de Agosto 2543';
            $secondaryAddress['street2']    = 'Local 3';
            $secondaryAddress['city']       = 'Ciudad de Los Fideos';
            $secondaryAddress['postalCode'] = '5123-4';
            $secondaryAddress['country']    = 'Latinoland';

            $account        = new Account();
            $account->name  = 'Some Account';
            $account->owner = $super;
            $this->assertTrue($account->save());

            $data['firstName']           = "Samuel";
            $data['lastName']            = "Smith with no permissions";
            $data['jobTitle']            = "President";
            $data['department']          = "Sales";
            $data['officePhone']         = "653-235-7824";
            $data['mobilePhone']         = "653-235-7821";
            $data['officeFax']           = "653-235-7834";
            $data['description']         = "Some desc.";
            $data['companyName']         = "Samuel Co";
            $data['website']             = "http://sample.com";

            $data['industry']['value']   = $industryValues[2];
            $data['source']['value']     = $sourceValues[1];
            $data['title']['value']      = $titles[3];
            $data['state']['id']         = LeadsUtil::getStartingState()->id;
            $data['account']['id']       = $account->id;

            $data['primaryEmail']        = $primaryEmail;
            $data['secondaryEmail']      = $secondaryEmail;
            $data['primaryAddress']      = $primaryAddress;
            $data['secondaryAddress']    = $secondaryAddress;
            // TODO: @Shoaibi/@Ivica: null does not work, empty works. null doesn't send it.
            $data['explicitReadWriteModelPermissions'] = array('nonEveryoneGroup' => '', 'type' => '');

            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $leadId     = $response['data']['id'];

            $this->assertArrayHasKey('owner', $response['data']);
            $this->assertCount(2, $response['data']['owner']);
            $this->assertArrayHasKey('id', $response['data']['owner']);
            $this->assertEquals($super->id, $response['data']['owner']['id']);
            $this->assertArrayHasKey('explicitReadWriteModelPermissions', $response['data']);
            $this->assertCount(2, $response['data']['explicitReadWriteModelPermissions']);
            $this->assertArrayHasKey('type', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals('', $response['data']['explicitReadWriteModelPermissions']['type']);
            // following also works. wonder why.
            //$this->assertTrue(null === $response['data']['explicitReadWriteModelPermissions']['type']);
            $this->assertArrayHasKey('nonEveryoneGroup', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals('', $response['data']['explicitReadWriteModelPermissions']['nonEveryoneGroup']);

            $data['owner'] = array(
                'id' => $super->id,
                'username' => 'super'
            );
            $data['createdByUser']    = array(
                'id' => $super->id,
                'username' => 'super'
            );
            $data['modifiedByUser'] = array(
                'id' => $super->id,
                'username' => 'super'
            );

            // We need to unset some empty values from response.
            unset($response['data']['createdDateTime']);
            unset($response['data']['modifiedDateTime']);
            unset($response['data']['primaryEmail']['id'] );
            unset($response['data']['primaryEmail']['isInvalid']);
            unset($response['data']['secondaryEmail']['id']);
            unset($response['data']['primaryAddress']['id']);
            unset($response['data']['primaryAddress']['state']);
            unset($response['data']['primaryAddress']['longitude']);
            unset($response['data']['primaryAddress']['latitude']);
            unset($response['data']['primaryAddress']['invalid']);

            unset($response['data']['secondaryAddress']['id']);
            unset($response['data']['secondaryAddress']['state']);
            unset($response['data']['secondaryAddress']['longitude']);
            unset($response['data']['secondaryAddress']['latitude']);
            unset($response['data']['secondaryAddress']['invalid']);
            unset($response['data']['industry']['id']);
            unset($response['data']['source']['id']);
            unset($response['data']['title']['id']);
            unset($response['data']['id']);
            unset($response['data']['googleWebTrackingId']);
            unset($response['data']['latestActivityDateTime']);

            ksort($data);
            ksort($response['data']);
            $this->assertEquals($data, $response['data']);

            $response = $this->createApiCallWithRelativeUrl('read/' . $leadId, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('data', $response);
            $this->assertArrayHasKey('owner', $response['data']);
            $this->assertCount(2, $response['data']['owner']);
            $this->assertArrayHasKey('id', $response['data']['owner']);
            $this->assertEquals($super->id, $response['data']['owner']['id']);

            $this->assertArrayHasKey('explicitReadWriteModelPermissions', $response['data']);
            $this->assertCount(2, $response['data']['explicitReadWriteModelPermissions']);
            $this->assertArrayHasKey('type', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals('', $response['data']['explicitReadWriteModelPermissions']['type']);
            $this->assertArrayHasKey('nonEveryoneGroup', $response['data']['explicitReadWriteModelPermissions']);
            $this->assertEquals('', $response['data']['explicitReadWriteModelPermissions']['nonEveryoneGroup']);
        }

        /**
         * @depends testCreateLead
         */
        public function testUpdateLead()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $leads = Contact::getByName('Samuel Smith with just owner');
            $this->assertEquals(1, count($leads));
            $compareData  = $this->getModelToApiDataUtilData($leads[0]);
            $group  = static::$randomNonEveryoneNonAdministratorsGroup;
            $explicitReadWriteModelPermissions = array('type' => 2, 'nonEveryoneGroup' => $group->id);
            $data['department']                                 = "Support";
            $compareData['department']                          = "Support";
            $data['explicitReadWriteModelPermissions']          = $explicitReadWriteModelPermissions;
            $compareData['explicitReadWriteModelPermissions']   = $explicitReadWriteModelPermissions;
            $response = $this->createApiCallWithRelativeUrl('update/' . $compareData['id'], 'PUT', $headers,
                                                                                                array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);

            // We need to unset some empty values from response and dates.
            unset($response['data']['modifiedDateTime']);
            unset($compareData['modifiedDateTime']);
            ksort($compareData);
            ksort($response['data']);
            $this->assertEquals($compareData, $response['data']);

            $response = $this->createApiCallWithRelativeUrl('read/' . $compareData['id'], 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            unset($response['data']['modifiedDateTime']);
            ksort($response['data']);
            $this->assertEquals($compareData, $response['data']);
        }

        /**
         * @depends testUpdateLead
         */
        public function testListLead()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $leads = Contact::getByName('Samuel Smith with just owner');
            $this->assertEquals(1, count($leads));
            $compareData  = $this->getModelToApiDataUtilData($leads[0]);

            $response = $this->createApiCallWithRelativeUrl('list/' , 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(3, count($response['data']['items']));
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(1, $response['data']['currentPage']);
            $this->assertEquals($compareData, $response['data']['items'][0]);
        }

        public function testListLeadAttributes()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );
            $allAttributes      = ApiRestTestHelper::getModelAttributes(new Contact());

            $response = $this->createApiCallWithRelativeUrl('listAttributes/' , 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals($allAttributes, $response['data']['items']);
        }

        /**
         * @depends testListLead
         */
        public function testUnprivilegedUserViewUpdateDeleteLead()
        {
            Yii::app()->user->userModel        = User::getByUsername('super');
            $notAllowedUser = UserTestHelper::createBasicUser('Steven');
            $notAllowedUser->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB_API);
            $saved = $notAllowedUser->save();

            $authenticationData = $this->login('steven', 'steven');
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $everyoneGroup = Group::getByName(Group::EVERYONE_GROUP_NAME);
            $this->assertTrue($everyoneGroup->save());

            $leads = Contact::getByName('Samuel Smith with just owner');
            $this->assertEquals(1, count($leads));
            $data['department']                = "Support";

            // Test with unprivileged user to view, edit and delete account.
            $authenticationData = $this->login('steven', 'steven');
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );
            $response = $this->createApiCallWithRelativeUrl('read/' . $leads[0]->id, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('You do not have rights to perform this action.', $response['message']);

            $response = $this->createApiCallWithRelativeUrl('update/' . $leads[0]->id, 'PUT', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('You do not have rights to perform this action.', $response['message']);

            $response = $this->createApiCallWithRelativeUrl('delete/' . $leads[0]->id, 'DELETE', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('You do not have rights to perform this action.', $response['message']);

            //now check if user have rights, but no permissions.
            $notAllowedUser->setRight('LeadsModule', LeadsModule::getAccessRight());
            $notAllowedUser->setRight('LeadsModule', LeadsModule::getCreateRight());
            $notAllowedUser->setRight('LeadsModule', LeadsModule::getDeleteRight());
            $saved = $notAllowedUser->save();
            $this->assertTrue($saved);

            $response = $this->createApiCallWithRelativeUrl('read/' . $leads[0]->id, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('You do not have permissions for this action.', $response['message']);

            $response = $this->createApiCallWithRelativeUrl('update/' . $leads[0]->id, 'PUT', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('You do not have permissions for this action.', $response['message']);

            $response = $this->createApiCallWithRelativeUrl('delete/' . $leads[0]->id, 'DELETE', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('You do not have permissions for this action.', $response['message']);

            // Update unprivileged user permissions
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            unset($data);
            $data['explicitReadWriteModelPermissions'] = array(
                'type' => ExplicitReadWriteModelPermissionsUtil::MIXED_TYPE_EVERYONE_GROUP
            );
            $response = $this->createApiCallWithRelativeUrl('update/' . $leads[0]->id, 'PUT', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);

            $authenticationData = $this->login('steven', 'steven');
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );
            $response = $this->createApiCallWithRelativeUrl('read/' . $leads[0]->id, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);

            unset($data);
            $data['department']                = "Support";
            $response = $this->createApiCallWithRelativeUrl('update/' . $leads[0]->id, 'PUT', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals('Support', $response['data']['department']);

            // Test with privileged user
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            //Test Delete
            $response = $this->createApiCallWithRelativeUrl('delete/' . $leads[0]->id, 'DELETE', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);

            $response = $this->createApiCallWithRelativeUrl('read/' . $leads[0]->id, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
        }

        /**
        * @depends testUnprivilegedUserViewUpdateDeleteLead
        */
        public function testBasicSearchLeads()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            Contact::deleteAll();
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );
            //Setup test data owned by the super user.
            $account  = AccountTestHelper::createAccountByNameForOwner('superAccount', $super);
            $account2 = AccountTestHelper::createAccountByNameForOwner('superAccount2', $super);

            LeadTestHelper::createLeadWithAccountByNameForOwner('First Lead', $super, $account);
            LeadTestHelper::createLeadWithAccountByNameForOwner('Second Lead', $super, $account);
            LeadTestHelper::createLeadWithAccountByNameForOwner('Third Lead', $super, $account);
            LeadTestHelper::createLeadWithAccountByNameForOwner('Forth Lead', $super, $account2);
            LeadTestHelper::createLeadWithAccountByNameForOwner('Fifth Lead', $super, $account2);
            ContactTestHelper::createContactWithAccountByNameForOwner('First Contact', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('Second Contact', $super, $account2);

            $searchParams = array(
                'pagination' => array(
                    'page'     => 1,
                    'pageSize' => 3,
                ),
                'search' => array(
                    'firstName' => '',
                ),
                'sort' => 'firstName',
            );
            $searchParamsQuery = http_build_query($searchParams);
            $response = $this->createApiCallWithRelativeUrl('list/filter/' . $searchParamsQuery, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(3, count($response['data']['items']));
            $this->assertEquals(5, $response['data']['totalCount']);
            $this->assertEquals(1, $response['data']['currentPage']);
            $this->assertEquals('Fifth Lead', $response['data']['items'][0]['firstName']);
            $this->assertEquals('First Lead', $response['data']['items'][1]['firstName']);
            $this->assertEquals('Forth Lead', $response['data']['items'][2]['firstName']);

            // Second page
            $searchParams['pagination']['page'] = 2;
            $searchParamsQuery = http_build_query($searchParams);
            $response = $this->createApiCallWithRelativeUrl('list/filter/' . $searchParamsQuery, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(2, count($response['data']['items']));
            $this->assertEquals(5, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['currentPage']);
            $this->assertEquals('Second Lead', $response['data']['items'][0]['firstName']);
            $this->assertEquals('Third Lead', $response['data']['items'][1]['firstName']);

            // Search by name
            $searchParams['pagination']['page'] = 1;
            $searchParams['search']['firstName'] = 'First Lead';
            $searchParamsQuery = http_build_query($searchParams);
            $response = $this->createApiCallWithRelativeUrl('list/filter/' . $searchParamsQuery, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(1, count($response['data']['items']));
            $this->assertEquals(1, $response['data']['totalCount']);
            $this->assertEquals(1, $response['data']['currentPage']);
            $this->assertEquals('First Lead', $response['data']['items'][0]['firstName']);

            // No results
            $searchParams['pagination']['page'] = 1;
            $searchParams['search']['firstName'] = 'First Lead 2';
            $searchParamsQuery = http_build_query($searchParams);
            $response = $this->createApiCallWithRelativeUrl('list/filter/' . $searchParamsQuery, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(0, $response['data']['totalCount']);
            $this->assertFalse(isset($response['data']['items']));

            // Search by name desc.
            $searchParams = array(
                'pagination' => array(
                    'page'     => 1,
                    'pageSize' => 3,
                ),
                'search' => array(
                    'firstName' => '',
                ),
                'sort' => 'firstName.desc',
            );
            $searchParamsQuery = http_build_query($searchParams);
            $response = $this->createApiCallWithRelativeUrl('list/filter/' . $searchParamsQuery, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(3, count($response['data']['items']));
            $this->assertEquals(5, $response['data']['totalCount']);
            $this->assertEquals(1, $response['data']['currentPage']);
            $this->assertEquals('Third Lead', $response['data']['items'][0]['firstName']);
            $this->assertEquals('Second Lead', $response['data']['items'][1]['firstName']);
            $this->assertEquals('Forth Lead', $response['data']['items'][2]['firstName']);

            // Second page
            $searchParams['pagination']['page'] = 2;
            $searchParamsQuery = http_build_query($searchParams);
            $response = $this->createApiCallWithRelativeUrl('list/filter/' . $searchParamsQuery, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(2, count($response['data']['items']));
            $this->assertEquals(5, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['currentPage']);
            $this->assertEquals('First Lead', $response['data']['items'][0]['firstName']);
            $this->assertEquals('Fifth Lead', $response['data']['items'][1]['firstName']);

            // Search by custom fields, order by name desc
            $searchParams = array(
                'pagination' => array(
                    'page'     => 1,
                    'pageSize' => 3,
                ),
                'search' => array(
                    'account' => array( 'id' => $account2->id),
                    'owner'   => array( 'id' => $super->id),
                ),
                'sort' => 'firstName.desc',
            );
            $searchParamsQuery = http_build_query($searchParams);
            $response = $this->createApiCallWithRelativeUrl('list/filter/' . $searchParamsQuery, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(2, $response['data']['totalCount']);
            $this->assertEquals(2, count($response['data']['items']));
            $this->assertEquals(1, $response['data']['currentPage']);
            $this->assertEquals('Forth Lead', $response['data']['items'][0]['firstName']);
            $this->assertEquals('Fifth Lead', $response['data']['items'][1]['firstName']);
        }

        /**
        * @depends testBasicSearchLeads
        */
        public function testDynamicSearchLeads()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel        = $super;

            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data = array(
                'dynamicSearch' => array(
                    'dynamicClauses' => array(
                        array(
                            'attributeIndexOrDerivedType' => 'owner',
                            'structurePosition' => 1,
                            'owner' => array(
                                'id' => Yii::app()->user->userModel->id,
                            ),
                        ),
                        array(
                            'attributeIndexOrDerivedType' => 'name',
                            'structurePosition' => 2,
                            'firstName' => 'Fi',
                        ),
                        array(
                            'attributeIndexOrDerivedType' => 'name',
                            'structurePosition' => 3,
                            'firstName' => 'Se',
                        ),
                    ),
                    'dynamicStructure' => '1 AND (2 OR 3)',
                ),
                'pagination' => array(
                    'page'     => 1,
                    'pageSize' => 2,
                ),
                'sort' => 'firstName.asc',
           );

            $response = $this->createApiCallWithRelativeUrl('list/filter/', 'POST', $headers, array('data' => $data));

            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(2, count($response['data']['items']));
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(1, $response['data']['currentPage']);
            $this->assertEquals('Fifth Lead', $response['data']['items'][0]['firstName']);
            $this->assertEquals('First Lead', $response['data']['items'][1]['firstName']);

            // Get second page
            $data['pagination']['page'] = 2;
            $response = $this->createApiCallWithRelativeUrl('list/filter/', 'POST', $headers, array('data' => $data));

            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(1, count($response['data']['items']));
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['currentPage']);
            $this->assertEquals('Second Lead', $response['data']['items'][0]['firstName']);
        }

        public function testNewSearchLeads()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel        = $super;

            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data = array(
                'search' => array(
                    'modelClassName' => 'Contact',
                    'searchAttributeData' => array(
                        'clauses' => array(
                            1 => array(
                                'attributeName'        => 'owner',
                                'relatedAttributeName' => 'id',
                                'operatorType'         => 'equals',
                                'value'                => Yii::app()->user->userModel->id,
                            ),
                            2 => array(
                                'attributeName'        => 'firstName',
                                'operatorType'         => 'startsWith',
                                'value'                => 'Fi'
                            ),
                            3 => array(
                                'attributeName'        => 'firstName',
                                'operatorType'         => 'startsWith',
                                'value'                => 'Se'
                            ),
                        ),
                        'structure' => '1 AND (2 OR 3)',
                    ),
                ),
                'pagination' => array(
                    'page'     => 1,
                    'pageSize' => 2,
                ),
                'sort' => 'firstName asc',
            );

            $response = $this->createApiCallWithRelativeUrl('search/filter/', 'POST', $headers, array('data' => $data));

            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(2, count($response['data']['items']));
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(1, $response['data']['currentPage']);
            $this->assertEquals('Fifth Lead', $response['data']['items'][0]['firstName']);
            $this->assertEquals('First Lead', $response['data']['items'][1]['firstName']);

            // Get second page
            $data['pagination']['page'] = 2;
            $response = $this->createApiCallWithRelativeUrl('search/filter/', 'POST', $headers, array('data' => $data));

            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(1, count($response['data']['items']));
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['currentPage']);
            $this->assertEquals('Second Lead', $response['data']['items'][0]['firstName']);
        }

        public function testEditLeadWithIncompleteData()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $lead = LeadTestHelper::createLeadbyNameForOwner('New Lead', $super);

            // Provide data without required fields.
            $data['companyName']         = "Test 123";

            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals(2, count($response['errors']));

            $id = $lead->id;
            $data = array();
            $data['lastName']                = '';
            $response = $this->createApiCallWithRelativeUrl('update/' . $id, 'PUT', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals(1, count($response['errors']));
        }

        public function testEditLeadWIthIncorrectDataType()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $lead = LeadTestHelper::createLeadbyNameForOwner('Newest Lead', $super);

            // Provide data with wrong type.
            $data['companyName']         = "A";
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals(2, count($response['errors']));

            $id = $lead->id;
            $data = array();
            $data['companyName']         = "A";
            $response = $this->createApiCallWithRelativeUrl('update/' . $id, 'PUT', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertEquals(0, count($response['errors']));
        }

        /**
         * Test if all newly created items was pulled from read permission tables via API.
         * Please note that here we do not test if data are inserted in read permission tables correctly, that is
         * part of read permission subscription tests
         * @throws NotFoundException
         * @throws NotImplementedException
         * @throws NotSupportedException
         */
        public function testGetCreatedLeads()
        {
            $timestamp = time();
            sleep(1);
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $lisa = UserTestHelper::createBasicUser('Lisa');
            $lisa->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB_API);
            $lisa->setRight('LeadsModule', LeadsModule::getAccessRight());
            $this->assertTrue($lisa->save());
            $this->deleteAllModelsAndRecordsFromReadPermissionTable('Contact');
            $job = new ReadPermissionSubscriptionUpdateJob();
            ReadPermissionsOptimizationUtil::rebuild();

            $lead1  = LeadTestHelper::createLeadbyNameForOwner('Mike', $super);
            sleep(1);
            $lead2  = LeadTestHelper::createLeadbyNameForOwner('Jake', $super);
            sleep(1);
            $lead3  = LeadTestHelper::createLeadbyNameForOwner('Joe',  $super);
            sleep(1);
            $lead1->primaryEmail->emailAddress = 'mike@example.com';
            $lead1->companyName = "IBM";
            $this->assertTrue($lead1->save());
            $lead2->primaryEmail->emailAddress = 'jake@example.com';
            $this->assertTrue($lead2->save());
            $lead3->primaryEmail->emailAddress = 'joe@example.com';
            $this->assertTrue($lead3->save());
            $this->assertTrue($job->run());

            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );
            $data = array(
                'sinceTimestamp' => $timestamp,
                'pagination' => array(
                    'pageSize' => 2,
                    'page'     => 1
                )
            );

            $response = $this->createApiCallWithRelativeUrl('getCreatedItems/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['pageSize']);
            $this->assertEquals(1, $response['data']['currentPage']);

            $this->assertEquals($lead1->id, $response['data']['items'][0]['id']);
            $this->assertEquals($super->id, $response['data']['items'][0]['owner']['id']);
            $this->assertEquals($lead1->firstName, $response['data']['items'][0]['firstName']);
            $this->assertEquals($lead1->lastName, $response['data']['items'][0]['lastName']);
            $this->assertEquals($lead1->companyName, $response['data']['items'][0]['companyName']);
            $this->assertEquals($lead1->primaryEmail->emailAddress, $response['data']['items'][0]['primaryEmail']['emailAddress']);

            $this->assertEquals($lead2->id, $response['data']['items'][1]['id']);
            $this->assertEquals($super->id, $response['data']['items'][1]['owner']['id']);
            $this->assertEquals($lead2->firstName, $response['data']['items'][1]['firstName']);
            $this->assertEquals($lead2->lastName, $response['data']['items'][1]['lastName']);
            $this->assertEquals($lead2->primaryEmail->emailAddress, $response['data']['items'][1]['primaryEmail']['emailAddress']);

            $data = array(
                'sinceTimestamp' => 0,
                'pagination' => array(
                    'pageSize' => 2,
                    'page'     => 2
                )
            );
            $response = $this->createApiCallWithRelativeUrl('getCreatedItems/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['pageSize']);
            $this->assertEquals(2, $response['data']['currentPage']);

            $this->assertEquals($lead3->id, $response['data']['items'][0]['id']);
            $this->assertEquals($super->id, $response['data']['items'][0]['owner']['id']);
            $this->assertEquals($lead3->firstName, $response['data']['items'][0]['firstName']);
            $this->assertEquals($lead3->lastName, $response['data']['items'][0]['lastName']);
            $this->assertEquals($lead3->primaryEmail->emailAddress, $response['data']['items'][0]['primaryEmail']['emailAddress']);

            // Change owner of $contact1, it should appear in Lisa's created contacts
            $lead1->owner = $lisa;
            $this->assertTrue($lead1->save());
            sleep(1);
            $this->assertTrue($job->run());

            $data = array(
                'sinceTimestamp' => $timestamp,
                'pagination' => array(
                    'pageSize' => 2,
                    'page'     => 1
                )
            );

            $response = $this->createApiCallWithRelativeUrl('getCreatedItems/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(2, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['pageSize']);
            $this->assertEquals(1, $response['data']['currentPage']);

            $this->assertEquals($lead2->id, $response['data']['items'][0]['id']);
            $this->assertEquals($super->id, $response['data']['items'][0]['owner']['id']);
            $this->assertEquals($lead2->firstName, $response['data']['items'][0]['firstName']);
            $this->assertEquals($lead2->lastName, $response['data']['items'][0]['lastName']);
            $this->assertEquals($lead2->companyName, $response['data']['items'][0]['companyName']);
            $this->assertEquals($lead2->primaryEmail->emailAddress, $response['data']['items'][0]['primaryEmail']['emailAddress']);

            $this->assertEquals($lead3->id, $response['data']['items'][1]['id']);
            $this->assertEquals($super->id, $response['data']['items'][1]['owner']['id']);
            $this->assertEquals($lead3->firstName, $response['data']['items'][1]['firstName']);
            $this->assertEquals($lead3->lastName, $response['data']['items'][1]['lastName']);
            $this->assertEquals($lead3->primaryEmail->emailAddress, $response['data']['items'][1]['primaryEmail']['emailAddress']);

            $authenticationData = $this->login('lisa', 'lisa');
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data = array(
                'sinceTimestamp' => $timestamp,
                'pagination' => array(
                    'pageSize' => 2,
                    'page'     => 1
                )
            );

            $response = $this->createApiCallWithRelativeUrl('getCreatedItems/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(1, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['pageSize']);
            $this->assertEquals(1, $response['data']['currentPage']);

            $this->assertEquals($lead1->id, $response['data']['items'][0]['id']);
            $this->assertEquals($lisa->id, $response['data']['items'][0]['owner']['id']);
            $this->assertEquals($lead1->firstName, $response['data']['items'][0]['firstName']);
            $this->assertEquals($lead1->lastName, $response['data']['items'][0]['lastName']);
            $this->assertEquals($lead1->companyName, $response['data']['items'][0]['companyName']);
            $this->assertEquals($lead1->primaryEmail->emailAddress, $response['data']['items'][0]['primaryEmail']['emailAddress']);
        }

        /**
         * Test if all modified items was pulled via API correctly.
         * Please note that here we do not test if data are inserted in read permission tables correctly, that is
         * part of read permission subscription tests
         * @throws NotFoundException
         */
        public function testGetModifiedLeads()
        {
            $timestamp = time();
            sleep(1);
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $this->deleteAllModelsAndRecordsFromReadPermissionTable('Contact');
            $job = new ReadPermissionSubscriptionUpdateJob();
            $lead1  = LeadTestHelper::createLeadbyNameForOwner('Michael', $super);
            $lead2  = LeadTestHelper::createLeadbyNameForOwner('Michael2', $super);
            $lead3  = LeadTestHelper::createLeadbyNameForOwner('Michael3',  $super);
            $lead4  = LeadTestHelper::createLeadbyNameForOwner('Michael4',  $super);
            $this->assertTrue($job->run());
            sleep(1);

            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data = array(
                'sinceTimestamp' => $timestamp,
                'pagination' => array(
                    'pageSize' => 2,
                    'page'     => 1
                )
            );

            $response = $this->createApiCallWithRelativeUrl('getModifiedItems/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(0, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['pageSize']);
            $this->assertEquals(1, $response['data']['currentPage']);

            $timestamp = time();
            sleep(1);
            $lead1->firstName = "Micheal Modified";
            $this->assertTrue($lead1->save());
            sleep(1);
            $lead3->firstName = "Micheal Modified";
            $this->assertTrue($lead3->save());
            sleep(1);
            $lead4->firstName = "Micheal Modified";
            $this->assertTrue($lead4->save());
            sleep(1);

            $data = array(
                'sinceTimestamp' => $timestamp,
                'pagination' => array(
                    'pageSize' => 2,
                    'page'     => 1
                )
            );

            $response = $this->createApiCallWithRelativeUrl('getModifiedItems/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['pageSize']);
            $this->assertEquals(1, $response['data']['currentPage']);

            $this->assertEquals($lead1->id, $response['data']['items'][0]['id']);
            $this->assertEquals($super->id, $response['data']['items'][0]['owner']['id']);
            $this->assertEquals($lead1->firstName, $response['data']['items'][0]['firstName']);
            $this->assertEquals($lead1->lastName, $response['data']['items'][0]['lastName']);
            $this->assertEquals($lead1->companyName, $response['data']['items'][0]['companyName']);
            $this->assertEquals($lead1->primaryEmail->emailAddress, $response['data']['items'][0]['primaryEmail']['emailAddress']);

            $this->assertEquals($lead3->id, $response['data']['items'][1]['id']);
            $this->assertEquals($super->id, $response['data']['items'][1]['owner']['id']);
            $this->assertEquals($lead3->firstName, $response['data']['items'][1]['firstName']);
            $this->assertEquals($lead3->lastName, $response['data']['items'][1]['lastName']);
            $this->assertEquals($lead3->companyName, $response['data']['items'][1]['companyName']);
            $this->assertEquals($lead3->primaryEmail->emailAddress, $response['data']['items'][1]['primaryEmail']['emailAddress']);

            $data = array(
                'sinceTimestamp' => $timestamp,
                'pagination' => array(
                    'pageSize' => 2,
                    'page'     => 2
                )
            );

            $response = $this->createApiCallWithRelativeUrl('getModifiedItems/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['pageSize']);
            $this->assertEquals(2, $response['data']['currentPage']);

            $this->assertEquals($lead4->id, $response['data']['items'][0]['id']);
            $this->assertEquals($super->id, $response['data']['items'][0]['owner']['id']);
            $this->assertEquals($lead4->firstName, $response['data']['items'][0]['firstName']);
            $this->assertEquals($lead4->lastName, $response['data']['items'][0]['lastName']);
            $this->assertEquals($lead4->companyName, $response['data']['items'][0]['companyName']);
            $this->assertEquals($lead4->primaryEmail->emailAddress, $response['data']['items'][0]['primaryEmail']['emailAddress']);
        }

        /**
         * Test if all deleted items was pulled from read permission tables via API.
         * Please note that here we do not test if data are inserted in read permission tables correctly, that is
         * part of read permission subscription tests
         * @throws NotFoundException
         */
        public function testGetDeletedLeads()
        {
            $timestamp = time();
            sleep(1);
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $this->deleteAllModelsAndRecordsFromReadPermissionTable('Contact');
            $job = new ReadPermissionSubscriptionUpdateJob();
            $lead1  = LeadTestHelper::createLeadbyNameForOwner('Michael', $super);
            $lead2  = LeadTestHelper::createLeadbyNameForOwner('Michael2', $super);
            $lead3  = LeadTestHelper::createLeadbyNameForOwner('Michael3',  $super);
            $this->assertTrue($job->run());
            sleep(1);
            $leadId1 = $lead1->id;
            $leadId2 = $lead2->id;
            $leadId3 = $lead3->id;
            $lead1->delete();
            $lead2->delete();
            $lead3->delete();

            $this->assertTrue($job->run());

            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );
            $data = array(
                'sinceTimestamp' => $timestamp,
                'pagination' => array(
                    'pageSize' => 2,
                    'page'     => 1
                )
            );

            $response = $this->createApiCallWithRelativeUrl('getDeletedItems/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['pageSize']);
            $this->assertEquals(1, $response['data']['currentPage']);
            $this->assertContains($leadId1, $response['data']['items']);
            $this->assertContains($leadId2, $response['data']['items']);

            $data = array(
                'sinceTimestamp' => 0,
                'pagination' => array(
                    'pageSize' => 2,
                    'page'     => 2
                )
            );

            $response = $this->createApiCallWithRelativeUrl('getDeletedItems/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(3, $response['data']['totalCount']);
            $this->assertEquals(2, $response['data']['pageSize']);
            $this->assertEquals(2, $response['data']['currentPage']);
            $this->assertContains($leadId3, $response['data']['items']);
        }

        protected function getApiControllerClassName()
        {
            Yii::import('application.modules.leads.controllers.ContactApiController', true);
            return 'LeadsContactApiController';
        }

        protected function getModuleBaseApiUrl()
        {
            return 'leads/contact/api/';
        }
    }
?>