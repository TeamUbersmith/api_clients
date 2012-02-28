# Copyright 2012, Internap Network Services Corp.
# All Rights Reserved.
#
#    Licensed under the Apache License, Version 2.0 (the "License"); you may
#    not use this file except in compliance with the License. You may obtain
#    a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0
#
#    Unless required by applicable law or agreed to in writing, software
#    distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
#    WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
#    License for the specific language governing permissions and limitations
#    under the License.

import os

import unittest2 as unittest

from ubersmith import (
    UbersmithClient,
    UbersmithError,
    json,
    )


def setup_module(self):
    cfg_filename = 'test_ubersmith.json'
    if not os.path.exists(cfg_filename):
        raise Exception('Test config not found: %s' % cfg_filename)
    f = open(cfg_filename)
    self.cfg = json.load(f)
    f.close()


class TestUbersmithClient(unittest.TestCase):

    def setUp(self):
        self.client = UbersmithClient(cfg['url'],
                                      cfg['username'],
                                      cfg['api_token'])

    @property
    def client_id(self):
        if not hasattr(self, '_client_id'):
            result = self.client.check_login(cfg['login'], cfg['pass'])
            self._client_id = result.get('client_id')
        return self._client_id

    @property
    def client_map(self):
        if not hasattr(self, '_client_map'):
            self._client_map = self.client.get_client(self.client_id,
                                                      metadata=True)
        return self._client_map

    def test_check_login(self):
        self.assertTrue(self.client_id)

    def test_check_login_fails_with_error(self):
        self.assertRaises(UbersmithError,
                          self.client.check_login,
                          'badusername',
                          '')

    def test_get_client(self):
        self.assertTrue('metadata' in self.client_map)

    def test_update_client(self):
        tenant_id = self.client_map['metadata']['keystone_tenant_id']
        if tenant_id == 'foo':
            new_tenant_id = 'bar'
        else:
            new_tenant_id = 'foo'
        success = self.client.update_client(
            self.client_id,
            meta_keystone_tenant_id=new_tenant_id)
        client_map = self.client.get_client(self.client_id, metadata=True)
        retrieved_tenant_id = client_map['metadata']['keystone_tenant_id']
        self.assertEqual(new_tenant_id, retrieved_tenant_id)
