#!/bin/bash
clear
#phpversions=( "8.2" )
phpversions=( "5.6" "7.0" "7.1" "7.2" "7.3" "7.4" "8.0" "8.1" "8.2" )
phpmode=("cli" "apache" "fpm")
phpextension=''

cd /usr/local
sudo rm -rf ioncube_loaders_lin_x86-64.tar.gz
sudo rm -rf /usr/local/ioncube

echo "Download and extractr the latest ioncube loader"
sudo wget http://downloads3.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz
sudo tar xzf ioncube_loaders_lin_x86-64.tar.gz

i=0
len=${#phpversions[@]}
while [ $i -lt $len ];
do
    echo "[PHP${phpversions[$i]}]: - Check PHP Version and exists the ioncube extension ioncube_loader_lin_${phpversions[$i]}.so"
    if [[ -f "/usr/bin/php${phpversions[$i]}" && -f "/usr/local/ioncube/ioncube_loader_lin_${phpversions[$i]}.so" ]];
    then
        echo "[PHP${phpversions[$i]}]: - PHP Version php${phpversions[$i]} is installed, now we installs the ioncube extension"
        phpextension=$(php-config${phpversions[$i]} --extension-dir)
        cp /usr/local/ioncube/ioncube_loader_lin_${phpversions[$i]}.so $phpextension/
        echo "zend_extension=ioncube_loader_lin_${phpversions[$i]}.so" > "/etc/php/${phpversions[$i]}/cli/conf.d/10-ioncube.ini"

    elif [[ ! -f "/usr/local/ioncube/ioncube_loader_lin_${phpversions[$i]}.so" ]];
    then
        echo "[PHP${phpversions[$i]}]: - ioncube extension does not exists for php${phpversions[$i]}"
    else
        echo "[PHP${phpversions[$i]}]: - PHP Version php${phpversions[$i]} is not installed"
    fi
    let i++
done

echo "Remove TEMP Files"
sudo rm -rf ioncube_loaders_lin_x86-64.tar.gz
sudo rm -rf /usr/local/ioncube
