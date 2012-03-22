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

from setuptools import setup, find_packages


version = 0.1


setup(name='Ubersmith',
      version=str(version),
      description='Python client for Ubersmith API (http://www.ubersmith.com)',
      author='Maru Newby',
      author_email='mnewby@internap.com',
      install_requires=['simplejson'],
      py_modules=['ubersmith'],
      test_suite='nose.collector',
      tests_require=['nose', 'unittest2'],
      )
