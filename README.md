# THG Web Master Version 1.0

THG Web for Web Master Version and Api Version
## Overview
This application is written in Laravel Framework version 8.0

### Installation / Deployment

THG Web Master Version requires php version > 7.1.0 and php composer
using npm for bootstrap and other js/css library and Redis server that running on your server

### Other Dependencies
`Redis server ( remember to install redis on your server)`

Install the dependencies and devDependencies and start the server.

```sh
$ cd your-project-directory
$ php composer install
$ php artisan key:generate
```

### Setup .env file

Copy .env.exam:
```sh
$ cp ./.env.exam ./.env
```

Find and Edit `APP` value with app stage 
```sh
APP_SECRET=YOUR_APP_SECRET
APP_ID_WEB=YOUR_APP_ID_WEB
APP_ID_MOBILE=YOUR_APP_ID_MOBILE
```

Find and Edit `API` value :
e.g.
```sh
API_URL=https://thg.arkamaya.net/api
APP_REDIRECT_URL=https://localhost:8000/
APP_SESSION_HOST=https://thg.arkamaya.net/
```
Find and Edit `MPC_URL` value :
e.g.
```sh
MPC_URL_AUTH_PAGE=https://openapi.allobank.com/api/v2.0/oauth/authorize
MPC_URL_REQUEST_TOKEN=https://openapi.allobank.com/api/v2.0/oauth/token/request
MPC_URL_REFRESH_TOKEN=https://openapi.allobank.com/api/v2.0/oauth/token/refresh
MPC_URL_GET_MEMBER_PROFILE=https://openapi.allobank.com/api/v2.0/member/profile/query
```

Find and Edit `Redis` section  value :
e.g.
```sh
REDIS_HOST=127.0.0.1 or your FQDN of your Server
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
REDIS_CACHE_DB=0
```

### Setup Private Key File
1. Make a private key file, you can see this [article] for an example
2. Place that file in 'storage/app/key' folder (create 'key' folder if you please and don't forget to change the folder permission)
3. Change the filename into 'private.key'

### Decrypt Header as pairing comparison Public dan Private Key
1. Make sure you have Public key dan Private Key, which created from your server
2. Put these two file in 'storage/app/key' folder
3. Change your public key filename into 'public.crt' and your private key filename into 'private.key'
4. Uncomment line 81-82 in AlloApiController.php to give static value for comparison purposes
5. Test using Postman to create 'Sign' Header Key, then copy-paste value from it to Parameter on Decrypt Header collection endpoint 
6. You'll see the value of data object BEFORE it's encrypted by the Encrypt Function
7. To compare the value, open AlloApiController.php file, then uncomment line 107, then HIT the Create Header Allo API on Postman
8. You'll see the value of data object, then compare it to the value that you have on Decrypt Function API on Postman before

## Copyright

Copyright (c) 2021, [PT. Arkamaya](http://www.arkamaya.co.id) All Rights Reserved.

 [article]: <https://vander.host/knowledgebase/security/how-to-generate-rsa-public-and-private-key-pair-in-pkcs8-format/>



