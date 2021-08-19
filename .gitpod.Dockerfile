FROM gitpod/workspace-full:latest

# Install custom tools, runtimes, etc.
# For example "bastet", a command-line tetris clone:
# RUN brew install bastet
#
# More information: https://www.gitpod.io/docs/config-docker/

USER root

# Update the Container
### base ###
RUN sudo apt-get update -yq \
    && sudo apt-get install apt-utils -yq \
    && sudo apt-get upgrade -yq \
    && sudo locale-gen de_DE.UTF-8 \
    && sudo apt-get clean \
    && sudo rm -rf /var/lib/apt/lists/*

ENV LANG=de_DE.UTF-8

# Install Krypton
RUN curl https://krypt.co/kr | sh

# Install latest composer v2 release
RUN curl -s https://getcomposer.org/installer | php \
    && sudo mv composer.phar /usr/bin/composer \
    && sudo mkdir -p /home/gitpod/.config \
    && sudo chown -R gitpod:gitpod /home/gitpod/.config

# Install ioncube
RUN sudo wget http://downloads3.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz -O /tmp/ioncube.tar.gz \
    && sudo tar xzf /tmp/ioncube.tar.gz -C /tmp \
    && sudo cp /tmp/ioncube/ioncube_loader_lin_7.4.so /usr/lib/php/20190902/ioncube_loader_lin_7.4.so \
    && sudo bash -c 'echo "zend_extension=ioncube_loader_lin_7.4.so" > /etc/php/7.4/apache2/conf.d/10-ioncube.ini' \
    && sudo bash -c 'echo "zend_extension=ioncube_loader_lin_7.4.so" > /etc/php/7.4/cli/conf.d/10-ioncube.ini' \
    && sudo rm -rf /tmp/ioncube.tar.gz /tmp/ioncube

# Install XDebug
RUN wget http://xdebug.org/files/xdebug-2.9.1.tgz \
    && tar -xvzf xdebug-2.9.1.tgz \
    && cd xdebug-2.9.1 \
    && phpize \
    && ./configure \
    && make \
    && sudo mkdir -p /usr/lib/php/20190902 \
    && sudo cp modules/xdebug.so /usr/lib/php/20190902 \
    && sudo bash -c "echo -e '\nzend_extension = /usr/lib/php/20190902/xdebug.so\n[XDebug]\nxdebug.remote_enable = 1\nxdebug.remote_autostart = 1\n' >> /etc/php/7.4/cli/conf.d/15-xdebug.ini" \
    && sudo bash -c "echo -e '\nzend_extension = /usr/lib/php/20190902/xdebug.so\n[XDebug]\nxdebug.remote_enable = 1\nxdebug.remote_autostart = 1\n' >> /etc/php/7.4/apache2/conf.d/15-xdebug.ini"

USER gitpod

# Install Changelogger
RUN COMPOSER_ALLOW_SUPERUSER=1 composer global require churchtools/changelogger

# Add composer bin folder to $PATH
ENV PATH="$PATH:$HOME/.config/composer/vendor/bin"
