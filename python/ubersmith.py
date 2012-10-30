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

    def _get_headers(self, headers=None):
        encoded_auth = base64.encodestring(
            '%s:%s' % (self.username, self.api_token))[:-1]
        if headers is None:
            headers = {}
        headers['Authorization'] = 'Basic %s' % encoded_auth
        return headers

    def __call__(self, method_name, **kwargs):
        """
        Calls the ubersmith api over http/s.  Parameters to the method
        in question are to be provided as named arguments to this
        method.

        :param method_name: The ubersmith api method to invoke

        """
        data = urllib.urlencode(kwargs)
        self.call_direct(method_name, data)

    def call_direct(self, method_name, data, headers=None):
        """
        Calls the ubersmith api over http/s.  Parameters shoud be pre-encoded
        and passed in data, which will be the body of the POST. Additional
        headers may also be specified.

        :param method_name: The ubersmith api method to invoke
        :param data: Preencoded (via urlencode or multipart/form-data) POST data
        :param headers: Optionally, additional headers to send

        """
        url = self._get_url_for_method(method_name)
        _headers = self._get_headers(headers)
        request = urllib2.Request(url, data, _headers)
        response = urllib2.urlopen(request)
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
