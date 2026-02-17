# pff2-rest

REST module for `stonedz/pff2` (v4) to build JSON APIs with:

- automatic REST method dispatch (`GET/POST/PUT/DELETE`)
- optional auth hook per request
- API version prefix routing (e.g. `api1/...`)
- JSON exception output for API requests

## Requirements

- PHP + `stonedz/pff2` v4
- Module dependencies declared in this package:
  - `exception_handler`
  - `pff2-permissions`
- Optional module:
	- `pff2-annotations` (only needed for annotation-based REST detection)

## Installation

```bash
composer require stonedz/pff2-rest
```

## Enable modules in app config

In your app `config.user.php`, ensure the modules list includes:

```php
$pffConfig['modules'] = [
	'exception_handler',
	'pff2-permissions',
	'pff2-rest',
];
```

If you want annotation-based REST detection, also add:

```php
'pff2-annotations',
```

## Module configuration

Create (or override) config at:

`app/config/modules/pff2-rest/module.conf.yaml`

Example:

```yaml
moduleConf:
  annotationName: Rest
  enableAuth: true
  authType: token
  authClass: ApiAuthCkecker
  apiVersions:
	- api1
```

### Config keys

- `annotationName`: method annotation name checked by `pff2-annotations` (when installed/enabled).
- `enableAuth`: if `true`, auth class is invoked before handling request.
- `authType`: currently informational in this module code.
- `authClass`: class name under namespace `\pff\models\` implementing `IRestAuth`.
- `apiVersions`: URL prefixes treated as API version roots (`api1`, `v2`, etc).

## How routing works

If request URL starts with a configured API version, pff2-rest rewrites route from:

`/api1/users/42`

to controller/action form:

`Api1_Users/index/42`

Then, if controller implements `IRestController`, action is replaced by HTTP method:

- `GET` → `getHandler`
- `POST` → `postHandler`
- `PUT` → `putHandler`
- `DELETE` → `deleteHandler`
- `OPTIONS` → immediate `200`

If `pff2-annotations` is installed and enabled, methods marked with the configured annotation name are also treated as REST requests.

## Creating a REST controller

Implement `pff\modules\Iface\IRestController`:

```php
<?php

namespace pff\controllers;

use pff\Abs\AController;
use pff\modules\Iface\IRestController;

class Api1_Users_Controller extends AController implements IRestController
{
	public function getHandler()
	{
		echo json_encode(['ok' => true, 'method' => 'GET']);
	}

	public function postHandler()
	{
		echo json_encode(['ok' => true, 'method' => 'POST']);
	}

	public function putHandler()
	{
		echo json_encode(['ok' => true, 'method' => 'PUT']);
	}

	public function deleteHandler()
	{
		echo json_encode(['ok' => true, 'method' => 'DELETE']);
	}
}
```

## Auth integration

When `enableAuth: true`, module creates:

`\pff\models\<authClass>`

and calls:

```php
authorize(IController $controller)
```

So your auth model should implement `pff\modules\Iface\IRestAuth`.

Example:

```php
<?php

namespace pff\models;

use pff\Iface\IController;
use pff\modules\Iface\IRestAuth;
use pff\modules\Exception\RestException;

class ApiAuthCkecker implements IRestAuth
{
	public function authorize(IController $controller)
	{
		$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
		if ($token === '') {
			throw new RestException('Unauthorized', 401);
		}
	}
}
```

## Error output format

For REST requests, uncaught exceptions are rendered as JSON:

```json
{
  "error": true,
  "message": "...",
  "file": "...::..."
}
```

HTTP status code is taken from exception code.

## Notes

- The default sample value is `ApiAuthCkecker` (spelling from legacy config). Use any class name you want, as long as it exists under `\pff\models\` and matches `authClass`.
- This module sets JSON output automatically for matched REST requests.
- For non-REST requests, normal pff2 flow is unchanged.
