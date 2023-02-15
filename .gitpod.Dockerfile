FROM gitpod/workspace-full:2023-01-16-03-31-28@sha256:d5787229cd062aceae91109f1690013d3f25062916492fb7f444d13de3186178

USER root

RUN sudo apt-get update -yq \
    && sudo apt-get install apt-utils -yq \
    && sudo apt-get upgrade -yq \
    && sudo locale-gen de_DE.UTF-8 \
    && sudo apt-get clean \
    && sudo rm -rf /var/lib/apt/lists/*

ENV LANG=de_DE.UTF-8

RUN sudo install-packages php-xdebug

USER gitpod

RUN COMPOSER_ALLOW_SUPERUSER=1 composer global require churchtools/changelogger

ENV PATH="$PATH:$HOME/.config/composer/vendor/bin"
