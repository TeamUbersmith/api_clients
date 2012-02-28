#Ubersmith API Python Client

This client has what you need to get started interacting with the
Ubersmith API in Python.  

###Example Usage

```python
import ubersmith

client = ubersmith.UbersmithClient('https://billing.mycompany.com',
                                   'myusername',
                                   'mytoken')
result = client('client.get', client_id='foo')
