# Google Text to Speech &amp; Speech to Text API

<img src="https://github.com/souravmsh/google-speech-recognition/blob/main/preview.png"/>

Follow some steps:- 

1. Go to the https://console.cloud.google.com/home/dashboard page.
2. Select your project and then click "Enable APIs and Services", select "Cloud Text-to-Speech API" and "Cloud Speech-to-Text API", then enable both with billing information.
3. In the console page, go to the "IAM and admin" -> "Service account" -> "KEYS" page and then create a JSON Key, then download, rename it as ```g-service-acc-key.json``` & place it at the root of the app directory.
4. Check out the PHP `` grpc`` extension. If not exists, then install gRPC for PHP with the following command:-
```
  $ sudo apt-get install autoconf
  $ sudo apt-get install zlib1g-dev
  $ sudo apt-get install php-dev 
  $ sudo apt-get install php-pear
```
You can also install extension and dependency packages with the ```$ sudo apt-get install autoconf zlib1g-dev php-dev php-pear``` command. Referenced by https://cloud.google.com/php/grpc .

5. Add ```extension=grpc.so``` to the php.ini file.
6. Create a composer.json file with the following commands: -
```
{
    "require": {
        "google/cloud-text-to-speech": "^1.3",
        "google/apiclient": "^2.12",
        "google/cloud-speech": "^1.5",
        "grpc/grpc": "^1.38"
    }
}
```
7. Now, install the packages by the command ``` composer install``` then browse the script recorder-default.html(chrome/mozila) or recorder-polyfill.html(chrome/mozila/safari) and ...
8. Enjoy :)



