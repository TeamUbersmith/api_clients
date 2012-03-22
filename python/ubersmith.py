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

"""Client library for Ubersmith.

Example usage:

 >>>> import ubersmith
 >>>> client = ubersmith.UbersmithClient('https://billing.mycompany.com',
                                         'myusername',
                                         'mytoken')
 >>>> client('client.get', client_id='foo')
 { ... }


Requirements:

  - Ubersmith API v2.0

"""

import base64
import urllib
import urllib2

try:
    # Python >= 2.6
    import json
except:
    # Python < 2.6 requires installation of simplejson
    import simplejson as json


__all__ = ['UbersmithClient', 'UbersmithError']


class UbersmithError(Exception):

    def __init__(self, errno, strerror):
        self.errno = errno
        self.strerror = strerror
        msg = '[Errno %s] %s' % (errno, strerror)
        super(UbersmithError, self).__init__(msg)


class UbersmithClient(object):

    def __init__(self, base_url, username, api_token):
        """

        :param base_url: The base ubersmith url, e.g. https://uber.mycompany.com
        :param username: The username to authenticate with
        :param api_token: The ubersmith api token for the username

        """
        self.base_url = base_url.rstrip('/')
        self.username = username
        self.api_token = api_token

    def _get_url_for_method(self, method_name):
        query = urllib.urlencode({'method': method_name})
        return '%s/api/2.0/?%s' % (self.base_url, query)

    def _get_headers(self):
        encoded_auth = base64.encodestring(
            '%s:%s' % (self.username, self.api_token))[:-1]
        return {
            'Authorization': 'Basic %s' % encoded_auth,
            }

    def __call__(self, method_name, **kwargs):
        """
        Calls the ubersmith api over http/s.  Parameters to the method
        in question are to be provided as named arguments to this
        method.

        :param method_name: The ubersmith api method to invoke

        """
        url = self._get_url_for_method(method_name)
        headers = self._get_headers()
        request = urllib2.Request(url, headers=headers)
        data = urllib.urlencode(kwargs)
        response = urllib2.urlopen(request, data=data)
        result = json.load(response)
        if not result.get('status'):
            raise UbersmithError(result.get('error_code'),
                                 result.get('error_message'))
        return result.get('data')

    def check_login(self, login, password):
        return self('uber.check_login', **{'login': login, 'pass': password})

    def get_client(self, client_id, metadata=False):
        return self('client.get', client_id=client_id,
                    metadata=int(metadata))

    def update_client(self, client_id, **kwargs):
        return self('client.update', client_id=client_id, **kwargs)
