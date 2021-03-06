Open Photo API / Get Photos
=======================
#### OpenPhoto, a photo service for the masses

----------------------------------------

1. [Purpose][purpose]
1. [Endpoint][endpoint]
1. [Parameters][parameters]
1. [Examples][examples]
  * [Command line][example-cli]
  * [PHP][example-php]
1. [Response][response]
  * [Sample][sample]

----------------------------------------

<a name="purpose"></a>
### Purpose of the update Tag API

Use this API to update a tag.

----------------------------------------

<a name="endpoint"></a>
### Endpoint

_Authentication: required_

    POST /tag/:id/update.json

<a name="parameters"></a>
### Parameters

1.  count (optional), Number of photos which contain this tag
1.  email (optional), An email address that corresponds to this tag
1.  latitude (optional), _i.e. 34.76_ - A decimal representation of latitude.
1.  longitude (optional), _i.e. -87.45_ - A decimal representation of longitude.

----------------------------------------

<a name="examples"></a>
### Examples

<a name="example-cli"></a>
#### Command Line (using [openphoto-php][openphoto-php])

    ./openphoto -p -X POST -h current.openphoto.me -e /tag/sunnyvale/update.json -F 'count=10'

<a name="example-php"></a>
#### PHP (using [openphoto-php][openphoto-php])

    $client = new OpenPhotoOAuth($host, $consumerKey, $consumerSecret, $oauthToken, $oauthTokenSecret);
    $response = $client->post("/tag/sunnyvale/update.json", array('count' => 10));

----------------------------------------

<a name="response"></a>
### Response

The response is in a standard [response envelope][Envelope].

* _message_, A string describing the result. Don't use this for anything but reading.
* _code_, _200_ on success
* _result_, A [Tag][Tag] object or FALSE on error

<a name="sample"></a>
#### Sample

    {
      "message":"",
      "code":200,
      "result":
      {
        "id": "mountain",
        "count": 1
      }
    }

[Envelope]: Envelope.markdown
[Tag]: ../schemas/Tag.markdown
[purpose]: #purpose
[endpoint]: #endpoint
[parameters]: #parameters
[examples]: #examples
[example-cli]: #example-cli
[example-php]: #example-php
[response]: #response
[sample]: #sample
[openphoto-php]: https://github.com/openphoto/openphoto-php

