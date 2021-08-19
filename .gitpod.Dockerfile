FROM gitpod/workspace

USER gitpod

RUN sudo apt-get -q update && \
    sudo apt-get install -y tig && \
    sudo rm -rf /var/lib/apt/lists/*

RUN sudo apt-get purge composer -y

RUN mkdir ~/bin
RUN echo "export PATH=$PATH:'~/bin'" >> ~/.bashrc

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar ~/bin/composer