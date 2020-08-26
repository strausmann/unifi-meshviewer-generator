## UniFi Meshviewer Generator

Der Unifi Meshviewer Generator verbindet sich per API mit dem Unifi Controller (z.B. dem Cloudkey) und ermittelt alle AccessPoints. Aus den Daten wird eine meshviewer.json und eine nodelist.json erzeugt. Diese können anschließend im Meshviewer und der Nodelist zusätzlich eingebunden werden. Somit sind auch Unifi AccessPoints die mit der Stock Firmware an einem Unifi Controller betrieben werden, zukünftig auf der Freifunk Map zu sehen. Dieses Projekt basiert auf der Idee von [Freifunk-Greifswald/UniFi.php](https://github.com/Freifunk-Greifswald/UniFi.php), vielen Dank für den Ansatz.

The GitHub repository is only a mirror of our [GitLab](https://git.isp-serverfarm.de/freifunk-nordheide/unifi-meshviewer-generator)

## Requirements

- a web server with PHP and cURL modules installed (tested on Apache 2.4 / NGINX with PHP Version 5.6.26 and cURL 7.42.1 and with PHP 7.2.10 and cURL 7.58.0)
- network connectivity between this web server and the server and port (normally TCP port 8443) where the UniFi Controller is running.
- Git
- Composer

## Installation ##

You can use [Git](#git) or simply [Download the Release](#download-the-release) to install this webservice.

### Git

The preferred method is via `git` command from the shell in your project directory:

```sh
git clone https://git.isp-serverfarm.de/freifunk-nordheide/unifi-meshviewer-generator.git .
```

* When git is done cloning, execute Composer with the following command:

```sh
composer install
```

### Download the Release

If you prefer not to use git, you can simply [download the package](https://git.isp-serverfarm.de/freifunk-nordheide/unifi-meshviewer-generator/-/releases), uncompress the zip file.

## Configuration

* Copy the .env.example to .env and do not rename the file.
* Add your specifications to the variables in the .env.

| NAME          | Description                                        | Value                       | Required |
|---------------|----------------------------------------------------|-----------------------------|----------|
| DEBUG         | Enable Disable the Debug Mode                      | TRUE/FALSE                  | X        |
| TIMEZONE      | Set the Timezone                                   | Europe/Berlin               | X        |
| OWNER_EMAIL   | Set the Node Owner Email or Phonenumber            | user@emaildomain.net        | X        |
| UNIFI_USER    | Set the Unifi Controller User Name                 | UnifiStatUser               | X        |
| UNIFI_PASS    | Set the Unifi Controller Password                  | P@ssw0rd#1234               | X        |
| UNIFI_URL     | Set the Unifi Controller URL incl. Port            | https://myuc.domain.de:8443 | X        |
| UNIFI_ZONE    | Set the Unifi Controller Zone                      | default                     | X        |
| UNIFI_VERSION | Set the Unifi Controller Version                   | 5.10.26                     |          |
| FREIFUNK_SSID | Filter the connected wireless clients by this ssid | nordheide.freifunk.net      | X        |
| GATEWAY_NEXTHOP    | Use the Offloader Node ID                          | 18e8295ccf02                | X        |

* Set the WWW root directory of your web server to the Public folder from the project. This prevents direct access to your .env and the folders /cache, /devices and /vendor.
* Copy the htaccess.txt files to .htaccess in the folders /public and /public/data and set your IP address in the /public/.htaccess file. Do not rename the files. Here you have to set the IP address of the client/server which is allowed to call the index.php regularly e.g. as cronjob. This is a security feature so that the Unifi Controller credentials are not displayed in plain text if an error occurs during execution.
* Perform a first manual call of index.php (http://myunifiservice.domain.de/index.php). Note: The "public" path should not be included in the call. Otherwise the .env could be reached directly from the internet.
* With the first call, all AccessPoints are determined and stored in the folder /devices as JSON file. The public name for the AccessPoints must be stored in the files.
* Create a cronjob that calls index.php every 5 minutes. With this call the information is retrieved via API from the Unifi controller and processed in JSON format.
* Now share the URL to your webservice with your Community Admin Team. The admins can then integrate the URL into the Meshviewer.

## Credits

This class is based on the initial work by the following developers:

- domwo: http://community.ubnt.com/t5/UniFi-Wireless/little-php-class-for-unifi-api/m-p/603051
- fbagnol: https://github.com/fbagnol/class.unifi.php
- Art-of-Wifi: https://github.com/Art-of-WiFi/UniFi-API-client
- Freifunk-Greifswald: https://github.com/Freifunk-Greifswald/UniFi.php

and the API as published by Ubiquiti:

- https://dl.ubnt.com/unifi/5.10.19/unifi_sh_api

## Important Disclaimer

Many of the functions in this API client class are not officially supported by UBNT and as such, may not be supported in future versions of the UniFi Controller API.

## Contribution

* TODO